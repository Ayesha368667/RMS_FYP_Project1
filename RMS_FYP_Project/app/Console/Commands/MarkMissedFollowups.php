<?php

namespace App\Console\Commands;

use App\Models\FollowupResponse;
use App\Models\Reminder;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MarkMissedFollowups extends Command
{
    protected $signature = 'followups:mark-missed {--today= : Pretend today is this date (YYYY-MM-DD). Used for testing.}';

    protected $description = 'Mark pending follow-up rows from days that have already ended as missed';

    public function handle(): int
    {
        try {
            $today = $this->option('today')
                ? Carbon::createFromFormat('Y-m-d', $this->option('today'))->startOfDay()
                : today();
        } catch (\Throwable $e) {
            $this->error('Wrong date. Please use the format YYYY-MM-DD.');

            return self::FAILURE;
        }

        // A day has ended when its date is before today.
        // Only rows that were never submitted are marked.
        $ids = FollowupResponse::where('status', 'pending')
            ->whereNull('submitted_at')
            ->whereDate('due_date', '<', $today)
            ->pluck('id');

        FollowupResponse::whereIn('id', $ids)->update(['status' => 'missed']);

        // Their in-app reminders are no longer needed
        Reminder::where('remindable_type', FollowupResponse::class)
            ->whereIn('remindable_id', $ids)
            ->where('status', 'pending')
            ->update(['status' => 'expired']);

        $this->info("Before {$today->toDateString()}: {$ids->count()} follow-up row(s) marked as missed.");

        return self::SUCCESS;
    }
}