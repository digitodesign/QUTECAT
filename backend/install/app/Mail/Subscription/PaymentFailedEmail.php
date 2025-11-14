<?php

namespace App\Mail\Subscription;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Payment Failed Email
 *
 * Sent when a subscription payment fails
 */
class PaymentFailedEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Subscription $subscription,
        public array $paymentDetails = []
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Failed - Action Required',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $amount = $this->paymentDetails['amount'] ?? $this->subscription->plan->price;
        $attemptCount = $this->paymentDetails['attempt_count'] ?? 1;
        $nextAttempt = $this->paymentDetails['next_payment_attempt'] ?? null;

        return new Content(
            view: 'mail.subscription.payment-failed',
            with: [
                'planName' => $this->subscription->plan->name,
                'amount' => $amount,
                'currency' => $this->paymentDetails['currency'] ?? 'USD',
                'attemptCount' => $attemptCount,
                'nextAttemptDate' => $nextAttempt ? date('M d, Y', $nextAttempt) : null,
                'shopName' => $this->subscription->shop->name,
                'billingPortalUrl' => route('api.subscription.billing-portal'),
            ],
        );
    }
}
