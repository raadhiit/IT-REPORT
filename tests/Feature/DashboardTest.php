<?php

use App\Models\Activity;
use App\Models\User;
use App\Services\WeeklyReportAggregator;
use Carbon\CarbonImmutable;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('it summarizes only the current week and scopes to the logged-in staff', function () {
    $user = User::factory()->create();
    $otherStaff = User::factory()->create();

    [$start] = WeeklyReportAggregator::currentWeek();
    Activity::factory()->for($user)->create(['tanggal' => $start->toDateString(), 'kategori' => 'support']);
    Activity::factory()->for($user)->create(['tanggal' => $start->toDateString(), 'kategori' => 'support']);
    Activity::factory()->for($user)->create(['tanggal' => $start->subDay()->toDateString(), 'kategori' => 'meeting']); // last week, excluded
    Activity::factory()->for($otherStaff)->create(['tanggal' => $start->addDay()->toDateString(), 'kategori' => 'project']); // other staff, excluded

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertInertia(fn ($page) => $page
        ->where('total', 2)
        ->where('topCategory.value', 'support')
        ->where('topCategory.count', 2)
    );
});

test('it counts todayCount only for activities logged today', function () {
    $user = User::factory()->create();
    $today = CarbonImmutable::today();

    Activity::factory()->for($user)->create(['tanggal' => $today->toDateString()]);
    Activity::factory()->for($user)->create(['tanggal' => $today->subDay()->toDateString()]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertInertia(fn ($page) => $page->where('todayCount', 1));
});

test('it compares the current week total against last week and lists 7 days', function () {
    $user = User::factory()->create();
    [$start, $end] = WeeklyReportAggregator::currentWeek();

    Activity::factory()->for($user)->create(['tanggal' => $start->toDateString()]);
    Activity::factory()->for($user)->count(2)->create(['tanggal' => $start->subWeek()->toDateString()]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertInertia(fn ($page) => $page
        ->where('total', 1)
        ->where('lastWeekTotal', 2)
        ->where('dailyCounts.0.date', $start->toDateString())
        ->where('dailyCounts.0.count', 1)
        ->where('dailyCounts.6.date', $end->toDateString())
        ->has('dailyCounts', 7)
    );
});

test('it includes a status breakdown for the dashboard donut', function () {
    $user = User::factory()->create();
    [$start] = WeeklyReportAggregator::currentWeek();

    Activity::factory()->for($user)->create(['tanggal' => $start->toDateString(), 'status' => 'selesai']);
    Activity::factory()->for($user)->create(['tanggal' => $start->toDateString(), 'status' => 'selesai']);
    Activity::factory()->for($user)->create(['tanggal' => $start->toDateString(), 'status' => 'pending']);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertInertia(fn ($page) => $page
        ->where('byStatus', fn ($statuses) => collect($statuses)->firstWhere('value', 'selesai')['count'] === 2)
        ->where('byStatus', fn ($statuses) => collect($statuses)->firstWhere('value', 'pending')['count'] === 1)
        // A staff member's own dashboard is scoped to themselves only, so byStaff stays empty here
        // (matches the same admin-vs-staff scoping as the weekly report aggregator).
        ->where('byStaff', [])
    );
});

test('topCategory is null when nothing has been logged this week', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertInertia(fn ($page) => $page->where('total', 0)->where('topCategory', null));
});
