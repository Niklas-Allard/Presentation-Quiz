<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import type { BreadcrumbItem } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/presentations';

const form = ref({
    title: '',
    description: '',
});

const errors = ref<Record<string, string>>({});
const isSubmitting = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Presentations',
        href: index().url,
    },
    {
        title: 'Create',
        href: '#',
    },
];

function goBack() {
    router.visit('/presentations');
}

function createPresentation() {
    if (isSubmitting.value) return;

    errors.value = {};
    isSubmitting.value = true;

    router.post('/presentations', form.value, {
        preserveScroll: true,
        onError: (err) => {
            errors.value = err;
            isSubmitting.value = false;
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
}
</script>

<template>
    <Head title="Create Presentation" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div>
                <h1 class="text-3xl font-bold">Create Presentation</h1>
                <p class="text-sm text-muted-foreground">Start building your interactive quiz</p>
            </div>

            <!-- Form Card -->
            <Card class="max-w-2xl">
                <CardHeader>
                    <CardTitle>Presentation Details</CardTitle>
                    <CardDescription>
                        Give your presentation a title and description. You can add questions after creation.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="createPresentation" class="space-y-6">
                        <!-- Title Input -->
                        <div class="space-y-2">
                            <Label for="title">Title *</Label>
                            <Input
                                id="title"
                                v-model="form.title"
                                type="text"
                                placeholder="e.g., JavaScript Fundamentals Quiz"
                                :class="{ 'border-destructive': errors.title }"
                                required
                            />
                            <p v-if="errors.title" class="text-sm text-destructive">{{ errors.title }}</p>
                        </div>

                        <!-- Description Textarea -->
                        <div class="space-y-2">
                            <Label for="description">Description (Optional)</Label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                placeholder="Describe what this presentation covers..."
                                rows="4"
                                :class="{ 'border-destructive': errors.description }"
                            />
                            <p v-if="errors.description" class="text-sm text-destructive">{{ errors.description }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ form.description.length }}/2000 characters
                            </p>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex gap-3 pt-4">
                            <Button type="submit" :disabled="isSubmitting || !form.title" size="lg" class="flex-1">
                                {{ isSubmitting ? 'Creating...' : 'Create & Continue Editing' }}
                            </Button>
                            <Button @click="goBack" type="button" variant="outline" size="lg">
                                Cancel
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
