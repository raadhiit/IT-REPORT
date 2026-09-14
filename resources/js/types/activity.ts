export type ActivityCategoryValue = 'maintenance' | 'project' | 'support' | 'meeting' | 'other';

export type ActivityCategoryOption = {
    value: ActivityCategoryValue;
    label: string;
};

export type ActivityStatusValue = 'selesai' | 'on_track' | 'pending';

export type ActivityStatusOption = {
    value: ActivityStatusValue;
    label: string;
};

export type ActivityAttachment = {
    id: number;
    original_name: string;
    size: number;
};

export type Activity = {
    id: number;
    tanggal: string;
    kategori: ActivityCategoryValue;
    deskripsi: string;
    status: ActivityStatusValue;
    progress_percent: number | null;
    target_selesai: string | null;
    attachments: ActivityAttachment[];
    created_at: string;
};
