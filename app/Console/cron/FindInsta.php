<?php

/**
 * Created by PhpStorm.
 * User: alex
 * Date: 22.05.17
 * Time: 23:57
 */

namespace App\Console\cron;


use Illuminate\Support\Facades\DB;
use InstagramAPI\Instagram;
use Illuminate\Database\Schema\Blueprint;

class FindInsta
{
    private $user;
    private $inst;

    public function __construct()
    {
        if ($this->in_user()) {
            $this->inst = new Instagram(false, false,
                [
                    'storage' => 'file',
                    'basefolder' => base_path() . '/storage/inst/'
                ]);
            if (!empty($this->user)) {
                foreach ($this->user as $user) {

                    $this->inst->setProxy($user->proxy);
                    $this->inst->setUser($user->in_name, $user->in_pass);
                    //$this->inst->login();
                    //$this->UsersTargetFollow($user->id);
                    //exec("php /home/alex/PhpstormProjects/insta-lara/artisan command:FindInsta >> /dev/null 2>&1 &");
                    sleep(30);
                }
            } else {
                echo "empty user";
            }

        }
    }

    private function in_user()
    {
        $res = false;
        $user = DB::table('in_user')
            ->join('proxy', 'in_user.proxy_id', '=', 'proxy.proxy_id')->get();
        if ($user->count() > 0) {
            $this->user = $user->toArray();
            $res = true;
        }
        return $res;
    }

    private function UsersTargetFollow($user_id)
    {
        $targets = DB::table('in_target')->where('in_user', $user_id)->get();
        foreach ($targets as $target) {
            $this->getUserFollowers($target);
        }
    }

    private function getUserFollowers($row_target)
    {
        $followers = [];
        $maxId = null;
        do {
            $response = $this->inst->getUserFollowers($row_target->friend_id, $maxId);
            sleep(1);
            $followers = array_merge($followers, $response->getUsers());
            $maxId = $response->getNextMaxId();
        } while ($maxId !== null);
        $sql_array = [];
        $i = 0;
        $j = 0;
        $all = count($followers);
        $this->create_tmp_table();
        foreach ($followers as $follower) {
            $sql_array[] = [
                'in_user' => $row_target->in_user,
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
                DB::table('tmp_in_user_search')->insert($sql_array);
                $sql_array = [];
                $i = 0;
            }
        }
        if ($row_target->first_search == 1) {
            DB::table('in_target')
                ->where('friend_id', $row_target->friend_id)
                ->where('in_user', $row_target->in_user)
                ->update(['first_search' => 0]);
        }
        $this->tmp_sort_user($row_target->in_user);
    }

    private function create_tmp_table()
    {
        \Schema::dropIfExists('tmp_in_user_search');
        \Schema::create('tmp_in_user_search', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('pk');
            $table->integer('in_user');
            $table->string('username');
            $table->string('full_name');
            $table->string('is_private');
            $table->string('profile_pic_url');
            $table->string('profile_pic_id')->nullable();
            $table->integer('first_search');
            $table->timestamps();
        });
    }

    private function tmp_sort_user($in_user)
    {
        DB::statement("DELETE FROM `tmp_in_user_search` 
              WHERE pk in (SELECT pk FROM in_user_search WHERE in_user=$in_user)");
        DB::statement("INSERT INTO `in_user_search` 
          (`in_user`, `pk`, `username`, `full_name`, `is_private`, `profile_pic_url`, `profile_pic_id`, `first_search`)  
SELECT 
t.`in_user`, t.`pk`, t.`username`, t.`full_name`, t.`is_private`, t.`profile_pic_url`, t.`profile_pic_id`, t.`first_search`
FROM tmp_in_user_search t WHERE 1");
    }
}