<?php

namespace App\Controllers\User;
use App\Models\userM;
use App\Models\permohonanM;
use App\Models\transaksiM;
use App\Models\rekeningM;
use App\Libraries\Parse_date_time;
use App\Libraries\Send_email;
use App\Libraries\generate_random;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
class PermohonanC extends ResourceController
{
    use ResponseTrait;
    public function __construct(){
        $this->pdt=new Parse_date_time();
        $this->userM=new userM();
        $this->permohonanM=new permohonanM();
        $this->rekeningM=new rekeningM();
        $this->transaksiM=new transaksiM();
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
    public function Kirim_revisi_permohonan()
    {
        $data_valid=\Config\Services::validation();
        $data_valid->setRules([
        "ktp"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"masukkan KTP"
            ]
        ],
        "id_permohonan"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Masukkan ID permohonan",
                ]
            ],
        "ket_revisi"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Masukkan Keterangan revisi",
                ]
            ],
        "jumlah"=>[
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
        "id_rekening"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Masukkan rekening tujuan pinjaman diberikan",
                ]
            ],
        "denda"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Isi Denda Pinjaman (0.00-100.00)",
                ]
            ],
        "kegunaan"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Isi Kegunaan Pinjaman",
                ]
            ],
        "termin"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Isi Termin",
                ]
            ]
        ]);
        $cek_valid=$data_valid->withRequest($this->request)->run();
        if($cek_valid){
            $cek_data_pinjaman=$this->permohonanM->read_one($this->request->getPost('id_permohonan'));
            if(count($cek_data_pinjaman)>0){
                if(($cek_data_pinjaman[0]->acc_l==1)&&($cek_data_pinjaman[0]->acc_b==1)){
                    return $this->respond(["message"=>"Pinjaman sudah dikunci, tidak bisa di revisi"],502);
                }
                else{
                    if($cek_data_pinjaman[0]->ktp_lender!=$this->request->getPost('ktp')){
                        return $this->respond(["message"=>"peminjamm tidak menawarkan kepada anda"],502);
                    }
                    else{
                        $tanggal_revisi=date('Y-m-d H:i:s');
                        $data_pinjaman=[
                            $this->request->getPost('jumlah'),
                            $this->request->getPost('id_rekening'),
                            $this->request->getPost('kegunaan'),
                            $this->request->getPost('tanggal_pengembalian'),
                            $this->request->getPost('termin'),
                            $this->request->getPost('denda'),
                            $tanggal_revisi,
                            $this->request->getPost('ket_revisi'),
                            $this->request->getPost('id_permohonan'),
                        ];
                        $revisi_permohonan=$this->permohonanM->revisi_permohonan($data_pinjaman);
                        if($revisi_permohonan){
                            $borower=$this->userM->read($this->permohonanM->read_one([$this->request->getPost('id_permohonan')])[0]->ktp_borrower);
                            $lender=$this->userM->read($this->permohonanM->read_one([$this->request->getPost('id_permohonan')])[0]->ktp_lender);
                            $tanggal=date_create($tanggal_revisi);
                            $hari=$this->pdt->parse_day($tanggal->format("D"));
                            $bulan=$this->pdt->parse_month($tanggal->format("M"));
                            $waktu=$hari." ".$tanggal->format('d')." ".$bulan." ".$tanggal->format("Y")." ".$tanggal->format("H").":".$tanggal->format("i");
                            $this->se->tujuan=$borower[0]->email;
                            $this->se->subject="informasi Revisi Permohonan Pinjaman";
                            $this->se->body="Salam, pada hari $waktu, Saudara/i bernama ".$lender[0]->nama." telah melakukan revisi terhadap pinjaman anda, untuk selanjutnya bisa anda lihat di aplikasi utangin.com, terimakasih";
                            $this->se->send();
                            return $this->respond(["pesan"=>"Revisi berhasil"],200);
                        }
                        else{
                            return $this->respond(["pesan"=>"Error edit pinjaman"],200);
                        }
                    }
                }
                
            }  
            else{
                return $this->respond(["message"=>"Data tidak ada"],502);
            }          
        }
        else{
            return $this->respond(["message"=>$data_valid->getErrors()],502);
        }
    }
    public function Kirim_permohonan()
    {
        $data_valid=\Config\Services::validation();
        $data_valid->setRules([
        "ktp_borrower"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"KTP Tidak Boleh Kosong",
                ]
            ],
        "ktp_lender"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"KTP Tujuan Tidak Boleh Kosong",
                ]
            ],
        "jumlah"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Isi Jumlah Pinjaman yang ditawarkan",
                ]
            ],
        "tanggal_pengembalian"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Isi Tenggat Waktu",
                ]
            ],
        "id_rekening"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Masukkan rekening tujuan pinjaman diberikan",
                ]
            ],
        "denda"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Isi Denda Pinjaman (0.00-100.00)",
                ]
            ],
        "kegunaan"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Isi Kegunaan Pinjaman",
                ]
            ],
        "termin"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Isi Termin",
                ]
            ]
        ]);
        $cek_valid=$data_valid->withRequest($this->request)->run();
        if($cek_valid){            
            $cek_verifikasi=$this->userM->read($this->request->getPost('ktp_borrower'));
            if(count($cek_verifikasi)>0){
                if($cek_verifikasi[0]->status=="0"){
                    return $this->respond(["pesan"=>"Akun Belum Diverifikasi"],502);
                }
                else{
                    $cek_tujuan=$this->userM->cek_ktp($this->request->getPost('ktp_lender'));
                    if($cek_tujuan[0]->jumlah_data<0){
                        return $this->respond(["pesan"=>"KTP Tujuan Pengajuan tidak ditemukan"],502);
                    }
                    else{
                        $tanggal_data=date("Y-m-d H:i:s");
                        $d=[
                            $this->gr->ranstring(10),
                            $this->request->getPost('ktp_borrower'),
                            $this->request->getPost('ktp_lender'),
                            $tanggal_data,
                            $this->request->getPost('jumlah'),     
                            $this->request->getPost('id_rekening'),
                            $this->request->getPost('kegunaan'),        
                            $this->request->getPost('tanggal_pengembalian'),
                            $this->request->getPost('termin'),
                            $this->request->getPost('denda'),
                            0,
                            1,
                            0,
                            0,
                            "-"
                        ];
                        $create=$this->permohonanM->create($d);
                        if($create){
                            $email_tujuan_permohonan=$this->userM->read($this->request->getPost('ktp_lender'));
                            $tanggal=date_create($tanggal_data);
                            $hari=$this->pdt->parse_day($tanggal->format("D"));
                            $bulan=$this->pdt->parse_month($tanggal->format("M"));
                            $waktu=$hari." ".$tanggal->format('d')." ".$bulan." ".$tanggal->format("Y")." ".$tanggal->format("H").":".$tanggal->format("i");
                            $this->se->tujuan=$email_tujuan_permohonan[0]->email;
                            $this->se->subject="informasi Permohonan Pinjaman";
                            $this->se->body="Salam, pada hari $waktu, Saudara/i bernama ".$cek_verifikasi[0]->nama." telah mengirimkan permohonan pinjaman kepada anda
                            sebanyak ".$this->request->getPost('jumlah')." dengan denda ".$this->request->getPost('denda')."% per hari, untuk selanjutnya silahkan anda pertimbangkan untuk menerima tawaran pinjaman atau tidak, terimakasih";
                            $this->se->send();
                            return $this->respond(["pesan"=>"Permohonan Pinjaman Telah Dikirim"],200);
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
            return $this->respond(["pesan"=>$data_valid->getErrors()],502);
        }
    }
    public function Permohonan_kepada_saya($d){
        $data=$this->permohonanM->read_ktp_lender($d);
        for($i=0;$i<count($data);$i++){
            $data_transaksi=$this->transaksiM->read_permohonan($data[$i]->id_permohonan);
            $status_pinjaman = "X";
            if((count($data_transaksi) >0 )) {
                $status_pinjaman=$data_transaksi[0]->status;
            }
            $databaru = [
                "id_permohonan"=>$data[$i]->id_permohonan,
                "tanggal_pengajuan"=>$data[$i]->tanggal_pengajuan,
                "nama_borrower"=>$data[$i]->nama_borrower,
                "jumlah"=>$data[$i]->jumlah,
                "acc_l"=>$data[$i]->acc_l,
                "acc_b"=>$data[$i]->acc_b,
                "revisi"=>$data[$i]->revisi,
                "status"=>$status_pinjaman,
            ];
            $data[$i]=$databaru;
        }
        return $this->respond($data,200);
    }

    public function Detail_permohonan($d){
        $data=$this->permohonanM->read_one($d);
        $data_rekening=$this->rekeningM->read_one($data[0]->id_rekening);
        $data[0]->no_rek="";
        $data[0]->bank="";
        if(count($data_rekening)>0) {
            $data[0]->no_rek=$data_rekening[0]->no_rek;
            $data[0]->bank=$data_rekening[0]->bank;
        }
        return $this->respond($data,200);
    }

    public function Permohonan_dari_saya($d){
        $data=$this->permohonanM->read_ktp_borrower($d);
        for($i=0;$i<count($data);$i++){
            $data_transaksi=$this->transaksiM->read_permohonan($data[$i]->id_permohonan);
            $status_pinjaman = "X";
            if((count($data_transaksi) >0 )) {
                $status_pinjaman=$data_transaksi[0]->status;
            }
            $databaru = [
                "id_permohonan"=>$data[$i]->id_permohonan,
                "tanggal_pengajuan"=>$data[$i]->tanggal_pengajuan,
                "nama_lender"=>$data[$i]->nama_lender,
                "jumlah"=>$data[$i]->jumlah,
                "acc_l"=>$data[$i]->acc_l,
                "acc_b"=>$data[$i]->acc_b,
                "status"=>$status_pinjaman,
            ];
            $data[$i]=$databaru;
        }
        return $this->respond($data,200);
    }
    public function ACC_lender($d){
        $data=$this->permohonanM->read_one($d);
        if($data[0]->ktp_lender!=$this->request->getPost('ktp')){
            return $this->respond(["pesan"=>"anda bukan lender pinjaman"],502);
        }
        else{
            $data=$this->permohonanM->acc_lender($d);
            if($data){
                return $this->respond(["pesan"=>"pinjaman di ACC lender"],200);
            }
            else{
                return $this->respond(["pesan"=>"terjadi kesalahan"],502);
            }
        }
    }
    public function ACC_borrower($d){
        $data=$this->permohonanM->read_one($d);
        if($data[0]->ktp_borrower!=$this->request->getPost('ktp')){
            return $this->respond(["pesan"=>"anda bukan peminjam"],502);
        }
        else{
            $data=$this->permohonanM->acc_borrower($d);
            if($data){
                return $this->respond(["pesan"=>"pinjaman di ACC borrower"],200);
            }
            else{
                return $this->respond(["pesan"=>"terjadi kesalahan"],502);
            }
        }
    }
    
}
