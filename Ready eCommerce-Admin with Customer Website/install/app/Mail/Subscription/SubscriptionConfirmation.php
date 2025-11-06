<?php

namespace App\Mail\Subscription;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Subscription Confirmation Email
 *
 * Sent when a vendor successfully subscribes to a paid plan
 */
class SubscriptionConfirmation extends Mailable
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
        return new Envelope(
            subject: 'Welcome to ' . $this->subscription->plan->name . ' 🎉',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.subscription.confirmation',
            with: [
                'planName' => $this->subscription->plan->name,
                'planPrice' => $this->subscription->plan->price,
                'trialDays' => $this->subscription->trial_ends_at ?
                    now()->diffInDays($this->subscription->trial_ends_at) : 0,
                'trialEndsAt' => $this->subscription->trial_ends_at,
                'productsLimit' => $this->subscription->shop->products_limit,
                'ordersLimit' => $this->subscription->shop->orders_per_month_limit,
                'storageLimit' => $this->subscription->shop->storage_limit_mb,
                'subdomain' => $this->subscription->shop->currentTenant?->domains->first()?->domain,
                'shopName' => $this->subscription->shop->name,
            ],
        );
    }
}
