<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 28.05.17
 * Time: 16:59
 */

namespace App\Console\cron;

use InstagramAPI\Instagram;
use Mockery\Exception;
use Illuminate\Database\Schema\Blueprint;

class InstaSearchFriends
{
    private $in_user;
    private $inst;
    private $tmp_table;
    private $process_id;
    private $process_row;
    private $firs_search;

    public function __construct($in_user)
    {
        $this->process_id = getmypid();
        $this->in_user = $in_user;
        $this->start();
        $this->tmp_table = 'tmp_in_user_search_' . $in_user;
        $user_targets = $this->getUserTarget();
        if (!empty($user_targets)) {
            $user = $this->getUserInfo();
            $this->inst = \App\Instagram::Login($user->in_name, $user->in_pass, $user->proxy, 'search_target');
            $this->update_process();
            foreach ($user_targets as $target) {
                $new_followers = $this->getTargetFollower($target);
                $this->update_process();
                if (!empty($new_followers)) {
                    $this->saveFollowers($target, $new_followers);
                    $this->firs_search = $target->first_search ;
                    if ($target->first_search == 1) {
                        \DB::table('in_target')
                            ->where('friend_id', $target->friend_id)
                            ->where('in_user', $target->in_user)
                            ->update(['first_search' => 0]);
                    }
                }
            }

        }

    }

    public function start()
    {
        \DB::insert("INSERT INTO `process`( process_id, in_user, process_type, dt_create, dt_update, dt_end) 
                                                                VALUES (?,?,'search_target',NOW(),NOW(),NULL )",
            [
                $this->process_id,
                $this->in_user
            ]);
        $this->process_row = \DB::getPdo()->lastInsertId();
    }

    public function update_process()
    {
        \DB::table('process')
            ->where('process_id', '=', $this->process_id)
            ->where('id', '=', $this->process_row)
            ->update(['dt_update' => date('Y-m-d H:i:s')]);
    }

    public function __destruct()
    {
        \DB::table('process_queue')
            ->where('process_type', '=', 'search_target')
            ->where('in_user', '=', $this->in_user)
            ->update(['last_use' => date('Y-m-d H:i:s')]);
        \DB::table('process')
            ->where('process_id', '=', $this->process_id)
            ->where('id', '=', $this->process_row)
            ->update(['dt_end' => date('Y-m-d H:i:s')]);
    }

    public function saveFollowers($row_target, $followers)
    {
        $this->create_tmp_table();
        $i = 0;
        $j = 0;
        $all = count($followers);
        foreach ($followers as $follower) {
            $sql_array[] = [
                'in_user' => $row_target->in_user,
                'target_id' => $row_target->id,
                'pk' => $follower->pk,
                'username' => $follower->username,
                'full_name' => $follower->full_name,
                'is_private' => $follower->is_private,
                'profile_pic_url' => $follower->profile_pic_url,
                'profile_pic_id' => $follower->profile_pic_id,
                'first_search' => $row_target->first_search
            ];
            $i++;
            $j++;
            if ($i == 1000 || $j == $all) {
                $this->update_process();
                \DB::table($this->tmp_table)->insert($sql_array);
                $sql_array = [];
                $i = 0;
            }
        }
        $this->tmp_sort_user($this->in_user);
        $this->update_process();
        $this->tpl_drop_table();
    }

    public function getTargetFollower($row_target)
    {
        $followers = [];
        $maxId = null;
        $error_qty = 0;
        do {
            try {
                $response = $this->inst->getUserFollowers($row_target->friend_id, $maxId);
                sleep(1);
                $this->update_process();
                $followers = array_merge($followers, $response->getUsers());
                $maxId = $response->getNextMaxId();
                $last_page_follower = end($followers);
                $checkFollower = $this->checkFollower($last_page_follower->pk, $row_target->id);
                if ($checkFollower) {
                    $maxId = null;
                }
            } catch (Exception $e) {
                echo $e->getMessage() . "\n";
                if ($error_qty == 10) {
                    $maxId = null;
                }
            }
        } while ($maxId !== null);
        $this->update_process();
        return $followers;
    }

    public function checkFollower($follower_pk, $target_id)
    {
        if ($this->firs_search == 1){
            return false;
        }
        $res = \DB::table('in_user_search')
            ->where('in_user', '=', $this->in_user)
            ->where('pk', '=', $follower_pk)
            ->where('target_id', '=', $target_id)->get()->toArray();
        return empty($res) ? false : true;
    }

    public function getUserTarget()
    {
        return \DB::table('in_target')
            ->where('in_user', '=', $this->in_user)
            ->get()->toArray();
    }

    public function getUserInfo()
    {
        $user = \DB::table('in_user')
            ->join('proxy', 'proxy.proxy_id', '=', 'in_user.proxy_id')
            ->where('id', $this->in_user)->get()->toArray();
        return $user['0'];
    }

    private function create_tmp_table()
    {
        $this->tpl_drop_table();
        \Schema::create($this->tmp_table, function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('pk')->index();
            $table->integer('in_user')->index();
            $table->integer('target_id')->index();
            $table->string('username');
            $table->string('full_name');
            $table->string('is_private');
            $table->string('profile_pic_url');
            $table->string('profile_pic_id')->nullable();
            $table->integer('first_search');
            $table->timestamps();
        });
    }

    private function tpl_drop_table()
    {
        \Schema::dropIfExists($this->tmp_table);
    }

    private function tmp_sort_user($in_user)
    {
        \DB::statement("DELETE FROM `" . $this->tmp_table . "` 
              WHERE pk in (SELECT pk FROM in_user_search WHERE in_user=$in_user)");
        \DB::statement("INSERT INTO `in_user_search` 
          (`in_user`, `target_id`, `pk`, `username`, `full_name`, `is_private`, `profile_pic_url`, `profile_pic_id`, `first_search`)  
SELECT 
t.`in_user`, t.`target_id`, t.`pk`, t.`username`, t.`full_name`, t.`is_private`, t.`profile_pic_url`, t.`profile_pic_id`, t.`first_search`
FROM `" . $this->tmp_table . "` t WHERE 1");
    }
}