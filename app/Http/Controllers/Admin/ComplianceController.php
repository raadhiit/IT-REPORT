<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Inertia\Inertia;
use Inertia\Response;

class ComplianceController extends Controller
{
    /**
     * Show which active staff haven't logged an activity on each weekday this week.
     */
    public function index(): Response
    {
        $today = CarbonImmutable::today();
        $weekStart = $today->startOfWeek(CarbonImmutable::MONDAY);
        $weekEnd = $weekStart->addDays(4); // Friday
        $rangeEnd = $today->lessThan($weekEnd) ? $today : $weekEnd;

        $dates = collect(CarbonPeriod::create($weekStart, $rangeEnd)->toArray())
            ->map(fn (CarbonInterface $date) => $date->toDateString());

        $staff = User::where('is_active', true)->where('role', 'staff')->orderBy('name')->get(['id', 'name']);

        $filledKeys = Activity::whereIn('user_id', $staff->pluck('id'))
            ->whereBetween('tanggal', [$weekStart->toDateString(), $rangeEnd->toDateString()])
            ->get(['user_id', 'tanggal'])
            ->map(fn (Activity $activity) => $activity->user_id.'|'.$activity->tanggal->toDateString())
            ->flip();

        return Inertia::render('admin/compliance/Index', [
            'dates' => $dates,
            'staff' => $staff->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'filled' => $dates->mapWithKeys(
                    fn (string $date) => [$date => $filledKeys->has($user->id.'|'.$date)]
                ),
            ]),
        ]);
    }
}
