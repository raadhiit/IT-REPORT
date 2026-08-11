import type { ActivityCategoryValue } from './activity';

export type ReportCategoryCount = {
    value: ActivityCategoryValue;
    label: string;
    count: number;
};

export type ReportStaffBreakdown = {
    id: number;
    name: string;
    total: number;
    byCategory: { value: ActivityCategoryValue; count: number }[];
};

export type ReportActivityDetail = {
    id: number;
    tanggal: string;
    deskripsi: string;
    staff: string;
};

export type ReportCategoryDetail = {
    value: ActivityCategoryValue;
    label: string;
    activities: ReportActivityDetail[];
};
