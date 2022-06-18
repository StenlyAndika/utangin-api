<?php

namespace App\Controllers\User;
use App\Models\userM;
use App\Models\revisi_permohonanM;
use App\Models\permohonanM;
use App\Libraries\Parse_date_time;
use App\Libraries\Send_email;
use App\Libraries\generate_random;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
class Revisi_permohonanC extends ResourceController
{
    use ResponseTrait;
    public function __construct(){
        $this->pdt=new Parse_date_time();
        $this->userM=new userM();
        $this->permohonanM=new permohonanM();
        $this->revisi_permohonanM=new revisi_permohonanM();
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
    public function Daftar_revisi($d){
        $data=$this->revisi_permohonanM->read_permohonan($d);
        return $this->respond($data,200);
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
                        $data_revisi=[
                            $this->gr->ranstring(10),
                            $this->request->getPost('id_permohonan'),
                            $this->request->getPost('ket_revisi'),
                            $tanggal_revisi,
                            $this->request->getPost('jumlah'),
                            $this->request->getPost('id_rekening'),
                            $this->request->getPost('kegunaan'),
                            $this->request->getPost('tanggal_pengembalian'),
                            $this->request->getPost('termin'),
                            $this->request->getPost('denda'),
                        ];
                        $create_revisi=$this->revisi_permohonanM->create($data_revisi);
                        if($create_revisi){
                            $data_pinjaman=[
                                $this->request->getPost('jumlah'),
                                $this->request->getPost('id_rekening'),
                                $this->request->getPost('kegunaan'),
                                $this->request->getPost('tanggal_pengembalian'),
                                $this->request->getPost('termin'),
                                $this->request->getPost('denda'),
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
                        else{
                            return $this->respond(["message"=>"kesalahan input revisi"],502);
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
    public function ACC_lender($d){
        $data=$this->revisi_permohonanM->read_one($d);
        if($data[0]->ktp_lender!=$this->request->getPost('ktp_lender')){
            return $this->respond(["pesan"=>"anda bukan lender pinjaman",502]);
        }
    }
    
}
