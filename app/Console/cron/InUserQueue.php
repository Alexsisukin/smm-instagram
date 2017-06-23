<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 28.05.17
 * Time: 0:50
 */

namespace App\Console\cron;


class InUserQueue
{
    public $command = [
        'search_target' => [
            'command' => 'php {patch}/artisan command:InstaSearchFriends {in_id} {ProcessRunInUser{in_id}} >> /dev/null 2>&1 &',
            'interval' => 60 * 60 * 3
        ],
        'follow_like' => [
            'command' => 'php {patch}/artisan command:InstaLikeAndFollow {in_id} {ProcessRunInUser{in_id}} >> /dev/null 2>&1 &',
            'interval' => 59
        ]
    ];

    public function __construct()
    {
        $this->InUsersIterator();
        exit();
    }

    public function InUsersIterator()
    {
        foreach ($this->getInUser() as $in_users) {
            /** если в у юзера ни чего не работает запускаем работу */
            if (!$this->CheckUserRun($in_users->id)) {
                $process = $this->getQueue($in_users->id);
                if (array_key_exists($process, $this->command)) {
                    /*if (!$this->ProcessInterval('1', $process)){
                        // TODO проверить метод
                        continue;
                    }*/
                    $exec = str_replace('{patch}', base_path(), $this->command[$process]['command']);
                    $exec = str_replace('{in_id}', $in_users->id, $exec);
                    exec($exec);
                }
            }
        }
    }

    public function getInUser()
    {
        return \DB::table('in_user')->get()->toArray();
    }

    public function getQueue($in_id)
    {
        $queues = \DB::table('process_queue')
            ->where('in_user', $in_id)
            ->orderBy('last_use', 'asc')->limit(1)->get()->toArray();
        return $queues[0]->process_type;
    }

    public function CheckUserRun($in_id)
    {
        $pattern = 'ps -xw | grep "{ProcessRunInUser' . $in_id . '}$"';
        exec($pattern, $res);
        return empty($res) ? false : true;
    }

    public function ProcessInterval($time, $process)
    {
        return (time() - strtotime($time)< $this->command[$process]['interval'])?false:true;
    }
}