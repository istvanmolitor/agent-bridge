<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class WorkspaceSeeder extends Seeder
{
    /**
     * Seed a default workspace for the admin user and ensure its directory exists.
     */
    public function run(): void
    {
        $user = User::where('email', 'admin@example.com')->firstOrFail();

        $workspace = Workspace::updateOrCreate(
            ['user_id' => $user->id, 'name' => 'Alapértelmezett'],
            ['path' => Workspace::resolvePath('default')],
        );

        File::ensureDirectoryExists($workspace->path);
    }
}
