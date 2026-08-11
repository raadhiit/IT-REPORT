<?php

namespace App\Http\Controllers;

use App\Enums\ActivityCategory;
use App\Http\Requests\StoreActivityRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
{
    /**
     * Display the activity log form and the user's recent entries.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('activities/Index', [
            'activities' => $user->activities()
                ->with('attachments')
                ->latest('tanggal')
                ->latest('id')
                ->get(),
            'categories' => collect(ActivityCategory::cases())
                ->map(fn (ActivityCategory $category) => ['value' => $category->value, 'label' => $category->label()]),
            'lastCategory' => $user->activities()->latest()->value('kategori'),
            'today' => now()->toDateString(),
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
}
