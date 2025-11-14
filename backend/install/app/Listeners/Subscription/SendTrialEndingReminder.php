<?php

namespace App\Listeners\Subscription;

use App\Events\Subscription\TrialWillEnd;
use App\Mail\Subscription\TrialEndingEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Send trial ending reminder email
 *
 * Listens to TrialWillEnd event and reminds vendor trial is expiring
 */
class SendTrialEndingReminder implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(TrialWillEnd $event): void
    {
        try {
            $subscription = $event->subscription;
            $shop = $subscription->shop;
            $vendor = $shop->user;

            if (!$vendor || !$vendor->email) {
                Log::warning('No vendor email for trial ending reminder', [
                    'subscription_id' => $subscription->id,
                    'shop_id' => $shop->id,
                ]);
                return;
            }

            // Only send if trial is actually ending
            if (!$subscription->trial_ends_at || $subscription->trial_ends_at->isPast()) {
                Log::info('Skipping trial ending email - trial already ended', [
                    'subscription_id' => $subscription->id,
                ]);
                return;
            }

            Mail::to($vendor->email)->send(
                new TrialEndingEmail($subscription)
            );

            $daysRemaining = $subscription->trial_ends_at->diffInDays(now());

            Log::info('Trial ending reminder sent', [
                'subscription_id' => $subscription->id,
                'shop_id' => $shop->id,
                'vendor_email' => $vendor->email,
                'days_remaining' => $daysRemaining,
                'trial_ends_at' => $subscription->trial_ends_at->toDateTimeString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send trial ending reminder', [
                'error' => $e->getMessage(),
                'subscription_id' => $event->subscription->id,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
