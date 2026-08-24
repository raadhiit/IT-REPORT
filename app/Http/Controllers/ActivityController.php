<?php

namespace App\Http\Controllers;

use App\Enums\ActivityCategory;
use App\Http\Requests\StoreActivityRequest;
use App\Services\WeeklyReportAggregator;
use Carbon\CarbonImmutable;
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

        $from = $this->parseDate($request->query('from')) ?? $defaultFrom;
        $to = $this->parseDate($request->query('to')) ?? $defaultTo;

        return Inertia::render('activities/Index', [
            'activities' => $user->activities()
                ->with('attachments')
                ->whereBetween('tanggal', [$from->toDateString(), $to->toDateString()])
                ->latest('tanggal')
                ->latest('id')
                ->get(),
            'categories' => collect(ActivityCategory::cases())
                ->map(fn (ActivityCategory $category) => ['value' => $category->value, 'label' => $category->label()]),
            'lastCategory' => $user->activities()->latest()->value('kategori'),
            'today' => now()->toDateString(),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);
    }

    /**
     * Parse a query-string date, returning null on missing/invalid input so callers can fall back.
     */
    private function parseDate(?string $value): ?CarbonImmutable
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
}
