<?php

namespace App\Events\Subscription;

use App\Models\Subscription;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a subscription is updated
 *
 * This event is triggered when:
 * - Vendor upgrades or downgrades plan
 * - Subscription status changes in Stripe
 * - Stripe webhook receives subscription.updated event
 */
class SubscriptionUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Subscription $subscription,
        public array $metadata = []
    ) {}
}
