<script setup lang="ts">
import { computed, ref, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import type { Presentation, Question, QuestionGroup } from '@/types/quiz';
import type { BreadcrumbItem } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import { usePresentationChannel } from '@/composables/usePresentationChannel';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { ChevronDown, Play, Copy, Check, Monitor } from 'lucide-vue-next';
import { index } from '@/routes/presentations';

const props = defineProps<{
    presentation: Presentation & { questions: Question[] };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Presentations',
        href: index().url,
    },
    {
        title: props.presentation.title,
        href: `/presentations/${props.presentation.id}/edit`,
    },
    {
        title: 'Live Control',
        href: '#',
    },
];

const currentQuestionId = ref<number | null>(null);
const isProcessing = ref(false);
const joinUrl = ref('');
const isCopied = ref(false);

const currentQuestionIndex = computed(() => {
    if (!currentQuestionId.value) return -1;
    return props.presentation.questions.findIndex((q) => q.id === currentQuestionId.value);
});

const nextQuestion = computed(() => {
    const currentIndex = currentQuestionIndex.value;
    if (currentIndex === -1) return props.presentation.questions[0];
    if (currentIndex < props.presentation.questions.length - 1) {
        return props.presentation.questions[currentIndex + 1];
    }
    return null;
});

const hasActiveQuestion = computed(() => currentQuestionId.value !== null);

const questionGroups = computed<QuestionGroup[]>(() => {
    const groups = new Map<string, Question[]>();

    props.presentation.questions.forEach((question) => {
        const groupName = question.group_name || 'Questions';
        if (!groups.has(groupName)) {
            groups.set(groupName, []);
        }
        groups.get(groupName)!.push(question);
    });

    return Array.from(groups.entries()).map(([name, questions]) => ({
        name,
        questions: questions.sort((a, b) => a.order - b.order),
    }));
});

onMounted(() => {
    joinUrl.value = `${window.location.origin}/quiz/join/${props.presentation.id}`;
});

async function startQuestion(question: Question) {
    if (isProcessing.value) return;

    isProcessing.value = true;

    try {
        await router.post(`/presentations/${props.presentation.id}/questions/${question.id}/start`, {}, {
            preserveState: true,
            onSuccess: () => {
                currentQuestionId.value = question.id;
            },
            onError: (errors) => {
                console.error('Failed to start question:', errors);
                alert('Failed to start question');
            },
        });
    } finally {
        isProcessing.value = false;
    }
}

async function endCurrentQuestion() {
    if (!currentQuestionId.value || isProcessing.value) return;

    isProcessing.value = true;

    try {
        await router.post(
            `/presentations/${props.presentation.id}/questions/${currentQuestionId.value}/end`,
            {},
            {
                preserveState: true,
                onSuccess: () => {
                    currentQuestionId.value = null;
                },
                onError: (errors) => {
                    console.error('Failed to end question:', errors);
                    alert('Failed to end question');
                },
            }
        );
    } finally {
        isProcessing.value = false;
    }
}

async function updateStatus(status: string) {
    if (isProcessing.value) return;

    isProcessing.value = true;

    try {
        await router.patch(`/presentations/${props.presentation.id}/status/${status}`, {}, {
            preserveState: true,
            onError: (errors) => {
                console.error('Failed to update status:', errors);
                alert('Failed to update status');
            },
        });
    } finally {
        isProcessing.value = false;
    }
}

function handleQuestionStarted(event: any) {
    currentQuestionId.value = event.question_id;
}

function handleQuestionEnded() {
    currentQuestionId.value = null;
}

async function startGroup(group: QuestionGroup) {
    if (isProcessing.value || group.questions.length === 0) return;

    // Start the first question in the group
    await startQuestion(group.questions[0]);
}

async function copyToClipboard() {
    try {
        await navigator.clipboard.writeText(joinUrl.value);
        isCopied.value = true;
        setTimeout(() => {
            isCopied.value = false;
        }, 2000);
    } catch (error) {
        console.error('Failed to copy:', error);
        alert('Failed to copy to clipboard');
    }
}

function goToPresentationMode() {
    router.visit(`/presentations/${props.presentation.id}/present`);
}

usePresentationChannel(props.presentation.id, {
    onQuestionStarted: handleQuestionStarted,
    onQuestionEnded: handleQuestionEnded,
});
</script>

<template>
    <Head :title="`Control: ${presentation.title}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">{{ presentation.title }}</h1>
                    <p class="text-sm text-muted-foreground">Presentation Control Panel</p>
                </div>
                <div class="flex items-center gap-3">
                    <Button @click="goToPresentationMode" size="lg" variant="default">
                        <Monitor class="mr-2 h-5 w-5" />
                        Presentation Mode
                    </Button>
                    <Badge
                        :variant="
                            presentation.status === 'active'
                                ? 'default'
                                : presentation.status === 'waiting'
                                  ? 'secondary'
                                  : 'outline'
                        "
                        class="text-sm"
                    >
                        {{ presentation.status.toUpperCase() }}
                    </Badge>
                </div>
            </div>

            <!-- Status Controls -->
            <Card>
                <CardHeader>
                    <CardTitle>Presentation Status</CardTitle>
                    <CardDescription>Control the presentation state</CardDescription>
                </CardHeader>
                <CardContent class="flex gap-3">
                    <Button
                        @click="updateStatus('waiting')"
                        :disabled="presentation.status === 'waiting' || isProcessing"
                        variant="outline"
                    >
                        Set to Waiting
                    </Button>
                    <Button
                        @click="updateStatus('active')"
                        :disabled="presentation.status === 'active' || isProcessing"
                    >
                        Start Presentation
                    </Button>
                    <Button
                        @click="updateStatus('finished')"
                        :disabled="presentation.status === 'finished' || isProcessing"
                        variant="destructive"
                    >
                        End Presentation
                    </Button>
                </CardContent>
            </Card>

            <!-- Participant Join Link -->
            <Card class="bg-primary/5 border-primary/20">
                <CardHeader>
                    <CardTitle>Participant Join Link</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex gap-2">
                        <Input
                            type="text"
                            :value="joinUrl"
                            readonly
                            class="flex-1"
                        />
                        <Button
                            @click="copyToClipboard"
                            variant="outline"
                            :disabled="!joinUrl"
                        >
                            <Check v-if="isCopied" class="mr-2 h-4 w-4" />
                            <Copy v-else class="mr-2 h-4 w-4" />
                            {{ isCopied ? 'Copied!' : 'Copy Link' }}
                        </Button>
                    </div>
                </CardContent>
            </Card>
            <Card v-if="hasActiveQuestion" class="border-green-500 bg-green-500/10 dark:bg-green-500/20">
                <CardHeader>
                    <CardTitle class="text-green-700 dark:text-green-400">🟢 Active Question</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div v-if="currentQuestionIndex >= 0">
                            <p class="text-sm text-muted-foreground">
                                Question {{ currentQuestionIndex + 1 }} of {{ presentation.questions.length }}
                            </p>
                            <p class="text-lg font-semibold">
                                {{ presentation.questions[currentQuestionIndex].content.text }}
                            </p>
                        </div>
                        <Button @click="endCurrentQuestion" :disabled="isProcessing" variant="destructive">
                            {{ isProcessing ? 'Ending...' : 'End Question & Show Results' }}
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Next Question -->
            <Card v-if="nextQuestion && !hasActiveQuestion">
                <CardHeader>
                    <CardTitle>Next Question Ready</CardTitle>
                    <CardDescription>
                        Question {{ currentQuestionIndex + 2 }} of {{ presentation.questions.length }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div>
                            <p class="text-xl font-semibold">{{ nextQuestion.content.text }}</p>
                            <img
                                v-if="nextQuestion.content.image_url"
                                :src="nextQuestion.content.image_url"
                                alt="Question preview"
                                class="mt-3 max-h-48 rounded-lg"
                            />
                        </div>
                        <div class="grid gap-2">
                            <div
                                v-for="(option, idx) in nextQuestion.options"
                                :key="option.id"
                                class="rounded-md border bg-muted/50 p-3"
                            >
                                <span class="font-medium">{{ String.fromCharCode(65 + idx) }}.</span>
                                {{ option.text }}
                                <span v-if="option.is_correct" class="ml-2 text-green-600 dark:text-green-400">✓ Correct</span>
                            </div>
                        </div>
                        <Button @click="startQuestion(nextQuestion)" :disabled="isProcessing" size="lg" class="w-full">
                            {{ isProcessing ? 'Starting...' : '▶️ Start This Question' }}
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Questions by Group -->
            <Card>
                <CardHeader>
                    <CardTitle>Questions by Group ({{ presentation.questions.length }} total)</CardTitle>
                    <CardDescription>Organize your presentation flow by starting questions individually or by group</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <Collapsible v-for="group in questionGroups" :key="group.name" :default-open="true">
                            <div class="rounded-lg border bg-muted/30">
                                <CollapsibleTrigger class="w-full">
                                    <div class="flex items-center justify-between p-4 hover:bg-muted/50 transition-colors">
                                        <div class="flex items-center gap-3">
                                            <ChevronDown class="h-5 w-5 text-muted-foreground transition-transform" />
                                            <h3 class="text-lg font-semibold">{{ group.name }}</h3>
                                            <Badge variant="secondary">{{ group.questions.length }} questions</Badge>
                                        </div>
                                        <Button
                                            @click.stop="startGroup(group)"
                                            :disabled="isProcessing || hasActiveQuestion"
                                            size="sm"
                                            variant="outline"
                                        >
                                            <Play class="mr-2 h-4 w-4" />
                                            Start Group
                                        </Button>
                                    </div>
                                </CollapsibleTrigger>

                                <CollapsibleContent>
                                    <div class="space-y-2 p-4 pt-0">
                                        <div
                                            v-for="(question, index) in group.questions"
                                            :key="question.id"
                                            :class="[
                                                'flex items-start gap-4 rounded-lg border p-4 transition-colors',
                                                currentQuestionId === question.id
                                                    ? 'border-green-500 bg-green-500/10 dark:bg-green-500/20'
                                                    : 'bg-card',
                                            ]"
                                        >
                                            <div
                                                :class="[
                                                    'flex h-8 w-8 shrink-0 items-center justify-center rounded-full font-bold',
                                                    currentQuestionId === question.id
                                                        ? 'bg-green-500 text-white'
                                                        : 'bg-muted text-muted-foreground',
                                                ]"
                                            >
                                                {{ index + 1 }}
                                            </div>
                                            <div class="flex-1">
                                                <p class="font-medium">{{ question.content.text }}</p>
                                                <p class="text-sm text-muted-foreground">
                                                    {{ question.options.length }} options • {{ question.time_limit_seconds }}s
                                                </p>
                                            </div>
                                            <Button
                                                v-if="currentQuestionId !== question.id"
                                                @click="startQuestion(question)"
                                                :disabled="isProcessing || hasActiveQuestion"
                                                size="sm"
                                                variant="outline"
                                            >
                                                Start
                                            </Button>
                                            <Badge v-else variant="default">Active</Badge>
                                        </div>
                                    </div>
                                </CollapsibleContent>
                            </div>
                        </Collapsible>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
