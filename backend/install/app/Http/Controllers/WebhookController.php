<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use App\Services\Subscription\StripeSubscriptionService;
use App\Events\Subscription\SubscriptionCreated;
use App\Events\Subscription\SubscriptionUpdated;
use App\Events\Subscription\SubscriptionCanceled;
use App\Events\Subscription\PaymentSucceeded;
use App\Events\Subscription\PaymentFailed;
use App\Events\Subscription\TrialWillEnd;

/**
 * Stripe Webhook Handler
 *
 * Handles incoming webhook events from Stripe to keep local subscription
 * data in sync with Stripe's records.
 *
 * Webhook Events Handled:
 * - customer.subscription.created
 * - customer.subscription.updated
 * - customer.subscription.deleted
 * - invoice.payment_succeeded
 * - invoice.payment_failed
 * - customer.subscription.trial_will_end
 */
class WebhookController extends Controller
{
    public function __construct(
        private StripeSubscriptionService $subscriptionService
    ) {}

    /**
     * Handle incoming Stripe webhook
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handleStripeWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('saas.stripe.webhook_secret');

        // Verify webhook signature
        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (\UnexpectedValueException $e) {
            Log::error('Stripe webhook payload error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Invalid payload'], 400);
        }

        Log::info('Stripe webhook received', [
            'type' => $event->type,
            'id' => $event->id,
            'created' => $event->created,
        ]);

        // Handle the event
        try {
            $this->dispatchWebhookEvent($event);

            Log::info('Stripe webhook processed successfully', [
                'type' => $event->type,
                'id' => $event->id,
            ]);

            return response()->json(['received' => true], 200);
        } catch (\Exception $e) {
            Log::error('Error processing Stripe webhook', [
                'type' => $event->type,
                'id' => $event->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return 200 to prevent Stripe from retrying
            // (we've logged the error for manual investigation)
            return response()->json(['received' => true, 'error' => $e->getMessage()], 200);
        }
    }

    /**
     * Dispatch webhook event to appropriate handler
     *
     * @param \Stripe\Event $event
     * @return void
     */
    private function dispatchWebhookEvent($event): void
    {
        switch ($event->type) {
            case 'customer.subscription.created':
                $this->handleSubscriptionCreated($event->data->object);
                break;

            case 'customer.subscription.updated':
                $this->handleSubscriptionUpdated($event->data->object);
                break;

            case 'customer.subscription.deleted':
                $this->handleSubscriptionDeleted($event->data->object);
                break;

            case 'invoice.payment_succeeded':
                $this->handlePaymentSucceeded($event->data->object);
                break;

            case 'invoice.payment_failed':
                $this->handlePaymentFailed($event->data->object);
                break;

            case 'customer.subscription.trial_will_end':
                $this->handleTrialWillEnd($event->data->object);
                break;

            default:
                Log::info('Unhandled Stripe webhook event type', [
                    'type' => $event->type,
                    'id' => $event->id,
                ]);
        }
    }

    /**
     * Handle subscription created event
     *
     * @param \Stripe\Subscription $stripeSubscription
     * @return void
     */
    private function handleSubscriptionCreated($stripeSubscription): void
    {
        Log::info('Processing subscription.created webhook', [
            'stripe_subscription_id' => $stripeSubscription->id,
            'customer_id' => $stripeSubscription->customer,
            'status' => $stripeSubscription->status,
        ]);

        $subscription = $this->subscriptionService->syncWithStripe(
            $stripeSubscription->id
        );

        if ($subscription) {
            event(new SubscriptionCreated($subscription));

            Log::info('Subscription created event dispatched', [
                'subscription_id' => $subscription->id,
                'shop_id' => $subscription->shop_id,
            ]);
        } else {
            Log::warning('Subscription not found for created webhook', [
                'stripe_subscription_id' => $stripeSubscription->id,
            ]);
        }
    }

    /**
     * Handle subscription updated event
     *
     * Triggered when subscription changes (upgrade, downgrade, status change)
     *
     * @param \Stripe\Subscription $stripeSubscription
     * @return void
     */
    private function handleSubscriptionUpdated($stripeSubscription): void
    {
        Log::info('Processing subscription.updated webhook', [
            'stripe_subscription_id' => $stripeSubscription->id,
            'status' => $stripeSubscription->status,
            'cancel_at_period_end' => $stripeSubscription->cancel_at_period_end,
        ]);

        $subscription = $this->subscriptionService->syncWithStripe(
            $stripeSubscription->id
        );

        if ($subscription) {
            event(new SubscriptionUpdated($subscription, [
                'previous_status' => $subscription->getOriginal('status'),
                'new_status' => $subscription->status,
            ]));

            Log::info('Subscription updated event dispatched', [
                'subscription_id' => $subscription->id,
                'shop_id' => $subscription->shop_id,
                'status' => $subscription->status,
            ]);
        } else {
            Log::warning('Subscription not found for updated webhook', [
                'stripe_subscription_id' => $stripeSubscription->id,
            ]);
        }
    }

    /**
     * Handle subscription deleted event
     *
     * Triggered when subscription is canceled or expires
     *
     * @param \Stripe\Subscription $stripeSubscription
     * @return void
     */
    private function handleSubscriptionDeleted($stripeSubscription): void
    {
        Log::info('Processing subscription.deleted webhook', [
            'stripe_subscription_id' => $stripeSubscription->id,
            'ended_at' => $stripeSubscription->ended_at,
        ]);

        $subscription = $this->subscriptionService->syncWithStripe(
            $stripeSubscription->id
        );

        if ($subscription) {
            event(new SubscriptionCanceled($subscription, [
                'reason' => 'stripe_webhook',
                'ended_at' => $stripeSubscription->ended_at,
            ]));

            Log::info('Subscription canceled event dispatched', [
                'subscription_id' => $subscription->id,
                'shop_id' => $subscription->shop_id,
            ]);
        } else {
            Log::warning('Subscription not found for deleted webhook', [
                'stripe_subscription_id' => $stripeSubscription->id,
            ]);
        }
    }

    /**
     * Handle payment succeeded event
     *
     * Triggered when invoice payment is successful (renewal, upgrade, etc.)
     *
     * @param \Stripe\Invoice $invoice
     * @return void
     */
    private function handlePaymentSucceeded($invoice): void
    {
        Log::info('Processing payment_succeeded webhook', [
            'invoice_id' => $invoice->id,
            'subscription_id' => $invoice->subscription,
            'amount' => $invoice->amount_paid / 100,
            'currency' => $invoice->currency,
        ]);

        if ($invoice->subscription) {
            $subscription = $this->subscriptionService->syncWithStripe(
                $invoice->subscription
            );

            if ($subscription) {
                event(new PaymentSucceeded($subscription, [
                    'invoice_id' => $invoice->id,
                    'amount' => $invoice->amount_paid / 100,
                    'currency' => $invoice->currency,
                    'paid_at' => $invoice->status_transitions->paid_at,
                ]));

                Log::info('Payment succeeded event dispatched', [
                    'subscription_id' => $subscription->id,
                    'shop_id' => $subscription->shop_id,
                    'amount' => $invoice->amount_paid / 100,
                ]);
            }
        } else {
            Log::info('Payment succeeded for non-subscription invoice', [
                'invoice_id' => $invoice->id,
            ]);
        }
    }

    /**
     * Handle payment failed event
     *
     * Triggered when invoice payment fails (card declined, insufficient funds, etc.)
     *
     * @param \Stripe\Invoice $invoice
     * @return void
     */
    private function handlePaymentFailed($invoice): void
    {
        Log::warning('Processing payment_failed webhook', [
            'invoice_id' => $invoice->id,
            'subscription_id' => $invoice->subscription,
            'amount' => $invoice->amount_due / 100,
            'attempt_count' => $invoice->attempt_count,
            'next_payment_attempt' => $invoice->next_payment_attempt,
        ]);

        if ($invoice->subscription) {
            $subscription = $this->subscriptionService->syncWithStripe(
                $invoice->subscription
            );

            if ($subscription) {
                event(new PaymentFailed($subscription, [
                    'invoice_id' => $invoice->id,
                    'amount' => $invoice->amount_due / 100,
                    'currency' => $invoice->currency,
                    'attempt_count' => $invoice->attempt_count,
                    'next_payment_attempt' => $invoice->next_payment_attempt,
                    'charge_id' => $invoice->charge,
                ]));

                Log::warning('Payment failed event dispatched', [
                    'subscription_id' => $subscription->id,
                    'shop_id' => $subscription->shop_id,
                    'amount' => $invoice->amount_due / 100,
                    'attempt_count' => $invoice->attempt_count,
                ]);
            }
        }
    }

    /**
     * Handle trial will end event
     *
     * Triggered 3 days before trial ends
     *
     * @param \Stripe\Subscription $stripeSubscription
     * @return void
     */
    private function handleTrialWillEnd($stripeSubscription): void
    {
        Log::info('Processing trial_will_end webhook', [
            'stripe_subscription_id' => $stripeSubscription->id,
            'trial_end' => $stripeSubscription->trial_end,
            'days_remaining' => ceil(($stripeSubscription->trial_end - time()) / 86400),
        ]);

        $subscription = $this->subscriptionService->syncWithStripe(
            $stripeSubscription->id
        );

        if ($subscription) {
            event(new TrialWillEnd($subscription, [
                'trial_ends_at' => $subscription->trial_ends_at,
                'days_remaining' => $subscription->trial_ends_at?->diffInDays(now()),
            ]));

            Log::info('Trial will end event dispatched', [
                'subscription_id' => $subscription->id,
                'shop_id' => $subscription->shop_id,
                'trial_ends_at' => $subscription->trial_ends_at,
            ]);
        }
    }
}
