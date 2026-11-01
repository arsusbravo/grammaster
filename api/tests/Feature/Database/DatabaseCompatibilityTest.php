<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

/*
 * The dev server runs MariaDB 5.5, which has no json type and allows at most 767 bytes per index key part (191
 * characters in utf8mb4). These tests keep new migrations from breaking that, whatever database the tests run on.
 */

it('uses no json columns', function (): void {
    $json = [];

    foreach (Schema::getTables() as $table) {
        foreach (Schema::getColumns($table['name']) as $column) {
            if (in_array($column['type_name'], ['json', 'jsonb'], true)) {
                $json[] = $table['name'].'.'.$column['name'];
            }
        }
    }

    expect($json)->toBe([]);
});

it('keeps every indexed string column within 191 characters', function (): void {
    $tooLong = [];

    foreach (Schema::getTables() as $table) {
        $columns = collect(Schema::getColumns($table['name']))->keyBy('name');

        foreach (Schema::getIndexes($table['name']) as $index) {
            foreach ($index['columns'] as $name) {
                $column = $columns[$name];

                if (in_array($column['type_name'], ['varchar', 'char'], true)
                    && preg_match('/\((\d+)\)/', $column['type'], $match)
                    && (int) $match[1] > 191) {
                    $tooLong[] = $table['name'].'.'.$name.' ('.$match[1].')';
                }
            }
        }
    }

    expect($tooLong)->toBe([]);
});
