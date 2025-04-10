<?php

namespace Nikunj\DbSchemaOptimizer;

use Illuminate\Support\Facades\DB;

class SchemaOptimizer
{
    public function analyze()
    {
        // Fetch all tables from the database
        $tables = DB::select('SHOW TABLES');
        $database = env('DB_DATABASE');
        $results = [];

        foreach ($tables as $table) {
            $tableName = array_values((array)$table)[0];
            $columns = DB::select("SHOW COLUMNS FROM {$tableName}");
            $indexes = DB::select("SHOW INDEXES FROM {$tableName}");

            $results[$tableName] = $this->analyzeTable($tableName, $columns, $indexes);
        }

        return $this->formatResults($results);
    }

    private function analyzeTable($tableName, $columns, $indexes)
    {
        $suggestions = [];

        // Example checks
        foreach ($columns as $column) {
            $field = $column->Field;
            $type = $column->Type;
            $null = $column->Null;
            $key = $column->Key;
            $default = $column->Default;
            $extra = $column->Extra;

            // Check for missing indexes on likely query columns (e.g., 'id', foreign keys)
            if (str_contains($field, 'id') && !$this->hasIndex($field, $indexes)) {
                $suggestions[] = [
                    'issue' => "Column '$field' lacks an index.",
                    'suggestion' => "Add an index to '$field' to improve query performance.",
                    'update' => "ALTER TABLE $tableName ADD INDEX idx_$field ($field);"
                ];
            }

            // Check for inefficient data types
            if (str_contains($type, 'text') && !$this->isTextNecessary($field)) {
                $suggestions[] = [
                    'issue' => "Column '$field' uses TEXT type unnecessarily.",
                    'suggestion' => "Consider using VARCHAR(255) for shorter strings.",
                    'update' => "ALTER TABLE $tableName MODIFY $field VARCHAR(255);"
                ];
            }

            // Check for columns that could benefit from NOT NULL constraints
            if ($null === 'YES' && $default === null) {
                $suggestions[] = [
                    'issue' => "Column '$field' allows NULL values without a default.",
                    'suggestion' => "Consider adding a NOT NULL constraint or a default value.",
                    'update' => "ALTER TABLE $tableName MODIFY $field {$type} NOT NULL;"
                ];
            }

            // Check for columns that could benefit from default values
            if ($null === 'YES' && $default === null && $extra !== 'auto_increment') {
                $suggestions[] = [
                    'issue' => "Column '$field' allows NULL values without a default.",
                    'suggestion' => "Consider adding a default value.",
                    'update' => "ALTER TABLE $tableName MODIFY $field {$type} DEFAULT 'default_value';"
                ];
            }
        }

        // Check for missing primary keys
        $hasPrimaryKey = false;
        foreach ($indexes as $index) {
            if ($index->Key_name === 'PRIMARY') {
                $hasPrimaryKey = true;
                break;
            }
        }
        if (!$hasPrimaryKey) {
            $suggestions[] = [
                'issue' => "Table '$tableName' does not have a primary key.",
                'suggestion' => "Consider adding a primary key.",
                'update' => "ALTER TABLE $tableName ADD PRIMARY KEY (column_name);"
            ];
        }

        return $suggestions;
    }

    private function hasIndex($column, $indexes)
    {
        foreach ($indexes as $index) {
            if ($index->Column_name === $column) {
                return true;
            }
        }
        return false;
    }

    private function isTextNecessary($field)
    {
        // Placeholder logic: Assume 'description' or 'content' fields need TEXT
        return in_array($field, ['description', 'content']);
    }

    private function formatResults($results)
    {
        $output = "<table border='1'><tr><th>Table</th><th>Issue</th><th>Suggestion</th><th>SQL Update</th></tr>";
        foreach ($results as $table => $suggestions) {
            foreach ($suggestions as $suggestion) {
                $output .= "<tr>";
                $output .= "<td>{$table}</td>";
                $output .= "<td>{$suggestion['issue']}</td>";
                $output .= "<td>{$suggestion['suggestion']}</td>";
                $output .= "<td><code>{$suggestion['update']}</code></td>";
                $output .= "</tr>";
            }
        }
        $output .= "</table>";

        return $output;
    }
}
