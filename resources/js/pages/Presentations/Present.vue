<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import QRCode from 'qrcode';
import type { Presentation, Question } from '@/types/quiz';
import { usePresentationChannel } from '@/composables/usePresentationChannel';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { X, ChevronLeft, ChevronRight, Eye, Monitor } from 'lucide-vue-next';

const props = defineProps<{
    presentation: Presentation & { questions: Question[] };
}>();

const currentScreen = ref<'welcome' | 'question' | 'results'>('welcome');
const currentQuestionIndex = ref(-1);
const showSolution = ref(false);
const qrCodeDataUrl = ref('');
const isFullscreen = ref(false);
const timeRemaining = ref(0);
let timerInterval: number | null = null;

const currentQuestion = computed(() => {
    if (currentQuestionIndex.value >= 0 && currentQuestionIndex.value < props.presentation.questions.length) {
        return props.presentation.questions[currentQuestionIndex.value];
    }
    return null;
});

const joinUrl = computed(() => {
    if (typeof window === 'undefined') return '';
    return `${window.location.origin}/quiz/join/${props.presentation.id}`;
});

// Generate QR Code
async function generateQRCode() {
    try {
        const url = await QRCode.toDataURL(joinUrl.value, {
            width: 400,
            margin: 2,
            color: {
                dark: '#000000',
                light: '#FFFFFF',
            },
        });
        qrCodeDataUrl.value = url;
    } catch (error) {
        console.error('Failed to generate QR code:', error);
    }
}

// Timer functions
function startTimer(seconds: number) {
    stopTimer();
    timeRemaining.value = seconds;

    timerInterval = setInterval(() => {
        if (timeRemaining.value > 0) {
            timeRemaining.value--;
        } else {
            stopTimer();
        }
    }, 1000);
}

function stopTimer() {
    if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
    }
}

// Navigation functions
function goToNextQuestion() {
    showSolution.value = false;
    stopTimer();
    if (currentScreen.value === 'welcome') {
        currentScreen.value = 'question';
        currentQuestionIndex.value = 0;
        startCurrentQuestion();
    } else if (currentQuestionIndex.value < props.presentation.questions.length - 1) {
        currentQuestionIndex.value++;
        startCurrentQuestion();
    }
}

function goToPreviousQuestion() {
    showSolution.value = false;
    stopTimer();
    if (currentQuestionIndex.value > 0) {
        currentQuestionIndex.value--;
        if (currentQuestion.value) {
            startTimer(currentQuestion.value.time_limit_seconds);
        }
    } else if (currentQuestionIndex.value === 0) {
        currentScreen.value = 'welcome';
        currentQuestionIndex.value = -1;
    }
}

function toggleSolution() {
    if (currentQuestion.value) {
        showSolution.value = !showSolution.value;
        if (showSolution.value) {
            stopTimer();
            endCurrentQuestion();
        } else {
            startTimer(currentQuestion.value.time_limit_seconds);
        }
    }
}

function startCurrentQuestion() {
    if (currentQuestion.value) {
        startTimer(currentQuestion.value.time_limit_seconds);
        router.post(`/presentations/${props.presentation.id}/questions/${currentQuestion.value.id}/start`, {}, {
            preserveState: true,
        });
    }
}

function endCurrentQuestion() {
    if (currentQuestion.value) {
        router.post(`/presentations/${props.presentation.id}/questions/${currentQuestion.value.id}/end`, {}, {
            preserveState: true,
        });
    }
}

// Keyboard controls
function handleKeyPress(event: KeyboardEvent) {
    switch (event.key) {
        case 'ArrowRight':
        case ' ':
        case 'Enter':
            event.preventDefault();
            goToNextQuestion();
            break;
        case 'ArrowLeft':
        case 'Backspace':
            event.preventDefault();
            goToPreviousQuestion();
            break;
        case 'r':
        case 'R':
        case 's':
        case 'S':
            event.preventDefault();
            toggleSolution();
            break;
        case 'Escape':
            event.preventDefault();
            exitPresentation();
            break;
        case 'f':
        case 'F':
            event.preventDefault();
            toggleFullscreen();
            break;
    }
}

// Fullscreen
async function toggleFullscreen() {
    if (!document.fullscreenElement) {
        await document.documentElement.requestFullscreen();
        isFullscreen.value = true;
    } else {
        await document.exitFullscreen();
        isFullscreen.value = false;
    }
}

function exitPresentation() {
    if (document.fullscreenElement) {
        document.exitFullscreen();
    }
    router.visit(`/presentations/${props.presentation.id}/control`);
}

// Lifecycle
onMounted(() => {
    generateQRCode();
    window.addEventListener('keydown', handleKeyPress);
    document.addEventListener('fullscreenchange', () => {
        isFullscreen.value = !!document.fullscreenElement;
    });
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyPress);
    stopTimer();
});

// Real-time updates
usePresentationChannel(props.presentation.id, {
    onQuestionStarted: (event) => {
        console.log('Question started:', event);
    },
    onQuestionEnded: (event) => {
        console.log('Question ended:', event);
    },
});
</script>

<template>
    <Head :title="`Present: ${presentation.title}`" />

    <div class="fixed inset-0 bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 text-white overflow-hidden">
        <!-- Controls Overlay (top) -->
        <div class="absolute top-0 left-0 right-0 z-50 p-4 bg-gradient-to-b from-black/50 to-transparent">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Badge variant="secondary" class="text-sm">
                        {{ currentScreen === 'welcome' ? 'Welcome' : `Question ${currentQuestionIndex + 1}/${presentation.questions.length}` }}
                    </Badge>
                    <span class="text-sm text-gray-300">{{ presentation.title }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <Button @click="toggleFullscreen" variant="ghost" size="sm" class="text-white hover:bg-white/10">
                        <Monitor class="h-4 w-4" />
                        {{ isFullscreen ? 'Exit Fullscreen (F)' : 'Fullscreen (F)' }}
                    </Button>
                    <Button @click="exitPresentation" variant="ghost" size="sm" class="text-white hover:bg-white/10">
                        <X class="h-4 w-4" />
                        Exit (Esc)
                    </Button>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="h-full flex items-center justify-center p-20">
            <!-- Welcome Screen with QR Code -->
            <div v-if="currentScreen === 'welcome'" class="text-center space-y-8 animate-fade-in">
                <h1 class="text-6xl font-bold mb-4">{{ presentation.title }}</h1>
                <p v-if="presentation.description" class="text-2xl text-gray-300 mb-8">
                    {{ presentation.description }}
                </p>

                <div class="bg-white p-8 rounded-2xl inline-block shadow-2xl">
                    <img v-if="qrCodeDataUrl" :src="qrCodeDataUrl" alt="QR Code" class="w-96 h-96" />
                </div>

                <div class="space-y-4">
                    <p class="text-3xl font-semibold">Scan to Join</p>
                    <p class="text-xl text-gray-400 font-mono">{{ joinUrl }}</p>
                    <p class="text-lg text-gray-500">{{ presentation.questions.length }} questions</p>
                </div>

                <p class="text-gray-400 text-sm mt-8">Press → or Space to start</p>
            </div>

            <!-- Question Screen -->
            <div v-else-if="currentScreen === 'question' && currentQuestion" class="w-full max-w-6xl space-y-8 animate-fade-in">
                <!-- Question Number -->
                <div class="text-center mb-8">
                    <Badge class="text-2xl px-6 py-2">
                        Question {{ currentQuestionIndex + 1 }} of {{ presentation.questions.length }}
                    </Badge>
                </div>

                <!-- Question Text -->
                <div class="text-center mb-12">
                    <h2 class="text-5xl font-bold leading-tight">
                        {{ currentQuestion.content.text }}
                    </h2>
                    <img
                        v-if="currentQuestion.content.image_url"
                        :src="currentQuestion.content.image_url"
                        alt="Question image"
                        class="mt-8 mx-auto max-h-64 rounded-lg shadow-lg"
                    />
                </div>

                <!-- Options -->
                <div class="grid grid-cols-2 gap-6">
                    <div
                        v-for="(option, idx) in currentQuestion.options"
                        :key="option.id"
                        :class="[
                            'p-8 rounded-xl text-2xl font-semibold transition-all duration-300',
                            showSolution && option.is_correct
                                ? 'bg-green-600 border-4 border-green-400 shadow-green-500/50 shadow-2xl scale-105'
                                : showSolution && !option.is_correct
                                ? 'bg-red-900/40 border-2 border-red-700 opacity-60'
                                : 'bg-white/10 border-2 border-white/20 hover:bg-white/20',
                        ]"
                    >
                        <div class="flex items-center gap-4">
                            <span class="text-4xl font-bold opacity-50">{{ String.fromCharCode(65 + idx) }}</span>
                            <span class="flex-1">{{ option.text }}</span>
                            <span v-if="showSolution && option.is_correct" class="text-4xl">✓</span>
                        </div>
                    </div>
                </div>

                <!-- Countdown Timer -->
                <div class="text-center mt-8">
                    <div
                        :class="[
                            'inline-flex items-center gap-3 px-8 py-4 rounded-full text-3xl font-bold transition-all duration-300',
                            timeRemaining > 10 ? 'bg-blue-600/30 text-blue-300' :
                            timeRemaining > 5 ? 'bg-yellow-600/30 text-yellow-300' :
                            'bg-red-600/30 text-red-300 animate-pulse'
                        ]"
                    >
                        <span class="text-4xl">⏱</span>
                        <span>{{ timeRemaining }}s</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Controls (bottom) -->
        <div class="absolute bottom-0 left-0 right-0 z-50 p-6 bg-gradient-to-t from-black/50 to-transparent">
            <div class="flex items-center justify-between max-w-6xl mx-auto">
                <Button
                    @click="goToPreviousQuestion"
                    variant="ghost"
                    size="lg"
                    class="text-white hover:bg-white/10"
                    :disabled="currentScreen === 'welcome' && currentQuestionIndex < 0"
                >
                    <ChevronLeft class="mr-2 h-6 w-6" />
                    Previous (←)
                </Button>

                <Button
                    v-if="currentQuestion"
                    @click="toggleSolution"
                    variant="ghost"
                    size="lg"
                    :class="[
                        'text-white hover:bg-white/10',
                        showSolution ? 'bg-green-600/30' : '',
                    ]"
                >
                    <Eye class="mr-2 h-6 w-6" />
                    {{ showSolution ? 'Hide Solution (R)' : 'Reveal Solution (R)' }}
                </Button>

                <Button
                    @click="goToNextQuestion"
                    variant="ghost"
                    size="lg"
                    class="text-white hover:bg-white/10"
                    :disabled="currentQuestionIndex >= presentation.questions.length - 1 && currentScreen !== 'welcome'"
                >
                    Next (→)
                    <ChevronRight class="ml-2 h-6 w-6" />
                </Button>
            </div>

            <!-- Keyboard Hints -->
            <div class="text-center text-gray-500 text-sm mt-4">
                ← Previous | → Next | R Reveal | F Fullscreen | Esc Exit
            </div>
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
    animation: fade-in 0.5s ease-out;
}
</style>
