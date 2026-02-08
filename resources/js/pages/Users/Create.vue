<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { ArrowLeft } from 'lucide-vue-next';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    is_admin: false,
});

function submit() {
    form.post('/users', {
        preserveScroll: true,
    });
}
</script>

<template>
    <AppLayout title="Create User">
        <div class="container mx-auto py-8 px-4 max-w-2xl">
            <!-- Back Button -->
            <Button
                @click="$inertia.visit('/users')"
                variant="ghost"
                class="mb-6"
            >
                <ArrowLeft class="mr-2 h-4 w-4" />
                Back to Users
            </Button>

            <!-- Create User Form -->
            <Card>
                <CardHeader>
                    <CardTitle>Create New User</CardTitle>
                    <CardDescription>
                        Add a new user account to the system
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Name -->
                        <div class="space-y-2">
                            <Label for="name">Name</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                placeholder="John Doe"
                                required
                                :class="{ 'border-destructive': form.errors.name }"
                            />
                            <p v-if="form.errors.name" class="text-sm text-destructive">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <!-- Email -->
                        <div class="space-y-2">
                            <Label for="email">Email</Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                placeholder="john@example.com"
                                required
                                :class="{ 'border-destructive': form.errors.email }"
                            />
                            <p v-if="form.errors.email" class="text-sm text-destructive">
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <!-- Password -->
                        <div class="space-y-2">
                            <Label for="password">Password</Label>
                            <Input
                                id="password"
                                v-model="form.password"
                                type="password"
                                placeholder="••••••••"
                                required
                                :class="{ 'border-destructive': form.errors.password }"
                            />
                            <p v-if="form.errors.password" class="text-sm text-destructive">
                                {{ form.errors.password }}
                            </p>
                        </div>

                        <!-- Password Confirmation -->
                        <div class="space-y-2">
                            <Label for="password_confirmation">Confirm Password</Label>
                            <Input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                                placeholder="••••••••"
                                required
                            />
                        </div>

                        <!-- Is Admin -->
                        <div class="flex items-center space-x-2">
                            <Checkbox
                                id="is_admin"
                                :checked="form.is_admin"
                                @update:checked="form.is_admin = $event"
                            />
                            <Label
                                for="is_admin"
                                class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 cursor-pointer"
                            >
                                Administrator privileges
                            </Label>
                        </div>
                        <p class="text-sm text-muted-foreground ml-6">
                            Admins can create users, manage presentations, and access all features
                        </p>

                        <!-- Submit Button -->
                        <div class="flex gap-3 pt-4">
                            <Button
                                type="submit"
                                :disabled="form.processing"
                                class="flex-1"
                            >
                                {{ form.processing ? 'Creating...' : 'Create User' }}
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                @click="$inertia.visit('/users')"
                                :disabled="form.processing"
                            >
                                Cancel
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
