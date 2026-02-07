<?php

namespace App\Services;

use App\Events\LeaderboardUpdated;
use App\Models\Participant;
use Illuminate\Support\Facades\Redis;

class LeaderboardService
{
    /**
     * Get the Redis key for a presentation's leaderboard.
     */
    protected function getLeaderboardKey(int $presentationId): string
    {
        return "leaderboard:{$presentationId}";
    }

    /**
     * Add points to a participant's score.
     *
     * @param  string  $participantId  UUID of the participant
     * @param  int  $presentationId  ID of the presentation
     * @param  int  $points  Points to add
     * @return int New total score
     */
    public function addPoints(string $participantId, int $presentationId, int $points): int
    {
        \Log::info('🎯 Adding points', [
            'participant_id' => $participantId,
            'presentation_id' => $presentationId,
            'points' => $points,
        ]);

        // Update MySQL database directly
        $participant = Participant::find($participantId);

        if ($participant) {
            \Log::info('👤 Participant found, current score: ' . $participant->score);

            $participant->increment('score', $points);
            $newScore = $participant->fresh()->score;

            \Log::info('✅ Score updated, new score: ' . $newScore);
        } else {
            \Log::error('❌ Participant not found: ' . $participantId);
            $newScore = 0;
        }

        // Also update Redis cache for fast queries (optional)
        try {
            $key = $this->getLeaderboardKey($presentationId);
            Redis::zincrby($key, $points, $participantId);
            Redis::expire($key, 86400);
        } catch (\Exception $e) {
            // Redis is optional, continue even if it fails
            \Log::warning('Redis cache update failed, continuing with MySQL: ' . $e->getMessage());
        }

        return (int) $newScore;
    }

    /**
     * Get the top N participants from the leaderboard.
     *
     * @param  int  $presentationId  ID of the presentation
     * @param  int  $limit  Number of top participants to retrieve (default: 10)
     * @return array Array of participants with scores
     */
    public function getTopParticipants(int $presentationId, int $limit = 10): array
    {
        // Query directly from MySQL
        $participants = Participant::where('presentation_id', $presentationId)
            ->where('score', '>', 0)
            ->orderBy('score', 'desc')
            ->limit($limit)
            ->get();

        $leaderboard = [];
        $rank = 1;

        foreach ($participants as $participant) {
            $leaderboard[] = [
                'rank' => $rank++,
                'participant_id' => $participant->id,
                'display_name' => $participant->display_name,
                'score' => (int) $participant->score,
            ];
        }

        return $leaderboard;
    }

    /**
     * Get a participant's rank and score.
     *
     * @param  string  $participantId  UUID of the participant
     * @param  int  $presentationId  ID of the presentation
     * @return array|null Array with rank and score, or null if not found
     */
    public function getParticipantRank(string $participantId, int $presentationId): ?array
    {
        $participant = Participant::find($participantId);

        if (! $participant || $participant->presentation_id !== $presentationId) {
            return null;
        }

        // Calculate rank by counting participants with higher scores
        $rank = Participant::where('presentation_id', $presentationId)
            ->where('score', '>', $participant->score)
            ->count() + 1;

        return [
            'rank' => $rank,
            'score' => (int) $participant->score,
        ];
    }

    /**
     * Broadcast leaderboard update event.
     *
     * @param  int  $presentationId  ID of the presentation
     * @param  int  $limit  Number of top participants to broadcast
     */
    public function broadcastLeaderboard(int $presentationId, int $limit = 10): void
    {
        $topParticipants = $this->getTopParticipants($presentationId, $limit);

        broadcast(new LeaderboardUpdated($presentationId, $topParticipants));
    }

    /**
     * Sync Redis scores to database for persistence.
     *
     * @param  int  $presentationId  ID of the presentation
     */
    public function syncToDatabase(int $presentationId): void
    {
        $key = $this->getLeaderboardKey($presentationId);

        $results = Redis::zrange($key, 0, -1, 'WITHSCORES');

        foreach ($results as $participantId => $score) {
            Participant::where('id', $participantId)
                ->update(['score' => (int) $score]);
        }
    }

    /**
     * Clear the leaderboard for a presentation.
     *
     * @param  int  $presentationId  ID of the presentation
     */
    public function clear(int $presentationId): void
    {
        $key = $this->getLeaderboardKey($presentationId);
        Redis::del($key);
    }
}
