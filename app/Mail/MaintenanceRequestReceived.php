<?php

namespace App\Mail;

use App\Models\MaintenanceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MaintenanceRequestReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public MaintenanceRequest $maintenanceRequest) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'تم استلام طلب صيانة BashaMob — '.$this->maintenanceRequest->request_number);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.maintenance-request-received');
    }
}
