<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { index } from '@/routes/admin/users';
import type { User } from '@/types';

defineProps<{
    user: Pick<User, 'id' | 'name' | 'email' | 'role' | 'is_active'>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Users', href: index() },
            { title: 'Edit user', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Edit user" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading title="Edit user" :description="`Update ${user.name}'s account`" />

        <Form
            v-bind="UserController.update.form(user.id)"
            class="max-w-md space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input id="name" name="name" :default-value="user.name" required autocomplete="name" />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    :default-value="user.email"
                    required
                    autocomplete="username"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password">New password</Label>
                <Input id="password" type="password" name="password" autocomplete="new-password" />
                <p class="text-sm text-muted-foreground">Leave blank to keep the current password.</p>
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Confirm new password</Label>
                <Input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    autocomplete="new-password"
                />
            </div>

            <div class="grid gap-2">
                <Label for="role">Role</Label>
                <Select name="role" :default-value="user.role">
                    <SelectTrigger id="role" class="w-full">
                        <SelectValue placeholder="Role" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="staff">Staff</SelectItem>
                        <SelectItem value="admin">Admin</SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="errors.role" />
            </div>

            <Label for="is_active" class="flex items-center gap-3">
                <Checkbox id="is_active" name="is_active" :default-checked="user.is_active" />
                <span>Active (can log in)</span>
            </Label>

            <Button :disabled="processing" type="submit">Save</Button>
        </Form>
    </div>
</template>
