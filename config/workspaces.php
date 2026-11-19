<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Workspace base directory
    |--------------------------------------------------------------------------
    |
    | Every workspace lives in a sub-folder of this base directory. The user
    | only provides a relative folder name; the absolute path is derived from
    | the base so host paths (e.g. /home/user/...) can never be entered by
    | mistake. Inside the Sail container this resolves to the mounted project
    | root, so /var/www/html/projects maps to ./projects on the host.
    |
    */

    'base' => env('WORKSPACES_BASE', base_path('projects')),

];
