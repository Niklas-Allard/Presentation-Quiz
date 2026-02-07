import { router } from '@inertiajs/vue3';
import type { ReorderQuestionData } from '@/types/quiz';

export function usePresentationEditor(presentationId: number) {
    const saveQuestion = async (questionId: number, questionData: any) => {
        return new Promise((resolve, reject) => {
            router.patch(`/questions/${questionId}`, questionData, {
                preserveScroll: true,
                onSuccess: () => resolve(true),
                onError: (errors) => reject(errors),
            });
        });
    };

    const deleteQuestion = async (questionId: number) => {
        return new Promise((resolve, reject) => {
            router.delete(`/questions/${questionId}`, {
                preserveScroll: true,
                onSuccess: () => resolve(true),
                onError: (errors) => reject(errors),
            });
        });
    };

    const reorderQuestions = async (reorderData: ReorderQuestionData[]) => {
        return new Promise((resolve, reject) => {
            router.post(
                `/presentations/${presentationId}/questions/reorder`,
                { questions: reorderData },
                {
                    preserveScroll: true,
                    onSuccess: () => resolve(true),
                    onError: (errors) => reject(errors),
                }
            );
        });
    };

    const addQuestion = async (groupName: string, questionData: any) => {
        return new Promise((resolve, reject) => {
            router.post(
                `/presentations/${presentationId}/questions`,
                {
                    ...questionData,
                    presentation_id: presentationId,
                    group_name: groupName,
                },
                {
                    preserveScroll: true,
                    onSuccess: () => resolve(true),
                    onError: (errors) => reject(errors),
                }
            );
        });
    };

    const addOption = async (questionId: number, optionData: any) => {
        return new Promise((resolve, reject) => {
            router.post(
                `/questions/${questionId}/options`,
                {
                    ...optionData,
                    question_id: questionId,
                },
                {
                    preserveScroll: true,
                    onSuccess: () => resolve(true),
                    onError: (errors) => reject(errors),
                }
            );
        });
    };

    const updateOption = async (optionId: number, optionData: any) => {
        return new Promise((resolve, reject) => {
            router.patch(`/options/${optionId}`, optionData, {
                preserveScroll: true,
                onSuccess: () => resolve(true),
                onError: (errors) => reject(errors),
            });
        });
    };

    const deleteOption = async (optionId: number) => {
        return new Promise((resolve, reject) => {
            router.delete(`/options/${optionId}`, {
                preserveScroll: true,
                onSuccess: () => resolve(true),
                onError: (errors) => reject(errors),
            });
        });
    };

    return {
        saveQuestion,
        deleteQuestion,
        reorderQuestions,
        addQuestion,
        addOption,
        updateOption,
        deleteOption,
    };
}
