<?php

namespace App\Listeners\Subscription;

use App\Events\Subscription\SubscriptionCreated;
use App\Mail\Subscription\SubscriptionConfirmation;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Send subscription confirmation email
 *
 * Listens to SubscriptionCreated event and sends welcome email
 */
class SendSubscriptionConfirmation implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(SubscriptionCreated $event): void
    {
        try {
            $subscription = $event->subscription;
            $shop = $subscription->shop;
            $vendor = $shop->user;

            if (!$vendor || !$vendor->email) {
                Log::warning('No vendor email for subscription confirmation', [
                    'subscription_id' => $subscription->id,
                    'shop_id' => $shop->id,
                ]);
                return;
            }

            Mail::to($vendor->email)->send(
                new SubscriptionConfirmation($subscription)
            );

            Log::info('Subscription confirmation email sent', [
                'subscription_id' => $subscription->id,
                'shop_id' => $shop->id,
                'vendor_email' => $vendor->email,
                'plan' => $subscription->plan->name,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send subscription confirmation email', [
                'error' => $e->getMessage(),
                'subscription_id' => $event->subscription->id,
                'trace' => $e->getTraceAsString(),
            ]);

            // Don't throw - we don't want email failures to break the subscription flow
        }
    }
}
