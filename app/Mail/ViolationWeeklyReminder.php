<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ViolationWeeklyReminder extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $violationCount;
    public $violations;
    public $weekNumber;

    public function __construct(User $user, int $weekNumber = 1)
    {
        $this->user = $user;
        $this->weekNumber = $weekNumber;
        
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
            subject: "Reminder: Parking Access Restricted - Week {$this->weekNumber}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.violation-weekly-reminder',
        );
    }
}