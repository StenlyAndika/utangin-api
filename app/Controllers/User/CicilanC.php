<?php

namespace App\Controllers\User;
use App\Models\userM;
use App\Models\cicilanM;
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
    public function Konfirmasi_cicilan($d){
        $konfirm=$this->cicilanM->konfirmasi_cicilan($d);
        if($konfirm){
            return $this->respond(["pesan"=>"Konfirmasi berhasil"],200);
        }
        else{
            return $this->respond(["pesan"=>"Konfirmasi Gagal"],502);
        }
    }
    public function Kirim_cicilan()
    {
        $valid=\Config\Services::validation();
        $valid->setRules([
            "id_transaksi"=>[
                "rules"=>"required",
                "errors"=>[
                    "required"=>"isi data transaksi"
                    ]
                ],
            "jml_angsuran"=>[
                "rules"=>"required",
                "errors"=>[
                    "required"=>"isi jumlah angsuran"
                    ]
                ], 
            "keterangan"=>[
                "rules"=>"required",
                "errors"=>[
                    "required"=>"isi Keterangan"
                    ]
                ],            
            "bukti_transfer"=>[
                "rules"=>"uploaded[bukti_transfer]|mime_in[bukti_transfer,image/jpg,image/jpeg,image/png]|max_size[bukti_transfer,5020]",
                "errors"=>[
                    "uploaded"=>"Mohon Lampirkan Bukti Transfer",
                    "mime_in"=>"Format gambar harus jpg,jpeg,atau png",
                    "max_size"=>"Ukuran maksimum 5mb"
                ]
            ],
        ]);
        $data_valid=$valid->withRequest($this->request)->run();
        if($data_valid){
            $tanggal=date('Y-m-d H:i:s');
            $namafile=strtotime(date("Y-m-d h:i:s"));
            $nama_file="BT_".$namafile.".".explode(".",$_FILES['bukti_transfer']['name'])[1];
            $data=[
                $this->gr->ranstring(10),
                $this->request->getPost('id_transaksi'),
                $tanggal,
                $this->request->getPost('jml_angsuran'),
                $nama_file,
                $this->request->getPost('keterangan'),
                "0"
            ];
                $simpan=$this->cicilanM->create($data);
                if($simpan){
                    $tanggal=date_create($tanggal);
                    $hari=$this->pdt->parse_day($tanggal->format("D"));
                    $bulan=$this->pdt->parse_month($tanggal->format("M"));
                    $waktu=$hari." ".$tanggal->format('d')." ".$bulan." ".$tanggal->format("Y")." ".$tanggal->format("H").":".$tanggal->format("i");
                    move_uploaded_file($_FILES['bukti_transfer']['tmp_name'],"uploads/Cicilan/".$nama_file);  
                    // $borower=$this->userM->read($this->cicilanM->read_one([$this->request->getPost('id_cicilan')])[0]->ktp_borrower); 
                    // $lender=$this->userM->read($this->cicilanM->read_one([$this->request->getPost('id_cicilan')])[0]->ktp_lender); 
                    // $this->se->tujuan=$borower[0]->email;
                    // $this->se->subject="Informasi lanjutan mengenai peminjaman";
                    // $this->se->body="Salam Bapak / Ibu, pada tanggal $waktu , saudara/i ".$lender[0]->nama." telah mengirimkan uang pinjaman ke rekening yang dituju, untuk selanjutnya anda bisa memeriksa bukti pinjaman di aplikasi utangin.com, terimakasih atas perhatiannya";
                    // $kirim_email=$this->se->send();
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
    
}
