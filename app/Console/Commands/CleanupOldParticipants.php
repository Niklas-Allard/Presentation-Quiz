<?php

namespace App\Console\Commands;

use App\Models\Participant;
use Illuminate\Console\Command;

class CleanupOldParticipants extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'participants:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete participants that have not been updated in the last 24 hours';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cutoffDate = now()->subDay();

        $this->info('🧹 Cleaning up old participants...');
        $this->info("Deleting participants not updated since: {$cutoffDate->format('Y-m-d H:i:s')}");

        $deletedCount = Participant::where('updated_at', '<', $cutoffDate)->delete();

        if ($deletedCount > 0) {
            $this->info("✅ Successfully deleted {$deletedCount} old participant(s)");
        } else {
            $this->info('✅ No old participants to delete');
        }

        return Command::SUCCESS;
    }
}
