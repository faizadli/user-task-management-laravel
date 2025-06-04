<?php

namespace App\Console\Commands;

use App\Services\TaskService;
use Illuminate\Console\Command;

class CheckOverdueTasks extends Command
{
    protected $signature = 'tasks:check-overdue';
    protected $description = 'Check for overdue tasks and log them';
    
    protected $taskService;
    
    public function __construct(TaskService $taskService)
    {
        parent::__construct();
        $this->taskService = $taskService;
    }

    public function handle()
    {
        $count = $this->taskService->checkOverdueTasks();
        
        $this->info("Checked overdue tasks. Found {$count} overdue tasks.");
        
        return 0;
    }
}