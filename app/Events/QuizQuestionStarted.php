<?php

namespace App\Events;

use App\Models\Question;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuizQuestionStarted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Question $question,
        public int $presentationId,
        public \DateTimeInterface $startedAt
    ) {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel("presentation.{$this->presentationId}"),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'question_id' => $this->question->id,
            'content' => $this->question->content,
            'time_limit_seconds' => $this->question->time_limit_seconds,
            'options' => $this->question->options->map(fn ($option) => [
                'id' => $option->id,
                'text' => $option->text,
                // NOTE: is_correct is NOT sent to participants
            ]),
            'started_at' => $this->startedAt->format('c'),
        ];
    }
}
