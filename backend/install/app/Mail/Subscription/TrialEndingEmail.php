<?php

namespace App\Mail\Subscription;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Trial Ending Email
 *
 * Sent 3 days before trial period ends
 */
class TrialEndingEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Subscription $subscription
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $daysRemaining = $this->subscription->trial_ends_at?->diffInDays(now()) ?? 0;

        return new Envelope(
            subject: 'Your Trial Ends in ' . $daysRemaining . ' Days',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $daysRemaining = $this->subscription->trial_ends_at?->diffInDays(now()) ?? 0;

        return new Content(
            view: 'mail.subscription.trial-ending',
            with: [
                'planName' => $this->subscription->plan->name,
                'planPrice' => $this->subscription->plan->price,
                'daysRemaining' => $daysRemaining,
                'trialEndsAt' => $this->subscription->trial_ends_at?->format('F d, Y'),
                'shopName' => $this->subscription->shop->name,
                'billingPortalUrl' => route('api.subscription.billing-portal'),
            ],
        );
    }
}
