<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ClipboardCheck, ClipboardList, Minus, Tags, TrendingDown, TrendingUp } from '@lucide/vue';
import { computed, ref } from 'vue';
import CategoryBreakdownBars from '@/components/CategoryBreakdownBars.vue';
import Heading from '@/components/Heading.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';
import { index as activitiesIndex } from '@/routes/activities';
import { weekly } from '@/routes/reports';
import type { ReportCategoryCount } from '@/types';

const props = defineProps<{
    total: number;
    lastWeekTotal: number;
    todayCount: number;
    today: string;
    topCategory: ReportCategoryCount | null;
    byCategory: ReportCategoryCount[];
    dailyCounts: { date: string; count: number }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

const delta = computed(() => props.total - props.lastWeekTotal);

const hoveredDay = ref<number | null>(null);
const maxDailyCount = computed(() => Math.max(1, ...props.dailyCounts.map((day) => day.count)));

function dayLabel(date: string): string {
    return new Date(`${date}T00:00:00`).toLocaleDateString('id-ID', { weekday: 'short' });
}

function fullDayLabel(date: string): string {
    return new Date(`${date}T00:00:00`).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'short' });
}
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading title="Dashboard" description="Ringkasan aktivitas minggu ini" />

        <div class="grid gap-4 sm:grid-cols-3">
            <Link :href="weekly()" class="block">
                <Card class="h-full transition-shadow hover:shadow-md">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Aktivitas minggu ini</CardTitle>
                        <span class="rounded-md bg-primary/10 p-1.5 text-primary">
                            <ClipboardList class="size-4" />
                        </span>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-semibold">{{ total }}</p>
                        <p
                            class="mt-1 flex items-center gap-1 text-xs"
                            :class="delta > 0 ? 'text-emerald-600 dark:text-emerald-500' : delta < 0 ? 'text-amber-600 dark:text-amber-500' : 'text-muted-foreground'"
                        >
                            <TrendingUp v-if="delta > 0" class="size-3.5" />
                            <TrendingDown v-else-if="delta < 0" class="size-3.5" />
                            <Minus v-else class="size-3.5" />
                            {{ delta === 0 ? 'Sama seperti' : `${delta > 0 ? '+' : ''}${delta} dari` }} minggu lalu ({{ lastWeekTotal }})
                        </p>
                    </CardContent>
                </Card>
            </Link>

            <Link :href="activitiesIndex()" class="block">
                <Card class="h-full transition-shadow hover:shadow-md">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Tercatat hari ini</CardTitle>
                        <span class="rounded-md bg-primary/10 p-1.5 text-primary">
                            <ClipboardCheck class="size-4" />
                        </span>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-semibold">{{ todayCount }}</p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ todayCount > 0 ? 'Sudah log hari ini' : 'Belum ada yang di-log' }}
                        </p>
                    </CardContent>
                </Card>
            </Link>

            <Card class="h-full">
                <CardHeader class="flex flex-row items-center justify-between space-y-0">
                    <CardTitle class="text-sm font-medium text-muted-foreground">Kategori terbanyak</CardTitle>
                    <span class="rounded-md bg-primary/10 p-1.5 text-primary">
                        <Tags class="size-4" />
                    </span>
                </CardHeader>
                <CardContent>
                    <p class="text-3xl font-semibold">{{ topCategory?.label ?? '—' }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{ topCategory ? `${topCategory.count} aktivitas` : 'Belum ada aktivitas' }}
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

        <Card>
            <CardHeader>
                <CardTitle>Breakdown per kategori</CardTitle>
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
</template>
