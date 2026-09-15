<?php

namespace App\Services;

use App\Enums\ActivityCategory;
use App\Enums\ActivityStatus;
use App\Models\Activity;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WeeklyReportAggregator
{
    /**
     * Get the Monday–Sunday boundaries of the current week.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public static function currentWeek(): array
    {
        $start = CarbonImmutable::today()->startOfWeek(CarbonImmutable::MONDAY);

        return [$start, $start->addDays(6)];
    }

    /**
     * Parse a query-string date, returning null on missing/invalid input so callers can fall back.
     */
    public static function parseDate(?string $value): ?CarbonImmutable
    {
        if (! $value) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Build the aggregated weekly report data for the given user.
     *
     * Admins get every staff member's activity for the period; staff only get their own.
     *
     * @return array{
     *     total: int,
     *     byCategory: Collection<int, array{value: string, label: string, count: int}>,
     *     byStatus: Collection<int, array{value: string, label: string, count: int}>,
     *     byStaff: Collection<int, array{id: int, name: string, total: int, byCategory: Collection<int, array{value: string, count: int}>, byStatus: array<string, int>}>,
     *     detailsByCategory: Collection<int, array{value: string, label: string, activities: Collection<int, array{id: int, tanggal: string, deskripsi: string, staff: string, status: string}>}>,
     *     projects: array<int, array{id: int, deskripsi: string, staff: string, progress_percent: int|null, target_selesai: string|null, status: string}>,
     * }
     */
    public function build(User $requestingUser, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $scopedToSelf = ! $requestingUser->isAdmin();

        $total = $this->baseQuery($requestingUser, $scopedToSelf, $start, $end)->count();

        $categoryCounts = $this->baseQuery($requestingUser, $scopedToSelf, $start, $end)
            ->select('kategori', DB::raw('count(*) as total'))
            ->groupBy('kategori')
            ->pluck('total', 'kategori');

        $byCategory = collect(ActivityCategory::cases())->map(fn (ActivityCategory $category) => [
            'value' => $category->value,
            'label' => $category->label(),
            'count' => (int) ($categoryCounts[$category->value] ?? 0),
        ]);

        $statusCounts = $this->baseQuery($requestingUser, $scopedToSelf, $start, $end)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $byStatus = collect(ActivityStatus::cases())->map(fn (ActivityStatus $status) => [
            'value' => $status->value,
            'label' => $status->label(),
            'count' => (int) ($statusCounts[$status->value] ?? 0),
        ]);

        $byStaff = collect($scopedToSelf ? [] : $this->buildStaffBreakdown($requestingUser, $start, $end)->all());

        $detailRows = $this->baseQuery($requestingUser, $scopedToSelf, $start, $end)
            ->with('user:id,name')
            ->orderBy('tanggal')
            ->get(['id', 'user_id', 'tanggal', 'kategori', 'status', 'deskripsi', 'progress_percent', 'target_selesai']);

        $detailsByCategory = collect(ActivityCategory::cases())->map(fn (ActivityCategory $category) => [
            'value' => $category->value,
            'label' => $category->label(),
            'activities' => $detailRows->where('kategori', $category)->map(fn (Activity $activity) => [
                'id' => $activity->id,
                'tanggal' => $activity->tanggal->toDateString(),
                'deskripsi' => $activity->deskripsi,
                'staff' => $activity->user->name,
                'status' => $activity->status->value,
            ])->values(),
        ]);

        $projects = $detailRows->where('kategori', ActivityCategory::Project)
            ->sortBy(fn (Activity $activity) => $activity->target_selesai?->toDateString() ?? '9999-12-31')
            ->map(fn (Activity $activity) => $this->buildProjectRow($activity))
            ->values()
            ->all();

        return [
            'total' => $total,
            'byCategory' => $byCategory,
            'byStatus' => $byStatus,
            'byStaff' => $byStaff,
            'detailsByCategory' => $detailsByCategory,
            'projects' => $projects,
        ];
    }

    /**
     * Per-day activity counts for the given range, scoped the same way as build().
     *
     * @return Collection<int, array{date: string, count: int}>
     */
    public function dailyCounts(User $requestingUser, CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        $scopedToSelf = ! $requestingUser->isAdmin();

        $counts = $this->baseQuery($requestingUser, $scopedToSelf, $start, $end)
            ->select('tanggal', DB::raw('count(*) as total'))
            ->groupBy('tanggal')
            ->pluck('total', 'tanggal');

        $days = collect();

        for ($date = $start; $date->lte($end); $date = $date->addDay()) {
            $days->push([
                'date' => $date->toDateString(),
                'count' => (int) ($counts[$date->toDateString()] ?? 0),
            ]);
        }

        return $days;
    }

    /**
     * @return Collection<int, array{id: int, name: string, total: int, byCategory: Collection<int, array{value: string, count: int}>, byStatus: array<string, int>}>
     */
    private function buildStaffBreakdown(User $requestingUser, CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        $rows = $this->baseQuery($requestingUser, false, $start, $end)
            ->select('user_id', 'kategori', 'status', DB::raw('count(*) as total'))
            ->groupBy('user_id', 'kategori', 'status')
            ->get();

        $names = User::whereIn('id', $rows->pluck('user_id')->unique())->pluck('name', 'id');

        return $rows->groupBy('user_id')
            ->map(fn (Collection $rowsForUser, int $userId) => $this->buildStaffRow($userId, $rowsForUser, $names))
            ->sortByDesc('total')
            ->values();
    }

    /**
     * @param  Collection<int, Activity>  $rowsForUser  Grouped rows (user_id, kategori, status, total) for one staff member.
     * @param  Collection<int, string>  $names
     * @return array{id: int, name: string, total: int, byCategory: Collection<int, array{value: string, count: int}>, byStatus: array<string, int>}
     */
    private function buildStaffRow(int $userId, Collection $rowsForUser, Collection $names): array
    {
        $byCategory = $rowsForUser->groupBy(fn (Activity $row) => $row->kategori->value)
            ->map(fn (Collection $categoryRows, string $value) => [
                'value' => $value,
                'count' => (int) $categoryRows->sum(fn (Activity $row) => $row->getAttribute('total')),
            ])->values();

        $byStatus = [];
        foreach (ActivityStatus::cases() as $status) {
            $byStatus[$status->value] = (int) $rowsForUser->where('status', $status)->sum(fn (Activity $row) => $row->getAttribute('total'));
        }

        return [
            'id' => $userId,
            'name' => (string) ($names[$userId] ?? '—'),
            'total' => (int) $rowsForUser->sum(fn (Activity $row) => $row->getAttribute('total')),
            'byCategory' => $byCategory,
            'byStatus' => $byStatus,
        ];
    }

    /**
     * @return array{id: int, deskripsi: string, staff: string, progress_percent: int|null, target_selesai: string|null, status: string}
     */
    private function buildProjectRow(Activity $activity): array
    {
        return [
            'id' => $activity->id,
            'deskripsi' => $activity->deskripsi,
            'staff' => $activity->user->name,
            'progress_percent' => $activity->progress_percent === null ? null : (int) $activity->progress_percent,
            'target_selesai' => $activity->target_selesai === null ? null : $activity->target_selesai->toDateString(),
            'status' => $activity->status->value,
        ];
    }

    /**
     * @return Builder<Activity>
     */
    private function baseQuery(User $requestingUser, bool $scopedToSelf, CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        $query = Activity::query()->whereBetween('tanggal', [$start->toDateString(), $end->toDateString()]);

        if ($scopedToSelf) {
            $query->where('user_id', $requestingUser->id);
        }

        return $query;
    }
}
