<?php
namespace App\Models;
class penawaranM{
    public function __construct(){
        $this->db=db_connect();
        $this->table_name="penawaran";
    }
    public function create($dt){
        $q="INSERT INTO ".$this->table_name." VALUES(?,?,?,?,?,?,?,?)";
        return $this->db->query($q,$dt);
    }
    public function edit_status($dt){
        $q="UPDATE ".$this->table_name." SET status=? WHERE id_penawaran=?";
        return $this->db->query($q,$dt);
    }
    public function cek_ktp_lender($id){
        $q="SELECT count(*) AS jumlah_data FROM ".$this->table_name." WHERE ktp_lender=?";
        return $this->db->query($q,[$id])->getResult();
    }
    public function read_ktp_lender($d){
        $q="SELECT penawaran.*,user.nama as nama_lender FROM ".$this->table_name." INNER JOIN user ON user.ktp=penawaran.ktp_lender WHERE ktp_borrower=?";
        return $this->db->query($q,$d)->getResult();
    }
    public function cek_ktp_borrower($id){
        $q="SELECT count(*) AS jumlah_data FROM ".$this->table_name." WHERE ktp_borrower=?";
        return $this->db->query($q,[$id])->getResult();
    }
    public function read_ktp_borrower($d){
        $q="SELECT * FROM ".$this->table_name." WHERE ktp_borrower=?";
        return $this->db->query($q,$d)->getResult();
    }
    public function update_verifikasi_ktp($d){
        $q="UPDATE ".$this->table_name." SET verifikasi_ktp=? WHERE ktp=?";
        return $this->db->query($q,$d);
    }
    public function delete($d){
        $q="DELETE FROM ".$this->table_name." WHERE id_penawaran=?";
        return $this->db->query($q,$d);
    }
    public function read_all(){
        $q="SELECT * FROM ".$this->table_name;
        return $this->db->query($q)->getResult();
    }
    public function read_one($d){
        $q="SELECT penawaran.*, user.email AS email_lender FROM ".$this->table_name." INNER JOIN user ON user.ktp=penawaran.ktp_lender WHERE id_penawaran=?";
        return $this->db->query($q,$d)->getResult();
    }
} 
?>