<?php

namespace App\Events\Subscription;

use App\Models\Subscription;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a subscription trial is about to end
 *
 * This event is triggered when:
 * - Stripe webhook receives customer.subscription.trial_will_end event
 * - Typically sent 3 days before trial ends
 *
 * Use this event to:
 * - Remind vendor trial is ending
 * - Prompt vendor to add payment method
 * - Highlight benefits of continuing subscription
 */
class TrialWillEnd
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Subscription $subscription,
        public array $metadata = []
    ) {}
}
