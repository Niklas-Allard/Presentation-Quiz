import { ref } from 'vue';
import { useDebounceFn } from '@vueuse/core';

export function useAutoSave(saveFn: (data: any) => Promise<any>, delay = 500) {
    const isSaving = ref(false);
    const lastSaved = ref<Date | null>(null);
    const error = ref<string | null>(null);

    const save = useDebounceFn(async (data: any) => {
        isSaving.value = true;
        error.value = null;

        try {
            await saveFn(data);
            lastSaved.value = new Date();
        } catch (e: any) {
            error.value = e?.message || 'Failed to save';
            console.error('Auto-save error:', e);
        } finally {
            isSaving.value = false;
        }
    }, delay);

    const saveImmediately = async (data: any) => {
        isSaving.value = true;
        error.value = null;

        try {
            await saveFn(data);
            lastSaved.value = new Date();
        } catch (e: any) {
            error.value = e?.message || 'Failed to save';
            console.error('Save error:', e);
        } finally {
            isSaving.value = false;
        }
    };

    return {
        save,
        saveImmediately,
        isSaving,
        lastSaved,
        error,
    };
}
