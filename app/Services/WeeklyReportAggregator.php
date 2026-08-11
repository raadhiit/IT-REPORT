<?php

namespace App\Services;

use App\Enums\ActivityCategory;
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
     * Build the aggregated weekly report data for the given user.
     *
     * Admins get every staff member's activity for the period; staff only get their own.
     *
     * @return array{
     *     total: int,
     *     byCategory: Collection<int, array{value: string, label: string, count: int}>,
     *     byStaff: Collection<int, array{id: int, name: string, total: int, byCategory: Collection<int, array{value: string, count: int}>}>,
     *     detailsByCategory: Collection<int, array{value: string, label: string, activities: Collection<int, array{id: int, tanggal: string, deskripsi: string, staff: string}>}>,
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

        $byStaff = $scopedToSelf ? collect() : $this->buildStaffBreakdown($requestingUser, $start, $end);

        $detailRows = $this->baseQuery($requestingUser, $scopedToSelf, $start, $end)
            ->with('user:id,name')
            ->orderBy('tanggal')
            ->get(['id', 'user_id', 'tanggal', 'kategori', 'deskripsi']);

        $detailsByCategory = collect(ActivityCategory::cases())->map(fn (ActivityCategory $category) => [
            'value' => $category->value,
            'label' => $category->label(),
            'activities' => $detailRows->where('kategori', $category)->map(fn (Activity $activity) => [
                'id' => $activity->id,
                'tanggal' => $activity->tanggal->toDateString(),
                'deskripsi' => $activity->deskripsi,
                'staff' => $activity->user->name,
            ])->values(),
        ]);

        return [
            'total' => $total,
            'byCategory' => $byCategory,
            'byStaff' => $byStaff,
            'detailsByCategory' => $detailsByCategory,
        ];
    }

    /**
     * @return Collection<int, array{id: int, name: string, total: int, byCategory: Collection<int, array{value: string, count: int}>}>
     */
    private function buildStaffBreakdown(User $requestingUser, CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        $rows = $this->baseQuery($requestingUser, false, $start, $end)
            ->select('user_id', 'kategori', DB::raw('count(*) as total'))
            ->groupBy('user_id', 'kategori')
            ->get();

        $names = User::whereIn('id', $rows->pluck('user_id')->unique())->pluck('name', 'id');

        return $rows->groupBy('user_id')
            ->map(fn (Collection $categoryRows, int $userId) => [
                'id' => $userId,
                'name' => (string) ($names[$userId] ?? '—'),
                'total' => (int) $categoryRows->sum(fn (Activity $row) => $row->getAttribute('total')),
                'byCategory' => $categoryRows->map(fn (Activity $row) => [
                    'value' => $row->kategori->value,
                    'count' => (int) $row->getAttribute('total'),
                ])->values(),
            ])
            ->sortByDesc('total')
            ->values();
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
