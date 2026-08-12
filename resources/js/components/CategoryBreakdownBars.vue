<script setup lang="ts">
import type { ActivityCategoryValue, ReportCategoryCount } from '@/types';

const props = defineProps<{
    byCategory: ReportCategoryCount[];
    total: number;
}>();

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
</script>

<template>
    <div class="flex flex-col gap-3">
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
    </div>
</template>
