<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { create, index } from '@/routes/admin/users';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Users', href: index() },
            { title: 'Add user', href: create() },
        ],
    },
});
</script>

<template>
    <Head title="Add user" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading title="Add user" description="Create a new IT staff account" />

        <Form
            v-bind="UserController.store.form()"
            class="max-w-md space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input id="name" name="name" required autocomplete="name" />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input id="email" type="email" name="email" required autocomplete="username" />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Password</Label>
                <Input id="password" type="password" name="password" required autocomplete="new-password" />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Confirm password</Label>
                <Input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                />
            </div>

            <div class="grid gap-2">
                <Label for="role">Role</Label>
                <Select name="role" default-value="staff">
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

            <div class="space-y-2 border-t pt-4">
                <p class="text-sm font-medium">Email kantor (opsional)</p>
                <p class="text-sm text-muted-foreground">
                    Mailbox webmail asli staff ini — dipakai buat kirim laporan mingguan "atas nama" staff ke GM.
                    Bukan email login sistem di atas.
                </p>
            </div>

            <div class="grid gap-2">
                <Label for="office_email">Email kantor</Label>
                <Input id="office_email" type="email" name="office_email" autocomplete="off" />
                <InputError :message="errors.office_email" />
            </div>

            <div class="grid gap-2">
                <Label for="office_email_password">Password email kantor</Label>
                <Input id="office_email_password" type="password" name="office_email_password" autocomplete="off" />
                <InputError :message="errors.office_email_password" />
            </div>

            <Button :disabled="processing" type="submit">Create user</Button>
        </Form>
    </div>
</template>
