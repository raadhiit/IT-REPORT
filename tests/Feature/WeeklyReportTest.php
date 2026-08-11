<?php

use App\Models\Activity;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Date::setTestNow(CarbonImmutable::parse('2026-08-12')); // Wednesday, week of 2026-08-10..2026-08-16
});

afterEach(function () {
    Date::setTestNow();
});

test('guests are redirected to login', function () {
    $response = $this->get(route('reports.weekly'));

    $response->assertRedirect(route('login'));
});

test('a staff member only sees their own activities in the report', function () {
    $staff = User::factory()->create();
    $otherStaff = User::factory()->create();

    Activity::factory()->for($staff)->create(['tanggal' => '2026-08-10', 'kategori' => 'support']);
    Activity::factory()->for($staff)->create(['tanggal' => '2026-08-11', 'kategori' => 'support']);
    Activity::factory()->for($otherStaff)->create(['tanggal' => '2026-08-10', 'kategori' => 'project']);

    $response = $this->actingAs($staff)->get(route('reports.weekly'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('reports/Weekly')
        ->where('total', 2)
        ->where('byStaff', [])
        ->where('byCategory', fn ($categories) => collect($categories)->firstWhere('value', 'support')['count'] === 2)
        ->where('byCategory', fn ($categories) => collect($categories)->firstWhere('value', 'project')['count'] === 0),
    );
});

test('an admin sees every staff member aggregated by category', function () {
    $admin = User::factory()->admin()->create();
    $budi = User::factory()->create(['name' => 'Budi']);
    $siti = User::factory()->create(['name' => 'Siti']);

    Activity::factory()->for($budi)->create(['tanggal' => '2026-08-10', 'kategori' => 'maintenance']);
    Activity::factory()->for($budi)->create(['tanggal' => '2026-08-11', 'kategori' => 'support']);
    Activity::factory()->for($siti)->count(3)->create(['tanggal' => '2026-08-12', 'kategori' => 'project']);

    $response = $this->actingAs($admin)->get(route('reports.weekly'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('reports/Weekly')
        ->where('total', 5)
        ->where('byStaff.0.name', 'Siti')
        ->where('byStaff.0.total', 3)
        ->where('byStaff.1.name', 'Budi')
        ->where('byStaff.1.total', 2)
        ->where('byCategory', fn ($categories) => collect($categories)->firstWhere('value', 'project')['count'] === 3),
    );
});

test('a user can download the weekly report as a pdf', function () {
    $user = User::factory()->create();
    Activity::factory()->for($user)->create(['tanggal' => '2026-08-10']);

    $response = $this->actingAs($user)->get(route('reports.weekly.pdf'));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
});

test('guests cannot download the weekly report pdf', function () {
    $response = $this->get(route('reports.weekly.pdf'));

    $response->assertRedirect(route('login'));
});

test('a user can download the weekly report as an excel workbook', function () {
    $user = User::factory()->create();
    Activity::factory()->for($user)->create(['tanggal' => '2026-08-10']);

    $response = $this->actingAs($user)->get(route('reports.weekly.excel'));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

test('guests cannot download the weekly report excel', function () {
    $response = $this->get(route('reports.weekly.excel'));

    $response->assertRedirect(route('login'));
});

test('activities outside the current week are excluded', function () {
    $staff = User::factory()->create();

    Activity::factory()->for($staff)->create(['tanggal' => '2026-08-09']); // last Sunday, outside this week
    Activity::factory()->for($staff)->create(['tanggal' => '2026-08-10']); // Monday, inside

    $response = $this->actingAs($staff)->get(route('reports.weekly'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('reports/Weekly')
        ->where('total', 1)
        ->where('start', '2026-08-10')
        ->where('end', '2026-08-16'),
    );
});
