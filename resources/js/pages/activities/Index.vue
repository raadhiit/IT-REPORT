<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Paperclip } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { index, store } from '@/routes/activities';
import { show as showAttachment } from '@/routes/activity-attachments';
import type { Activity, ActivityCategoryOption, ActivityCategoryValue } from '@/types';

const props = defineProps<{
    activities: Activity[];
    categories: ActivityCategoryOption[];
    lastCategory: ActivityCategoryValue | null;
    today: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Activities', href: index() }],
    },
});

function categoryLabel(value: ActivityCategoryValue): string {
    return props.categories.find((category) => category.value === value)?.label ?? value;
}

function formatSize(bytes: number): string {
    return `${Math.max(1, Math.round(bytes / 1024))} KB`;
}
</script>

<template>
    <Head title="Activities" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading title="Log activity" description="Catat aktivitas hari ini" />

        <Card>
            <CardContent>
                <Form v-bind="store.form()" reset-on-success class="space-y-4" v-slot="{ errors, processing }">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="tanggal">Tanggal</Label>
                            <Input id="tanggal" type="date" name="tanggal" :default-value="today" required />
                            <InputError :message="errors.tanggal" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="kategori">Kategori</Label>
                            <Select name="kategori" :default-value="lastCategory ?? categories[0]?.value">
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

        <Card class="overflow-hidden py-0">
            <CardContent class="p-0">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50 text-left text-xs text-muted-foreground uppercase">
                        <tr>
                            <th class="px-4 py-3 font-medium">Tanggal</th>
                            <th class="px-4 py-3 font-medium">Kategori</th>
                            <th class="px-4 py-3 font-medium">Deskripsi</th>
                            <th class="px-4 py-3 font-medium">Lampiran</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="activities.length === 0">
                            <td colspan="4" class="px-4 py-10 text-center text-muted-foreground">
                                Belum ada aktivitas.
                            </td>
                        </tr>
                        <tr
                            v-for="activity in activities"
                            :key="activity.id"
                            class="border-b last:border-0"
                        >
                            <td class="px-4 py-3 whitespace-nowrap text-muted-foreground">{{ activity.tanggal }}</td>
                            <td class="px-4 py-3">
                                <Badge variant="outline">{{ categoryLabel(activity.kategori) }}</Badge>
                            </td>
                            <td class="px-4 py-3">{{ activity.deskripsi }}</td>
                            <td class="px-4 py-3">
                                <div v-if="activity.attachments.length" class="flex flex-col gap-1">
                                    <a
                                        v-for="attachment in activity.attachments"
                                        :key="attachment.id"
                                        :href="showAttachment(attachment.id).url"
                                        class="flex items-center gap-1 text-primary hover:underline"
                                    >
                                        <Paperclip class="size-3.5" />
                                        {{ attachment.original_name }}
                                        <span class="text-xs text-muted-foreground">({{ formatSize(attachment.size) }})</span>
                                    </a>
                                </div>
                                <span v-else class="text-muted-foreground">—</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </CardContent>
        </Card>
    </div>
</template>
