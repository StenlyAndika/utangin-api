<?php

namespace App\Controllers\User;
use App\Models\userM;
use App\Models\pengajuanM;
use App\Libraries\Parse_date_time;
use App\Libraries\Send_email;
use App\Libraries\generate_random;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
class PengajuanC extends ResourceController
{
    use ResponseTrait;
    public function __construct(){
        $this->pdt=new Parse_date_time();
        $this->userM=new userM();
        $this->pengajuanM=new pengajuanM();
        $this->gr=new generate_random();
        $this->se=new Send_email();
        $this->session=\Config\Services::session();
    }
    public function index()
    {
        $data=$this->userM->read();
        for($i=0;$i<count($data);$i++){
            $tgl=date_create($data[$i]->tgl_lahir);
            $hari=$this->pdt->parse_day(date_format($tgl,"D"));
            $bulan=$this->pdt->parse_month(date_format($tgl,"M"));
            $data[$i]->tgl_lahir_parse=$hari." ".date_format($tgl,"d")." ".$bulan." ".date_format($tgl,"Y");
        }
        return $this->respond($data,200);
    }
    public function Kirim_pengajuan()
    {
        $data_valid=\Config\Services::validation();
        $data_valid->setRules([
        "ktp_asal"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"KTP Tidak Boleh Kosong",
                ]
            ],
        "ktp_tujuan"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"KTP Tujuan Tidak Boleh Kosong",
                ]
            ],
        "tgl_pinjaman"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Isi Tanggal Pinjaman",
                ]
            ],
        "jml_pinjaman"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Isi Jumlah Pinjaman",
                ]
            ],
        "kegunaan"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Isi Kegunaan Pinjaman",
                ]
            ],
        "tgl_pengembalian"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Tanggal Pengembalian Harus diisi",
                ]
            ],
        "termin"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Isi Termin",
                ]
            ],
        "sat_termin"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Isi Satuan Termin",
                ]
            ],
        "denda"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Isi Denda Pinjaman (0.00-100.00)",
                ]
            ],
        "sat_denda"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Isi Satuan Denda (Hari, Minggu, Bulan)",
                ]
            ]
        ]);
        $cek_valid=$data_valid->withRequest($this->request)->run();
        if($cek_valid){            
            $cek_verifikasi=$this->userM->read($this->request->getPost('ktp_asal'));
            if(count($cek_verifikasi)>0){
                if($cek_verifikasi[0]->status=="0"){
                    return $this->respond(["pesan"=>"Akun Belum Diverifikasi"],502);
                }
                else{
                    $cek_tujuan=$this->userM->cek_ktp($this->request->getPost('ktp_tujuan'));
                    if($cek_tujuan[0]->jumlah_data<0){
                        return $this->respond(["pesan"=>"KTP Tujuan Pengajuan tidak ditemukan"],502);
                    }
                    else{
                        $tanggal_data=date("Y-m-d H:i:s");
                        $d=[
                            $this->gr->ranstring(10),
                            $this->request->getPost('ktp_asal'),
                            $this->request->getPost('ktp_tujuan'),
                            $tanggal_data,
                            $this->request->getPost('tgl_pinjaman'),
                            $this->request->getPost('jml_pinjaman'),
                            $this->request->getPost('kegunaan'),
                            $this->request->getPost('tgl_pengembalian'),
                            $this->request->getPost('termin'),
                            $this->request->getPost('sat_termin'),
                            $this->request->getPost('denda'),
                            $this->request->getPost('sat_denda'),
                            "TB",
                            0
                        ];
                        $create=$this->pengajuanM->create($d);
                        if($create){
                            $email_peminjam=$cek_verifikasi[0]->email;
                            $email_tujuan_pinjam=$this->userM->read($this->request->getPost('ktp_tujuan'));
                            $tanggal=date_create($tanggal_data);
                            $hari=$this->pdt->parse_day($tanggal->format("D"));
                            $bulan=$this->pdt->parse_month($tanggal->format("M"));
                            $waktu=$hari." ".$tanggal->format('d')." ".$bulan." ".$tanggal->format("Y")." ".$tanggal->format("H").":".$tanggal->format("i");
                            $this->se->tujuan=$email_tujuan_pinjam[0]->email;
                            $this->se->subject="informasi Permohonan Pinjaman";
                            $this->se->body="Salam, pada tanggal $waktu Saudara/i bernama ".$cek_verifikasi[0]->nama." telah mengajukan pinjaman kepada anda
                            sebanyak ".$this->request->getPost('jml_pinjaman').", untuk selanjutnya silahkan anda pertimbangkan untuk menerima permohonan pinjaman atau tidak, terimakasih";
                            $this->se->send();
                            return $this->respond(["pesan"=>"Pengajuan Pinjaman Telah Dikirim"],200);
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
    public function Edit_pengajuan($id)
    {
        $data_valid=\Config\Services::validation();
        $data_valid->setRules([
        "tgl_pinjaman"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Isi Tanggal Pinjaman",
                ]
            ],
        "jml_pinjaman"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Isi Jumlah Pinjaman",
                ]
            ],
        "kegunaan"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Isi Kegunaan Pinjaman",
                ]
            ],
        "tgl_pengembalian"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Tanggal Pengembalian Harus diisi",
                ]
            ],
        "termin"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Isi Termin",
                ]
            ],
        "sat_termin"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Isi Satuan Termin",
                ]
            ],
        "denda"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Isi Denda Pinjaman (0.00-100.00)",
                ]
            ],
        "sat_denda"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Isi Satuan Denda (Hari, Minggu, Bulan)",
                ]
            ]
        ]);
        $cek_valid=$data_valid->withRequest($this->request)->run();
        if($cek_valid){            
            $d=[
                $this->request->getPost('tgl_pinjaman'),
                $this->request->getPost('jml_pinjaman'),
                $this->request->getPost('kegunaan'),
                $this->request->getPost('tgl_pengembalian'),
                $this->request->getPost('termin'),
                $this->request->getPost('sat_termin'),
                $this->request->getPost('denda'),
                $this->request->getPost('sat_denda'),
                1,
                $id
            ];
            $data_lama=$this->pengajuanM->read_one($id);
            if($data_lama[0]->keterangan=='TB'){
                $create=$this->pengajuanM->edit($d);
                if($create){
                    $user_peminjam=$this->userM->read($data_lama[0]->ktp_1);
                    $user_tujuan=$this->userM->read($data_lama[0]->ktp_2);
                    $tanggal=date_create(date('Y-m-d H:i:s'));
                    $hari=$this->pdt->parse_day($tanggal->format("D"));
                    $bulan=$this->pdt->parse_month($tanggal->format("M"));
                    $waktu=$hari." ".$tanggal->format('d')." ".$bulan." ".$tanggal->format("Y")." ".$tanggal->format("H").":".$tanggal->format("i");
                    $tanggal_revisi=date_create($data_lama[0]->tgl_data);
                    $hari=$this->pdt->parse_day($tanggal_revisi->format("D"));
                    $bulan=$this->pdt->parse_month($tanggal_revisi->format("M"));
                    $waktu_revisi=$hari." ".$tanggal_revisi->format('d')." ".$bulan." ".$tanggal_revisi->format("Y")." ".$tanggal_revisi->format("H").":".$tanggal_revisi->format("i");
                    $this->se->tujuan=$user_tujuan[0]->email;
                    $this->se->subject="informasi Revisi Permohonan Pinjaman";
                    $this->se->body="Salam, pada tanggal $waktu Saudara/i bernama ".$user_peminjam[0]->nama." telah melakukan revisi mengenai pinjaman yang diajukan kepada anda
                    pada tanggal $waktu_revisi, untuk selanjutnya silahkan anda pertimbangkan untuk menerima permohonan pinjaman atau tidak, terimakasih";
                    $this->se->send();
                    return $this->respond(["pesan"=>"Revisi Pengajuan Pinjaman Telah Dikirim"],200);
                }
                else{
                    return $this->respond(["pesan"=>"Terjadi Kesalahan"],502);
                }
            }
            else{
                return $this->respond(["pesan"=>"Data Tidak Memuat permohonan pinjaman"],502);
            }            
        }
        else{
            return $this->respond(["message"=>$data_valid->getErrors()],502);
        }
    }
    public function Edit_tawaran($id)
    {
        $data_valid=\Config\Services::validation();
        $data_valid->setRules([
        "tgl_pinjaman"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Isi Tanggal Pinjaman",
                ]
            ],
        "jml_pinjaman"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Isi Jumlah Pinjaman",
                ]
            ],
        "kegunaan"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Isi Kegunaan Pinjaman",
                ]
            ],
        "tgl_pengembalian"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Tanggal Pengembalian Harus diisi",
                ]
            ],
        "termin"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Isi Termin",
                ]
            ],
        "sat_termin"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Isi Satuan Termin",
                ]
            ],
        "denda"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Isi Denda Pinjaman (0.00-100.00)",
                ]
            ],
        "sat_denda"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Isi Satuan Denda (Hari, Minggu, Bulan)",
                ]
            ]
        ]);
        $cek_valid=$data_valid->withRequest($this->request)->run();
        if($cek_valid){            
            $d=[
                $this->request->getPost('tgl_pinjaman'),
                $this->request->getPost('jml_pinjaman'),
                $this->request->getPost('kegunaan'),
                $this->request->getPost('tgl_pengembalian'),
                $this->request->getPost('termin'),
                $this->request->getPost('sat_termin'),
                $this->request->getPost('denda'),
                $this->request->getPost('sat_denda'),
                1,
                $id
            ];
            $data_lama=$this->pengajuanM->read_one($id);
            if($data_lama[0]->keterangan=='TL'){
                $create=$this->pengajuanM->edit($d);
                if($create){
                    $data_lama=$this->pengajuanM->read_one($id);
                    $user_peminjam=$this->userM->read($data_lama[0]->ktp_1);
                    $user_tujuan=$this->userM->read($data_lama[0]->ktp_2);
                    $tanggal=date_create(date('Y-m-d H:i:s'));
                    $hari=$this->pdt->parse_day($tanggal->format("D"));
                    $bulan=$this->pdt->parse_month($tanggal->format("M"));
                    $waktu=$hari." ".$tanggal->format('d')." ".$bulan." ".$tanggal->format("Y")." ".$tanggal->format("H").":".$tanggal->format("i");
                    $tanggal_revisi=date_create($data_lama[0]->tgl_data);
                    $hari=$this->pdt->parse_day($tanggal_revisi->format("D"));
                    $bulan=$this->pdt->parse_month($tanggal_revisi->format("M"));
                    $waktu_revisi=$hari." ".$tanggal_revisi->format('d')." ".$bulan." ".$tanggal_revisi->format("Y")." ".$tanggal_revisi->format("H").":".$tanggal_revisi->format("i");
                    $this->se->tujuan=$user_tujuan[0]->email;
                    $this->se->subject="informasi Revisi Permohonan Pinjaman";
                    $this->se->body="Salam, pada tanggal $waktu Saudara/i bernama ".$user_peminjam[0]->nama." telah melakukan revisi mengenai tawaran pinjaman yang diajukan kepada anda
                    pada tanggal $waktu_revisi, untuk selanjutnya silahkan anda pertimbangkan untuk menerima permohonan pinjaman atau tidak, terimakasih";
                    $this->se->send();
                    return $this->respond(["pesan"=>"Revisi Tawaran Pinjaman Telah Dikirim"],200);
                }
                else{
                    return $this->respond(["pesan"=>"Terjadi Kesalahan"],502);
                }
            }
            else{
                return $this->respond(["pesan"=>"Data tidak memuat tawaran pinjaman"],502);
            }            
        }
        else{
            return $this->respond(["message"=>$data_valid->getErrors()],502);
        }
    }
    public function Daftar_tawaran_saya($d){
        $data=$this->pengajuanM->read_ktp([$d,"TL"]);
        return $this->respond($data,200);
    }
    public function Daftar_permohonan_saya($d){
        $data=$this->pengajuanM->read_ktp([$d,"TB"]);
        return $this->respond($data,200);
    }
    public function Kirim_tawaran()
    {
        $data_valid=\Config\Services::validation();
        $data_valid->setRules([
        "ktp_asal"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"KTP Tidak Boleh Kosong",
                ]
            ],
        "ktp_tujuan"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"KTP Tujuan Tidak Boleh Kosong",
                ]
            ],
        "tgl_pinjaman"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Isi Tanggal Pinjaman",
                ]
            ],
        "jml_pinjaman"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Isi Jumlah Pinjaman",
                ]
            ],
        "kegunaan"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Isi Kegunaan Pinjaman",
                ]
            ],
        "tgl_pengembalian"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Tanggal Pengembalian Harus diisi",
                ]
            ],
        "termin"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Isi Termin",
                ]
            ],
        "sat_termin"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Isi Satuan Termin",
                ]
            ],
        "denda"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Isi Denda Pinjaman (0.00-100.00)",
                ]
            ],
        "sat_denda"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Isi Satuan Denda (Hari, Minggu, Bulan)",
                ]
            ]
        ]);
        $cek_valid=$data_valid->withRequest($this->request)->run();
        if($cek_valid){            
            $cek_verifikasi=$this->userM->read($this->request->getPost('ktp_asal'));
            if(count($cek_verifikasi)>0){
                if($cek_verifikasi[0]->status=="0"){
                    return $this->respond(["pesan"=>"Akun Belum Diverifikasi"],502);
                }
                else{
                    $cek_tujuan=$this->userM->cek_ktp($this->request->getPost('ktp_tujuan'));
                    if($cek_tujuan[0]->jumlah_data<0){
                        return $this->respond(["pesan"=>"KTP Tujuan Pengajuan tidak ditemukan"],502);
                    }
                    else{
                        $tanggal_data=date("Y-m-d H:i:s");
                        $d=[
                            $this->gr->ranstring(10),
                            $this->request->getPost('ktp_asal'),
                            $this->request->getPost('ktp_tujuan'),
                            $tanggal_data,
                            $this->request->getPost('tgl_pinjaman'),
                            $this->request->getPost('jml_pinjaman'),
                            $this->request->getPost('kegunaan'),
                            $this->request->getPost('tgl_pengembalian'),
                            $this->request->getPost('termin'),
                            $this->request->getPost('sat_termin'),
                            $this->request->getPost('denda'),
                            $this->request->getPost('sat_denda'),
                            "TL",
                            0

                        ];
                        $create=$this->pengajuanM->create($d);
                        if($create){
                            $email_peminjam=$cek_verifikasi[0]->email;
                            $email_tujuan_pinjam=$this->userM->read($this->request->getPost('ktp_tujuan'));

                            $tanggal=date_create($tanggal_data);
                            $hari=$this->pdt->parse_day($tanggal->format("D"));
                            $bulan=$this->pdt->parse_month($tanggal->format("M"));
                            $waktu=$hari." ".$tanggal->format('d')." ".$bulan." ".$tanggal->format("Y")." ".$tanggal->format("H").":".$tanggal->format("i");
                            $this->se->tujuan=$email_tujuan_pinjam[0]->email;
                            $this->se->subject="informasi Permohonan Pinjaman";
                            $this->se->body="Salam, pada hari $waktu, Saudara/i bernama ".$cek_verifikasi[0]->nama." telah mengirimkan tawaran pinjaman kepada anda
                            sebanyak ".$this->request->getPost('jml_pinjaman')." dengan denda ".$this->request->getPost('denda')."% per ".$this->request->getPost('sat_denda')." untuk selanjutnya silahkan anda pertimbangkan untuk menerima tawaran pinjaman atau tidak, terimakasih";
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
    
}
