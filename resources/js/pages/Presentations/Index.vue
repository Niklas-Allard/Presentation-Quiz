<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import type { Presentation } from '@/types/quiz';
import type { BreadcrumbItem } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { MoreVertical, Plus, Edit, Play, Trash2 } from 'lucide-vue-next';
import { index } from '@/routes/presentations';

const props = defineProps<{
    presentations: Presentation[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Presentations',
        href: index().url,
    },
];

function createPresentation() {
    router.visit('/presentations/create');
}

function editPresentation(id: number) {
    router.visit(`/presentations/${id}/edit`);
}

function goToControl(id: number) {
    router.visit(`/presentations/${id}/control`);
}

function deletePresentation(id: number) {
    if (confirm('Are you sure you want to delete this presentation? This cannot be undone.')) {
        router.delete(`/presentations/${id}`);
    }
}

function getStatusVariant(status: string) {
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
}

function formatDate(dateString: string) {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}
</script>

<template>
    <Head title="Presentations" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">My Presentations</h1>
                    <p class="text-sm text-muted-foreground">Create and manage your quiz presentations</p>
                </div>
                <Button @click="createPresentation" size="lg">
                    <Plus class="mr-2 h-4 w-4" />
                    Create Presentation
                </Button>
            </div>

            <!-- Presentations Grid -->
            <div v-if="presentations.length > 0" class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <Card v-for="presentation in presentations" :key="presentation.id" class="hover:border-primary/50 transition-colors">
                    <CardHeader>
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <CardTitle class="text-xl">{{ presentation.title }}</CardTitle>
                                <CardDescription v-if="presentation.description" class="mt-1">
                                    {{ presentation.description.substring(0, 100) }}{{ presentation.description.length > 100 ? '...' : '' }}
                                </CardDescription>
                            </div>
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button variant="ghost" size="icon">
                                        <MoreVertical class="h-4 w-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem @click="editPresentation(presentation.id)">
                                        <Edit class="mr-2 h-4 w-4" />
                                        Edit
                                    </DropdownMenuItem>
                                    <DropdownMenuItem @click="goToControl(presentation.id)">
                                        <Play class="mr-2 h-4 w-4" />
                                        Live Control
                                    </DropdownMenuItem>
                                    <DropdownMenuItem @click="deletePresentation(presentation.id)" class="text-destructive">
                                        <Trash2 class="mr-2 h-4 w-4" />
                                        Delete
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <Badge :variant="getStatusVariant(presentation.status)">
                                    {{ presentation.status.toUpperCase() }}
                                </Badge>
                                <span class="text-sm text-muted-foreground">
                                    {{ presentation.questions_count || 0 }} questions
                                </span>
                            </div>
                            <span class="text-xs text-muted-foreground">
                                {{ formatDate(presentation.updated_at) }}
                            </span>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <Card v-else class="border-dashed">
                <CardContent class="p-12 text-center">
                    <div class="mx-auto max-w-md space-y-4">
                        <div class="mx-auto h-24 w-24 rounded-full bg-primary/10 flex items-center justify-center">
                            <Plus class="h-12 w-12 text-primary" />
                        </div>
                        <h3 class="text-2xl font-semibold">No presentations yet</h3>
                        <p class="text-muted-foreground">
                            Get started by creating your first quiz presentation. Organize questions into groups and engage your audience with interactive quizzes.
                        </p>
                        <Button @click="createPresentation" size="lg" class="mt-4">
                            <Plus class="mr-2 h-4 w-4" />
                            Create Your First Presentation
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
