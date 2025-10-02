<?php

namespace App\Jobs;

use App\Mail\ViolationWarningMail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class SendViolationWarningEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $userId;
    public int $stage;

    /**
     * @param int $userId
     * @param int $stage
     */
    public function __construct(int $userId, int $stage = 1)
    {
        $this->userId = $userId;
        $this->stage = $stage;
    }

    public function handle(): void
    {
        $user = User::find($this->userId);
        if (! $user || empty($user->email)) {
            Log::warning('SendViolationWarningEmail: user not found or no email', ['user_id' => $this->userId]);
            return;
        }

        Log::info('SendViolationWarningEmail job started', ['user_id' => $user->id, 'stage' => $this->stage]);

        // Defensive: recalc approved count and ensure threshold is met for this stage
        $stages = config('violations.stages', [
            1 => ['threshold' => 1],
            2 => ['threshold' => 2],
            3 => ['threshold' => 3],
        ]);
        $threshold = $stages[$this->stage]['threshold'] ?? $this->stage;

        $approvedCount = DB::table('violations')
            ->where('violator_id', $user->id)
            ->whereIn('status', ['approved', 'for_endorsement'])
            ->count();

        if ($approvedCount < $threshold) {
            Log::info('SendViolationWarningEmail: threshold not met, skipping send', [
                'user_id' => $user->id,
                'approvedCount' => $approvedCount,
                'threshold' => $threshold,
                'stage' => $this->stage
            ]);
            return;
        }

        try {
            Mail::to($user->email)->send(new ViolationWarningMail($user, $this->stage));
            Log::info('SendViolationWarningEmail: mail sent', ['user_id' => $user->id, 'stage' => $this->stage]);
        } catch (\Exception $e) {
            Log::error('SendViolationWarningEmail: failed to send mail', [
                'user_id' => $user->id,
                'stage' => $this->stage,
                'error' => $e->getMessage(),
            ]);
            // rethrow to allow retry if desired
            throw $e;
        }
    }
}
