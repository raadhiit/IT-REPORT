<?php

use App\Models\Activity;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Inertia\Testing\AssertableInertia as Assert;

test('admin can view the compliance page', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('admin.compliance.index'));

    $response->assertOk();
});

test('staff cannot view the compliance page', function () {
    $staff = User::factory()->create();

    $response = $this->actingAs($staff)->get(route('admin.compliance.index'));

    $response->assertForbidden();
});

test('compliance page marks logged days as filled and others as unfilled', function () {
    Date::setTestNow(CarbonImmutable::parse('2026-08-12')); // Wednesday

    $admin = User::factory()->admin()->create();
    $staff = User::factory()->create(['name' => 'Budi']);
    Activity::factory()->for($staff)->create(['tanggal' => '2026-08-10']); // Monday

    $response = $this->actingAs($admin)->get(route('admin.compliance.index'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('admin/compliance/Index')
        ->where('dates', ['2026-08-10', '2026-08-11', '2026-08-12'])
        ->where('staff.0.name', 'Budi')
        ->where('staff.0.filled.2026-08-10', true)
        ->where('staff.0.filled.2026-08-11', false)
        ->where('staff.0.filled.2026-08-12', false),
    );

    Date::setTestNow();
});

test('inactive staff are excluded from the compliance page', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->inactive()->create(['name' => 'Inactive Staff']);

    $response = $this->actingAs($admin)->get(route('admin.compliance.index'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('admin/compliance/Index')
        ->has('staff', 0),
    );
});
