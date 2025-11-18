<?php

declare(strict_types=1);

if (! function_exists('famiq_permission_table_name')) {
    function famiq_permission_table_name(string $table): string
    {
        $prefix = config('famiq-permission.table_prefix', 'fp_');

        return $prefix.$table;
    }
}
