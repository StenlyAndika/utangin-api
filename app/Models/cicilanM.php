<?php
namespace App\Models;
class cicilanM{
    public function __construct(){
        $this->db=db_connect();
        $this->table_name="cicilan";
    }
    public function read($dt=false){
        if($dt==false){
            $q="SELECT * FROM ".$this->table_name;
        }
        else{
            $q="SELECT * FROM ".$this->table_name." WHERE id_device=?";
        }
        return $this->db->query($q,[$dt])->getResult();
    }
    public function create($dt){
        $q="INSERT INTO ".$this->table_name." VALUES(?,?,?,?,?,?,?,?)";
        return $this->db->query($q,$dt);
    }
    public function delete($dt){
        $q="DELETE FROM ".$this->table_name." WHERE id_cicilan=?";
        return $this->db->query($q,[$dt]);
    }
    public function konfirmasi_cicilan($dt){
        $q="UPDATE ".$this->table_name." SET status=1 WHERE id_cicilan=?";
        return $this->db->query($q,[$dt]);
    }
} 
?>