<?php

namespace App\Events\Subscription;

use App\Models\Subscription;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a subscription is canceled
 *
 * This event is triggered when:
 * - Vendor cancels subscription via API
 * - Subscription expires after cancellation
 * - Stripe webhook receives subscription.deleted event
 */
class SubscriptionCanceled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Subscription $subscription,
        public array $metadata = []
    ) {}
}
