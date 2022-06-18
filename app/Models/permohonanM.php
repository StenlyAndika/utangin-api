<?php
namespace App\Models;
class permohonanM{
    public function __construct(){
        $this->db=db_connect();
        $this->table_name="permohonan";
    }
    public function create($dt){
        $q="INSERT INTO ".$this->table_name." VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        return $this->db->query($q,$dt);
    }
    public function revisi_permohonan($dt){
        $q="UPDATE ".$this->table_name." SET jumlah=?,id_rekening=?,kegunaan=?,tanggal_pengembalian=?,termin=?,denda=?,acc_l=1,acc_b=0,revisi=1,tanggal_revisi=?,keterangan=? WHERE id_permohonan=?";
        return $this->db->query($q,$dt);
    }
    public function acc_lender($dt){
        $q="UPDATE ".$this->table_name." SET acc_l=1 WHERE id_permohonan=?";
        return $this->db->query($q,$dt);
    }
    public function acc_borrower($dt){
        $q="UPDATE ".$this->table_name." SET acc_b=1 WHERE id_permohonan=?";
        return $this->db->query($q,$dt);
    }
    public function cek_ktp_lender($id){
        $q="SELECT count(*) AS jumlah_data FROM ".$this->table_name." WHERE ktp_lender=?";
        return $this->db->query($q,[$id])->getResult();
    }
    public function read_ktp_lender($d){
        $q="SELECT permohonan.*,user.nama as nama_borrower FROM ".$this->table_name." INNER JOIN user ON user.ktp=permohonan.ktp_borrower WHERE ktp_lender=?";
        return $this->db->query($q,$d)->getResult();
    }
    public function read_ktp_borrower($d){
        $q="SELECT permohonan.*,user.nama as nama_lender FROM ".$this->table_name." INNER JOIN user ON user.ktp=permohonan.ktp_lender WHERE ktp_borrower=?";
        return $this->db->query($q,$d)->getResult();
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
        $q="SELECT permohonan.*,user_borrower.nama,user_borrower.email FROM ".$this->table_name." INNER JOIN user as user_borrower ON permohonan.ktp_borrower=user_borrower.ktp WHERE permohonan.id_permohonan=?";
        return $this->db->query($q,$d)->getResult();
    }
} 
?>