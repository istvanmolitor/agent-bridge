<?php

use App\Events\WorkflowNodeStatusChanged;
use App\Jobs\ExecuteWorkflowNode;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowNode;
use App\Workflows\Nodes\StartNode;
use App\Workflows\WorkflowRunner;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

test('a node resolves to an executable type instance', function () {
    $node = WorkflowNode::factory()->create(['type' => 'start']);

    expect($node->resolveType())->toBeInstanceOf(StartNode::class)
        ->and($node->resolveType()->node->is($node))->toBeTrue();
});

test('the start node execute forwards its parameters', function () {
    $node = WorkflowNode::factory()->create(['type' => 'start']);

    expect($node->resolveType()->execute(['a' => 1]))->toBe(['a' => 1]);
});

test('the runner queues the node id and parameters', function () {
    Queue::fake();
    $node = WorkflowNode::factory()->create();

    app(WorkflowRunner::class)->run($node, ['foo' => 'bar']);

    Queue::assertPushed(
        ExecuteWorkflowNode::class,
        fn (ExecuteWorkflowNode $job) => $job->nodeId === $node->id
            && $job->parameters === ['foo' => 'bar'],
    );
});

test('executing a node starts its children with the execute return value', function () {
    Queue::fake();
    $workflow = Workflow::factory()->create();
    $start = WorkflowNode::factory()->for($workflow)->create(['type' => 'start']);
    $childA = WorkflowNode::factory()->for($workflow)->create(['type' => 'agent']);
    $childB = WorkflowNode::factory()->for($workflow)->create(['type' => 'http_request']);

    foreach ([$childA, $childB] as $child) {
        $workflow->connections()->create([
            'source_node_id' => $start->id,
            'target_node_id' => $child->id,
        ]);
    }

    (new ExecuteWorkflowNode($start->id, ['x' => 1]))->handle(app(WorkflowRunner::class));

    foreach ([$childA, $childB] as $child) {
        Queue::assertPushed(
            ExecuteWorkflowNode::class,
            fn (ExecuteWorkflowNode $job) => $job->nodeId === $child->id
                && $job->parameters === ['x' => 1],
        );
    }
});

test('executing a node marks it running during execute and idle afterwards', function () {
    Queue::fake();
    $node = WorkflowNode::factory()->create(['type' => 'agent', 'status' => 'idle']);

    $seen = [];
    WorkflowNode::saved(function (WorkflowNode $saved) use (&$seen, $node) {
        if ($saved->id === $node->id) {
            $seen[] = $saved->status;
        }
    });

    (new ExecuteWorkflowNode($node->id, []))->handle(app(WorkflowRunner::class));

    expect($seen)->toBe(['running', 'idle'])
        ->and($node->fresh()->status)->toBe('idle');
});

test('executing a node broadcasts its running then idle status', function () {
    Queue::fake();
    Event::fake([WorkflowNodeStatusChanged::class]);
    $node = WorkflowNode::factory()->create(['type' => 'agent']);

    (new ExecuteWorkflowNode($node->id, []))->handle(app(WorkflowRunner::class));

    foreach (['running', 'idle'] as $status) {
        Event::assertDispatched(
            WorkflowNodeStatusChanged::class,
            fn (WorkflowNodeStatusChanged $event) => $event->nodeId === $node->id
                && $event->workflowId === $node->workflow_id
                && $event->status === $status,
        );
    }
});

test('executing a leaf node starts no further jobs', function () {
    Queue::fake();
    $node = WorkflowNode::factory()->create(['type' => 'agent']);

    (new ExecuteWorkflowNode($node->id, []))->handle(app(WorkflowRunner::class));

    Queue::assertNothingPushed();
});

test('the play button endpoint queues the start node', function () {
    Queue::fake();
    $admin = User::factory()->create(['is_admin' => true]);
    $workflow = Workflow::factory()->for($admin)->create();
    $start = WorkflowNode::factory()->for($workflow)->create(['type' => 'start']);

    $this->actingAs($admin)
        ->from(route('admin.workflows.show', $workflow))
        ->post(route('admin.workflows.nodes.run', [$workflow, $start]))
        ->assertRedirect(route('admin.workflows.show', $workflow));

    Queue::assertPushed(
        ExecuteWorkflowNode::class,
        fn (ExecuteWorkflowNode $job) => $job->nodeId === $start->id,
    );
});

test('a user cannot run another user\'s workflow node', function () {
    Queue::fake();
    $admin = User::factory()->create(['is_admin' => true]);
    $workflow = Workflow::factory()->create();
    $start = WorkflowNode::factory()->for($workflow)->create(['type' => 'start']);

    $this->actingAs($admin)
        ->post(route('admin.workflows.nodes.run', [$workflow, $start]))
        ->assertForbidden();

    Queue::assertNothingPushed();
});
