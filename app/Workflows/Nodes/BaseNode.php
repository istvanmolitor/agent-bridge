<?php

namespace App\Workflows\Nodes;

use App\Models\WorkflowNode;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Base class for every workflow node type.
 *
 * Each concrete type (e.g. StartNode, AgentNode, HttpRequestNode) extends this
 * class and describes itself through the static accessors below. The type key
 * returned by {@see key()} is what gets persisted on the workflow_nodes.type
 * column and is used to resolve the class back through the NodeRegistry.
 *
 * An instance is bound to a concrete {@see WorkflowNode} and knows how to
 * {@see execute()} the work that node represents.
 */
abstract class BaseNode
{
    public function __construct(
        public readonly WorkflowNode $node,
    ) {}

    /**
     * Run the node's work. The returned array becomes the parameters passed to
     * each of its child nodes.
     *
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    abstract public function execute(array $parameters): array;

    /**
     * Validation rules for the incoming parameters. Override in a node type to
     * reject input it cannot work with before {@see execute()} runs.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Custom validation messages for {@see rules()}.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * Validation rules for the node's persisted settings (keyed by the setting
     * name, e.g. "prompt_id"). Override in a node type that stores editable
     * configuration on the {@see WorkflowNode::$settings} column.
     *
     * @return array<string, mixed>
     */
    public static function settingsRules(): array
    {
        return [];
    }

    /**
     * Default map of input roles to their human label and the parameter key
     * each one reads from. The role is the fixed name the node's code uses
     * internally; the label is shown in the editor (localised); the key is the
     * (overridable) key it is looked up under in the incoming parameters.
     *
     * Override in a node type that consumes named inputs, e.g.
     * ['prompt' => ['label' => 'Prompt', 'key' => 'prompt']].
     *
     * @return array<string, array{label: string, key: string}>
     */
    public static function inputs(): array
    {
        return [];
    }

    /**
     * Default map of output roles to their label and the parameter key each one
     * is written under. Mirrors {@see inputs()} for the values a node hands
     * downstream, e.g. ['response' => ['label' => 'Válasz', 'key' => 'response']].
     *
     * @return array<string, array{label: string, key: string}>
     */
    public static function outputs(): array
    {
        return [];
    }

    /**
     * The parameter key this node reads the given input role from. Honours an
     * override stored on the node's settings and falls back to the type default
     * (and finally to the role name itself).
     */
    public function inputKey(string $role): string
    {
        return $this->resolveKey($role, 'inputs', static::inputs());
    }

    /**
     * The parameter key this node writes the given output role under. The
     * output counterpart of {@see inputKey()}.
     */
    public function outputKey(string $role): string
    {
        return $this->resolveKey($role, 'outputs', static::outputs());
    }

    /**
     * Resolve the effective key for a role: a non-empty override on the node's
     * settings wins, then the type default, then the role name itself.
     *
     * @param  array<string, array{label: string, key: string}>  $defaults
     */
    private function resolveKey(string $role, string $bag, array $defaults): string
    {
        $override = $this->node->settings[$bag][$role] ?? null;

        if (is_string($override) && $override !== '') {
            return $override;
        }

        $default = $defaults[$role]['key'] ?? null;

        return is_string($default) && $default !== '' ? $default : $role;
    }

    /**
     * Validate the incoming parameters against {@see rules()}.
     *
     * @param  array<string, mixed>  $parameters
     *
     * @throws ValidationException
     */
    public function validate(array $parameters): void
    {
        Validator::make($parameters, $this->rules(), $this->messages())->validate();
    }

    /**
     * The unique, persisted type key for this node (e.g. "http_request").
     */
    abstract public static function key(): string;

    /**
     * Human-readable label shown in the editor.
     */
    abstract public static function label(): string;

    /**
     * Short hint describing what the node does.
     */
    public static function description(): string
    {
        return '';
    }

    /**
     * Lucide icon name used on the canvas and in the palette.
     */
    public static function icon(): string
    {
        return 'circle';
    }

    /**
     * Accent colour (hex) used to visually distinguish the type.
     */
    public static function color(): string
    {
        return '#6366f1';
    }

    /**
     * Whether the node runs on the queue. Lightweight nodes that only prepare
     * or forward data (e.g. the start and prompt nodes) override this to run
     * synchronously, so they execute at once instead of waiting on a worker.
     */
    public static function queued(): bool
    {
        return true;
    }

    /**
     * Whether the node accepts an incoming connection (a target handle).
     */
    public static function hasInput(): bool
    {
        return true;
    }

    /**
     * Whether the node exposes an outgoing connection (a source handle).
     */
    public static function hasOutput(): bool
    {
        return true;
    }

    /**
     * Serialise the type descriptor for the frontend.
     *
     * @return array{key: string, label: string, description: string, icon: string, color: string, hasInput: bool, hasOutput: bool, hasSettings: bool, inputs: array<string, array{label: string, key: string}>, outputs: array<string, array{label: string, key: string}>}
     */
    public static function toArray(): array
    {
        return [
            'key' => static::key(),
            'label' => static::label(),
            'description' => static::description(),
            'icon' => static::icon(),
            'color' => static::color(),
            'hasInput' => static::hasInput(),
            'hasOutput' => static::hasOutput(),
            'hasSettings' => static::settingsRules() !== [],
            'inputs' => static::inputs(),
            'outputs' => static::outputs(),
        ];
    }
}
