<?php

namespace App\Http\Controllers;

use App\Events\QuizQuestionEnded;
use App\Events\QuizQuestionStarted;
use App\Models\Presentation;
use App\Models\Question;
use App\Services\LeaderboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class PresentationControlController extends Controller
{
    public function __construct(protected LeaderboardService $leaderboardService)
    {
        //
    }

    /**
     * Start a specific question for the presentation.
     */
    public function startQuestion(Presentation $presentation, Question $question): RedirectResponse
    {
        // Verify the question belongs to this presentation
        if ($question->presentation_id !== $presentation->id) {
            return back()->withErrors(['message' => 'Question does not belong to this presentation.']);
        }

        // Verify presentation is in correct state
        if (! $presentation->isActive() && ! $presentation->isWaiting()) {
            return back()->withErrors(['message' => 'Presentation must be in waiting or active state to start a question.']);
        }

        // Update presentation status to active if it was waiting
        if ($presentation->isWaiting()) {
            $presentation->update(['status' => 'active']);
        }

        $startedAt = now();

        // Store current question info in cache for late joiners
        Cache::put(
            "presentation:{$presentation->id}:current_question",
            [
                'question_id' => $question->id,
                'started_at' => $startedAt->toIso8601String(),
            ],
            now()->addMinutes(10)
        );

        // Load question options for broadcasting
        $question->load('options');

        // Broadcast to all participants
        broadcast(new QuizQuestionStarted(
            $question,
            $presentation->id,
            $startedAt
        ));

        return back()->with('success', 'Question started successfully.');
    }

    /**
     * End a question and broadcast results.
     */
    public function endQuestion(Presentation $presentation, Question $question): RedirectResponse
    {
        // Verify the question belongs to this presentation
        if ($question->presentation_id !== $presentation->id) {
            return back()->withErrors(['message' => 'Question does not belong to this presentation.']);
        }

        // Load question with options
        $question->load('options');

        $correctOption = $question->correctOption();

        if (! $correctOption) {
            return back()->withErrors(['message' => 'Question has no correct option defined.']);
        }

        // Calculate statistics
        $statistics = $this->calculateQuestionStatistics($question, $presentation->id);

        // Clear current question from cache
        Cache::forget("presentation:{$presentation->id}:current_question");

        // Broadcast question ended event
        broadcast(new QuizQuestionEnded(
            $question->id,
            $presentation->id,
            $correctOption->id,
            $statistics
        ));

        // Broadcast updated leaderboard
        $this->leaderboardService->broadcastLeaderboard($presentation->id);

        return back()->with('success', 'Question ended successfully.');
    }

    /**
     * Calculate statistics for a question.
     */
    protected function calculateQuestionStatistics(Question $question, int $presentationId): array
    {
        $statistics = [
            'total_answers' => 0,
            'correct_answers' => 0,
            'options' => [],
        ];

        foreach ($question->options as $option) {
            // Count how many participants selected this option
            $pattern = "answer:*:{$question->id}";
            $keys = Redis::keys($pattern);

            // This is a simplified version - in production, you'd track
            // which option each participant selected in Redis
            $optionCount = 0;

            $statistics['options'][] = [
                'option_id' => $option->id,
                'text' => $option->text,
                'is_correct' => $option->is_correct,
                'count' => $optionCount,
            ];
        }

        return $statistics;
    }

    /**
     * Update presentation status.
     */
    public function updateStatus(Presentation $presentation, string $status): RedirectResponse
    {
        $validStatuses = ['draft', 'waiting', 'active', 'finished'];

        if (! in_array($status, $validStatuses)) {
            return back()->withErrors(['message' => 'Invalid status.']);
        }

        $presentation->update(['status' => $status]);

        return back()->with('success', 'Status updated successfully.');
    }
}
