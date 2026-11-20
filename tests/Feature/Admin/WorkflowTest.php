<?php

use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowNode;
use App\Workflows\Nodes\HttpRequestNode;
use Inertia\Testing\AssertableInertia as Assert;

test('the workflows page lists only the current user\'s workflows', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $own = Workflow::factory()->for($admin)->create(['name' => 'Mine']);
    Workflow::factory()->create(['name' => 'Someone else\'s']);

    $this->actingAs($admin)
        ->get(route('admin.workflows.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Workflows')
            ->has('workflows', 1)
            ->where('workflows.0.id', $own->id)
            ->where('workflows.0.name', 'Mine'),
        );
});

test('an admin can create a workflow', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->from(route('admin.workflows.index'))
        ->post(route('admin.workflows.store'), [
            'name' => 'Cikk feldolgozás',
            'description' => 'Letöltés és elemzés',
        ])
        ->assertRedirect(route('admin.workflows.index'));

    $workflow = $admin->workflows()->sole();
    expect($workflow->name)->toBe('Cikk feldolgozás')
        ->and($workflow->description)->toBe('Letöltés és elemzés');
});

test('creating a workflow requires a name', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->from(route('admin.workflows.index'))
        ->post(route('admin.workflows.store'), ['name' => ''])
        ->assertSessionHasErrors('name');
});

test('the editor renders the workflow with its nodes and connections', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $workflow = Workflow::factory()->for($admin)->create();
    $source = WorkflowNode::factory()->for($workflow)->create();
    $target = WorkflowNode::factory()->for($workflow)->create();
    $workflow->connections()->create([
        'source_node_id' => $source->id,
        'target_node_id' => $target->id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.workflows.show', $workflow))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/WorkflowShow')
            ->where('workflow.id', $workflow->id)
            ->has('nodeTypes', 5)
            ->where('nodeTypes.0.key', 'start')
            ->has('prompts')
            ->has('nodes', 2)
            ->has('nodes.0.type')
            ->has('connections', 1)
            ->where('connections.0.source_node_id', $source->id)
            ->where('connections.0.target_node_id', $target->id),
        );
});

test('a user cannot view another user\'s workflow', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $workflow = Workflow::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.workflows.show', $workflow))
        ->assertForbidden();
});

test('an admin can add a node to a workflow', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $workflow = Workflow::factory()->for($admin)->create();

    $this->actingAs($admin)
        ->from(route('admin.workflows.show', $workflow))
        ->post(route('admin.workflows.nodes.store', $workflow), [
            'type' => 'http_request',
            'name' => 'Letöltés',
            'description' => 'Letölti a cikket',
            'position_x' => 120,
            'position_y' => 80,
        ])
        ->assertRedirect(route('admin.workflows.show', $workflow));

    $node = $workflow->nodes()->sole();
    expect($node->name)->toBe('Letöltés')
        ->and($node->type)->toBe('http_request')
        ->and($node->position_x)->toBe(120.0);
});

test('adding a node requires a name and a valid type', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $workflow = Workflow::factory()->for($admin)->create();

    $this->actingAs($admin)
        ->from(route('admin.workflows.show', $workflow))
        ->post(route('admin.workflows.nodes.store', $workflow), [
            'name' => '',
            'type' => 'nonsense',
        ])
        ->assertSessionHasErrors(['name', 'type']);
});

test('a node resolves its type class through the registry', function () {
    $node = WorkflowNode::factory()->create(['type' => 'http_request']);

    expect($node->typeClass())->toBe(HttpRequestNode::class);
});

test('a node position can be updated', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $workflow = Workflow::factory()->for($admin)->create();
    $node = WorkflowNode::factory()->for($workflow)->create();

    $this->actingAs($admin)
        ->patch(route('admin.workflows.nodes.position', [$workflow, $node]), [
            'position_x' => 333,
            'position_y' => 444,
        ]);

    expect($node->fresh()->position_x)->toBe(333.0)
        ->and($node->fresh()->position_y)->toBe(444.0);
});

test('an admin can connect two nodes of a workflow', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $workflow = Workflow::factory()->for($admin)->create();
    $source = WorkflowNode::factory()->for($workflow)->create(['type' => 'agent']);
    $target = WorkflowNode::factory()->for($workflow)->create(['type' => 'agent']);

    $this->actingAs($admin)
        ->post(route('admin.workflows.connections.store', $workflow), [
            'source_node_id' => $source->id,
            'target_node_id' => $target->id,
        ]);

    expect($workflow->connections()->count())->toBe(1);
});

test('a node cannot be connected to itself', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $workflow = Workflow::factory()->for($admin)->create();
    $node = WorkflowNode::factory()->for($workflow)->create();

    $this->actingAs($admin)
        ->from(route('admin.workflows.show', $workflow))
        ->post(route('admin.workflows.connections.store', $workflow), [
            'source_node_id' => $node->id,
            'target_node_id' => $node->id,
        ])
        ->assertSessionHasErrors('target_node_id');
});

test('a connection cannot reference a node from another workflow', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $workflow = Workflow::factory()->for($admin)->create();
    $source = WorkflowNode::factory()->for($workflow)->create();
    $foreign = WorkflowNode::factory()->create();

    $this->actingAs($admin)
        ->from(route('admin.workflows.show', $workflow))
        ->post(route('admin.workflows.connections.store', $workflow), [
            'source_node_id' => $source->id,
            'target_node_id' => $foreign->id,
        ])
        ->assertSessionHasErrors('target_node_id');
});

test('a start node cannot be the target of a connection', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $workflow = Workflow::factory()->for($admin)->create();
    $agent = WorkflowNode::factory()->for($workflow)->create(['type' => 'agent']);
    $start = WorkflowNode::factory()->for($workflow)->create(['type' => 'start']);

    $this->actingAs($admin)
        ->from(route('admin.workflows.show', $workflow))
        ->post(route('admin.workflows.connections.store', $workflow), [
            'source_node_id' => $agent->id,
            'target_node_id' => $start->id,
        ])
        ->assertSessionHasErrors('target_node_id');

    expect($workflow->connections()->count())->toBe(0);
});

test('a start node can be the source of a connection', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $workflow = Workflow::factory()->for($admin)->create();
    $start = WorkflowNode::factory()->for($workflow)->create(['type' => 'start']);
    $agent = WorkflowNode::factory()->for($workflow)->create(['type' => 'agent']);

    $this->actingAs($admin)
        ->from(route('admin.workflows.show', $workflow))
        ->post(route('admin.workflows.connections.store', $workflow), [
            'source_node_id' => $start->id,
            'target_node_id' => $agent->id,
        ])
        ->assertSessionHasNoErrors();

    expect($workflow->connections()->count())->toBe(1);
});

test('deleting a node cascades its connections', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $workflow = Workflow::factory()->for($admin)->create();
    $source = WorkflowNode::factory()->for($workflow)->create();
    $target = WorkflowNode::factory()->for($workflow)->create();
    $connection = $workflow->connections()->create([
        'source_node_id' => $source->id,
        'target_node_id' => $target->id,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.workflows.nodes.destroy', [$workflow, $source]));

    expect(WorkflowNode::find($source->id))->toBeNull()
        ->and($workflow->connections()->whereKey($connection->id)->exists())->toBeFalse();
});

test('an admin can delete a workflow', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $workflow = Workflow::factory()->for($admin)->create();

    $this->actingAs($admin)
        ->delete(route('admin.workflows.destroy', $workflow))
        ->assertRedirect(route('admin.workflows.index'));

    expect(Workflow::find($workflow->id))->toBeNull();
});
