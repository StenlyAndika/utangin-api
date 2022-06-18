<?php
namespace App\Models;
class userM{
    public function __construct(){
        $this->db=db_connect();
        $this->table_name="user";
    }
    public function create($dt){
        $q="INSERT INTO ".$this->table_name." VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        return $this->db->query($q,$dt);
    }
    public function cek_ktp($id){
        $q="SELECT count(*) AS jumlah_data FROM ".$this->table_name." WHERE ktp=?";
        return $this->db->query($q,[$id])->getResult();
    }
    public function read_ktp($id){
        $q="SELECT * FROM ".$this->table_name." WHERE ktp=?";
        return $this->db->query($q,[$id])->getResult();
    }
    public function cek_email($id){
        $q="SELECT count(*) AS jumlah_data FROM ".$this->table_name." WHERE email=?";
        return $this->db->query($q,[$id])->getResult();
    }
    public function read_email($id){
        $q="SELECT * FROM ".$this->table_name." WHERE email=?";
        return $this->db->query($q,[$id])->getResult();
    }
    public function search_nama($id){
        $q="SELECT * FROM ".$this->table_name." WHERE nama like %?%";
        return $this->db->query($q,[$id])->getResult();
    }
    public function update_verifikasi_ktp($d){
        $q="UPDATE ".$this->table_name." SET verifikasi_ktp=? WHERE ktp=?";
        return $this->db->query($q,$d);
    }
    public function delete(){
        echo "DATA DELETE";
    }
    public function read_all(){
        $q="SELECT * FROM ".$this->table_name;
        return $this->db->query($q)->getResult();
    }
    public function read($ktp=false){
        if($ktp==false){
            $q="SELECT * FROM ".$this->table_name;
        }
        else{
            $q="SELECT * FROM ".$this->table_name." WHERE ktp=?";
        }        
        return $this->db->query($q,[$ktp])->getResult();
    }
    public function read_one_id($id){
        $q="SELECT * FROM ".$this->table_name." WHERE id=?";
        return $this->db->query($q,$id)->getResult();
    }
} 
?>