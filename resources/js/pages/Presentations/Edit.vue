
<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import draggable from 'vuedraggable';
import type { Presentation, QuestionGroup } from '@/types/quiz';
import type { BreadcrumbItem } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import QuestionGroupCard from '@/components/presentation/QuestionGroupCard.vue';
import { Play, Plus } from 'lucide-vue-next';
import { index } from '@/routes/presentations';

const props = defineProps<{
    presentation: Presentation & { questions: any[] };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Presentations',
        href: index().url,
    },
    {
        title: props.presentation.title,
        href: '#',
    },
];

const localPresentation = ref({
    title: props.presentation.title,
    description: props.presentation.description || '',
});

const isSaving = ref(false);
const lastSaved = ref<Date | null>(null);
const showAddGroup = ref(false);
const newGroupName = ref('');

// Function to compute grouped questions
function computeGroupedQuestions() {
    const groups = new Map<string, any[]>();

    props.presentation.questions.forEach((question) => {
        const groupName = question.group_name || 'Questions';
        if (!groups.has(groupName)) {
            groups.set(groupName, []);
        }
        groups.get(groupName)!.push(question);
    });

    // Sort questions within each group by order
    groups.forEach((questions) => {
        questions.sort((a, b) => a.order - b.order);
    });

    return Array.from(groups.entries()).map(([name, questions]) => ({
        name,
        questions,
    }));
}

// Group questions by group_name
const groupedQuestions = ref<QuestionGroup[]>(computeGroupedQuestions());

// Watch for changes to props.presentation.questions and update groupedQuestions
watch(() => props.presentation.questions, () => {
    groupedQuestions.value = computeGroupedQuestions();
}, { deep: true });

const getStatusVariant = (status: string) => {
    switch (status) {
        case 'active':
            return 'default';
        case 'waiting':
            return 'secondary';
        case 'finished':
            return 'destructive';
        default:
            return 'outline';
    }
};

// Auto-save presentation details
const savePresentation = useDebounceFn(() => {
    isSaving.value = true;

    router.patch(
        `/presentations/${props.presentation.id}`,
        localPresentation.value,
        {
            preserveScroll: true,
            onSuccess: () => {
                lastSaved.value = new Date();
            },
            onFinish: () => {
                isSaving.value = false;
            },
        }
    );
}, 500);

function goToControl() {
    router.visit(`/presentations/${props.presentation.id}/control`);
}

function addGroup() {
    showAddGroup.value = true;
}

function createGroup() {
    if (!newGroupName.value.trim()) return;

    // Create a placeholder question in the new group
    const maxOrder = Math.max(...props.presentation.questions.map((q) => q.order), -1);

    router.post(
        `/presentations/${props.presentation.id}/questions`,
        {
            presentation_id: props.presentation.id,
            content: { text: 'New Question' },
            time_limit_seconds: 30,
            order: maxOrder + 1,
            group_name: newGroupName.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                newGroupName.value = '';
                showAddGroup.value = false;
            },
        }
    );
}

function handleGroupReorder() {
    // Reorder all questions based on new group order
    const questionUpdates: any[] = [];
    let currentOrder = 0;

    groupedQuestions.value.forEach((group) => {
        group.questions.forEach((question) => {
            questionUpdates.push({
                id: question.id,
                order: currentOrder++,
                group_name: group.name,
            });
        });
    });

    router.post(
        `/presentations/${props.presentation.id}/questions/reorder`,
        { questions: questionUpdates },
        {
            preserveScroll: true,
        }
    );
}

function formatRelativeTime(date: Date) {
    const seconds = Math.floor((new Date().getTime() - date.getTime()) / 1000);

    if (seconds < 10) return 'just now';
    if (seconds < 60) return `${seconds}s ago`;

    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `${minutes}m ago`;

    const hours = Math.floor(minutes / 60);
    return `${hours}h ago`;
}
</script>

<template>
    <Head :title="`Edit: ${presentation.title}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-3xl font-bold">{{ presentation.title }}</h1>
                        <Badge :variant="getStatusVariant(presentation.status)">
                            {{ presentation.status.toUpperCase() }}
                        </Badge>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        {{ presentation.questions.length }} questions
                        <span v-if="isSaving" class="ml-2">• Saving...</span>
                        <span v-else-if="lastSaved" class="ml-2">
                            • Saved {{ formatRelativeTime(lastSaved) }}
                        </span>
                    </p>
                </div>
                <div class="flex gap-3">
                    <Button @click="goToControl" variant="default">
                        <Play class="mr-2 h-4 w-4" />
                        Go to Live Control
                    </Button>
                </div>
            </div>

            <!-- Presentation Details Card -->
            <Card>
                <CardHeader>
                    <CardTitle>Presentation Details</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="space-y-2">
                        <Label for="title">Title</Label>
                        <Input
                            id="title"
                            v-model="localPresentation.title"
                            type="text"
                            @blur="savePresentation"
                            @input="savePresentation"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="description">Description</Label>
                        <Textarea
                            id="description"
                            v-model="localPresentation.description"
                            rows="3"
                            @blur="savePresentation"
                            @input="savePresentation"
                        />
                    </div>
                </CardContent>
            </Card>

            <!-- Question Groups Section -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-semibold">Questions by Group</h2>
                    <Button @click="addGroup" size="sm" variant="outline">
                        <Plus class="mr-2 h-4 w-4" />
                        Add Group
                    </Button>
                </div>

                <!-- Sortable Groups -->
                <draggable
                    v-model="groupedQuestions"
                    @end="handleGroupReorder"
                    item-key="name"
                    tag="div"
                    class="space-y-4"
                    ghost-class="opacity-50"
                    :animation="200"
                >
                    <template #item="{ element: group }">
                        <QuestionGroupCard
                            :group="group"
                            :presentation-id="presentation.id"
                        />
                    </template>
                </draggable>

                <!-- Add Group Card -->
                <Card v-if="showAddGroup" class="border-dashed">
                    <CardContent class="pt-6">
                        <div class="flex gap-3">
                            <Input
                                v-model="newGroupName"
                                placeholder="Enter group name (e.g., Introduction, Advanced Topics)"
                                @keyup.enter="createGroup"
                            />
                            <Button @click="createGroup" :disabled="!newGroupName.trim()">
                                Create
                            </Button>
                            <Button @click="showAddGroup = false" variant="outline">
                                Cancel
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Empty State -->
                <Card v-if="groupedQuestions.length === 0" class="border-dashed">
                    <CardContent class="py-12 text-center">
                        <div class="mx-auto max-w-md space-y-3">
                            <h3 class="text-xl font-semibold">No questions yet</h3>
                            <p class="text-muted-foreground">
                                Add your first group to organize questions by topic or difficulty level.
                            </p>
                            <Button @click="addGroup" class="mt-4">
                                <Plus class="mr-2 h-4 w-4" />
                                Add Your First Group
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
