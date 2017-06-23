<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class InUserQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:InUserQueue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Запускает актуальную задачу для учетки инстаграмм если учетка не занята';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        new \App\Console\cron\InUserQueue();
    }
}
