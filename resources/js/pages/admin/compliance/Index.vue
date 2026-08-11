<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Check, X } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Card, CardContent } from '@/components/ui/card';
import { index } from '@/routes/admin/compliance';
import type { ComplianceStaff } from '@/types';

defineProps<{
    dates: string[];
    staff: ComplianceStaff[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Compliance', href: index() }],
    },
});

function formatDate(date: string): string {
    return new Date(`${date}T00:00:00`).toLocaleDateString('id-ID', {
        weekday: 'short',
        day: 'numeric',
        month: 'numeric',
    });
}
</script>

<template>
    <Head title="Compliance" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading title="Belum diisi" description="Staff yang belum log aktivitas minggu ini" />

        <Card class="overflow-hidden py-0">
            <CardContent class="p-0">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50 text-left text-xs text-muted-foreground uppercase">
                        <tr>
                            <th class="px-4 py-3 font-medium">Staff</th>
                            <th v-for="date in dates" :key="date" class="px-4 py-3 text-center font-medium">
                                {{ formatDate(date) }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="staff.length === 0">
                            <td :colspan="dates.length + 1" class="px-4 py-10 text-center text-muted-foreground">
                                Tidak ada staff aktif.
                            </td>
                        </tr>
                        <tr v-for="person in staff" :key="person.id" class="border-b last:border-0">
                            <td class="px-4 py-3 font-medium">{{ person.name }}</td>
                            <td v-for="date in dates" :key="date" class="px-4 py-3 text-center">
                                <Check v-if="person.filled[date]" class="mx-auto size-4 text-emerald-600" />
                                <X v-else class="mx-auto size-4 text-destructive" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </CardContent>
        </Card>
    </div>
</template>
