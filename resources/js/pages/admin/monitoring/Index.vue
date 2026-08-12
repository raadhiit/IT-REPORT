<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { index } from '@/routes/admin/monitoring';

defineProps<{
    schedule: {
        send_day_label: string;
        send_time: string;
        last_sent_at: string | null;
        is_due_now: boolean;
        server_time: string;
    };
    recentLog: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Monitoring', href: index() }],
    },
});
</script>

<template>
    <Head title="Monitoring" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading title="Monitoring" description="Status cron laporan mingguan dan log aplikasi terbaru" />

        <Card>
            <CardHeader>
                <CardTitle>Status cron laporan mingguan</CardTitle>
            </CardHeader>
            <CardContent class="grid gap-3 text-sm sm:grid-cols-2">
                <div>
                    <p class="text-muted-foreground">Jadwal</p>
                    <p class="font-medium">{{ schedule.send_day_label }}, {{ schedule.send_time }} WIB</p>
                </div>
                <div>
                    <p class="text-muted-foreground">Terakhir dikirim</p>
                    <p class="font-medium">{{ schedule.last_sent_at ?? 'Belum pernah' }}</p>
                </div>
                <div>
                    <p class="text-muted-foreground">Jam server sekarang (WIB)</p>
                    <p class="font-medium">{{ schedule.server_time }}</p>
                </div>
                <div>
                    <p class="text-muted-foreground">Status saat ini</p>
                    <Badge :variant="schedule.is_due_now ? 'default' : 'secondary'">
                        {{ schedule.is_due_now ? 'Due — akan kirim di cron berikutnya' : 'Belum due' }}
                    </Badge>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Log aplikasi terbaru</CardTitle>
            </CardHeader>
            <CardContent>
                <pre
                    class="max-h-[32rem] overflow-auto rounded-md bg-muted p-4 text-xs whitespace-pre-wrap"
                >{{ recentLog || 'Belum ada log.' }}</pre>
            </CardContent>
        </Card>
    </div>
</template>
