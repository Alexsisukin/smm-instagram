<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class InstaSearchFriends extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:InstaSearchFriends {in_user} {work?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Поиск потенцильных друзей в инстаграмме для конкретного пользователя';

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
        new \App\Console\cron\InstaSearchFriends($this->argument('in_user'));
    }
}
