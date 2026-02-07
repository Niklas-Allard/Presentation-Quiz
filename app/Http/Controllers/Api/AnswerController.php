<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitAnswerRequest;
use App\Models\Option;
use App\Models\Question;
use App\Services\LeaderboardService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class AnswerController extends Controller
{
    public function __construct(protected LeaderboardService $leaderboardService)
    {
        //
    }

    /**
     * Submit an answer to a question.
     */
    public function submit(SubmitAnswerRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $participant = $request->input('participant');

        // Load question with options
        $question = Question::with('options')->findOrFail($validated['question_id']);
        $option = Option::findOrFail($validated['option_id']);

        // Verify option belongs to question
        if ($option->question_id !== $question->id) {
            return response()->json([
                'message' => 'Option does not belong to this question.',
                'is_correct' => false,
                'points_earned' => 0,
            ], 400);
        }

        // Get question start time from cache
        $currentQuestion = Cache::get("presentation:{$participant->presentation_id}:current_question");

        if (! $currentQuestion || $currentQuestion['question_id'] !== $question->id) {
            return response()->json([
                'message' => 'This question is not currently active.',
                'is_correct' => false,
                'points_earned' => 0,
            ], 400);
        }

        // Prevent duplicate answers
        $answerKey = "answer:{$participant->id}:{$question->id}";
        if (Cache::has($answerKey)) {
            return response()->json([
                'message' => 'You have already answered this question.',
                'is_correct' => false,
                'points_earned' => 0,
            ], 400);
        }

        // Mark answer as submitted (prevent duplicates)
        Cache::put($answerKey, true, now()->addMinutes(10));

        $isCorrect = $option->is_correct;
        $pointsEarned = 0;

        if ($isCorrect) {
            // Use elapsed time sent by frontend (more accurate as it's based on when they received the question)
            $timeElapsed = $validated['elapsed_seconds'];

            \Log::info('⏱️ Time calculation', [
                'time_elapsed' => $timeElapsed,
                'time_limit' => $question->time_limit_seconds,
            ]);

            $pointsEarned = $this->calculatePoints($timeElapsed, $question->time_limit_seconds);

            \Log::info('💰 Points calculated', [
                'points_earned' => $pointsEarned,
                'participant_id' => $participant->id,
            ]);

            // Add points to leaderboard (atomic operation)
            $newScore = $this->leaderboardService->addPoints(
                $participant->id,
                $participant->presentation_id,
                $pointsEarned
            );

            \Log::info('📊 New total score: ' . $newScore);

            // Broadcast leaderboard update
            $this->leaderboardService->broadcastLeaderboard($participant->presentation_id);
        }

        return response()->json([
            'is_correct' => $isCorrect,
            'points_earned' => $pointsEarned,
            'correct_option_id' => $question->correctOption()?->id,
        ]);
    }

    /**
     * Calculate points based on answer speed.
     * Base points: 1000
     * Time bonus: Up to 500 points for fastest answers.
     */
    protected function calculatePoints(int $timeElapsed, int $timeLimit): int
    {
        $basePoints = 1000;
        $maxTimeBonus = 500;

        // Calculate time bonus (faster = more points)
        // If answered instantly (0s): +500 bonus
        // If answered at time limit: +0 bonus
        $timeRatio = max(0, min(1, 1 - ($timeElapsed / $timeLimit)));
        $timeBonus = (int) ($maxTimeBonus * $timeRatio);

        return $basePoints + $timeBonus;
    }
}
