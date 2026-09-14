<script setup lang="ts">
import { computed } from 'vue';
import type { ActivityStatusValue, ReportStatusCount } from '@/types';

const props = defineProps<{
    byStatus: ReportStatusCount[];
    total: number;
}>();

const statusColor: Record<ActivityStatusValue, string> = {
    selesai: '#059669',
    on_track: '#d97706',
    pending: '#e11d48',
};

const statusDotClass: Record<ActivityStatusValue, string> = {
    selesai: 'bg-emerald-600',
    on_track: 'bg-amber-600',
    pending: 'bg-rose-600',
};

function percent(count: number): number {
    return props.total === 0 ? 0 : Math.round((count / props.total) * 100);
}

const gradient = computed(() => {
    if (props.total === 0) {
        return 'conic-gradient(var(--muted) 0deg 360deg)';
    }

    let cursor = 0;
    const stops = props.byStatus
        .filter((status) => status.count > 0)
        .map((status) => {
            const start = cursor;
            cursor += (status.count / props.total) * 360;

            return `${statusColor[status.value]} ${start}deg ${cursor}deg`;
        });

    return `conic-gradient(${stops.join(', ')})`;
});
</script>

<template>
    <div class="flex items-center gap-6">
        <div class="relative size-28 shrink-0 rounded-full" :style="{ background: gradient }">
            <div class="absolute inset-3 flex flex-col items-center justify-center rounded-full bg-card">
                <span class="text-xl font-semibold tabular-nums">{{ total }}</span>
                <span class="text-[10px] tracking-wide text-muted-foreground uppercase">Total</span>
            </div>
        </div>
        <div class="flex flex-col gap-2">
            <div v-for="status in byStatus" :key="status.value" class="flex items-center gap-2 text-sm">
                <span class="size-2 rounded-sm" :class="statusDotClass[status.value]" />
                <span class="font-medium">{{ status.label }}</span>
                <span class="text-muted-foreground">· {{ status.count }} ({{ percent(status.count) }}%)</span>
            </div>
        </div>
    </div>
</template>
