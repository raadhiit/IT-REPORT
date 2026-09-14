<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Minus, TrendingDown, TrendingUp } from '@lucide/vue';
import { computed, ref } from 'vue';
import CategoryBreakdownBars from '@/components/CategoryBreakdownBars.vue';
import Heading from '@/components/Heading.vue';
import StatusBreakdownDonut from '@/components/StatusBreakdownDonut.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';
import { weekly } from '@/routes/reports';
import type { ReportCategoryCount, ReportStaffBreakdown, ReportStatusCount } from '@/types';

const props = defineProps<{
    total: number;
    lastWeekTotal: number;
    todayCount: number;
    today: string;
    topCategory: ReportCategoryCount | null;
    byCategory: ReportCategoryCount[];
    byStatus: ReportStatusCount[];
    byStaff: ReportStaffBreakdown[];
    dailyCounts: { date: string; count: number }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

const delta = computed(() => props.total - props.lastWeekTotal);

function statusCount(value: 'selesai' | 'on_track' | 'pending'): number {
    return props.byStatus.find((status) => status.value === value)?.count ?? 0;
}

const hoveredDay = ref<number | null>(null);
const maxDailyCount = computed(() => Math.max(1, ...props.dailyCounts.map((day) => day.count)));
const maxStaffTotal = computed(() => Math.max(1, ...props.byStaff.map((staff) => staff.total)));

function dayLabel(date: string): string {
    return new Date(`${date}T00:00:00`).toLocaleDateString('id-ID', { weekday: 'short' });
}

function fullDayLabel(date: string): string {
    return new Date(`${date}T00:00:00`).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'short' });
}
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-col space-y-4 p-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <Heading title="Dashboard" description="Ringkasan aktivitas minggu ini" />
            <Badge :variant="todayCount > 0 ? 'default' : 'outline'">
                {{ todayCount > 0 ? `${todayCount} aktivitas tercatat hari ini` : 'Belum log hari ini' }}
            </Badge>
        </div>

        <div class="grid gap-4 sm:grid-cols-4">
            <Card class="border-l-4 border-l-foreground">
                <CardContent>
                    <p class="text-2xl font-semibold tabular-nums">{{ total }}</p>
                    <p class="text-xs text-muted-foreground uppercase">Total Aktivitas</p>
                    <p
                        class="mt-1 flex items-center gap-1 text-xs"
                        :class="delta > 0 ? 'text-emerald-600 dark:text-emerald-500' : delta < 0 ? 'text-amber-600 dark:text-amber-500' : 'text-muted-foreground'"
                    >
                        <TrendingUp v-if="delta > 0" class="size-3.5" />
                        <TrendingDown v-else-if="delta < 0" class="size-3.5" />
                        <Minus v-else class="size-3.5" />
                        {{ delta === 0 ? 'sama seperti' : `${delta > 0 ? '+' : ''}${delta}` }} minggu lalu
                    </p>
                </CardContent>
            </Card>
            <Card class="border-l-4 border-l-emerald-600">
                <CardContent>
                    <p class="text-2xl font-semibold tabular-nums">{{ statusCount('selesai') }}</p>
                    <p class="text-xs text-muted-foreground uppercase">Selesai</p>
                </CardContent>
            </Card>
            <Card class="border-l-4 border-l-amber-600">
                <CardContent>
                    <p class="text-2xl font-semibold tabular-nums">{{ statusCount('on_track') }}</p>
                    <p class="text-xs text-muted-foreground uppercase">On Track</p>
                </CardContent>
            </Card>
            <Card class="border-l-4 border-l-rose-600">
                <CardContent>
                    <p class="text-2xl font-semibold tabular-nums">{{ statusCount('pending') }}</p>
                    <p class="text-xs text-muted-foreground uppercase">Pending</p>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Status Pengerjaan</CardTitle>
                </CardHeader>
                <CardContent>
                    <StatusBreakdownDonut :by-status="byStatus" :total="total" />
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0">
                    <CardTitle>Distribusi Kategori</CardTitle>
                    <span v-if="topCategory" class="text-xs text-muted-foreground">
                        Terbanyak: <span class="font-medium text-foreground">{{ topCategory.label }}</span>
                    </span>
                </CardHeader>
                <CardContent>
                    <CategoryBreakdownBars v-if="total > 0" :by-category="byCategory" :total="total" />
                    <p v-else class="text-sm text-muted-foreground">
                        Belum ada aktivitas minggu ini.
                        <Link :href="weekly()" class="text-primary hover:underline">Lihat laporan mingguan</Link>
                    </p>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Aktivitas per hari</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="flex h-32 items-end gap-2">
                    <div
                        v-for="(day, i) in dailyCounts"
                        :key="day.date"
                        class="relative flex flex-1 flex-col items-center gap-2 focus:outline-none"
                        tabindex="0"
                        @mouseenter="hoveredDay = i"
                        @mouseleave="hoveredDay = null"
                        @focus="hoveredDay = i"
                        @blur="hoveredDay = null"
                    >
                        <div
                            v-if="hoveredDay === i"
                            class="absolute -top-8 z-10 rounded-md bg-foreground px-2 py-1 text-xs whitespace-nowrap text-background"
                        >
                            <span class="font-semibold">{{ day.count }}</span> aktivitas · {{ fullDayLabel(day.date) }}
                        </div>
                        <div class="flex h-24 w-full items-end justify-center">
                            <div
                                class="w-full max-w-6 rounded-t-[4px] transition-colors"
                                :class="[day.date === today ? 'bg-primary' : 'bg-primary/30', hoveredDay === i && 'brightness-110']"
                                :style="{ height: `${Math.max(4, (day.count / maxDailyCount) * 100)}%` }"
                            />
                        </div>
                        <span class="text-xs" :class="day.date === today ? 'font-semibold text-foreground' : 'text-muted-foreground'">
                            {{ dayLabel(day.date) }}
                        </span>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card v-if="byStaff.length > 0">
            <CardHeader>
                <CardTitle>Aktivitas per Staff</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="flex flex-col gap-3">
                    <div v-for="staff in byStaff" :key="staff.id" class="grid grid-cols-[128px_1fr_28px] items-center gap-3">
                        <span class="truncate text-sm font-medium">{{ staff.name }}</span>
                        <span class="h-2 overflow-hidden rounded-full bg-muted">
                            <span
                                class="block h-full rounded-full bg-foreground"
                                :style="{ width: `${Math.round((staff.total / maxStaffTotal) * 100)}%` }"
                            />
                        </span>
                        <span class="text-right text-sm text-muted-foreground">{{ staff.total }}</span>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
