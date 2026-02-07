<?php

namespace Database\Seeders;

use App\Models\Participant;
use App\Models\Presentation;
use App\Services\LeaderboardService;
use Illuminate\Database\Seeder;

class ParticipantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $presentation = Presentation::where('title', 'Laravel Knowledge Quiz')->first();

        if (! $presentation) {
            $this->command->warn('Laravel Knowledge Quiz presentation not found. Run PresentationSeeder first.');

            return;
        }

        $leaderboardService = app(LeaderboardService::class);

        // Create test participants with scores
        $participantsData = [
            ['display_name' => 'Alice Johnson', 'score' => 8500],
            ['display_name' => 'Bob Smith', 'score' => 7200],
            ['display_name' => 'Charlie Brown', 'score' => 6800],
            ['display_name' => 'Diana Prince', 'score' => 6500],
            ['display_name' => 'Eve Adams', 'score' => 5900],
            ['display_name' => 'Frank Miller', 'score' => 5400],
            ['display_name' => 'Grace Lee', 'score' => 4800],
            ['display_name' => 'Henry Ford', 'score' => 4200],
            ['display_name' => 'Iris West', 'score' => 3600],
            ['display_name' => 'Jack Ryan', 'score' => 3100],
            ['display_name' => 'Kate Bishop', 'score' => 2500],
            ['display_name' => 'Leo Martinez', 'score' => 2000],
        ];

        foreach ($participantsData as $data) {
            $participant = Participant::create([
                'presentation_id' => $presentation->id,
                'display_name' => $data['display_name'],
                'score' => $data['score'],
            ]);

            // Also add to Redis leaderboard
            $leaderboardService->addPoints(
                $participant->id,
                $presentation->id,
                $data['score']
            );

            $this->command->info("Created participant: {$participant->display_name} (Score: {$data['score']})");
        }

        $this->command->info("\nLeaderboard created for presentation: {$presentation->title}");
        $this->command->info('You can view it at: GET /api/presentations/'.$presentation->id.'/leaderboard');
    }
}
