<?php

use App\Events\WorkflowNodeStatusChanged;
use App\Jobs\ExecuteWorkflowNode;
use App\Models\Agent;
use App\Models\Prompt;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowNode;
use App\Models\Workspace;
use App\Services\ClaudeCli;
use App\Workflows\Nodes\StartNode;
use App\Workflows\WorkflowRunner;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
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

test('the prompt node outputs the configured prompt under the prompt key', function () {
    $prompt = Prompt::factory()->create(['content' => 'Write a haiku.']);
    $node = WorkflowNode::factory()->create([
        'type' => 'prompt',
        'settings' => ['prompt_id' => $prompt->id],
    ]);

    expect($node->resolveType()->execute(['foo' => 'bar']))
        ->toBe(['foo' => 'bar', 'prompt' => 'Write a haiku.']);
});

test('the prompt node outputs an empty prompt when none is configured', function () {
    $node = WorkflowNode::factory()->create(['type' => 'prompt', 'settings' => null]);

    expect($node->resolveType()->execute([]))->toBe(['prompt' => '']);
});

test('the prompt node feeds the downstream agent its prompt', function () {
    Queue::fake();
    $prompt = Prompt::factory()->create(['content' => 'Summarise this.']);
    $workflow = Workflow::factory()->create();
    $promptNode = WorkflowNode::factory()->for($workflow)->create([
        'type' => 'prompt',
        'settings' => ['prompt_id' => $prompt->id],
    ]);
    $agent = WorkflowNode::factory()->for($workflow)->create(['type' => 'agent']);
    $workflow->connections()->create([
        'source_node_id' => $promptNode->id,
        'target_node_id' => $agent->id,
    ]);

    (new ExecuteWorkflowNode($promptNode->id))->handle(app(WorkflowRunner::class));

    Queue::assertPushed(
        ExecuteWorkflowNode::class,
        fn (ExecuteWorkflowNode $job) => $job->nodeId === $agent->id
            && $job->parameters === ['prompt' => 'Summarise this.'],
    );
});

test('the log node writes the parameters it received to the log file and forwards them', function () {
    Log::spy();
    $node = WorkflowNode::factory()->create(['type' => 'log', 'settings' => null]);

    $result = $node->resolveType()->execute(['prompt' => 'Hi', 'response' => 'Yo']);

    expect($result)->toBe(['prompt' => 'Hi', 'response' => 'Yo']);
    Log::shouldHaveReceived('info')
        ->once()
        ->withArgs(fn (string $message, array $context): bool => $context['parameters'] === ['prompt' => 'Hi', 'response' => 'Yo']);
});

test('the log node records the received parameters on the node so the canvas shows them', function () {
    $node = WorkflowNode::factory()->create(['type' => 'log', 'settings' => null]);

    $node->resolveType()->execute(['prompt' => 'Hi', 'response' => 'Yo']);

    $output = $node->fresh()->output;
    expect($output)->toBeString()
        ->and(json_decode($output, true))->toBe(['prompt' => 'Hi', 'response' => 'Yo']);
});

test('the log node records its output even when the log file is off', function () {
    $node = WorkflowNode::factory()->create(['type' => 'log', 'settings' => ['to_file' => false]]);

    $node->resolveType()->execute(['response' => 'Visible']);

    expect(json_decode((string) $node->fresh()->output, true))->toBe(['response' => 'Visible']);
});

test('the log node skips the log file when the to_file setting is off', function () {
    Log::spy();
    $node = WorkflowNode::factory()->create(['type' => 'log', 'settings' => ['to_file' => false]]);

    $result = $node->resolveType()->execute(['prompt' => 'Hi']);

    expect($result)->toBe(['prompt' => 'Hi']);
    Log::shouldNotHaveReceived('info');
});

test('the agent node runs the configured agent and returns its answer under the response key', function () {
    $agent = Agent::factory()->create();
    $workspace = Workspace::factory()->create();
    $node = WorkflowNode::factory()->create([
        'type' => 'agent',
        'settings' => ['agent_id' => $agent->id, 'workspace_id' => $workspace->id],
    ]);

    $this->mock(ClaudeCli::class)
        ->shouldReceive('run')
        ->once()
        ->with('Hello', Mockery::on(fn (array $options): bool => $options['workdir'] === $workspace->path
            && $options['systemPrompt'] === $agent->system_prompt))
        ->andReturn([
            'success' => true,
            'result' => 'Hi there',
            'error' => null,
            'session_id' => null,
            'raw' => null,
        ]);

    expect($node->resolveType()->execute(['prompt' => 'Hello']))
        ->toBe(['prompt' => 'Hello', 'response' => 'Hi there']);
});

test('the agent node returns an empty response when no agent or workspace is configured', function () {
    $node = WorkflowNode::factory()->create(['type' => 'agent', 'settings' => null]);

    expect($node->resolveType()->execute(['prompt' => 'Hello']))
        ->toBe(['prompt' => 'Hello', 'response' => '']);
});

test('updating an agent node persists the chosen agent and workspace in its settings', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $workflow = Workflow::factory()->for($admin)->create();
    $agent = Agent::factory()->for($admin)->create();
    $workspace = Workspace::factory()->for($admin)->create();
    $node = WorkflowNode::factory()->for($workflow)->create([
        'type' => 'agent',
        'name' => 'Agent',
    ]);

    $this->actingAs($admin)
        ->from(route('admin.workflows.show', $workflow))
        ->put(route('admin.workflows.nodes.update', [$workflow, $node]), [
            'name' => 'Agent',
            'description' => null,
            'settings' => ['agent_id' => $agent->id, 'workspace_id' => $workspace->id],
        ])
        ->assertRedirect(route('admin.workflows.show', $workflow));

    expect($node->fresh()->settings)
        ->toBe(['agent_id' => $agent->id, 'workspace_id' => $workspace->id]);
});

test('updating an agent node rejects a missing or unknown agent and workspace', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $workflow = Workflow::factory()->for($admin)->create();
    $node = WorkflowNode::factory()->for($workflow)->create(['type' => 'agent']);

    $this->actingAs($admin)
        ->from(route('admin.workflows.show', $workflow))
        ->put(route('admin.workflows.nodes.update', [$workflow, $node]), [
            'name' => 'Agent',
            'settings' => ['agent_id' => 999999, 'workspace_id' => 999999],
        ])
        ->assertSessionHasErrors(['settings.agent_id', 'settings.workspace_id']);
});

test('the runner queues the node id and parameters', function () {
    Queue::fake();
    $node = WorkflowNode::factory()->create(['type' => 'http_request']);

    app(WorkflowRunner::class)->run($node, ['url' => 'https://example.com']);

    Queue::assertPushed(
        ExecuteWorkflowNode::class,
        fn (ExecuteWorkflowNode $job) => $job->nodeId === $node->id
            && $job->parameters === ['url' => 'https://example.com'],
    );
});

test('an immediate node type runs synchronously instead of queueing', function () {
    Queue::fake();
    $node = WorkflowNode::factory()->create(['type' => 'start']);

    $queued = app(WorkflowRunner::class)->run($node, ['foo' => 'bar']);

    expect($queued)->toBeTrue();
    // Start runs off the queue, so nothing is pushed for it.
    Queue::assertNotPushed(
        ExecuteWorkflowNode::class,
        fn (ExecuteWorkflowNode $job) => $job->nodeId === $node->id,
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

    $parameters = ['prompt' => 'Hello', 'url' => 'https://example.com'];

    (new ExecuteWorkflowNode($start->id, $parameters))->handle(app(WorkflowRunner::class));

    foreach ([$childA, $childB] as $child) {
        Queue::assertPushed(
            ExecuteWorkflowNode::class,
            fn (ExecuteWorkflowNode $job) => $job->nodeId === $child->id
                && $job->parameters === $parameters,
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

    (new ExecuteWorkflowNode($node->id, ['prompt' => 'Hello']))->handle(app(WorkflowRunner::class));

    expect($seen)->toBe(['running', 'idle'])
        ->and($node->fresh()->status)->toBe('idle');
});

test('executing a node broadcasts its running then idle status', function () {
    Queue::fake();
    Event::fake([WorkflowNodeStatusChanged::class]);
    $node = WorkflowNode::factory()->create(['type' => 'agent']);

    (new ExecuteWorkflowNode($node->id, ['prompt' => 'Hello']))->handle(app(WorkflowRunner::class));

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

    (new ExecuteWorkflowNode($node->id, ['prompt' => 'Hello']))->handle(app(WorkflowRunner::class));

    Queue::assertNothingPushed();
});

test('the play button endpoint runs the immediate start chain and queues the agent', function () {
    Queue::fake();
    $admin = User::factory()->create(['is_admin' => true]);
    $workflow = Workflow::factory()->for($admin)->create();
    $prompt = Prompt::factory()->for($admin)->create(['content' => 'Do the thing.']);

    $start = WorkflowNode::factory()->for($workflow)->create(['type' => 'start']);
    $promptNode = WorkflowNode::factory()->for($workflow)->create([
        'type' => 'prompt',
        'settings' => ['prompt_id' => $prompt->id],
    ]);
    $agent = WorkflowNode::factory()->for($workflow)->create(['type' => 'agent']);

    $workflow->connections()->create(['source_node_id' => $start->id, 'target_node_id' => $promptNode->id]);
    $workflow->connections()->create(['source_node_id' => $promptNode->id, 'target_node_id' => $agent->id]);

    $this->actingAs($admin)
        ->from(route('admin.workflows.show', $workflow))
        ->post(route('admin.workflows.nodes.run', [$workflow, $start]))
        ->assertRedirect(route('admin.workflows.show', $workflow));

    // Start and prompt ran synchronously; only the agent reached the queue,
    // carrying the prompt the prompt node produced.
    Queue::assertNotPushed(
        ExecuteWorkflowNode::class,
        fn (ExecuteWorkflowNode $job) => $job->nodeId === $start->id || $job->nodeId === $promptNode->id,
    );
    Queue::assertPushed(
        ExecuteWorkflowNode::class,
        fn (ExecuteWorkflowNode $job) => $job->nodeId === $agent->id
            && $job->parameters === ['prompt' => 'Do the thing.'],
    );
});

test('the play button endpoint shows the validation error and queues nothing for invalid input', function () {
    Queue::fake();
    $admin = User::factory()->create(['is_admin' => true]);
    $workflow = Workflow::factory()->for($admin)->create();
    $agent = WorkflowNode::factory()->for($workflow)->create(['type' => 'agent']);

    $this->actingAs($admin)
        ->from(route('admin.workflows.show', $workflow))
        ->post(route('admin.workflows.nodes.run', [$workflow, $agent]))
        ->assertRedirect(route('admin.workflows.show', $workflow))
        ->assertInertiaFlash('toast.type', 'error')
        ->assertInertiaFlash('toast.message', __('validation.required', ['attribute' => 'prompt']));

    expect($agent->fresh()->status)->toBe('failed');
    Queue::assertNothingPushed();
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

test('the runner does not queue a node with invalid input and marks it failed', function () {
    Queue::fake();
    $node = WorkflowNode::factory()->create(['type' => 'http_request', 'status' => 'idle']);

    $queued = app(WorkflowRunner::class)->run($node, ['url' => 'not-a-url']);

    expect($queued)->toBeFalse();
    $node->refresh();
    expect($node->status)->toBe('failed')
        ->and($node->error)->toBe(__('validation.url', ['attribute' => 'url']));
    Queue::assertNothingPushed();
});

test('the runner broadcasts the failed status of an invalid node', function () {
    Queue::fake();
    Event::fake([WorkflowNodeStatusChanged::class]);
    $node = WorkflowNode::factory()->create(['type' => 'http_request']);

    app(WorkflowRunner::class)->run($node, []);

    Event::assertDispatched(
        WorkflowNodeStatusChanged::class,
        fn (WorkflowNodeStatusChanged $event) => $event->nodeId === $node->id
            && $event->status === 'failed',
    );
});

test('the runner does not queue an agent node without a prompt', function () {
    Queue::fake();
    $node = WorkflowNode::factory()->create(['type' => 'agent']);

    app(WorkflowRunner::class)->run($node, ['foo' => 'bar']);

    $node->refresh();
    expect($node->status)->toBe('failed')
        ->and($node->error)->toBe(__('validation.required', ['attribute' => 'prompt']));
    Queue::assertNothingPushed();
});

test('the runner queues a valid node and clears its previous failure', function () {
    Queue::fake();
    $node = WorkflowNode::factory()->create([
        'type' => 'http_request',
        'status' => 'failed',
        'error' => 'Old error',
    ]);

    $queued = app(WorkflowRunner::class)->run($node, ['url' => 'https://example.com']);

    expect($queued)->toBeTrue();
    $node->refresh();
    expect($node->status)->toBe('idle')
        ->and($node->error)->toBeNull();
    Queue::assertPushed(ExecuteWorkflowNode::class, fn (ExecuteWorkflowNode $job) => $job->nodeId === $node->id);
});

test('updating a prompt node persists the chosen prompt in its settings', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $workflow = Workflow::factory()->for($admin)->create();
    $prompt = Prompt::factory()->for($admin)->create();
    $node = WorkflowNode::factory()->for($workflow)->create([
        'type' => 'prompt',
        'name' => 'Prompt',
    ]);

    $this->actingAs($admin)
        ->from(route('admin.workflows.show', $workflow))
        ->put(route('admin.workflows.nodes.update', [$workflow, $node]), [
            'name' => 'Prompt',
            'description' => null,
            'settings' => ['prompt_id' => $prompt->id],
        ])
        ->assertRedirect(route('admin.workflows.show', $workflow));

    expect($node->fresh()->settings)->toBe(['prompt_id' => $prompt->id]);
});

test('updating a prompt node rejects a missing or unknown prompt', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $workflow = Workflow::factory()->for($admin)->create();
    $node = WorkflowNode::factory()->for($workflow)->create(['type' => 'prompt']);

    $this->actingAs($admin)
        ->from(route('admin.workflows.show', $workflow))
        ->put(route('admin.workflows.nodes.update', [$workflow, $node]), [
            'name' => 'Prompt',
            'settings' => ['prompt_id' => 999999],
        ])
        ->assertSessionHasErrors('settings.prompt_id');
});

test('a child with invalid input is marked failed and not queued', function () {
    Queue::fake();
    $workflow = Workflow::factory()->create();
    $start = WorkflowNode::factory()->for($workflow)->create(['type' => 'start']);
    $child = WorkflowNode::factory()->for($workflow)->create(['type' => 'agent']);
    $workflow->connections()->create([
        'source_node_id' => $start->id,
        'target_node_id' => $child->id,
    ]);

    (new ExecuteWorkflowNode($start->id, []))->handle(app(WorkflowRunner::class));

    expect($child->fresh()->status)->toBe('failed');
    Queue::assertNothingPushed();
});
