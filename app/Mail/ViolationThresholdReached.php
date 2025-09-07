<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ViolationThresholdReached extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $violationCount;
    public $violations;

    public function __construct(User $user)
    {
        $this->user = $user;
        
        // Get violation count and recent violations
        $this->violationCount = DB::table('violations')
            ->where('violator_id', $user->id)
            ->where('status', 'approved')
            ->count();
            
        $this->violations = DB::table('violations')
            ->where('violator_id', $user->id)
            ->where('status', 'approved')
            ->latest()
            ->take(5)
            ->get();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Important: Parking Violations Alert - Access Restrictions Applied',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.violation-threshold-reached',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}