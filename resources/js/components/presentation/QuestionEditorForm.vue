<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import type { Question } from '@/types/quiz';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import OptionEditor from './OptionEditor.vue';
import { Plus, Save, X } from 'lucide-vue-next';

const props = defineProps<{
    question: Question;
}>();

const emit = defineEmits<{
    save: [];
    cancel: [];
}>();

const form = ref({
    content: { ...props.question.content },
    time_limit_seconds: props.question.time_limit_seconds,
});

const isSaving = ref(false);

function addOption() {
    router.post(
        `/questions/${props.question.id}/options`,
        {
            question_id: props.question.id,
            text: 'New Option',
            is_correct: false,
        },
        {
            preserveScroll: true,
        }
    );
}

function saveQuestion() {
    isSaving.value = true;

    router.patch(
        `/questions/${props.question.id}`,
        {
            content: form.value.content,
            time_limit_seconds: form.value.time_limit_seconds,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                emit('save');
            },
            onFinish: () => {
                isSaving.value = false;
            },
        }
    );
}
</script>

<template>
    <div class="space-y-6">
        <!-- Question Text -->
        <div class="space-y-2">
            <Label for="question-text">Question Text</Label>
            <Textarea
                id="question-text"
                v-model="form.content.text"
                rows="4"
                placeholder="Enter your question..."
                class="text-base"
            />
            <p class="text-xs text-gray-500">{{ form.content.text.length }}/500 characters</p>
        </div>

        <!-- Image URL (Future Enhancement) -->
        <div class="space-y-2">
            <Label for="image-url">Image URL (Optional)</Label>
            <Input
                id="image-url"
                v-model="form.content.image_url"
                type="url"
                placeholder="https://example.com/image.jpg"
            />
            <p class="text-xs text-gray-500">Add an image to accompany your question</p>
        </div>

        <Separator />

        <!-- Time Limit -->
        <div class="space-y-2">
            <Label for="time-limit">Time Limit</Label>
            <div class="flex items-center gap-3">
                <Input
                    id="time-limit"
                    type="number"
                    v-model.number="form.time_limit_seconds"
                    min="5"
                    max="300"
                    class="w-32"
                />
                <span class="text-sm text-gray-600">seconds</span>
                <span class="text-xs text-gray-500 ml-auto">
                    ({{ Math.floor(form.time_limit_seconds / 60) }}:{{ String(form.time_limit_seconds % 60).padStart(2, '0') }})
                </span>
            </div>
            <input
                type="range"
                v-model.number="form.time_limit_seconds"
                min="5"
                max="300"
                step="5"
                class="w-full"
            />
        </div>

        <Separator />

        <!-- Answer Options -->
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <Label>Answer Options</Label>
                <Button @click="addOption" size="sm" variant="outline">
                    <Plus class="mr-2 h-4 w-4" />
                    Add Option
                </Button>
            </div>

            <div class="space-y-2">
                <OptionEditor
                    v-for="option in question.options"
                    :key="option.id"
                    :option="option"
                    :question-id="question.id"
                />
            </div>

            <p class="text-xs text-gray-500">
                Mark at least one option as correct. Participants will select one answer.
            </p>
        </div>

        <Separator />

        <!-- Actions -->
        <div class="flex gap-3 pt-4">
            <Button @click="saveQuestion" :disabled="isSaving" class="flex-1">
                <Save class="mr-2 h-4 w-4" />
                {{ isSaving ? 'Saving...' : 'Save Changes' }}
            </Button>
            <Button @click="emit('cancel')" variant="outline">
                <X class="mr-2 h-4 w-4" />
                Cancel
            </Button>
        </div>
    </div>
</template>
