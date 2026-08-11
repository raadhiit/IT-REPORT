export type ActivityCategoryValue = 'maintenance' | 'project' | 'support' | 'meeting' | 'other';

export type ActivityCategoryOption = {
    value: ActivityCategoryValue;
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
    attachments: ActivityAttachment[];
    created_at: string;
};
