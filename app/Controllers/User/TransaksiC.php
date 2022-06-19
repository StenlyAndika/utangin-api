<?php

namespace App\Controllers\User;
use App\Models\userM;
use App\Models\cicilanM;
use App\Models\permohonanM;
use App\Models\transaksiM;
use App\Libraries\Parse_date_time;
use App\Libraries\Send_email;
use App\Libraries\generate_random;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
class TransaksiC extends ResourceController
{
    use ResponseTrait;
    public function __construct(){
        $this->pdt=new Parse_date_time();
        $this->userM=new userM();
        $this->cicilanM=new cicilanM();
        $this->permohonanM=new permohonanM();
        $this->transaksiM=new transaksiM();
        $this->gr=new generate_random();
        $this->se=new Send_email();
        $this->session=\Config\Services::session();
    }
    public function index()
    {
        echo ":)";
    }
    public function konfirmasi_pinjaman()
    {
        $valid=\Config\Services::validation();
        $valid->setRules([
            "id_permohonan"=>[
                "rules"=>"required",
                "errors"=>[
                    "required"=>"isi data permohonan"
                    ]
                ],
        ]);
        $data_valid=$valid->withRequest($this->request)->run();
        if($data_valid){
            $tanggal=date('Y-m-d H:i:s');
            $namafile=$string=strtotime(date("Y-m-d h:i:s"));
            $nama_file="";
            $id_transaksi=$this->gr->ranstring(10);
            if((isset($_FILES['bukti_peminjaman']))&&($_FILES['bukti_peminjaman']['size']>0)){
                $nama_file="BP_".$namafile.".".explode(".",$_FILES['bukti_peminjaman']['name'])[1];
            }            
            $data=[
                $id_transaksi,
                $this->request->getPost('id_permohonan'),
                $tanggal,
                $nama_file,
                "0"
            ];
            $data_permohonan=$this->permohonanM->read_one($this->request->getPost('id_permohonan'));
            $selisih=date_diff(date_create($data_permohonan[0]->tanggal_pengembalian),date_create($data_permohonan[0]->tanggal_pengajuan));
            
            $tanggal_pengembalian_sah=date_modify(date_create($tanggal),"+".$selisih->days." days");
            $detail_cicilan=[];
            $awal_cicilan=$tanggal;
            $jangka_cicilan=$selisih->days/$data_permohonan[0]->termin;
            $kontainer_jangka_cicilan=$selisih->days;
            $jangka_cicilan=(int)$jangka_cicilan;
            $bayar_per_cicilan=$data_permohonan[0]->jumlah/$data_permohonan[0]->termin;
            $bayar_per_cicilan=(int)$bayar_per_cicilan;
            $kontainer_jumlah=$data_permohonan[0]->jumlah;
            for($i=1;$i<=$data_permohonan[0]->termin;$i++){
                $awal_cicilan=date_modify(date_create($awal_cicilan),"+".$jangka_cicilan." days");
                $awal_cicilan=date_format($awal_cicilan,"Y-m-d");
                array_push($detail_cicilan,["tanggal"=>$awal_cicilan,"cicilan"=>$bayar_per_cicilan]);
                $kontainer_jangka_cicilan-=$jangka_cicilan;
                $kontainer_jumlah-=$bayar_per_cicilan;
            }
            if($kontainer_jangka_cicilan>0){
                $awal_cicilan=date_modify(date_create($detail_cicilan[count($detail_cicilan)-1]["tanggal"]),"+".$kontainer_jangka_cicilan." days");
                $awal_cicilan=date_format($awal_cicilan,"Y-m-d");
                $detail_cicilan[count($detail_cicilan)-1]["tanggal"]=$awal_cicilan;
            }
            if($kontainer_jumlah>0){
                $awal_cicilan=date_modify(date_create($detail_cicilan[count($detail_cicilan)-1]["tanggal"]),"+".$kontainer_jangka_cicilan." days");
                $awal_cicilan=date_format($awal_cicilan,"Y-m-d");
                $detail_cicilan[count($detail_cicilan)-1]["cicilan"]=$detail_cicilan[count($detail_cicilan)-1]["cicilan"]+$kontainer_jumlah;
            }
                $simpan=$this->transaksiM->create($data);
                if($simpan){
                    for($i=0;$i<count($detail_cicilan);$i++){
                        $data_cicilan=[
                            $this->gr->ranstring(10),
                            $id_transaksi,
                            $i+1,
                            "0000-00-00",
                            $detail_cicilan[$i]["tanggal"],
                            $detail_cicilan[$i]["cicilan"],
                            "0",
                            "-",
                            "-",
                            "0"
                        ];
                        $simpan_cicilan=$this->cicilanM->create($data_cicilan);
                    }
                    $tanggal=date_create($tanggal);
                    $hari=$this->pdt->parse_day($tanggal->format("D"));
                    $bulan=$this->pdt->parse_month($tanggal->format("M"));
                    $waktu=$hari." ".$tanggal->format('d')." ".$bulan." ".$tanggal->format("Y")." ".$tanggal->format("H").":".$tanggal->format("i");
                if((isset($_FILES['bukti_peminjaman']))&&($_FILES['bukti_peminjaman']['size']>0)){
                }
                    $borower=$this->userM->read($this->permohonanM->read_one([$this->request->getPost('id_permohonan')])[0]->ktp_borrower); 
                    $lender=$this->userM->read($this->permohonanM->read_one([$this->request->getPost('id_permohonan')])[0]->ktp_lender); 
                    $this->se->tujuan=$borower[0]->email;
                    $this->se->subject="Informasi lanjutan mengenai peminjaman";
                    $this->se->body="Salam Bapak / Ibu, pada tanggal $waktu , saudara/i ".$lender[0]->nama." telah mengirimkan uang pinjaman ke rekening yang dituju, untuk selanjutnya anda bisa memeriksa bukti pinjaman di aplikasi utangin.com, terimakasih atas perhatiannya";
                    $kirim_email=$this->se->send();
                    return $this->respond(["pesan"=>"Transaksi berhasil"],200);
                    
                }
                else{
                    return $this->respond(["pesan"=>"terjadi kesalahan2"],502);
                }

            
        }
        else{
            return $this->respond(["pesan"=>$valid->getErrors()],502);
        }
        
    }
    public function Jumlah_hutang_berjalan($d)
    {
        $data=$this->transaksiM->read_ktp_borrower($d);
        $jumlah_saya_pinjam=$this->transaksiM->cek_ktp_lender($d);
        $jumlah_hutang=0;
        $hutang_lunas=0;
        for($i=0;$i<count($data);$i++){
            if($data[$i]->status==1){
                $hutang_lunas+=1;
            }
            else if($data[$i]->status==0){
                $data_cicilan=$this->cicilanM->read_transaksi($data[$i]->id_transaksi);
                $data_pinjaman=$this->permohonanM->read_one($data[$i]->id_permohonan)[0];
                $denda=$data_pinjaman->denda;
                for($j=0;$j<count($data_cicilan);$j++){
                    $total_denda=0;
                    $data_cicilan[$j]->denda=$denda*$data_cicilan[$j]->jml_angsuran;
                    $data_cicilan[$j]->denda=(int)$data_cicilan[$j]->denda;
                    $tanggal_sekarang=date('Y-m-d');
                    $tanggal_batas=$data_cicilan[$j]->tanggal_batas;
                    if(strtotime($tanggal_batas)<strtotime($tanggal_sekarang)){                    
                        if($data_cicilan[$j]->status==0){                      
                            $telat=date_diff(date_create($tanggal_sekarang),date_create($tanggal_batas));
                            $total_denda=$data_cicilan[$j]->denda*$telat->days;  
                            $data_cicilan[$j]->total_denda=$total_denda;                                                
                        }
                    }
                    if($data_cicilan[$j]->status==0){  
                        $jumlah_hutang+=$total_denda+$data_cicilan[$j]->jml_angsuran;
                    }
                }
            }            
        }
        return $this->respond(["jumlah pinjaman saya"=>count($data),"Jumlah orang yang saya pinjam"=>$jumlah_saya_pinjam[0]->jumlah_data,"Hutang lunas"=>$hutang_lunas,"hutang_berjalan"=>$jumlah_hutang],200);
    }

    public function Jumlah_piutang_berjalan($d)
    {
        $data=$this->transaksiM->read_ktp_lender($d);
        $jumlah_saya_pinjam=$this->transaksiM->cek_ktp_lender($d);
        $jumlah_hutang=0;
        $hutang_lunas=0;
        for($i=0;$i<count($data);$i++){
            if($data[$i]->status==1){
                $hutang_lunas+=1;
            }
            else if($data[$i]->status==0){
                $data_cicilan=$this->cicilanM->read_transaksi($data[$i]->id_transaksi);
                $data_pinjaman=$this->permohonanM->read_one($data[$i]->id_permohonan)[0];
                $denda=$data_pinjaman->denda;
                for($j=0;$j<count($data_cicilan);$j++){
                    $total_denda=0;
                    $data_cicilan[$j]->denda=$denda*$data_cicilan[$j]->jml_angsuran;
                    $data_cicilan[$j]->denda=(int)$data_cicilan[$j]->denda;
                    $tanggal_sekarang=date('Y-m-d');
                    $tanggal_batas=$data_cicilan[$j]->tanggal_batas;
                    if(strtotime($tanggal_batas)<strtotime($tanggal_sekarang)){                    
                        if($data_cicilan[$j]->status==0){                      
                            $telat=date_diff(date_create($tanggal_sekarang),date_create($tanggal_batas));
                            $total_denda=$data_cicilan[$j]->denda*$telat->days;  
                            $data_cicilan[$j]->total_denda=$total_denda;                                                
                        }
                    }
                    if($data_cicilan[$j]->status==0){  
                        $jumlah_hutang+=$total_denda+$data_cicilan[$j]->jml_angsuran;
                    }
                }
            }            
        }
        $newData = [
            "jumlah_pinjaman"=>count($data),
            "jumlah_peminjam"=>$jumlah_saya_pinjam[0]->jumlah_data,
            "pinjaman_lunas"=>$hutang_lunas,
            "piutang_berjalan"=>$jumlah_hutang,
        ];
        return $this->respond($newData,200);
    }

    
}
