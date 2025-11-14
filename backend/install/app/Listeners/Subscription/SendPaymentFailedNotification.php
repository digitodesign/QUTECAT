<?php

namespace App\Listeners\Subscription;

use App\Events\Subscription\PaymentFailed;
use App\Mail\Subscription\PaymentFailedEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Send payment failed notification email
 *
 * Listens to PaymentFailed event and notifies vendor to update payment method
 */
class SendPaymentFailedNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(PaymentFailed $event): void
    {
        try {
            $subscription = $event->subscription;
            $shop = $subscription->shop;
            $vendor = $shop->user;

            if (!$vendor || !$vendor->email) {
                Log::warning('No vendor email for payment failed notification', [
                    'subscription_id' => $subscription->id,
                    'shop_id' => $shop->id,
                ]);
                return;
            }

            Mail::to($vendor->email)->send(
                new PaymentFailedEmail($subscription, $event->metadata)
            );

            Log::warning('Payment failed notification sent', [
                'subscription_id' => $subscription->id,
                'shop_id' => $shop->id,
                'vendor_email' => $vendor->email,
                'attempt_count' => $event->metadata['attempt_count'] ?? 1,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send payment failed notification', [
                'error' => $e->getMessage(),
                'subscription_id' => $event->subscription->id,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
