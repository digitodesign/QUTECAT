<?php

namespace App\Mail\Subscription;

use App\Models\Shop;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Limit Warning Email
 *
 * Sent when usage approaches plan limits (80%, 90%, 100%)
 */
class LimitWarningEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Shop $shop,
        public string $limitType, // 'products', 'orders', 'storage'
        public int $percentUsed
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $status = $this->percentUsed >= 100 ? 'Reached' : 'Approaching';

        return new Envelope(
            subject: ucfirst($this->limitType) . ' Limit ' . $status,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $limitMessages = [
            'products' => [
                'current' => $this->shop->current_products_count,
                'limit' => $this->shop->products_limit,
                'unit' => 'products',
                'action' => 'add more products',
            ],
            'orders' => [
                'current' => $this->shop->current_orders_count,
                'limit' => $this->shop->orders_per_month_limit,
                'unit' => 'orders this month',
                'action' => 'accept more orders',
            ],
            'storage' => [
                'current' => $this->shop->storage_used_mb,
                'limit' => $this->shop->storage_limit_mb,
                'unit' => 'MB',
                'action' => 'upload more images',
            ],
        ];

        $details = $limitMessages[$this->limitType] ?? [];

        return new Content(
            view: 'mail.subscription.limit-warning',
            with: [
                'shopName' => $this->shop->name,
                'limitType' => ucfirst($this->limitType),
                'current' => $details['current'] ?? 0,
                'limit' => $details['limit'] ?? 0,
                'unit' => $details['unit'] ?? '',
                'action' => $details['action'] ?? 'continue',
                'percentUsed' => $this->percentUsed,
                'isAtLimit' => $this->percentUsed >= 100,
                'planName' => $this->shop->currentPlan?->name ?? 'Free',
                'upgradeUrl' => route('api.subscription.plans'),
            ],
        );
    }
}
