<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DocumentUploaded extends Notification
{
    use Queueable;

    protected $details;

    public function __construct($details)
    {
        $this->details = $details;
    }

    public function via($notifiable)
    {
        return ['database']; // Important: Store in database
    }

    public function toArray($notifiable)
    {
        return [
            'message' => $this->details['message'],
            'document_name' => $this->details['document_name'],
            'by_user' => $this->details['by_user'],
            'time' => now(),
        ];
    }
}