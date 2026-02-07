<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { usePresentationChannel } from '@/composables/usePresentationChannel';
import type { LeaderboardEntry, Option, QuizQuestionEndedEvent, QuizQuestionStartedEvent } from '@/types/quiz';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

const props = defineProps<{
    presentationId: number;
    presentationTitle: string;
}>();

const participantId = ref<string | null>(null);
const participantName = ref<string | null>(null);
const currentQuestion = ref<QuizQuestionStartedEvent | null>(null);
const selectedOptionId = ref<number | null>(null);
const hasAnswered = ref(false);
const correctOptionId = ref<number | null>(null);
const timeRemaining = ref(0);
const leaderboard = ref<LeaderboardEntry[]>([]);
const myRank = ref<{ rank: number; score: number } | null>(null);
const isWaiting = ref(true);
const questionStartTime = ref<number | null>(null);

let timerInterval: number | null = null;

const isAnswerCorrect = computed(() => {
    return hasAnswered.value && selectedOptionId.value === correctOptionId.value;
});

const isAnswerWrong = computed(() => {
    return hasAnswered.value && selectedOptionId.value !== correctOptionId.value;
});

function startTimer(startedAt: string, timeLimitSeconds: number) {
    if (timerInterval) clearInterval(timerInterval);

    const startTime = new Date(startedAt).getTime();
    const endTime = startTime + timeLimitSeconds * 1000;

    timerInterval = setInterval(() => {
        const now = Date.now();
        const remaining = Math.max(0, Math.ceil((endTime - now) / 1000));
        timeRemaining.value = remaining;

        if (remaining === 0 && timerInterval) {
            clearInterval(timerInterval);
        }
    }, 100);
}

async function submitAnswer(optionId: number) {
    if (hasAnswered.value || !currentQuestion.value || !participantId.value) return;

    selectedOptionId.value = optionId;
    hasAnswered.value = true;

    // Calculate elapsed time in seconds from when we received the question
    const elapsedSeconds = questionStartTime.value
        ? Math.floor((Date.now() - questionStartTime.value) / 1000)
        : 0;

    console.log('📝 Submitting answer:', {
        questionId: currentQuestion.value.question_id,
        optionId,
        elapsedSeconds
    });

    try {
        const response = await fetch('/api/answers', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Participant-ID': participantId.value,
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                question_id: currentQuestion.value.question_id,
                option_id: optionId,
                answered_at: new Date().toISOString(),
                elapsed_seconds: elapsedSeconds,
            }),
        });

        console.log('📡 Answer response status:', response.status);

        if (response.ok) {
            const data = await response.json();
            console.log('✅ Answer response:', data);
            console.log('🎯 Was answer correct?', data.is_correct);
            console.log('💰 Points earned:', data.points_earned);

            if (data.is_correct) {
                console.log('🎉 CORRECT ANSWER! Leaderboard should update now.');
                // Fetch updated leaderboard and rank
                setTimeout(() => {
                    fetchLeaderboard();
                    fetchMyRank();
                }, 1000);
            }
        } else {
            const error = await response.json();
            console.error('❌ Failed to submit answer:', error);
            hasAnswered.value = false;
            selectedOptionId.value = null;
        }
    } catch (error) {
        console.error('💥 Error submitting answer:', error);
        hasAnswered.value = false;
        selectedOptionId.value = null;
    }
}

function handleQuestionStarted(event: QuizQuestionStartedEvent) {
    isWaiting.value = false;
    currentQuestion.value = event;
    selectedOptionId.value = null;
    hasAnswered.value = false;
    correctOptionId.value = null;
    questionStartTime.value = Date.now(); // Record when we received the question
    startTimer(event.started_at, event.time_limit_seconds);
}

function handleQuestionEnded(event: QuizQuestionEndedEvent) {
    correctOptionId.value = event.correct_option_id;
    if (timerInterval) clearInterval(timerInterval);
    timeRemaining.value = 0;

    console.log('Question ended, showing results for 5 seconds...');

    // Show results for a moment before clearing
    setTimeout(() => {
        currentQuestion.value = null;
        isWaiting.value = true;
        console.log('⏳ Now in waiting state. isWaiting:', isWaiting.value, 'leaderboard entries:', leaderboard.value.length, 'myRank:', myRank.value);
    }, 5000);
}

function handleLeaderboardUpdated(event: { leaderboard: LeaderboardEntry[] }) {
    leaderboard.value = event.leaderboard;

    // Find my rank
    if (participantId.value) {
        const myEntry = event.leaderboard.find(e => e.participant_id === participantId.value);
        if (myEntry) {
            myRank.value = { rank: myEntry.rank, score: myEntry.score };
        }
    }
}

usePresentationChannel(props.presentationId, {
    onQuestionStarted: handleQuestionStarted,
    onQuestionEnded: handleQuestionEnded,
    onLeaderboardUpdated: handleLeaderboardUpdated,
});

async function fetchLeaderboard() {
    try {
        console.log('🔄 Fetching leaderboard from:', `/api/presentations/${props.presentationId}/leaderboard`);
        const response = await fetch(`/api/presentations/${props.presentationId}/leaderboard`);
        console.log('📡 Leaderboard response status:', response.status);

        if (response.ok) {
            const data = await response.json();
            console.log('📦 Raw leaderboard data:', data);
            leaderboard.value = data.leaderboard || [];
            console.log('📊 Leaderboard set to:', leaderboard.value.length, 'entries', leaderboard.value);
        } else {
            console.error('❌ Leaderboard request failed:', response.status, await response.text());
        }
    } catch (error) {
        console.error('💥 Failed to fetch leaderboard:', error);
    }
}

async function fetchMyRank() {
    if (!participantId.value) {
        console.warn('⚠️ Cannot fetch rank - no participantId');
        return;
    }

    try {
        console.log('🔄 Fetching rank from:', `/api/presentations/${props.presentationId}/participants/${participantId.value}/rank`);
        const response = await fetch(`/api/presentations/${props.presentationId}/participants/${participantId.value}/rank`);
        console.log('📡 Rank response status:', response.status);

        if (response.ok) {
            const data = await response.json();
            console.log('📦 Raw rank data:', data);
            myRank.value = { rank: data.rank, score: data.score };
            console.log('🏅 My rank set to:', myRank.value);
        } else {
            console.error('❌ Rank request failed:', response.status, await response.text());
        }
    } catch (error) {
        console.error('💥 Failed to fetch rank:', error);
    }
}

onMounted(async () => {
    participantId.value = localStorage.getItem('participant_id');
    participantName.value = localStorage.getItem('participant_name');

    if (!participantId.value) {
        window.location.href = `/quiz/join/${props.presentationId}`;
        return;
    }

    // Fetch initial leaderboard and rank
    await Promise.all([
        fetchLeaderboard(),
        fetchMyRank(),
    ]);

    // Poll for updates every 10 seconds as backup
    setInterval(() => {
        fetchLeaderboard();
        fetchMyRank();
    }, 5000);
});
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 p-4">
        <div class="mx-auto max-w-4xl">
            <!-- Header -->
            <div class="mb-8 text-center animate-fade-in">
                <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-400 to-indigo-400 bg-clip-text text-transparent mb-3">
                    {{ presentationTitle }}
                </h1>
                <p class="text-2xl text-white font-semibold">{{ participantName }}</p>
                <div v-if="myRank" class="mt-3 inline-flex items-center gap-4 bg-white/10 backdrop-blur-sm rounded-full px-6 py-2">
                    <span class="text-lg text-gray-300">Rank: <span class="font-bold text-blue-400">#{{ myRank.rank }}</span></span>
                    <span class="text-gray-500">|</span>
                    <span class="text-lg text-gray-300">Score: <span class="font-bold text-indigo-400">{{ myRank.score }}</span></span>
                </div>
            </div>

            <!-- Waiting State -->
            <div v-if="isWaiting" class="space-y-6 animate-fade-in">
                <Card class="text-center bg-white/5 backdrop-blur-sm border-2 border-white/10 shadow-2xl">
                    <CardContent class="py-12">
                        <div class="mb-6 text-8xl animate-pulse">⏳</div>
                        <h2 class="text-3xl font-bold text-white mb-4">
                            {{ currentQuestion ? 'Get ready for the next question...' : 'Waiting for question...' }}
                        </h2>
                        <p class="text-xl text-gray-400">The presenter will start the next question soon</p>

                        <!-- Your Current Stats -->
                        <div v-if="myRank" class="mt-8 inline-flex items-center gap-6 bg-gradient-to-r from-blue-600/30 to-indigo-600/30 backdrop-blur-sm rounded-2xl px-8 py-4 border border-blue-500/30">
                            <div class="text-center">
                                <div class="text-4xl font-black text-blue-400">#{{ myRank.rank }}</div>
                                <div class="text-sm text-gray-400 mt-1">Your Rank</div>
                            </div>
                            <div class="h-12 w-px bg-gray-600"></div>
                            <div class="text-center">
                                <div class="text-4xl font-black text-indigo-400">{{ myRank.score }}</div>
                                <div class="text-sm text-gray-400 mt-1">Your Score</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Show Leaderboard during waiting -->
                <Card v-if="leaderboard.length > 0" class="bg-white/95 dark:bg-gray-900/95 backdrop-blur-sm border-2 border-white/20 shadow-2xl">
                    <CardHeader class="border-b border-gray-200 dark:border-gray-700">
                        <CardTitle class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span class="text-3xl">🏆</span>
                            Current Standings
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="pt-6">
                        <div class="space-y-3">
                            <div
                                v-for="entry in leaderboard.slice(0, 10)"
                                :key="entry.participant_id"
                                :class="[
                                    'flex items-center justify-between rounded-xl p-4 transition-all duration-200',
                                    entry.participant_id === participantId
                                        ? 'bg-gradient-to-r from-blue-500 to-indigo-500 text-white shadow-lg shadow-blue-500/50 scale-105'
                                        : 'bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700',
                                ]"
                            >
                                <div class="flex items-center gap-4">
                                    <span
                                        :class="[
                                            'flex h-10 w-10 items-center justify-center rounded-full font-bold text-lg',
                                            entry.rank === 1 ? 'bg-yellow-400 text-yellow-900 shadow-lg shadow-yellow-400/50' : '',
                                            entry.rank === 2 ? 'bg-gray-300 text-gray-900 shadow-lg shadow-gray-300/50' : '',
                                            entry.rank === 3 ? 'bg-orange-400 text-orange-900 shadow-lg shadow-orange-400/50' : '',
                                            entry.rank > 3 ? 'bg-gray-600 text-white' : '',
                                        ]"
                                    >
                                        {{ entry.rank }}
                                    </span>
                                    <span :class="[
                                        'font-bold text-lg',
                                        entry.participant_id === participantId ? 'text-white' : 'text-gray-900 dark:text-white'
                                    ]">
                                        {{ entry.display_name }}
                                    </span>
                                </div>
                                <span :class="[
                                    'text-2xl font-black',
                                    entry.participant_id === participantId ? 'text-white' : 'text-gray-900 dark:text-white'
                                ]">
                                    {{ entry.score }}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Active Question -->
            <div v-else-if="currentQuestion" class="space-y-6 animate-fade-in">
                <!-- Timer -->
                <Card class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white border-0 shadow-2xl shadow-blue-500/50">
                    <CardContent class="py-8 text-center">
                        <div class="text-7xl font-black mb-2">{{ timeRemaining }}s</div>
                        <div class="text-xl font-semibold opacity-90">⏱ Time Remaining</div>
                    </CardContent>
                </Card>

                <!-- Question -->
                <Card class="bg-white/95 dark:bg-gray-900/95 backdrop-blur-sm border-2 border-white/20 shadow-2xl">
                    <CardHeader>
                        <CardTitle class="text-3xl font-bold text-gray-900 dark:text-white leading-tight">
                            {{ currentQuestion.content.text }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <img
                            v-if="currentQuestion.content.image_url"
                            :src="currentQuestion.content.image_url"
                            alt="Question image"
                            class="mb-8 w-full rounded-xl object-contain shadow-lg"
                            style="max-height: 300px"
                        />

                        <!-- Options -->
                        <div class="grid gap-4 md:grid-cols-2">
                            <button
                                v-for="option in currentQuestion.options"
                                :key="option.id"
                                @click="submitAnswer(option.id)"
                                :disabled="hasAnswered || timeRemaining === 0"
                                :class="[
                                    'rounded-xl border-3 p-6 text-left transition-all duration-300 transform hover:scale-105',
                                    {
                                        'border-green-500 bg-gradient-to-br from-green-500 to-green-600 text-white shadow-lg shadow-green-500/50 scale-105':
                                            correctOptionId && option.id === correctOptionId,
                                        'border-red-500 bg-gradient-to-br from-red-500 to-red-600 text-white shadow-lg shadow-red-500/50':
                                            isAnswerWrong && option.id === selectedOptionId,
                                        'border-blue-500 bg-gradient-to-br from-blue-500 to-blue-600 text-white shadow-lg shadow-blue-500/50':
                                            selectedOptionId === option.id && !correctOptionId,
                                        'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:border-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30 shadow-md':
                                            !hasAnswered && !correctOptionId && selectedOptionId !== option.id,
                                        'cursor-not-allowed opacity-40': hasAnswered || timeRemaining === 0,
                                    },
                                ]"
                            >
                                <span class="text-xl font-bold block">{{ option.text }}</span>
                                <span v-if="correctOptionId && option.id === correctOptionId" class="mt-2 block text-2xl">✓</span>
                            </button>
                        </div>

                        <!-- Feedback -->
                        <div v-if="hasAnswered && correctOptionId" class="mt-8 rounded-xl p-6 text-center animate-fade-in">
                            <div v-if="isAnswerCorrect" class="bg-gradient-to-r from-green-500/20 to-emerald-500/20 border-2 border-green-500/30 rounded-xl p-6">
                                <div class="mb-3 text-6xl">🎉</div>
                                <p class="text-3xl font-black text-green-400">Correct!</p>
                                <p class="text-lg text-green-300 mt-2">Great job!</p>
                            </div>
                            <div v-else class="bg-gradient-to-r from-red-500/20 to-rose-500/20 border-2 border-red-500/30 rounded-xl p-6">
                                <div class="mb-3 text-6xl">❌</div>
                                <p class="text-3xl font-black text-red-400">Incorrect</p>
                                <p class="text-lg text-red-300 mt-2">Better luck next time!</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Leaderboard -->
            <Card v-if="leaderboard.length > 0" class="mt-8 bg-white/95 dark:bg-gray-900/95 backdrop-blur-sm border-2 border-white/20 shadow-2xl animate-fade-in">
                <CardHeader class="border-b border-gray-200 dark:border-gray-700">
                    <CardTitle class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="text-3xl">🏆</span>
                        Top 10 Leaderboard
                    </CardTitle>
                </CardHeader>
                <CardContent class="pt-6">
                    <div class="space-y-3">
                        <div
                            v-for="entry in leaderboard.slice(0, 10)"
                            :key="entry.participant_id"
                            :class="[
                                'flex items-center justify-between rounded-xl p-4 transition-all duration-200',
                                entry.participant_id === participantId
                                    ? 'bg-gradient-to-r from-blue-500 to-indigo-500 text-white shadow-lg shadow-blue-500/50 scale-105'
                                    : 'bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700',
                            ]"
                        >
                            <div class="flex items-center gap-4">
                                <span
                                    :class="[
                                        'flex h-10 w-10 items-center justify-center rounded-full font-bold text-lg',
                                        entry.rank === 1 ? 'bg-yellow-400 text-yellow-900 shadow-lg shadow-yellow-400/50' : '',
                                        entry.rank === 2 ? 'bg-gray-300 text-gray-900 shadow-lg shadow-gray-300/50' : '',
                                        entry.rank === 3 ? 'bg-orange-400 text-orange-900 shadow-lg shadow-orange-400/50' : '',
                                        entry.rank > 3 ? 'bg-gray-600 text-white' : '',
                                    ]"
                                >
                                    {{ entry.rank }}
                                </span>
                                <span :class="[
                                    'font-bold text-lg',
                                    entry.participant_id === participantId ? 'text-white' : 'text-gray-900 dark:text-white'
                                ]">
                                    {{ entry.display_name }}
                                </span>
                            </div>
                            <span :class="[
                                'text-2xl font-black',
                                entry.participant_id === participantId ? 'text-white' : 'text-gray-900 dark:text-white'
                            ]">
                                {{ entry.score }}
                            </span>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>

<style scoped>
@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fade-in 0.6s ease-out;
}
</style>
