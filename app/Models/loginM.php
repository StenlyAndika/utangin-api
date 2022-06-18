<?php
namespace App\Models;
class loginM{
    public function __construct(){
        $this->db=db_connect();
        $this->table_name="login";
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
        $q="INSERT INTO ".$this->table_name." VALUES(?,?,?)";
        return $this->db->query($q,$dt);
    }
    public function delete($dt){
        $q="DELETE FROM ".$this->table_name." WHERE id_device=?";
        return $this->db->query($q,[$dt]);
    }
} 
?>