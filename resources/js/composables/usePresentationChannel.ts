import { echo } from '@laravel/echo-vue';
import { onBeforeUnmount, onMounted } from 'vue';
import type {
    LeaderboardUpdatedEvent,
    QuizQuestionEndedEvent,
    QuizQuestionStartedEvent,
} from '@/types/quiz';

export function usePresentationChannel(
    presentationId: number,
    handlers: {
        onQuestionStarted?: (event: QuizQuestionStartedEvent) => void;
        onQuestionEnded?: (event: QuizQuestionEndedEvent) => void;
        onLeaderboardUpdated?: (event: LeaderboardUpdatedEvent) => void;
    },
) {
    let channel: any;

    onMounted(() => {
        channel = echo().channel(`presentation.${presentationId}`);

        if (handlers.onQuestionStarted) {
            channel.listen('QuizQuestionStarted', handlers.onQuestionStarted);
        }

        if (handlers.onQuestionEnded) {
            channel.listen('QuizQuestionEnded', handlers.onQuestionEnded);
        }

        if (handlers.onLeaderboardUpdated) {
            channel.listen('LeaderboardUpdated', handlers.onLeaderboardUpdated);
        }
    });

    onBeforeUnmount(() => {
        if (channel) {
            channel.stopListening('QuizQuestionStarted');
            channel.stopListening('QuizQuestionEnded');
            channel.stopListening('LeaderboardUpdated');
            echo().leave(`presentation.${presentationId}`);
        }
    });

    return { channel };
}
