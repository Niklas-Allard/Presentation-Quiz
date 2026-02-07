<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import type { Presentation } from '@/types/quiz';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import InputError from '@/components/InputError.vue';

const props = defineProps<{
    presentation: Presentation;
}>();

const displayName = ref('');
const isJoining = ref(false);
const errors = ref<{ display_name?: string; general?: string }>({});

async function joinPresentation() {
    if (!displayName.value.trim()) {
        errors.value.display_name = 'Please enter your name';
        return;
    }

    isJoining.value = true;
    errors.value = {};

    try {
        const response = await fetch('/api/participants', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                display_name: displayName.value,
                presentation_id: props.presentation.id,
            }),
        });

        if (!response.ok) {
            const text = await response.text();
            let errorMessage = 'Failed to join';
            try {
                const errorData = JSON.parse(text);
                errorMessage = errorData.message || errorMessage;
            } catch {
                console.error('Server response:', text);
                errorMessage = 'Server error occurred';
            }
            throw new Error(errorMessage);
        }

        const data = await response.json();

        // Store participant ID in localStorage
        localStorage.setItem('participant_id', data.participant_id);
        localStorage.setItem('participant_name', data.display_name);

        // Navigate to quiz page
        router.visit(`/quiz/${data.presentation_id}`);
    } catch (error: any) {
        errors.value.general = error.message;
    } finally {
        isJoining.value = false;
    }
}
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 p-4">
        <div class="w-full max-w-md animate-fade-in">
            <Card class="bg-white/95 dark:bg-gray-900/95 backdrop-blur-sm border-2 border-white/20 shadow-2xl">
                <CardHeader class="text-center space-y-3 pb-6">
                    <CardTitle class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
                        {{ presentation.title }}
                    </CardTitle>
                    <CardDescription class="text-xl text-gray-600 dark:text-gray-300">
                        Enter your name to join the quiz
                    </CardDescription>
                </CardHeader>

                <CardContent class="space-y-6">
                    <div v-if="presentation.status === 'finished'" class="rounded-xl bg-amber-500/20 border border-amber-500/30 p-6 text-center">
                        <p class="text-lg font-semibold text-amber-200">This presentation has ended.</p>
                    </div>

                    <div v-else class="space-y-6">
                        <div class="space-y-3">
                            <Input
                                v-model="displayName"
                                type="text"
                                placeholder="Your Name"
                                class="text-xl h-14 text-center font-semibold bg-white/50 dark:bg-gray-800/50 border-2 focus:border-blue-500 transition-all"
                                maxlength="50"
                                @keyup.enter="joinPresentation"
                                :disabled="isJoining"
                            />
                            <InputError :message="errors.display_name" class="text-center" />
                        </div>

                        <InputError :message="errors.general" class="text-center text-lg" />

                        <Button
                            @click="joinPresentation"
                            class="w-full text-xl h-14 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold shadow-lg hover:shadow-xl transition-all transform hover:scale-105"
                            size="lg"
                            :disabled="isJoining || !displayName.trim()"
                        >
                            {{ isJoining ? 'Joining...' : 'Join Quiz 🚀' }}
                        </Button>

                        <div class="rounded-xl bg-blue-500/10 border border-blue-500/20 p-5">
                            <p class="text-center text-lg">
                                <span class="font-semibold text-gray-700 dark:text-gray-200">Status:</span>
                                <span class="ml-2 text-blue-600 dark:text-blue-400 font-bold">
                                    {{ presentation.status === 'waiting' ? '⏳ Waiting to start' : '▶️ In Progress' }}
                                </span>
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Footer -->
            <div class="text-center mt-6 text-gray-400 text-sm">
                <p>Get ready to compete! 🎯</p>
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
    animation: fade-in 0.6s ease-out;
}
</style>
