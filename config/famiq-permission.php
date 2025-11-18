<?php

declare(strict_types=1);

return [
    'user_model' => App\Models\User::class,
    'project_model' => App\Models\Project::class,

    'tables' => [
        'users' => 'users',
        'projects' => 'projects',
    ],

    'table_prefix' => 'fp_',

    'use_foreign_keys' => true,
];
