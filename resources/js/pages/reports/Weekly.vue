<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { FileDown } from '@lucide/vue';
import { computed } from 'vue';
import CategoryBreakdownBars from '@/components/CategoryBreakdownBars.vue';
import Heading from '@/components/Heading.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useInitials } from '@/composables/useInitials';
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

const { getInitials } = useInitials();

const categoryColors: Record<ActivityCategoryValue, string> = {
    maintenance: 'bg-amber-600',
    project: 'bg-blue-600',
    support: 'bg-teal-600',
    meeting: 'bg-purple-600',
    other: 'bg-gray-500',
};

function formatDate(date: string): string {
    return new Date(`${date}T00:00:00`).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
}

function staffPercent(count: number): number {
    const max = Math.max(1, ...props.byStaff.map((staff) => staff.total));

    return Math.round((count / max) * 100);
}

const filledCategories = computed(() => props.detailsByCategory.filter((category) => category.activities.length > 0));

/**
 * On the ≥lg 3-column grid, cards default to spanning 2 of 6 columns. The last
 * row, if it isn't full, gets its cards widened to fill the row instead of
 * leaving empty space: a lone card spans the full row, a pair spans half each.
 */
function categorySpanClass(index: number): string {
    const total = filledCategories.value.length;
    const remainder = total % 3;
    const remainderStart = total - remainder;

    if (index < remainderStart) {
        return 'lg:col-span-2';
    }

    return remainder === 1 ? 'lg:col-span-6' : 'lg:col-span-3';
}
</script>

<template>
    <Head title="Weekly Report" />

    <div class="flex flex-col space-y-8 p-4">
        <Heading title="Laporan mingguan" description="Ringkasan aktivitas tim minggu berjalan" />

        <Card class="overflow-hidden py-0">
            <CardContent class="flex flex-col gap-6 py-6 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">Periode laporan</p>
                    <p class="mt-1 text-xl font-semibold">{{ formatDate(start) }} – {{ formatDate(end) }}</p>
                </div>
                <div class="flex items-center gap-8">
                    <div>
                        <p class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">Total aktivitas</p>
                        <p class="mt-1 text-4xl font-semibold">{{ total }}</p>
                    </div>
                </div>
            </CardContent>
            <div class="flex gap-2 border-t bg-muted/30 px-6 py-3">
                <Button as-child variant="outline" size="sm">
                    <a :href="pdf().url">
                        <FileDown />
                        Download PDF
                    </a>
                </Button>
                <Button as-child variant="outline" size="sm">
                    <a :href="excel().url">
                        <FileDown />
                        Download Excel
                    </a>
                </Button>
            </div>
        </Card>

        <section>
            <h2 class="mb-3 text-sm font-semibold tracking-wide text-muted-foreground uppercase">Ringkasan per kategori</h2>
            <Card>
                <CardContent>
                    <CategoryBreakdownBars :by-category="byCategory" :total="total" />
                </CardContent>
            </Card>
        </section>

        <section v-if="byStaff.length > 0">
            <h2 class="mb-3 text-sm font-semibold tracking-wide text-muted-foreground uppercase">Breakdown per staff</h2>
            <Card class="overflow-hidden py-0">
                <CardContent class="divide-y p-0">
                    <div v-for="(staff, index) in byStaff" :key="staff.id" class="flex items-center gap-4 px-4 py-3">
                        <span class="w-4 text-sm font-medium text-muted-foreground">{{ index + 1 }}</span>
                        <Avatar class="size-8 shrink-0">
                            <AvatarFallback class="text-xs">{{ getInitials(staff.name) }}</AvatarFallback>
                        </Avatar>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ staff.name }}</p>
                            <span class="mt-1.5 block h-1.5 max-w-48 overflow-hidden rounded-full bg-muted">
                                <span
                                    class="block h-full rounded-full bg-primary transition-[width]"
                                    :style="{ width: `${staffPercent(staff.total)}%` }"
                                />
                            </span>
                        </div>
                        <span class="text-sm font-semibold">{{ staff.total }}</span>
                    </div>
                </CardContent>
            </Card>
        </section>

        <section>
            <h2 class="mb-3 text-sm font-semibold tracking-wide text-muted-foreground uppercase">Detail aktivitas</h2>
            <div v-if="filledCategories.length > 0" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6">
                <Card
                    v-for="(category, index) in filledCategories"
                    :key="category.value"
                    class="flex flex-col overflow-hidden py-0"
                    :class="categorySpanClass(index)"
                >
                    <div class="flex items-center gap-2 border-b bg-muted/30 px-4 py-3">
                        <span class="size-2 rounded-sm" :class="categoryColors[category.value]" />
                        <h3 class="text-sm font-semibold">{{ category.label }}</h3>
                        <Badge variant="secondary" class="ml-auto">{{ category.activities.length }}</Badge>
                    </div>
                    <ul class="max-h-72 divide-y overflow-y-auto">
                        <li v-for="activity in category.activities" :key="activity.id" class="px-4 py-3 text-sm">
                            <p class="text-xs text-muted-foreground">{{ activity.staff }} · {{ formatDate(activity.tanggal) }}</p>
                            <p class="mt-1">{{ activity.deskripsi }}</p>
                        </li>
                    </ul>
                </Card>
            </div>
            <Card v-else>
                <CardContent class="text-sm text-muted-foreground">Belum ada aktivitas yang tercatat minggu ini.</CardContent>
            </Card>
        </section>
    </div>
</template>
