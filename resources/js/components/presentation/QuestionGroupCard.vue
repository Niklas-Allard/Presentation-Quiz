<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import draggable from 'vuedraggable';
import type { QuestionGroup } from '@/types/quiz';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import QuestionListItem from './QuestionListItem.vue';
import { ChevronDown, GripVertical, MoreVertical, Plus, Trash2, Edit3 } from 'lucide-vue-next';

const props = defineProps<{
    group: QuestionGroup;
    presentationId: number;
}>();

const isOpen = ref(true);
const isEditingName = ref(false);
const editedGroupName = ref(props.group.name);
const questions = ref([...props.group.questions]);

// Sync questions when prop changes
watch(() => props.group.questions, (newQuestions) => {
    questions.value = [...newQuestions];
}, { deep: true });

function handleDragEnd() {
    // Send reorder request to server
    const questionUpdates = questions.value.map((q, index) => ({
        id: q.id,
        order: props.group.questions[0]?.order ?? 0 + index,
        group_name: props.group.name,
    }));

    router.post(
        `/presentations/${props.presentationId}/questions/reorder`,
        { questions: questionUpdates },
        {
            preserveScroll: true,
        }
    );
}

function toggleGroup() {
    isOpen.value = !isOpen.value;
}

function startEditingName() {
    editedGroupName.value = props.group.name;
    isEditingName.value = true;
}

function saveGroupName() {
    if (!editedGroupName.value.trim() || editedGroupName.value === props.group.name) {
        isEditingName.value = false;
        return;
    }

    // Update all questions in this group to the new group name
    const questionUpdates = props.group.questions.map((q, index) => ({
        id: q.id,
        order: q.order,
        group_name: editedGroupName.value,
    }));

    router.post(
        `/presentations/${props.presentationId}/questions/reorder`,
        { questions: questionUpdates },
        {
            preserveScroll: true,
            onSuccess: () => {
                isEditingName.value = false;
            },
        }
    );
}

function deleteGroup() {
    if (
        !confirm(
            `Delete group "${props.group.name}" and all ${props.group.questions.length} questions? This cannot be undone.`
        )
    ) {
        return;
    }

    // Delete all questions in this group
    props.group.questions.forEach((question) => {
        router.delete(`/questions/${question.id}`, {
            preserveScroll: true,
        });
    });
}

function addQuestion() {
    const maxOrder = Math.max(...props.group.questions.map((q) => q.order), -1);

    router.post(
        `/presentations/${props.presentationId}/questions`,
        {
            presentation_id: props.presentationId,
            content: { text: 'New Question' },
            time_limit_seconds: 30,
            order: maxOrder + 1,
            group_name: props.group.name,
        },
        {
            preserveScroll: true,
        }
    );
}
</script>

<template>
    <Collapsible v-model:open="isOpen">
        <Card class="overflow-hidden transition-shadow hover:shadow-md hover:border-primary/50">
            <CollapsibleTrigger as-child>
                <CardHeader class="cursor-pointer bg-primary/5 hover:bg-primary/10 transition-colors">
                    <div class="flex items-center gap-3">
                        <GripVertical class="h-5 w-5 text-muted-foreground cursor-grab active:cursor-grabbing hover:text-foreground flex-shrink-0" />

                        <ChevronDown
                            :class="[
                                'h-5 w-5 text-muted-foreground transition-transform flex-shrink-0',
                                isOpen ? 'rotate-0' : '-rotate-90',
                            ]"
                        />

                        <div v-if="!isEditingName" class="flex-1 flex items-center gap-3">
                            <h3 class="text-lg font-semibold">{{ group.name }}</h3>
                            <Badge variant="secondary">{{ group.questions.length }} questions</Badge>
                        </div>

                        <Input
                            v-else
                            v-model="editedGroupName"
                            @click.stop
                            @keyup.enter="saveGroupName"
                            @keyup.esc="isEditingName = false"
                            @blur="saveGroupName"
                            class="flex-1"
                        />

                        <DropdownMenu>
                            <DropdownMenuTrigger as-child @click.stop>
                                <Button variant="ghost" size="icon">
                                    <MoreVertical class="h-4 w-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem @click.stop="startEditingName">
                                    <Edit3 class="mr-2 h-4 w-4" />
                                    Rename Group
                                </DropdownMenuItem>
                                <DropdownMenuItem @click.stop="deleteGroup" class="text-red-600">
                                    <Trash2 class="mr-2 h-4 w-4" />
                                    Delete Group
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </CardHeader>
            </CollapsibleTrigger>

            <CollapsibleContent>
                <CardContent class="space-y-3 pt-4">
                    <!-- Questions List -->
                    <draggable
                        v-model="questions"
                        @end="handleDragEnd"
                        handle=".drag-handle"
                        item-key="id"
                        tag="div"
                        class="space-y-2"
                        ghost-class="opacity-50"
                        :animation="200"
                    >
                        <template #item="{ element: question, index }">
                            <QuestionListItem
                                :question="question"
                                :index="index"
                                :group-name="group.name"
                            />
                        </template>
                    </draggable>

                    <!-- Add Question Button -->
                    <Button @click="addQuestion" variant="ghost" class="w-full border-2 border-dashed">
                        <Plus class="mr-2 h-4 w-4" />
                        Add Question to {{ group.name }}
                    </Button>
                </CardContent>
            </CollapsibleContent>
        </Card>
    </Collapsible>
</template>
