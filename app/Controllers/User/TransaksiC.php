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
            // return $this->respond([
            //     "tanggal pengembalian"=>$data_permohonan[0]->tanggal_pengembalian,
            //     "tanggal pengajuan"=>$data_permohonan[0]->tanggal_pengajuan,
            //     "tanggal awal peminjaman"=>$tanggal,
            //     "selisih pengajuan dan pengembalian"=>$selisih->days,
            //     "tanggal pengembalian sah"=>date_format($tanggal_pengembalian_sah,"Y-m-d"),
            //     "Termin"=>$data_permohonan[0]->termin,
            //     "Jumlah Pinjaman"=>$data_permohonan[0]->jumlah,
            //     "jangka cicilan"=>$selisih->days/$data_permohonan[0]->termin,
            //     "bayar per cicilan"=>$data_permohonan[0]->jumlah/$data_permohonan[0]->termin,
            //     "detail cicilan"=>$detail_cicilan
            // ],200);
                $simpan=$this->transaksiM->create($data);
                if($simpan){
                    for($i=0;$i<count($detail_cicilan);$i++){
                        $data_cicilan=[
                            $this->gr->ranstring(10),
                            $id_transaksi,
                            "0000-00-00",
                            $detail_cicilan[$i]["tanggal"],
                            $detail_cicilan[$i]["cicilan"],
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
                //     move_uploaded_file($_FILES['bukti_peminjaman']['tmp_name'],"uploads/Bukti_peminjaman/".$nama_file);  
                }
                    $borower=$this->userM->read($this->permohonanM->read_one([$this->request->getPost('id_permohonan')])[0]->ktp_borrower); 
                    $lender=$this->userM->read($this->permohonanM->read_one([$this->request->getPost('id_permohonan')])[0]->ktp_lender); 
                    $this->se->tujuan=$borower[0]->email;
                    $this->se->subject="Informasi lanjutan mengenai peminjaman";
                    $this->se->body="Salam Bapak / Ibu, pada tanggal $waktu , saudara/i ".$lender[0]->nama." telah mengirimkan uang pinjaman ke rekening yang dituju, untuk selanjutnya anda bisa memeriksa bukti pinjaman di aplikasi utangin.com, terimakasih atas perhatiannya";
                    $kirim_email=$this->se->send();
                    // if($kirim_email){
                    //     return $this->respond(["pesan"=>"Transaksi berhasil"],200);
                    // }
                    // else{
                    //     return $this->respond(["pesan"=>"Terjadi Kesalahan1"],502);
                    // }
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
        $jumlah_hutang=0;
        for($i=0;$i<count($data);$i++){
            //$tgl_pengembalian=date_create($data[$i]->tanggal_pengembalian);
            $tgl_pengembalian=strtotime($data[$i]->tanggal_pengembalian);
            $tgl_hari_ini=strtotime(date('Y-m-d'));
            if($tgl_hari_ini>$tgl_pengembalian){
                $selisih_tanggal=date_diff(date_create($data[$i]->tanggal_pengembalian),date_create(date('Y-m-d')));
                print_r($selisih_tanggal);
            }
            $jumlah_hutang+=$data[$i]->jumlah;
            echo $tgl_pengembalian;
        }
        //return $this->respond(["hutang_berjalan"=>$jumlah_hutang],200);
        return $this->respond($data,200);
    }
    
}
