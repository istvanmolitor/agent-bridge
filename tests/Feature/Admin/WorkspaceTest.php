<?php

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia as Assert;

function tempWorkspacePath(): string
{
    return sys_get_temp_dir().'/agent-bridge-test/ws-'.bin2hex(random_bytes(6));
}

function workspaceBase(): string
{
    return sys_get_temp_dir().'/agent-bridge-test/base';
}

beforeEach(function () {
    config(['workspaces.base' => workspaceBase()]);
});

afterEach(function () {
    File::deleteDirectory(sys_get_temp_dir().'/agent-bridge-test');
});

test('workspaces page lists only the current user\'s workspaces', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $own = Workspace::factory()->for($admin)->create(['name' => 'Mine']);
    Workspace::factory()->create(['name' => 'Someone else\'s']);

    $this->actingAs($admin)
        ->get(route('admin.workspaces.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Workspaces')
            ->has('workspaces', 1)
            ->where('workspaces.0.id', $own->id)
            ->where('workspaces.0.name', 'Mine'),
        );
});

test('creating a workspace creates its physical directory under the base', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $expected = workspaceBase().'/docs';

    expect(File::isDirectory($expected))->toBeFalse();

    $this->actingAs($admin)
        ->from(route('admin.workspaces.index'))
        ->post(route('admin.workspaces.store'), ['name' => 'Docs', 'folder' => 'docs'])
        ->assertRedirect(route('admin.workspaces.index'));

    $workspace = $admin->workspaces()->sole();

    expect($workspace->name)->toBe('Docs')
        ->and($workspace->path)->toBe($expected)
        ->and($workspace->relative_path)->toBe('docs')
        ->and(File::isDirectory($expected))->toBeTrue();
});

test('creating a workspace requires a name and a folder', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->from(route('admin.workspaces.index'))
        ->post(route('admin.workspaces.store'), ['name' => '', 'folder' => ''])
        ->assertSessionHasErrors(['name', 'folder']);
});

test('creating a workspace rejects a folder that escapes the base', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->from(route('admin.workspaces.index'))
        ->post(route('admin.workspaces.store'), ['name' => 'Escape', 'folder' => '../escape'])
        ->assertSessionHasErrors('folder');

    expect($admin->workspaces()->count())->toBe(0)
        ->and(File::isDirectory(dirname(workspaceBase()).'/escape'))->toBeFalse();
});

test('creating a workspace fails gracefully when the folder cannot be created', function () {
    config(['workspaces.base' => '/proc/agent-bridge-forbidden']);
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->from(route('admin.workspaces.index'))
        ->post(route('admin.workspaces.store'), ['name' => 'Nope', 'folder' => 'x'])
        ->assertRedirect(route('admin.workspaces.index'))
        ->assertSessionHasErrors('folder');

    expect($admin->workspaces()->count())->toBe(0);
});

test('deleting a workspace keeps its physical directory', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $path = tempWorkspacePath();
    File::ensureDirectoryExists($path);
    $workspace = Workspace::factory()->for($admin)->create(['path' => $path]);

    $this->actingAs($admin)
        ->delete(route('admin.workspaces.destroy', $workspace))
        ->assertRedirect(route('admin.workspaces.index'));

    expect(Workspace::find($workspace->id))->toBeNull()
        ->and(File::isDirectory($path))->toBeTrue();
});

test('an admin cannot delete another admin\'s workspace', function () {
    $owner = User::factory()->create(['is_admin' => true]);
    $workspace = Workspace::factory()->for($owner)->create();
    $other = User::factory()->create(['is_admin' => true]);

    $this->actingAs($other)
        ->delete(route('admin.workspaces.destroy', $workspace))
        ->assertForbidden();

    expect(Workspace::find($workspace->id))->not->toBeNull();
});

test('the file tree lists directories first, then files', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $path = tempWorkspacePath();
    File::ensureDirectoryExists($path.'/sub');
    File::put($path.'/readme.txt', 'hello');
    $workspace = Workspace::factory()->for($admin)->create(['path' => $path]);

    $this->actingAs($admin)
        ->getJson(route('admin.workspaces.tree', $workspace))
        ->assertOk()
        ->assertJsonPath('entries.0.type', 'directory')
        ->assertJsonPath('entries.0.name', 'sub')
        ->assertJsonPath('entries.1.type', 'file')
        ->assertJsonPath('entries.1.name', 'readme.txt');
});

test('a file inside the workspace can be read', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $path = tempWorkspacePath();
    File::ensureDirectoryExists($path);
    File::put($path.'/note.md', '# Title');
    $workspace = Workspace::factory()->for($admin)->create(['path' => $path]);

    $this->actingAs($admin)
        ->getJson(route('admin.workspaces.file', [$workspace, 'path' => 'note.md']))
        ->assertOk()
        ->assertJsonPath('content', '# Title')
        ->assertJsonPath('binary', false);
});

test('the file tree rejects path traversal outside the workspace', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $path = tempWorkspacePath();
    File::ensureDirectoryExists($path);
    $workspace = Workspace::factory()->for($admin)->create(['path' => $path]);

    $this->actingAs($admin)
        ->getJson(route('admin.workspaces.tree', [$workspace, 'path' => '../../../../etc']))
        ->assertNotFound();

    $this->actingAs($admin)
        ->getJson(route('admin.workspaces.file', [$workspace, 'path' => '../../../../etc/passwd']))
        ->assertNotFound();
});

test('a user cannot read files from another user\'s workspace', function () {
    $owner = User::factory()->create(['is_admin' => true]);
    $path = tempWorkspacePath();
    File::ensureDirectoryExists($path);
    $workspace = Workspace::factory()->for($owner)->create(['path' => $path]);
    $other = User::factory()->create(['is_admin' => true]);

    $this->actingAs($other)
        ->getJson(route('admin.workspaces.tree', $workspace))
        ->assertForbidden();
});

test('a non-admin user cannot access the workspaces pages', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $workspace = Workspace::factory()->for($user)->create();

    $this->actingAs($user)->get(route('admin.workspaces.index'))->assertForbidden();
    $this->actingAs($user)->post(route('admin.workspaces.store'), ['name' => 'x', 'folder' => 'x'])->assertForbidden();
    $this->actingAs($user)->get(route('admin.workspaces.show', $workspace))->assertForbidden();
});

test('a guest is redirected to login for the workspaces pages', function () {
    $this->get(route('admin.workspaces.index'))->assertRedirect(route('login'));
});
