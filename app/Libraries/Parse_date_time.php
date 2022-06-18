<?php
namespace App\Libraries;
class Parse_date_time{
    public function parse_day($p){
        $hari=["Sun"=>"Minggu","Mon"=>"Senin","Tue"=>"Selasa","Wed"=>"Rabu","Thu"=>"Kamis","Fri"=>"Jum at","Sat"=>"Sabtu"];
        return $hari[$p];
    }
    public function parse_month($p){
        $bulan=["Jan"=>"Januari","Feb"=>"Februari","Mar"=>"Maret","Apr"=>"April","May"=>"Mei","Jun"=>"Juni","Jul"=>"Juli","Aug"=>"Agustus","Sep"=>"September","Okt"=>"Oktober","Nov"=>"November","Dec"=>"Desember"];
        return $bulan[$p];
    }
}
?>