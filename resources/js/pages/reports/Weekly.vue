<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { FileDown } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { weekly } from '@/routes/reports';
import { excel, pdf } from '@/routes/reports/weekly';
import type { ActivityCategoryValue, ReportCategoryCount, ReportCategoryDetail, ReportStaffBreakdown } from '@/types';

const props = defineProps<{
    start: string;
    end: string;
    total: number;
    byCategory: ReportCategoryCount[];
    byStaff: ReportStaffBreakdown[];
    detailsByCategory: ReportCategoryDetail[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Weekly Report', href: weekly() }],
    },
});

const categoryColors: Record<ActivityCategoryValue, string> = {
    maintenance: 'bg-amber-600',
    project: 'bg-blue-600',
    support: 'bg-teal-600',
    meeting: 'bg-purple-600',
    other: 'bg-gray-500',
};

function percent(count: number): number {
    return props.total === 0 ? 0 : Math.round((count / props.total) * 100);
}

function formatDate(date: string): string {
    return new Date(`${date}T00:00:00`).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
}
</script>

<template>
    <Head title="Weekly Report" />

    <div class="flex flex-col space-y-6 p-4">
        <div class="flex items-center justify-between">
            <Heading
                title="Laporan mingguan"
                :description="`${formatDate(start)} – ${formatDate(end)} · ${total} aktivitas`"
            />
            <div class="flex gap-2">
                <Button as-child variant="outline">
                    <a :href="pdf().url">
                        <FileDown />
                        Download PDF
                    </a>
                </Button>
                <Button as-child variant="outline">
                    <a :href="excel().url">
                        <FileDown />
                        Download Excel
                    </a>
                </Button>
            </div>
        </div>

        <Card>
            <CardContent class="flex flex-col gap-3">
                <div v-for="category in byCategory" :key="category.value" class="grid grid-cols-[128px_1fr_28px] items-center gap-3">
                    <span class="flex items-center gap-2 text-sm font-medium">
                        <span class="size-2 rounded-sm" :class="categoryColors[category.value]" />
                        {{ category.label }}
                    </span>
                    <span class="h-2 overflow-hidden rounded-full bg-muted">
                        <span
                            class="block h-full rounded-full"
                            :class="categoryColors[category.value]"
                            :style="{ width: `${percent(category.count)}%` }"
                        />
                    </span>
                    <span class="text-right text-sm text-muted-foreground">{{ category.count }}</span>
                </div>
            </CardContent>
        </Card>

        <Card v-if="byStaff.length > 0" class="overflow-hidden py-0">
            <CardContent class="p-0">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50 text-left text-xs text-muted-foreground uppercase">
                        <tr>
                            <th class="px-4 py-3 font-medium">Staff</th>
                            <th class="px-4 py-3 text-right font-medium">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="staff in byStaff" :key="staff.id" class="border-b last:border-0">
                            <td class="px-4 py-3 font-medium">{{ staff.name }}</td>
                            <td class="px-4 py-3 text-right text-muted-foreground">{{ staff.total }}</td>
                        </tr>
                    </tbody>
                </table>
            </CardContent>
        </Card>

        <Card v-for="category in detailsByCategory.filter((c) => c.activities.length > 0)" :key="category.value">
            <CardContent>
                <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold">
                    <span class="size-2 rounded-sm" :class="categoryColors[category.value]" />
                    {{ category.label }}
                    <span class="font-normal text-muted-foreground">({{ category.activities.length }})</span>
                </h3>
                <ul class="flex flex-col gap-2">
                    <li v-for="activity in category.activities" :key="activity.id" class="flex gap-2 text-sm">
                        <span class="w-28 shrink-0 text-xs text-muted-foreground">{{ activity.staff }} · {{ formatDate(activity.tanggal) }}</span>
                        <span>{{ activity.deskripsi }}</span>
                    </li>
                </ul>
            </CardContent>
        </Card>
    </div>
</template>
