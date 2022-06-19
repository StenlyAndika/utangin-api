<?php
namespace App\Models;
class cicilanM{
    public function __construct(){
        $this->db=db_connect();
        $this->table_name="cicilan";
    }
    public function create($dt){
        $q="INSERT INTO ".$this->table_name." VALUES(?,?,?,?,?,?,?,?,?,?)";
        return $this->db->query($q,$dt);
    }
    public function delete($dt){
        $q="DELETE FROM ".$this->table_name." WHERE id_cicilan=?";
        return $this->db->query($q,[$dt]);
    }
    public function read_transaksi($dt){
        $q="SELECT * FROM ".$this->table_name." WHERE id_transaksi=? ORDER BY cicilan_ke ASC";
        return $this->db->query($q,[$dt])->getResult();
    }
    public function konfirmasi_cicilan($dt){
        $q="UPDATE ".$this->table_name." SET status=2 WHERE id_cicilan=?";
        return $this->db->query($q,[$dt]);
    }
    public function read_one($dt){
        $q="SELECT * FROM ".$this->table_name." WHERE id_cicilan=?";
        return $this->db->query($q,[$dt])->getResult();
    }
    public function read_cicilan_ke($dt){
        $q="SELECT * FROM cicilan WHERE id_transaksi=? and cicilan_ke=?";
        return $this->db->query($q,$dt)->getResult();
    }
    public function update_cicilan($dt){
        $q="UPDATE ".$this->table_name." SET tanggal_angsuran=?,jml_bayar=?,bukti_transfer=?,keterangan=? WHERE id_cicilan=?";
        return $this->db->query($q,$dt);
    }
    public function update_jml_bayar($dt){
        $q="UPDATE ".$this->table_name." SET jml_bayar=? WHERE id_cicilan=?";
        return $this->db->query($q,$dt);
    }

    public function detail_cicilan($dt) {
        $q="SELECT user.email,user.nama,transaksi.tanggal,permohonan.jumlah,cicilan.cicilan_ke,cicilan.jml_angsuran FROM cicilan INNER JOIN transaksi ON transaksi.id_transaksi=cicilan.id_transaksi INNER JOIN permohonan ON permohonan.id_permohonan=transaksi.id_permohonan INNER JOIN user ON user.ktp=permohonan.ktp_lender WHERE cicilan.id_cicilan=?";
        return $this->db->query($q,[$dt])->getResult();
    }

    public function detail_cicilan_lender($dt) {
        $q="SELECT user.email,user.nama,transaksi.tanggal,permohonan.jumlah,cicilan.cicilan_ke,cicilan.jml_angsuran, cicilan.jml_bayar,cicilan.keterangan,cicilan.bukti_transfer, cicilan.id_cicilan FROM cicilan INNER JOIN transaksi ON transaksi.id_transaksi=cicilan.id_transaksi INNER JOIN permohonan ON permohonan.id_permohonan=transaksi.id_permohonan INNER JOIN user ON user.ktp=permohonan.ktp_borrower WHERE cicilan.id_cicilan=?";
        return $this->db->query($q,[$dt])->getResult();
    }
} 
?>