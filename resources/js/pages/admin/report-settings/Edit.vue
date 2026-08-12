<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import ReportSettingController from '@/actions/App/Http/Controllers/Admin/ReportSettingController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { edit } from '@/routes/admin/report-settings';

defineProps<{
    setting: {
        gm_email: string | null;
        gm_name: string | null;
        spv_email: string | null;
        spv_name: string | null;
        send_day: number;
        send_time: string;
        office_mail_host: string | null;
        office_mail_port: number | null;
        office_mail_encryption: 'ssl' | 'tls' | null;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Report Settings', href: edit() }],
    },
});

const days = [
    { value: '0', label: 'Minggu' },
    { value: '1', label: 'Senin' },
    { value: '2', label: 'Selasa' },
    { value: '3', label: 'Rabu' },
    { value: '4', label: 'Kamis' },
    { value: '5', label: 'Jumat' },
    { value: '6', label: 'Sabtu' },
];
</script>

<template>
    <Head title="Report Settings" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading
            title="Report settings"
            description="Penerima dan jadwal pengiriman laporan mingguan otomatis"
        />

        <Card class="max-w-md">
            <CardContent>
                <Form
                    v-bind="ReportSettingController.update.form()"
                    class="space-y-6"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <Label for="gm_name">Nama GM</Label>
                        <Input
                            id="gm_name"
                            name="gm_name"
                            :default-value="setting.gm_name ?? ''"
                            required
                            placeholder="Rendra"
                        />
                        <InputError :message="errors.gm_name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="gm_email">Email GM</Label>
                        <Input
                            id="gm_email"
                            type="email"
                            name="gm_email"
                            :default-value="setting.gm_email ?? ''"
                            required
                            placeholder="gm@perusahaan.com"
                        />
                        <InputError :message="errors.gm_email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="spv_name">Nama SPV (opsional)</Label>
                        <Input
                            id="spv_name"
                            name="spv_name"
                            :default-value="setting.spv_name ?? ''"
                            placeholder="Ryan"
                        />
                        <InputError :message="errors.spv_name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="spv_email">Email SPV (CC)</Label>
                        <Input
                            id="spv_email"
                            type="email"
                            name="spv_email"
                            :default-value="setting.spv_email ?? ''"
                            placeholder="spv@perusahaan.com (opsional)"
                        />
                        <InputError :message="errors.spv_email" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="send_day">Hari kirim</Label>
                            <Select name="send_day" :default-value="String(setting.send_day)">
                                <SelectTrigger id="send_day" class="w-full">
                                    <SelectValue placeholder="Pilih hari" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="day in days" :key="day.value" :value="day.value">
                                        {{ day.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="errors.send_day" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="send_time">Jam kirim (WIB)</Label>
                            <Input
                                id="send_time"
                                type="time"
                                name="send_time"
                                :default-value="setting.send_time"
                                required
                            />
                            <InputError :message="errors.send_time" />
                        </div>
                    </div>

                    <div class="space-y-2 border-t pt-4">
                        <p class="text-sm font-medium">SMTP mailbox kantor staff</p>
                        <p class="text-sm text-muted-foreground">
                            Server mail bersama yang dipakai buat kirim laporan mingguan "atas nama" tiap staff (lihat
                            field Email Kantor di halaman Users). Kosongkan kalau belum dipakai.
                        </p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="office_mail_host">Host SMTP</Label>
                        <Input
                            id="office_mail_host"
                            name="office_mail_host"
                            :default-value="setting.office_mail_host ?? ''"
                            placeholder="mail.perusahaan.com"
                        />
                        <InputError :message="errors.office_mail_host" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="office_mail_port">Port</Label>
                            <Input
                                id="office_mail_port"
                                type="number"
                                name="office_mail_port"
                                :default-value="setting.office_mail_port ?? ''"
                                placeholder="465"
                            />
                            <InputError :message="errors.office_mail_port" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="office_mail_encryption">Enkripsi</Label>
                            <Select name="office_mail_encryption" :default-value="setting.office_mail_encryption ?? undefined">
                                <SelectTrigger id="office_mail_encryption" class="w-full">
                                    <SelectValue placeholder="Pilih" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="ssl">SSL</SelectItem>
                                    <SelectItem value="tls">TLS</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="errors.office_mail_encryption" />
                        </div>
                    </div>

                    <Button :disabled="processing" type="submit">Simpan</Button>
                </Form>
            </CardContent>
        </Card>
    </div>
</template>
