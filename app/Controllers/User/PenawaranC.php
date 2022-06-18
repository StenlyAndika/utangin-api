<?php

namespace App\Controllers\User;
use App\Models\userM;
use App\Models\penawaranM;
use App\Libraries\Parse_date_time;
use App\Libraries\Send_email;
use App\Libraries\generate_random;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
class PenawaranC extends ResourceController
{
    use ResponseTrait;
    public function __construct(){
        $this->pdt=new Parse_date_time();
        $this->userM=new userM();
        $this->penawaranM=new penawaranM();
        $this->gr=new generate_random();
        $this->se=new Send_email();
        $this->session=\Config\Services::session();
    }
    public function index()
    {
        // $data=$this->userM->read();
        // for($i=0;$i<count($data);$i++){
        //     $tgl=date_create($data[$i]->tgl_lahir);
        //     $hari=$this->pdt->parse_day(date_format($tgl,"D"));
        //     $bulan=$this->pdt->parse_month(date_format($tgl,"M"));
        //     $data[$i]->tgl_lahir_parse=$hari." ".date_format($tgl,"d")." ".$bulan." ".date_format($tgl,"Y");
        // }
        // return $this->respond($data,200);
        echo ":)";
    }
    public function Tawaran_dari_saya($d){
        $data=$this->penawaranM->read_ktp_lender([$d]);
        return $this->respond($data,200);
    }
    public function Tawaran_kepada_saya($d){
        $data=$this->penawaranM->read_ktp_lender($d);
        for($i=0;$i<count($data);$i++){
            $databaru = [
                "id_penawaran"=>$data[$i]->id_penawaran,
                "tanggal_diajukan"=>$data[$i]->tanggal_diajukan,
                "nama_lender"=>$data[$i]->nama_lender,
                "jumlah_tawaran"=>$data[$i]->jumlah_tawaran,
                "ktp_lender"=>$data[$i]->ktp_lender,
                "ktp_borrower"=>$data[$i]->ktp_borrower,
                "denda"=>$data[$i]->denda,
                "status"=>$data[$i]->status,
            ];
            $data[$i]=$databaru;
        }
        return $this->respond($data,200);
    }

    public function Detail_tawaran($d){
        $data=$this->penawaranM->read_one($d);
        return $this->respond($data,200);
    }

    public function Kirim_tawaran()
    {
        $data_valid=\Config\Services::validation();
        $data_valid->setRules([
        "ktp_lender"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"KTP Tidak Boleh Kosong",
                ]
            ],
        "ktp_borrower"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"KTP Tujuan Tidak Boleh Kosong",
                ]
            ],
        "jumlah_tawaran"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Isi Jumlah Pinjaman yang ditawarkan",
                ]
            ],
        "tanggal_pengembalian"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Isi Tanggal Pengembalian",
                ]
            ],
        "denda"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Isi Denda Pinjaman (0.00-100.00)",
                ]
            ],
        ]);
        $cek_valid=$data_valid->withRequest($this->request)->run();
        if($cek_valid){            
            $cek_verifikasi=$this->userM->read($this->request->getPost('ktp_lender'));
            if(count($cek_verifikasi)>0){
                if($cek_verifikasi[0]->status=="0"){
                    return $this->respond(["pesan"=>"Akun Belum Diverifikasi"],502);
                }
                else{
                    $cek_tujuan=$this->userM->cek_ktp($this->request->getPost('ktp_borrower'));
                    if($cek_tujuan[0]->jumlah_data<0){
                        return $this->respond(["pesan"=>"KTP Tujuan Pengajuan tidak ditemukan"],502);
                    }
                    else{
                        $tanggal_data=date("Y-m-d H:i:s");
                        $d=[
                            $this->gr->ranstring(10),
                            $this->request->getPost('ktp_lender'),
                            $this->request->getPost('ktp_borrower'),
                            $this->request->getPost('jumlah_tawaran'),
                            $tanggal_data,
                            $this->request->getPost('tanggal_pengembalian'),
                            $this->request->getPost('denda'),
                            0
                        ];
                        $create=$this->penawaranM->create($d);
                        if($create){
                            $email_tujuan_pinjam=$this->userM->read($this->request->getPost('ktp_borrower'));
                            $tanggal=date_create($tanggal_data);
                            $hari=$this->pdt->parse_day($tanggal->format("D"));
                            $bulan=$this->pdt->parse_month($tanggal->format("M"));
                            $waktu=$hari." ".$tanggal->format('d')." ".$bulan." ".$tanggal->format("Y")." ".$tanggal->format("H").":".$tanggal->format("i");
                            $this->se->tujuan=$email_tujuan_pinjam[0]->email;
                            $this->se->subject="informasi Tawaran Pinjaman";
                            $sat_denda=["H"=>"hari","B"=>"Bulan","T"=>"Tahun"];
                            $this->se->body="Salam, pada hari $waktu, Saudara/i bernama ".$cek_verifikasi[0]->nama." telah mengirimkan tawaran pinjaman kepada anda
                            sebanyak ".$this->request->getPost('jumlah_tawaran')." dengan denda ".$this->request->getPost('denda')."% per hari, untuk selanjutnya silahkan anda pertimbangkan untuk menerima tawaran pinjaman atau tidak, terimakasih";
                            $this->se->send();
                            return $this->respond(["pesan"=>"Tawaran Pinjaman Telah Dikirim"],200);
                        }
                        else{
                            return $this->respond(["pesan"=>"Terjadi Kesalahan"],502);
                        }
                    }                    
                }    
            }
            else{
                return $this->respond(["pesan"=>"KTP Tidak Ditemukan"],502);
            }
        }
        else{
            return $this->respond(["message"=>$data_valid->getErrors()],502);
        }
    }
    public function Tawaran_diterima($d){
        $edit_tawaran=$this->penawaranM->edit_status(["1",$d]);
        $baca_data=$this->penawaranM->read_one([$d]);
        $tujuan_pinjam=$this->userM->read($baca_data[0]->ktp_borrower);
        $asal_tawaran=$this->userM->read($baca_data[0]->ktp_lender);
        if($edit_tawaran){
            // $tanggal=date_create(date('Y-m-d H:i:s'));
            // $hari=$this->pdt->parse_day($tanggal->format("D"));
            // $bulan=$this->pdt->parse_month($tanggal->format("M"));
            // $waktu=$hari." ".$tanggal->format('d')." ".$bulan." ".$tanggal->format("Y")." ".$tanggal->format("H").":".$tanggal->format("i");
            // $this->se->tujuan=$asal_tawaran[0]->email;
            // $this->se->subject="informasi Tawaran Pinjaman";
            // $this->se->body="Salam, pada hari $waktu, Saudara/i bernama ".$tujuan_pinjam[0]->nama." telah menerima tawaran pinjaman dari anda
            // dan telah mengirimkan permohonan pinjaman berdasarkan tawaran yang sudah anda berikan sebelumnya, terimakasih atas perhatiannya";
            // $this->se->send();
            return $this->respond(["pesan"=>"Tawaran anda diterima oleh peminjam"],200);
        }
        else{
            return $this->respond(["pesan"=>"Terjadi Kesalahan"],200);
        }
    }
    
}
