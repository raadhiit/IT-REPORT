<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { Paperclip, Pencil } from '@lucide/vue';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { index, store, update } from '@/routes/activities';
import { show as showAttachment } from '@/routes/activity-attachments';
import type { Activity, ActivityCategoryOption, ActivityCategoryValue, ActivityStatusOption, ActivityStatusValue } from '@/types';

const props = defineProps<{
    activities: Activity[];
    categories: ActivityCategoryOption[];
    statuses: ActivityStatusOption[];
    lastCategory: ActivityCategoryValue | null;
    today: string;
    from: string;
    to: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Activities', href: index() }],
    },
});

const statusBadgeClass: Record<ActivityStatusValue, string> = {
    selesai: 'border-transparent bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300',
    on_track: 'border-transparent bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300',
    pending: 'border-transparent bg-rose-100 text-rose-800 dark:bg-rose-500/20 dark:text-rose-300',
};

const filterFrom = ref(props.from);
const filterTo = ref(props.to);
const editingActivity = ref<Activity | null>(null);
const createKategori = ref<ActivityCategoryValue>(props.lastCategory ?? props.categories[0]?.value ?? 'maintenance');
const editKategori = ref<ActivityCategoryValue | null>(null);

function openEdit(activity: Activity): void {
    editingActivity.value = activity;
    editKategori.value = activity.kategori;
}

function closeEdit(): void {
    editingActivity.value = null;
    editKategori.value = null;
}

function applyFilter(): void {
    router.get(
        index().url,
        { from: filterFrom.value, to: filterTo.value },
        { preserveState: true, preserveScroll: true, only: ['activities', 'from', 'to'] },
    );
}

function categoryLabel(value: ActivityCategoryValue): string {
    return props.categories.find((category) => category.value === value)?.label ?? value;
}

function statusLabel(value: ActivityStatusValue): string {
    return props.statuses.find((status) => status.value === value)?.label ?? value;
}

function formatSize(bytes: number): string {
    return `${Math.max(1, Math.round(bytes / 1024))} KB`;
}

function formatDate(date: string): string {
    return new Date(`${date}T00:00:00`).toLocaleDateString('id-ID', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}
</script>

<template>
    <Head title="Activities" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading title="Log activity" description="Catat aktivitas hari ini" />

        <Card>
            <CardContent>
                <Form v-bind="store.form()" reset-on-success class="space-y-4" v-slot="{ errors, processing }">
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="grid gap-2">
                            <Label for="tanggal">Tanggal</Label>
                            <Input id="tanggal" type="date" name="tanggal" :default-value="today" required />
                            <InputError :message="errors.tanggal" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="kategori">Kategori</Label>
                            <Select v-model="createKategori" name="kategori">
                                <SelectTrigger id="kategori" class="w-full">
                                    <SelectValue placeholder="Pilih kategori" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="category in categories" :key="category.value" :value="category.value">
                                        {{ category.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="errors.kategori" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="status">Status</Label>
                            <Select name="status" default-value="selesai">
                                <SelectTrigger id="status" class="w-full">
                                    <SelectValue placeholder="Pilih status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="status in statuses" :key="status.value" :value="status.value">
                                        {{ status.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="errors.status" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="deskripsi">Deskripsi</Label>
                        <Input
                            id="deskripsi"
                            name="deskripsi"
                            placeholder="Apa yang dikerjakan?"
                            required
                            autofocus
                        />
                        <InputError :message="errors.deskripsi" />
                    </div>

                    <div v-if="createKategori === 'project'" class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="progress_percent">Progress (%)</Label>
                            <Input id="progress_percent" type="number" name="progress_percent" min="0" max="100" placeholder="0" />
                            <InputError :message="errors.progress_percent" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="target_selesai">Target selesai</Label>
                            <Input id="target_selesai" type="date" name="target_selesai" />
                            <InputError :message="errors.target_selesai" />
                        </div>
                    </div>

                    <Collapsible>
                        <CollapsibleTrigger as-child>
                            <Button type="button" variant="ghost" size="sm" class="-ml-2 text-muted-foreground">
                                <Paperclip />
                                Add attachment
                            </Button>
                        </CollapsibleTrigger>
                        <CollapsibleContent class="grid gap-2 pt-2">
                            <input
                                type="file"
                                name="attachments[]"
                                multiple
                                accept=".pdf,.png,.jpg,.jpeg,.docx"
                                class="text-sm text-muted-foreground file:mr-3 file:rounded-md file:border-0 file:bg-secondary file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-secondary-foreground"
                            />
                            <p class="text-xs text-muted-foreground">Maks 2MB per file. Format: pdf, png, jpg, docx.</p>
                            <InputError :message="errors['attachments.0']" />
                        </CollapsibleContent>
                    </Collapsible>

                    <Button :disabled="processing" type="submit">Simpan</Button>
                </Form>
            </CardContent>
        </Card>

        <div class="flex flex-wrap items-end gap-4">
            <div class="grid gap-2">
                <Label for="filter_from">Dari tanggal</Label>
                <Input id="filter_from" v-model="filterFrom" type="date" class="w-40" @change="applyFilter" />
            </div>
            <div class="grid gap-2">
                <Label for="filter_to">Sampai tanggal</Label>
                <Input id="filter_to" v-model="filterTo" type="date" class="w-40" @change="applyFilter" />
            </div>
        </div>

        <Card class="overflow-hidden py-0">
            <CardContent class="overflow-x-auto p-0">
                <table class="w-full min-w-[40rem] text-sm">
                    <thead class="border-b bg-muted/50 text-left text-xs text-muted-foreground uppercase">
                        <tr>
                            <th class="px-4 py-3 font-medium">Tanggal</th>
                            <th class="px-4 py-3 font-medium">Kategori</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium">Deskripsi</th>
                            <th class="px-4 py-3 font-medium">Lampiran</th>
                            <th class="px-4 py-3 font-medium"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="activities.length === 0">
                            <td colspan="6" class="px-4 py-10 text-center text-muted-foreground">
                                Belum ada aktivitas.
                            </td>
                        </tr>
                        <tr
                            v-for="activity in activities"
                            :key="activity.id"
                            class="border-b last:border-0"
                        >
                            <td class="px-4 py-3 whitespace-nowrap text-muted-foreground">{{ formatDate(activity.tanggal) }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <Badge variant="outline">{{ categoryLabel(activity.kategori) }}</Badge>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <Badge :class="statusBadgeClass[activity.status]">{{ statusLabel(activity.status) }}</Badge>
                                <span v-if="activity.kategori === 'project' && activity.progress_percent !== null" class="ml-1 text-xs text-muted-foreground">
                                    {{ activity.progress_percent }}%
                                </span>
                            </td>
                            <td class="px-4 py-3">{{ activity.deskripsi }}</td>
                            <td class="px-4 py-3">
                                <div v-if="activity.attachments.length" class="flex flex-col gap-1">
                                    <a
                                        v-for="attachment in activity.attachments"
                                        :key="attachment.id"
                                        :href="showAttachment(attachment.id).url"
                                        class="flex items-center gap-1 whitespace-nowrap text-primary hover:underline"
                                    >
                                        <Paperclip class="size-3.5 shrink-0" />
                                        {{ attachment.original_name }}
                                        <span class="text-xs text-muted-foreground">({{ formatSize(attachment.size) }})</span>
                                    </a>
                                </div>
                                <span v-else class="text-muted-foreground">—</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Button variant="ghost" size="icon" class="size-8" @click="openEdit(activity)">
                                    <Pencil class="size-4" />
                                    <span class="sr-only">Edit</span>
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </CardContent>
        </Card>
    </div>

    <Dialog :open="editingActivity !== null" @update:open="(open) => !open && closeEdit()">
        <DialogContent v-if="editingActivity">
            <DialogHeader>
                <DialogTitle>Edit aktivitas</DialogTitle>
            </DialogHeader>

            <Form
                :key="editingActivity.id"
                v-bind="update.form(editingActivity.id)"
                @success="closeEdit()"
                class="space-y-4"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="edit_tanggal">Tanggal</Label>
                        <Input id="edit_tanggal" type="date" name="tanggal" :default-value="editingActivity.tanggal" required />
                        <InputError :message="errors.tanggal" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit_kategori">Kategori</Label>
                        <Select v-model="editKategori" name="kategori">
                            <SelectTrigger id="edit_kategori" class="w-full">
                                <SelectValue placeholder="Pilih kategori" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="category in categories" :key="category.value" :value="category.value">
                                    {{ category.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.kategori" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="edit_status">Status</Label>
                    <Select name="status" :default-value="editingActivity.status">
                        <SelectTrigger id="edit_status" class="w-full">
                            <SelectValue placeholder="Pilih status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="status in statuses" :key="status.value" :value="status.value">
                                {{ status.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="errors.status" />
                </div>

                <div class="grid gap-2">
                    <Label for="edit_deskripsi">Deskripsi</Label>
                    <Input id="edit_deskripsi" name="deskripsi" :default-value="editingActivity.deskripsi" required />
                    <InputError :message="errors.deskripsi" />
                </div>

                <div v-if="editKategori === 'project'" class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="edit_progress_percent">Progress (%)</Label>
                        <Input
                            id="edit_progress_percent"
                            type="number"
                            name="progress_percent"
                            min="0"
                            max="100"
                            :default-value="editingActivity.progress_percent ?? undefined"
                        />
                        <InputError :message="errors.progress_percent" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="edit_target_selesai">Target selesai</Label>
                        <Input
                            id="edit_target_selesai"
                            type="date"
                            name="target_selesai"
                            :default-value="editingActivity.target_selesai ?? undefined"
                        />
                        <InputError :message="errors.target_selesai" />
                    </div>
                </div>

                <DialogFooter>
                    <Button :disabled="processing" type="submit">Simpan perubahan</Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
