<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewLicenseKeyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected readonly string $licenseKey,
        protected readonly string $customerEmail,
        protected readonly string $productName,
        protected readonly string $brandName,
    ) {}

    /**
     * @param  Authenticatable|null  $notifiable
     * @return array<int, string>
     */
    public function via($notifiable): array
    {

        return ['mail'];
    }

    /**
     * @param  Authenticatable|null  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Your license key for {$this->productName}")
            ->view(
                'emails.new-license-key',
                [
                    'licenseKey' => formatKey($this->licenseKey),
                    'brand' => $this->brandName,
                    'product' => $this->productName,
                    'customerEmail' => $this->customerEmail,
                ]
            );
    }

    /**
     * @param  Authenticatable|null  $notifiable
     * @return array<int|string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [];
    }
}
