<?php

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Support\Facades\Hash;

test('it creates an admin user with the expected credentials', function () {
    $this->seed(AdminUserSeeder::class);

    $admin = User::where('email', 'admin@example.com')->sole();

    expect($admin->is_admin)->toBeTrue()
        ->and(Hash::check('password', $admin->password))->toBeTrue();
});

test('running it again does not create a duplicate user', function () {
    $this->seed(AdminUserSeeder::class);
    $this->seed(AdminUserSeeder::class);

    expect(User::where('email', 'admin@example.com')->count())->toBe(1);
});
