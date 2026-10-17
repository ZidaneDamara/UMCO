<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    Permission::firstOrCreate(['name' => 'pos.access', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'production.bar', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'production.kitchen', 'guard_name' => 'web']);

    $cashierRole = Role::firstOrCreate(['name' => 'cashier', 'guard_name' => 'web']);
    $cashierRole->syncPermissions(['pos.access']);

    $baristaRole = Role::firstOrCreate(['name' => 'barista', 'guard_name' => 'web']);
    $baristaRole->syncPermissions(['production.bar', 'pos.access']);

    $kitchenRole = Role::firstOrCreate(['name' => 'kitchen', 'guard_name' => 'web']);
    $kitchenRole->syncPermissions(['production.kitchen']);
});

test('unauthenticated user is redirected to login', function () {
    $response = $this->get('/pos');
    $response->assertRedirect('/login');
});

test('cashier can access pos screen', function () {
    $cashier = User::factory()->create();
    $cashier->assignRole('cashier');

    $response = $this->actingAs($cashier)->get('/pos');
    $response->assertOk();
});

test('barista can access pos screen', function () {
    $barista = User::factory()->create();
    $barista->assignRole('barista');

    $response = $this->actingAs($barista)->get('/pos');
    $response->assertOk();
});

test('kitchen cannot access pos screen', function () {
    $kitchen = User::factory()->create();
    $kitchen->assignRole('kitchen');

    $response = $this->actingAs($kitchen)->get('/pos');
    $response->assertForbidden();
});

test('barista can access bar production screen', function () {
    $barista = User::factory()->create();
    $barista->assignRole('barista');

    $response = $this->actingAs($barista)->get('/station/bar');
    $response->assertOk();
});

test('kitchen can access kitchen production screen', function () {
    $kitchen = User::factory()->create();
    $kitchen->assignRole('kitchen');

    $response = $this->actingAs($kitchen)->get('/station/kitchen');
    $response->assertOk();
});
