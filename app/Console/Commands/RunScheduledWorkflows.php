<?php

namespace App\Console\Commands;

use App\Models\WorkflowNode;
use App\Workflows\Nodes\IntervalNode;
use App\Workflows\WorkflowRunner;
use Illuminate\Console\Command;

class RunScheduledWorkflows extends Command
{
    /**
     * @var string
     */
    protected $signature = 'workflows:run-scheduled';

    /**
     * @var string
     */
    protected $description = 'Fire every interval node whose configured interval has elapsed.';

    /**
     * Run the due interval nodes. Each fires its workflow and records the time
     * so the next run waits out its full interval again.
     */
    public function handle(WorkflowRunner $runner): int
    {
        $nodes = WorkflowNode::query()
            ->where('type', IntervalNode::key())
            ->get();

        $fired = 0;

        foreach ($nodes as $node) {
            if (! $this->isDue($node)) {
                continue;
            }

            // Stamp the run time before firing so a long or failing run never
            // causes the same node to be picked up twice in a row.
            $node->forceFill(['last_run_at' => now()])->save();

            $runner->run($node);
            $fired++;
        }

        $this->info("Fired {$fired} scheduled workflow(s).");

        return self::SUCCESS;
    }

    /**
     * Whether a node is active and its interval has elapsed since the last run.
     */
    private function isDue(WorkflowNode $node): bool
    {
        if (($node->settings['enabled'] ?? true) === false) {
            return false;
        }

        $interval = $node->settings['interval_minutes'] ?? null;

        if (! is_int($interval) || $interval < 1) {
            return false;
        }

        return $node->last_run_at === null
            || $node->last_run_at->copy()->addMinutes($interval)->isPast();
    }
}
