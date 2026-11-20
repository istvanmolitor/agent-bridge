<?php

use App\Models\User;
use App\Models\Workflow;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (string) $user->id === (string) $id;
});

// Only the workflow's owner may listen to its live node-status updates.
Broadcast::channel('workflows.{workflow}', function (User $user, Workflow $workflow) {
    return $user->can('view', $workflow);
});
