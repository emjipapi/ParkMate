<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ViolationWarningMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public int $stage;
    public int $violationCount;
    public $recentViolations;

    public function __construct(User $user, int $stage = 1)
    {
        $this->user = $user;
        $this->stage = $stage;
        $this->violationCount = DB::table('violations')
            ->where('violator_id', $user->id)
            ->whereIn('status', ['approved', 'for_endorsement'])
            ->count();

        $this->recentViolations = DB::table('violations')
            ->where('violator_id', $user->id)
            ->whereIn('status', ['approved', 'for_endorsement'])
            ->latest()
            ->take(5)
            ->get();
    }

    public function envelope(): Envelope
    {
        $subjects = [
            1 => 'Notice: 1st Parking Violation — Please Review',
            2 => 'Reminder: 2nd Parking Violation — Actions & Penalties',
            3 => 'Alert: 3rd Parking Violation — Access Restrictions Applied',
        ];

        return new Envelope(
            subject: $subjects[$this->stage] ?? $subjects[1],
        );
    }

    public function content(): Content
    {
        $views = [
            1 => 'emails.violations.stage1',
            2 => 'emails.violations.stage2',
            3 => 'emails.violations.stage3',
        ];

        $view = $views[$this->stage] ?? $views[1];

        return new Content(
            view: $view
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
