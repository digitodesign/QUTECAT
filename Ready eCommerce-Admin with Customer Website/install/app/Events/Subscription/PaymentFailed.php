<?php

namespace App\Events\Subscription;

use App\Models\Subscription;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a subscription payment fails
 *
 * This event is triggered when:
 * - Invoice payment fails (card declined, insufficient funds, etc.)
 * - Stripe webhook receives invoice.payment_failed event
 *
 * Use this event to:
 * - Notify vendor of payment failure
 * - Update subscription status
 * - Trigger retry logic
 */
class PaymentFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Subscription $subscription,
        public array $metadata = []
    ) {}
}
