<?php

namespace App\Http\Controllers;

use App\Services\WeeklyReportAggregator;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show a quick summary of the logged-in user's activity this week.
     */
    public function index(Request $request, WeeklyReportAggregator $aggregator): Response
    {
        [$start, $end] = WeeklyReportAggregator::currentWeek();
        $user = $request->user();

        $report = $aggregator->build($user, $start, $end);
        $lastWeekTotal = $aggregator->build($user, $start->subWeek(), $end->subWeek())['total'];
        $today = CarbonImmutable::today()->toDateString();

        $topCategory = $report['byCategory']->sortByDesc('count')->first();
        $todayCount = $report['detailsByCategory']
            ->flatMap(fn (array $category) => $category['activities'])
            ->where('tanggal', $today)
            ->count();

        return Inertia::render('Dashboard', [
            'total' => $report['total'],
            'lastWeekTotal' => $lastWeekTotal,
            'todayCount' => $todayCount,
            'today' => $today,
            'topCategory' => $topCategory && $topCategory['count'] > 0 ? $topCategory : null,
            'byCategory' => $report['byCategory'],
            'dailyCounts' => $aggregator->dailyCounts($user, $start, $end),
        ]);
    }
}
