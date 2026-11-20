<?php

namespace App\Workflows\Nodes;

use App\Models\WorkflowNode;

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
     * @return array{key: string, label: string, description: string, icon: string, color: string, hasInput: bool, hasOutput: bool}
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
        ];
    }
}
