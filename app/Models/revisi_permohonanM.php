<?php
namespace App\Models;
class revisi_permohonanM{
    public function __construct(){
        $this->db=db_connect();
        $this->table_name="revisi_permohonan";
    }
    public function create($dt){
        $q="INSERT INTO ".$this->table_name." VALUES(?,?,?,?,?,?,?,?,?,?,?,?)";
        return $this->db->query($q,$dt);
    }
    public function edit_status($dt){
        $q="UPDATE ".$this->table_name." SET status=? WHERE id_penawaran=?";
        return $this->db->query($q,$dt);
    }
    public function cek_ktp_penawar($id){
        $q="SELECT count(*) AS jumlah_data FROM ".$this->table_name." WHERE ktp_penawar=?";
        return $this->db->query($q,[$id])->getResult();
    }
    public function read_ktp_penawar($d){
        $q="SELECT * FROM ".$this->table_name." WHERE ktp_penawar=?";
        return $this->db->query($q,$d)->getResult();
    }
    public function cek_ktp_tujuan($id){
        $q="SELECT count(*) AS jumlah_data FROM ".$this->table_name." WHERE ktp_tujuan=?";
        return $this->db->query($q,[$id])->getResult();
    }
    public function read_ktp_tujuan($d){
        $q="SELECT * FROM ".$this->table_name." WHERE ktp_tujuan=?";
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
        $q="SELECT * FROM ".$this->table_name." WHERE id_revisi=?";
        return $this->db->query($q,$d)->getResult();
    }
    public function read_permohonan($d){
        $q="SELECT * FROM ".$this->table_name." WHERE id_permohonan=? ORDER BY tanggal_revisi";
        return $this->db->query($q,$d)->getResult();
    }
} 
?>