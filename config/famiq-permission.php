<?php

declare(strict_types=1);

return [
    'user_model' => App\Models\User::class,

    'tables' => [
        'users' => 'users',
    ],

    'table_prefix' => 'fp_',

    'use_foreign_keys' => true,
];
