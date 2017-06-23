<?php

namespace App\Console\Commands;

use App\Instagram;
use Mockery\Exception;
use Illuminate\Console\Command;

class InstaUserInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:InstaUserInfo';

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
        $insta = Instagram::Login('alexander_sisukin', 'zevs789', 'http://mG6aEV:neumpV@185.183.162.214:16501');
        $a = $insta->getUserInfoById('4515757830');
        //file_put_contents('/home/alex/test/info.txt', print_r($a , true));
    }
}
