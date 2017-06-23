<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 28.05.17
 * Time: 18:08
 */

namespace App\Console\cron;


use InstagramAPI\Exception\EmptyResponseException;
use InstagramAPI\Exception\EndpointException;
use Mockery\Exception;

class InstaLikeAndFollow
{
    private $in_user;
    private $inst;
    private $process_id;
    private $process_row;
    private $timeout = 60;

    public function __construct($in_user)
    {
        $this->in_user = $in_user;
        $this->process_id = getmypid();
        $this->start();
        $target_users = $this->getFollowQueue();
        if (!empty($target_users)) {
            $user = $this->getUserInfo();
            $this->inst = \App\Instagram::Login($user->in_name, $user->in_pass, $user->proxy, 'follow_like');
            foreach ($target_users as $target_user) {

                if ($target_user->is_private == 0) {
                    $this->update_process();
                    if ($this->like($target_user)) {
                        $this->update_status('in_like', $target_user->id);
                    }
                }
                $this->update_process();
                if ($this->follow($target_user)) {
                    $this->update_status('follow', $target_user->id);
                }
            }
        }

    }

    public function __destruct()
    {
        \DB::table('process_queue')
            ->where('process_type', '=', 'follow_like')
            ->where('in_user', '=', $this->in_user)
            ->update(['last_use' => date('Y-m-d H:i:s')]);
        \DB::table('process')
            ->where('process_id', '=', $this->process_id)
            ->where('id', '=', $this->process_row)
            ->update(['dt_end' => date('Y-m-d H:i:s')]);
    }

    private function update_status($action, $row_id)
    {
        \DB::table('in_user_search')->where('id', '=', $row_id)->update([
            $action => 1
        ]);
    }

    private function follow($target_user)
    {
        $res = false;
        try {
            $this->inst->follow($target_user->pk);
            $this->sleep('follow', $target_user->id);
            $res = true;
        } catch (EmptyResponseException $e) {
            echo $e->getMessage() . "\n";
        }
        return $res;
    }

    private function like($target_user)
    {
        if (empty($target_user->profile_pic_id)) {
            return false;
        }
        $res = false;
        try {
            $this->inst->like($target_user->profile_pic_id);
            $this->sleep('like', $target_user->id);
            $res = true;
        } catch (EndpointException $e) {
            echo $e->getMessage() . "\n";
        }
        return $res;
    }

    private function writeStatistic($action, $owner)
    {
        \DB::insert("INSERT INTO `statistic_action`(`in_user_id`, `queue_id`, `action`) VALUES (?,?,?)", [
            $this->in_user,
            $owner,
            $action
        ]);
    }

    public function update_process()
    {
        \DB::table('process')
            ->where('process_id', '=', $this->process_id)
            ->where('id', '=', $this->process_row)
            ->update(['dt_update' => date('Y-m-d H:i:s')]);
    }

    private function sleep($action, $owner)
    {
        $time = time();
        $this->writeStatistic($action, $owner);
        $this->update_process();
        $wait = time() - $time;
        if ($wait < $this->timeout) {
            $sleep = $this->timeout - $wait;
            sleep($sleep);
        }
    }

    public function getUserInfo()
    {
        $user = \DB::table('in_user')
            ->join('proxy', 'proxy.proxy_id', '=', 'in_user.proxy_id')
            ->where('id', $this->in_user)->get()->toArray();
        return $user['0'];
    }

    private function getFollowQueue()
    {

        return \DB::table('in_user_search')
            ->where('in_user', $this->in_user)
            ->where('first_search', '=', 0)
            ->where('follow', '=', 0)
            ->get()->toArray();
    }

    public function start()
    {
        \DB::insert("INSERT INTO `process`( process_id, in_user, process_type, dt_create, dt_update, dt_end) 
                                                                VALUES (?,?,'follow_like',NOW(),NOW(),NULL )",
            [
                $this->process_id,
                $this->in_user
            ]);
        $this->process_row = \DB::getPdo()->lastInsertId();
    }

}