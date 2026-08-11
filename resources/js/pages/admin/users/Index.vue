<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { UserPlus } from '@lucide/vue';
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import Heading from '@/components/Heading.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useInitials } from '@/composables/useInitials';
import { create, edit, index } from '@/routes/admin/users';
import type { User } from '@/types';

defineProps<{
    users: Pick<User, 'id' | 'name' | 'email' | 'role' | 'is_active'>[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Users', href: index() }],
    },
});

const { getInitials } = useInitials();
</script>

<template>
    <Head title="Users" />

    <div class="flex flex-col space-y-6 p-4">
        <div class="flex items-center justify-between">
            <Heading title="Users" description="Manage IT staff accounts" />
            <Button as-child>
                <Link :href="create()">
                    <UserPlus />
                    Add user
                </Link>
            </Button>
        </div>

        <Card class="overflow-hidden py-0">
            <CardContent class="p-0">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50 text-left text-xs text-muted-foreground uppercase">
                        <tr>
                            <th class="px-4 py-3 font-medium">User</th>
                            <th class="px-4 py-3 font-medium">Role</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="users.length === 0">
                            <td colspan="4" class="px-4 py-10 text-center text-muted-foreground">
                                No users yet.
                            </td>
                        </tr>
                        <tr
                            v-for="user in users"
                            :key="user.id"
                            class="border-b transition-colors last:border-0 hover:bg-muted/40"
                        >
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <Avatar class="h-8 w-8 rounded-lg">
                                        <AvatarFallback class="rounded-lg text-xs">
                                            {{ getInitials(user.name) }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div class="grid leading-tight">
                                        <span class="font-medium">{{ user.name }}</span>
                                        <span class="text-xs text-muted-foreground">{{ user.email }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <Badge variant="outline" class="capitalize">{{ user.role }}</Badge>
                            </td>
                            <td class="px-4 py-3">
                                <Badge :variant="user.is_active ? 'default' : 'secondary'">
                                    {{ user.is_active ? 'Active' : 'Inactive' }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <Button size="sm" variant="outline" as-child>
                                        <Link :href="edit(user.id)">Edit</Link>
                                    </Button>
                                    <Form v-if="user.is_active" v-bind="UserController.destroy.form(user.id)">
                                        <Button size="sm" variant="destructive" type="submit">
                                            Deactivate
                                        </Button>
                                    </Form>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </CardContent>
        </Card>
    </div>
</template>
