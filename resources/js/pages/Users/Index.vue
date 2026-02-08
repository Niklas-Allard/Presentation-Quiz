<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Shield, Trash2, UserPlus } from 'lucide-vue-next';

interface User {
    id: number;
    name: string;
    email: string;
    is_admin: boolean;
    created_at: string;
}

const props = defineProps<{
    users: User[];
}>();

function deleteUser(userId: number) {
    if (confirm('Are you sure you want to delete this user?')) {
        router.delete(`/users/${userId}`);
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
    <AppLayout title="User Management">
        <div class="container mx-auto py-8 px-4">
            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">User Management</h1>
                    <p class="text-muted-foreground mt-1">
                        Manage user accounts and permissions
                    </p>
                </div>
                <Button @click="router.visit('/users/create')" size="lg">
                    <UserPlus class="mr-2 h-5 w-5" />
                    Create User
                </Button>
            </div>

            <!-- Users Table -->
            <Card>
                <CardHeader>
                    <CardTitle>All Users</CardTitle>
                    <CardDescription>
                        {{ users.length }} {{ users.length === 1 ? 'user' : 'users' }} registered
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Role</TableHead>
                                <TableHead>Created</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="user in users" :key="user.id">
                                <TableCell class="font-medium">{{ user.name }}</TableCell>
                                <TableCell>{{ user.email }}</TableCell>
                                <TableCell>
                                    <Badge v-if="user.is_admin" variant="default">
                                        <Shield class="mr-1 h-3 w-3" />
                                        Admin
                                    </Badge>
                                    <Badge v-else variant="secondary">
                                        User
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-muted-foreground">
                                    {{ formatDate(user.created_at) }}
                                </TableCell>
                                <TableCell class="text-right">
                                    <Button
                                        @click="deleteUser(user.id)"
                                        variant="ghost"
                                        size="sm"
                                        class="text-destructive hover:text-destructive"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
