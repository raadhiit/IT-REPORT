<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { FileDown } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import monitoring, { index } from '@/routes/admin/monitoring';

defineProps<{
    schedule: {
        send_day_label: string;
        send_time: string;
        last_sent_at: string | null;
        is_due_now: boolean;
        server_time: string;
    };
    recentLog: string;
    reportLogs: {
        id: number;
        staff: string;
        period_start: string;
        period_end: string;
        status: 'sent' | 'failed';
        status_label: string;
        recipient_email: string;
        error_message: string | null;
        has_excel: boolean;
        sent_at: string;
    }[];
}>();

function formatDate(date: string): string {
    return new Date(`${date}T00:00:00`).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
}

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

        <Card class="overflow-hidden py-0">
            <CardHeader class="py-4">
                <CardTitle>Riwayat pengiriman laporan mingguan</CardTitle>
            </CardHeader>
            <CardContent class="overflow-x-auto p-0">
                <table class="w-full min-w-[48rem] text-sm">
                    <thead class="border-b bg-muted/50 text-left text-xs text-muted-foreground uppercase">
                        <tr>
                            <th class="px-4 py-3 font-medium">Dikirim</th>
                            <th class="px-4 py-3 font-medium">Periode</th>
                            <th class="px-4 py-3 font-medium">Staff</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium">Keterangan</th>
                            <th class="px-4 py-3 font-medium">Excel</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="reportLogs.length === 0">
                            <td colspan="6" class="px-4 py-10 text-center text-muted-foreground">Belum ada riwayat pengiriman.</td>
                        </tr>
                        <tr v-for="log in reportLogs" :key="log.id" class="border-b last:border-0">
                            <td class="px-4 py-3 whitespace-nowrap text-muted-foreground">{{ log.sent_at }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ formatDate(log.period_start) }} – {{ formatDate(log.period_end) }}</td>
                            <td class="px-4 py-3 font-medium">{{ log.staff }}</td>
                            <td class="px-4 py-3">
                                <Badge :variant="log.status === 'sent' ? 'default' : 'destructive'">{{ log.status_label }}</Badge>
                            </td>
                            <td class="px-4 py-3 text-xs text-muted-foreground">
                                {{ log.status === 'failed' ? log.error_message : `ke ${log.recipient_email}` }}
                            </td>
                            <td class="px-4 py-3">
                                <a
                                    v-if="log.has_excel"
                                    :href="monitoring.reportLogs.excel(log.id).url"
                                    class="flex items-center gap-1 text-primary hover:underline"
                                >
                                    <FileDown class="size-3.5" />
                                    Download
                                </a>
                                <span v-else class="text-muted-foreground">—</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
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
