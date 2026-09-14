import type { ActivityCategoryValue, ActivityStatusValue } from './activity';

export type ReportCategoryCount = {
    value: ActivityCategoryValue;
    label: string;
    count: number;
};

export type ReportStatusCount = {
    value: ActivityStatusValue;
    label: string;
    count: number;
};

export type ReportStaffBreakdown = {
    id: number;
    name: string;
    total: number;
    byCategory: { value: ActivityCategoryValue; count: number }[];
    byStatus: Record<ActivityStatusValue, number>;
};

export type ReportActivityDetail = {
    id: number;
    tanggal: string;
    deskripsi: string;
    staff: string;
    status: ActivityStatusValue;
};

export type ReportCategoryDetail = {
    value: ActivityCategoryValue;
    label: string;
    activities: ReportActivityDetail[];
};
