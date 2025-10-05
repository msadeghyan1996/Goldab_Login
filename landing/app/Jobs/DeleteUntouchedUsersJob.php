<?php

namespace App\Jobs;

use App\Models\User\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeleteUntouchedUsersJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly int $ageByDays = 30)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        User::query()->whereNull('password')->where('created_at', '<', now()->subDays($this->ageByDays))->delete();
    }
}
