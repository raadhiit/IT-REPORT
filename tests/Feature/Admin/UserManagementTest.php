<?php

use App\Models\User;

test('admin can view the user list', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create();

    $response = $this->actingAs($admin)->get(route('admin.users.index'));

    $response->assertOk();
});

test('staff cannot view the user list', function () {
    $staff = User::factory()->create();

    $response = $this->actingAs($staff)->get(route('admin.users.index'));

    $response->assertForbidden();
});

test('guests are redirected to login', function () {
    $response = $this->get(route('admin.users.index'));

    $response->assertRedirect(route('login'));
});

test('admin can create a user', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'New Staff',
        'email' => 'staff@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'staff',
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseHas('users', [
        'email' => 'staff@example.com',
        'role' => 'staff',
    ]);
});

test('staff cannot create a user', function () {
    $staff = User::factory()->create();

    $response = $this->actingAs($staff)->post(route('admin.users.store'), [
        'name' => 'New Staff',
        'email' => 'staff@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'staff',
    ]);

    $response->assertForbidden();
    $this->assertDatabaseMissing('users', ['email' => 'staff@example.com']);
});

test('admin can update a user role without changing the password', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['role' => 'staff']);
    $originalPassword = $user->password;

    $response = $this->actingAs($admin)->put(route('admin.users.update', $user), [
        'name' => $user->name,
        'email' => $user->email,
        'role' => 'admin',
        'is_active' => '1',
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('admin.users.index'));

    $user->refresh();
    expect($user->role)->toBe('admin');
    expect($user->password)->toBe($originalPassword);
});

test('admin can deactivate a user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $user));

    $response->assertRedirect(route('admin.users.index'));
    expect($user->fresh()->is_active)->toBeFalse();
});

test('deactivated users cannot log in', function () {
    $user = User::factory()->inactive()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors();
    $this->assertGuest();
});
