<?php
namespace App\Models;
class rekeningM{
    public function __construct(){
        $this->db=db_connect();
        $this->table_name="rekening";
    }
    public function create($dt){
        $q="INSERT INTO ".$this->table_name." VALUES(?,?,?,?)";
        return $this->db->query($q,$dt);
    }
    public function read_one($d){
        $q="SELECT * FROM ".$this->table_name." WHERE id_rekening=?";
        return $this->db->query($q,$d)->getResult();
    }
    public function read_all(){
        $q="SELECT * FROM ".$this->table_name;
        return $this->db->query($q)->getResult();
    }
    public function cek_ktp($id){
        $q="SELECT count(*) AS jumlah_data FROM ".$this->table_name." WHERE ktp=?";
        return $this->db->query($q,[$id])->getResult();
    }
    public function cek_rek($id){
        $q="SELECT count(*) AS jumlah_data FROM ".$this->table_name." WHERE no_rek=? and bank=?";
        return $this->db->query($q,$id)->getResult();
    }
    public function read_ktp($id){
        $q="SELECT * FROM ".$this->table_name." WHERE ktp=?";
        return $this->db->query($q,[$id])->getResult();
    }
    public function update($d){
        $q="UPDATE ".$this->table_name." SET no_rek=?,bank=? WHERE id_rekening=?";
        return $this->db->query($q,$d);
    }
    
    public function delete($d){
        $q="DELETE FROM ".$this->table_name." WHERE id_rekening=?";
        return $this->db->query($q,$d);
    }
} 
?>