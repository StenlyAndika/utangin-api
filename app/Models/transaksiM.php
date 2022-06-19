<?php
namespace App\Models;
class transaksiM{
    public function __construct(){
        $this->db=db_connect();
        $this->table_name="transaksi";
    }
    public function create($dt){
        $q="INSERT INTO ".$this->table_name." VALUES(?,?,?,?,?)";
        return $this->db->query($q,$dt);
    }
    public function seekId($dt){
        $q="SELECT id_transaksi FROM ".$this->table_name." WHERE id_permohonan=?";
        return $this->db->query($q,[$dt])->getResult();
    }
    public function read_permohonan($dt){
        $q="SELECT * FROM ".$this->table_name." WHERE id_permohonan=?";
        return $this->db->query($q,[$dt])->getResult();
    }
    public function read_ktp_borrower($dt){
        $q="SELECT transaksi.*,permohonan.tanggal_pengembalian,permohonan.denda,permohonan.jumlah,user_borrower.nama,user_borrower.ktp FROM ".$this->table_name." INNER JOIN permohonan ON transaksi.id_permohonan=permohonan.id_permohonan INNER JOIN user AS user_borrower ON user_borrower.ktp=permohonan.ktp_borrower WHERE user_borrower.ktp=?";
        return $this->db->query($q,[$dt])->getResult();
    }
    public function read_ktp_lender($dt){
        $q="SELECT transaksi.*,permohonan.tanggal_pengembalian,permohonan.denda,permohonan.jumlah,user_lender.nama,user_lender.ktp FROM ".$this->table_name." INNER JOIN permohonan ON transaksi.id_permohonan=permohonan.id_permohonan INNER JOIN user AS user_lender ON user_lender.ktp=permohonan.ktp_lender WHERE user_lender.ktp=?";
        return $this->db->query($q,[$dt])->getResult();
    }
    public function cek_ktp_lender($dt){
        $q="SELECT count(DISTINCT user.ktp) as jumlah_data FROM user INNER JOIN permohonan ON permohonan.ktp_lender=user.ktp INNER JOIN transaksi ON transaksi.id_permohonan=permohonan.id_permohonan WHERE user.ktp=?; ";
        return $this->db->query($q,[$dt])->getResult();
    }
    public function cek_ktp_borrower($dt){
        $q="SELECT count(DISTINCT user.ktp) as jumlah_data FROM user INNER JOIN permohonan ON permohonan.ktp_borrower=user.ktp INNER JOIN transaksi ON transaksi.id_permohonan=permohonan.id_permohonan WHERE user.ktp=?; ";
        return $this->db->query($q,[$dt])->getResult();
    }
    
} 
?>