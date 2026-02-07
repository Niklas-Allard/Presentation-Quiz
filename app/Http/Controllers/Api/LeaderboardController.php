<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Presentation;
use App\Services\LeaderboardService;
use Illuminate\Http\JsonResponse;

class LeaderboardController extends Controller
{
    public function __construct(protected LeaderboardService $leaderboardService)
    {
        //
    }

    /**
     * Get the leaderboard for a presentation.
     */
    public function show(Presentation $presentation, int $limit = 10): JsonResponse
    {
        $leaderboard = $this->leaderboardService->getTopParticipants($presentation->id, $limit);

        return response()->json([
            'leaderboard' => $leaderboard,
            'presentation_id' => $presentation->id,
        ]);
    }

    /**
     * Get a participant's rank and score.
     */
    public function participant(Presentation $presentation, string $participantId): JsonResponse
    {
        $rank = $this->leaderboardService->getParticipantRank($participantId, $presentation->id);

        if (! $rank) {
            return response()->json([
                'message' => 'Participant not found in leaderboard.',
            ], 404);
        }

        return response()->json([
            'rank' => $rank['rank'],
            'score' => $rank['score'],
            'participant_id' => $participantId,
        ]);
    }
}
