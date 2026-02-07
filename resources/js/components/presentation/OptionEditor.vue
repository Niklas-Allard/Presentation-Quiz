<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import type { Option } from '@/types/quiz';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Trash2, Check } from 'lucide-vue-next';

const props = defineProps<{
    option: Option;
    questionId: number;
}>();

const localOption = ref({
    text: props.option.text,
    is_correct: props.option.is_correct ?? false,
});

// Watch for prop changes and sync localOption
watch(() => props.option, (newOption) => {
    localOption.value.text = newOption.text;
    localOption.value.is_correct = newOption.is_correct ?? false;
}, { deep: true });

const saveOption = useDebounceFn(() => {
    console.log('Saving option:', {
        id: props.option.id,
        text: localOption.value.text,
        is_correct: localOption.value.is_correct,
    });

    router.patch(
        `/options/${props.option.id}`,
        {
            text: localOption.value.text,
            is_correct: localOption.value.is_correct,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                console.log('Option saved successfully');
            },
            onError: (errors) => {
                console.error('Failed to save option:', errors);
                alert('Failed to save option. Check console for details.');
            },
        }
    );
}, 500);

function handleCheckboxChange() {
    console.log('Checkbox changed, new value:', localOption.value.is_correct);
    saveOption();
}

function deleteOption() {
    if (!confirm('Delete this option?')) {
        return;
    }

    router.delete(`/options/${props.option.id}`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <div class="flex items-center gap-3 p-2 rounded-md bg-muted/30 hover:bg-muted/50 transition-colors">
        <!-- Temporary: Using native HTML checkbox for debugging -->
        <input
            type="checkbox"
            v-model="localOption.is_correct"
            @change="handleCheckboxChange"
            class="shrink-0 h-4 w-4 rounded border-gray-300"
        />
        <!-- Original shadcn checkbox (commented for debugging):
        <Checkbox
            v-model:checked="localOption.is_correct"
            @update:checked="handleCheckboxChange"
            class="shrink-0"
        />
        -->

        <Input
            v-model="localOption.text"
            @blur="saveOption"
            @input="saveOption"
            placeholder="Option text"
            :class="[
                'flex-1',
                localOption.is_correct ? 'border-green-500 dark:border-green-400 bg-green-50 dark:bg-green-500/10' : '',
            ]"
        />

        <Button @click="deleteOption" variant="ghost" size="icon" class="shrink-0 text-destructive hover:text-destructive">
            <Trash2 class="h-4 w-4" />
        </Button>

        <div v-if="localOption.is_correct" class="shrink-0 text-green-600 dark:text-green-400 font-medium text-sm flex items-center gap-1">
            <Check class="h-4 w-4" />
            Correct
        </div>
    </div>
</template>
