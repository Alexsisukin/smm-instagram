<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
///[22:09:04] Сергей Василенко: familytriprnd
//F@milyTrip
class InstaLikeAndFollow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:InstaLikeAndFollow {in_user} {work?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        new \App\Console\cron\InstaLikeAndFollow($this->argument('in_user'));
    }
}
