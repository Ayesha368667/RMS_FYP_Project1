<?php

namespace App\Console\Commands;

use App\Models\FollowupResponse;
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
        $count = FollowupResponse::where('status', 'pending')
            ->whereNull('submitted_at')
            ->whereDate('due_date', '<', $today)
            ->update(['status' => 'missed']);

        $this->info("Before {$today->toDateString()}: {$count} follow-up row(s) marked as missed.");

        return self::SUCCESS;
    }
}