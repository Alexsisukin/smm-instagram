<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Instagram extends Model
{
    public static function Login($login, $pass, $proxy, $proc = '')
    {
        $inst = new \InstagramAPI\Instagram(false, false,
            [
                'storage' => 'file',
                'basefolder' => base_path() . '/storage/inst/'
            ]);

        $inst->setProxy($proxy);
        $inst->setUser($login, $pass);
        try {
            $inst->login();
        } catch (\Exception $e) {
           echo  $e->getMessage()."\n";
            echo 'not login'." $login $proc \n";
            exit();
        }
        return $inst;
    }
}
