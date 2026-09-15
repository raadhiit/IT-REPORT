<?php

namespace App\Http\Controllers;

use App\Enums\ActivityCategory;
use App\Enums\ActivityStatus;
use App\Http\Requests\StoreActivityRequest;
use App\Http\Requests\UpdateActivityRequest;
use App\Models\Activity;
use App\Services\WeeklyReportAggregator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
{
    /**
     * Display the activity log form and the user's recent entries, filtered by date range
     * (defaults to the current Monday–Sunday week, same definition used across the app).
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        [$defaultFrom, $defaultTo] = WeeklyReportAggregator::currentWeek();

        $from = WeeklyReportAggregator::parseDate($request->query('from')) ?? $defaultFrom;
        $to = WeeklyReportAggregator::parseDate($request->query('to')) ?? $defaultTo;

        return Inertia::render('activities/Index', [
            'activities' => $user->activities()
                ->with('attachments')
                ->whereBetween('tanggal', [$from->toDateString(), $to->toDateString()])
                ->latest('tanggal')
                ->latest('id')
                ->get(),
            'categories' => collect(ActivityCategory::cases())
                ->map(fn (ActivityCategory $category) => ['value' => $category->value, 'label' => $category->label()]),
            'statuses' => collect(ActivityStatus::cases())
                ->map(fn (ActivityStatus $status) => ['value' => $status->value, 'label' => $status->label()]),
            'lastCategory' => $user->activities()->latest()->value('kategori'),
            'today' => now()->toDateString(),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);
    }

    /**
     * Store a newly logged activity.
     */
    public function store(StoreActivityRequest $request): RedirectResponse
    {
        $activity = $request->user()->activities()->create($request->safe()->except('attachments'));

        foreach ($request->file('attachments', []) as $file) {
            $path = $file->store('activity-attachments/'.$activity->id, 'local');

            $activity->attachments()->create([
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Activity logged.')]);

        return to_route('activities.index');
    }

    /**
     * Update a previously logged activity (owner or admin only, enforced by UpdateActivityRequest).
     */
    public function update(UpdateActivityRequest $request, Activity $activity): RedirectResponse
    {
        $activity->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Activity updated.')]);

        return to_route('activities.index');
    }
}
