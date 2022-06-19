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
class CicilanC extends ResourceController
{
    use ResponseTrait;
    public function __construct(){
        $this->pdt=new Parse_date_time();
        $this->userM=new userM();
        $this->cicilanM=new cicilanM();
        $this->transaksiM=new transaksiM();
        $this->gr=new generate_random();
        $this->se=new Send_email();
        $this->session=\Config\Services::session();
    }
    public function index()
    {
        echo ":)";
    }
    public function Bayar_cicilan()
    {
        $data_valid=\Config\Services::validation();
        $data_valid->setRules([
            "id_cicilan"=>[
                "rules"=>"required",
                "errors"=>[
                    "required"=>"Masukkan Data Cicilan"
                ]
            ],
            "jml_bayar"=>[
                "rules"=>"required",
                "errors"=>[
                    "required"=>"Masukkan jumlah bayaran"
                ]
            ]
        ]);
        $cek_valid=$data_valid->withRequest($this->request)->run();
        if($cek_valid){
            $read_data_cicilan=$this->cicilanM->read_one($this->request->getPost('id_cicilan'));
            $bisa_bayar_cicilan=true;
            if($read_data_cicilan[0]->cicilan_ke>1){
                $read_cicilan_sebelumnya=$this->cicilanM->read_cicilan_ke([$read_data_cicilan[0]->id_transaksi,($read_data_cicilan[0]->cicilan_ke-1)]);
                if($read_cicilan_sebelumnya[0]->status==0){
                    $bisa_bayar_cicilan=false;
                    return $this->respond(["message"=>"Lunasi cicilan sebelumnya"],502);
                }
            }
            if($bisa_bayar_cicilan==true){
                $jumlah_bayar=$read_data_cicilan[0]->jml_bayar;
                $bukti_transfer="";
                if(isset($_FILES["bukti_transfer"])){
                    $tipe_file=explode(".",$_FILES["bukti_transfer"]['name'])[1];
                    $bukti_transfer="BT-".strtotime(date("Y-m-d h:i:s")).".".$tipe_file;
                    move_uploaded_file($_FILES['bukti_transfer']['tmp_name'],"uploads/Cicilan/".$bukti_transfer);
                }
                $data=[
                    date("Y-m-d H:i:s"),
                    $this->request->getPost('jml_bayar')+$jumlah_bayar,
                    $bukti_transfer,
                    $this->request->getPost('keterangan'),
                    $this->request->getPost('id_cicilan')
                ];
                $update_cicilan=$this->cicilanM->update_cicilan($data);
                if($update_cicilan){
                    return $this->respond(["message"=>"Sukses"],200);
                }
                else{
                    return $this->respond(["message"=>"Gagal"],502);
                }
            
            }
        }
        else{            
            return $this->respond(["message"=>$data_valid->getErrors()],502);
        }
    }
    public function Konfirmasi_cicilan($d){
        $data_cicilan=$this->cicilanM->read_one($d);
        $data_transaksi=$this->cicilanM->read_transaksi($data_cicilan[0]->id_transaksi);
        $uang_cicil=$data_cicilan[0]->jml_bayar;
        for($i=$data_cicilan[0]->cicilan_ke-1;$i<count($data_transaksi);$i++){
            if($data_transaksi[$i]->status!=2){                
                if($data_transaksi[$i]->jml_angsuran<=$uang_cicil){
                    $this->cicilanM->Konfirmasi_cicilan($data_transaksi[$i]->id_cicilan);
                    $this->cicilanM->update_jml_bayar([$data_transaksi[$i]->jml_angsuran,$data_transaksi[$i]->id_cicilan]);
                    $uang_cicil=$uang_cicil-$data_transaksi[$i]->jml_angsuran;
                }
                else{
                    if($i==$data_cicilan[0]->cicilan_ke-1){
                        $this->cicilanM->Konfirmasi_cicilan($data_transaksi[$i]->id_cicilan);
                        $uang_cicil=0;
                    }
                    else{                        
                        $data_transaksi[$i]->jml_bayar=$uang_cicil; 
                        $this->cicilanM->update_jml_bayar([$uang_cicil,$data_transaksi[$i]->id_cicilan]);
                        $uang_cicil=0;
                    }
                }
            }  
            else{
                break;
            }  
        }
        return $this->respond(["pesan"=>"Cicilan sudah dikonfirmasi"],200);
    }
    public function Kirim_cicilan()
    {
        $daftar_cicilan=$this->cicilanM->read_transaksi($this->request->getPost('id_transaksi'));
        $bayar=$this->request->getPost('jml_bayar');
        for($i=0;$i<count($daftar_cicilan);$i++){
            if($daftar_cicilan[$i]->jml_angsuran>0){
                if($bayar<=$daftar_cicilan[$i]->jml_angsuran){
                    $daftar_cicilan[$i]->jml_angsuran=$daftar_cicilan[$i]->jml_angsuran-$bayar;
                    $bayar=0;
                }
                else{
                    $bayar=$bayar-$daftar_cicilan[$i]->jml_angsuran;
                    $daftar_cicilan[$i]->jml_angsuran=0;
                }
            }
        }
        return $this->respond($daftar_cicilan,200);
    }
    
    public function cariIdTransaksi($a)
    {
        $data=$this->transaksiM->seekId($a);
        return $this->respond($data,200);
    }

    public function Daftar_cicilan()
    {
        $daftar_cicilan=$this->cicilanM->read_transaksi($this->request->getPost('id_transaksi'));
        for($i=0;$i<count($daftar_cicilan);$i++) {
            if(($daftar_cicilan[$i]->jml_bayar>0)&&($daftar_cicilan[$i]->status==0)) {
                $daftar_cicilan[$i]->status="1";
            }
        }
        return $this->respond($daftar_cicilan,200);
    }

    public function Detail_cicilan($d) {
        $data=$this->cicilanM->detail_cicilan($d);
        return $this->respond($data,200);
    }

    public function Detail_cicilan_lender($d) {
        $data=$this->cicilanM->detail_cicilan_lender($d);
        return $this->respond($data,200);
    }
    
}
