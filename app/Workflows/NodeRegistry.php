<?php

namespace App\Workflows;

use App\Workflows\Nodes\AgentNode;
use App\Workflows\Nodes\BaseNode;
use App\Workflows\Nodes\HttpRequestNode;
use App\Workflows\Nodes\IntervalNode;
use App\Workflows\Nodes\LogNode;
use App\Workflows\Nodes\PromptNode;
use App\Workflows\Nodes\StartInputNode;
use App\Workflows\Nodes\StartNode;

/**
 * Central registry of the available workflow node types.
 *
 * Register new node types by adding their class to {@see TYPES}.
 */
class NodeRegistry
{
    /**
     * @var list<class-string<BaseNode>>
     */
    protected const TYPES = [
        StartNode::class,
        StartInputNode::class,
        IntervalNode::class,
        PromptNode::class,
        AgentNode::class,
        HttpRequestNode::class,
        LogNode::class,
    ];

    /**
     * All registered node type classes.
     *
     * @return list<class-string<BaseNode>>
     */
    public static function all(): array
    {
        return self::TYPES;
    }

    /**
     * The valid type keys, e.g. ['start', 'agent', 'http_request'].
     *
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_map(fn (string $type): string => $type::key(), self::TYPES);
    }

    /**
     * Resolve the class backing a type key, or null when unknown.
     *
     * @return class-string<BaseNode>|null
     */
    public static function classFor(string $key): ?string
    {
        foreach (self::TYPES as $type) {
            if ($type::key() === $key) {
                return $type;
            }
        }

        return null;
    }

    /**
     * Serialise every type descriptor for the editor palette.
     *
     * @return list<array{key: string, label: string, description: string, icon: string, color: string, hasInput: bool, hasOutput: bool}>
     */
    public static function options(): array
    {
        return array_map(fn (string $type): array => $type::toArray(), self::TYPES);
    }
}
