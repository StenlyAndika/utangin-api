<?php
namespace App\Libraries;
class generate_random{
    function ranstring($lim){
        $char="1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $res="";
        for($i=0;$i<$lim;$i++){
            $res=$res.$char[rand(0,strlen($char)-1)];
        }
        return $res;
    }
    function rannum($lim){
        $char="1234567890";
        $res="";
        for($i=0;$i<$lim;$i++){
            $res=$res.$char[rand(0,strlen($char)-1)];
        }
        return $res;
    }
}
?>