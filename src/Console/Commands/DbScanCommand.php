<?php

namespace Nikunj\DbSchemaOptimizer\Console\Commands;

use Illuminate\Console\Command;
use Nikunj\DbSchemaOptimizer\SchemaOptimizer;

class DbScanCommand extends Command
{
    protected $signature = 'db:scan';
    protected $description = 'Scan the database schema for optimizations';

    protected $schemaOptimizer;

    public function __construct(SchemaOptimizer $schemaOptimizer)
    {
        parent::__construct();
        $this->schemaOptimizer = $schemaOptimizer;
    }

    public function handle()
    {
        $results = $this->schemaOptimizer->analyze();
        $this->info('Database schema analysis completed.');
        $this->line($results);
    }
}
