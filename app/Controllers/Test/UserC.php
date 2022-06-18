<?php

namespace App\Controllers\Test;
use App\Models\userM;
use App\Libraries\Parse_date_time;
use App\Libraries\generate_random;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class UserC extends ResourceController
{
    use ResponseTrait;
    public function __construct(){
        $this->pdt=new Parse_date_time();
        $this->model=new userM();
        $this->gr=new generate_random();
        $this->session=\Config\Services::session();
    }
    public function index()
    {
        $data=$this->model->read_all();
        for($i=0;$i<count($data);$i++){
            $tgl=date_create($data[$i]->tgl_lahir);
            $hari=$this->pdt->parse_day(date_format($tgl,"D"));
            $bulan=$this->pdt->parse_month(date_format($tgl,"M"));
            $data[$i]->tgl_lahir_parse=$hari." ".date_format($tgl,"d")." ".$bulan." ".date_format($tgl,"Y");
            //echo date_format($tgl,"D")." ".date_format($tgl,"d")." ".date_format($tgl,"M")." ".date_format($tgl,"Y");
        }
        return $this->respond($data,200);
    }
    public function Aaa()
    {
        echo $this->session->get('ktp');
    }
    public function Create()
    // {
    //     print_r($_FILES['foto_ktp']);
    // }
    {
        $valid=\Config\Services::validation();
        $valid->setRules([
            "ktp"=>[
                "rules"=>"required|exact_length[16]",
                "errors"=>[
                    "required"=>"Nama tidak boleh Kosong",
                    "exact_length"=>"jumlah nomor ktp kurang"
                    ]
                ],
            "email"=>[
                "rules"=>"required|valid_email",
                "errors"=>[
                    "required"=>"Email tidak boleh Kosong",
                    "valid_email"=>"Format Email Salah"
                ]
            ],
            "nama"=>[
                "rules"=>"required",
                "errors"=>[
                    "required"=>"Nama Tidak Boleh Kosong"
                ]
            ],
            "pass"=>[
                "rules"=>"required|min_length[5]|max_length[12]",
                "errors"=>[
                    "required"=>"Password tidak boleh Kosong",
                    "min_length"=>"Password harus lebih dari 5",
                    "max_length"=>"Password tidak boleh lebih dari 12"
                ]
            ],
            "pass_konf"=>[
                "rules"=>"required|min_length[5]|max_length[12]|matches[pass]",
                "errors"=>[
                    "required"=>"Password Konfirmasi tidak boleh Kosong",
                    "min_length"=>"Password Konfirmasi harus lebih dari 5",
                    "max_length"=>"Password Konfirmasi tidak boleh lebih dari 12",
                    "matches"=>"Password Konfirmasi Tidak Sama"
                ]
            ],
            "tgl_lahir"=>[
                "rules"=>"required|valid_date[Y-m-d]",
                "errors"=>[
                    "required"=>"Tanggal tidak boleh Kosong",
                    "valid_date"=>"Format Tanggal tidak valid",
                ]
            ],
            "alamat"=>[
                "rules"=>"required",
                "errors"=>[
                    "required"=>"alamat tidak boleh Kosong",
                ]
            ]
            ,
            "provinsi"=>[
                "rules"=>"required",
                "errors"=>[
                    "required"=>"Provinsi tidak boleh Kosong",
                ]
            ],
            "pendidikan"=>[
                "rules"=>"required",
                "errors"=>[
                    "required"=>"Pendidikan tidak boleh Kosong",
                ]
            ],
            "pekerjaan"=>[
                "rules"=>"required",
                "errors"=>[
                    "required"=>"Pekerjaan tidak boleh Kosong",
                ]
            ],
            "no_hp"=>[
                "rules"=>"required|numeric",
                "errors"=>[
                    "required"=>"NO Hp tidak boleh Kosong",
                    "numeric"=>"Format Nomor HP Salah"
                ]
            ],
            "foto_ktp"=>[
                "rules"=>"uploaded[foto_ktp]|mime_in[foto_ktp,image/jpg,image/jpeg,image/png]|max_size[foto_ktp,5020]",
                "errors"=>[
                    "uploaded"=>"Mohon Lampirkan Foto KTP",
                    "mime_in"=>"Format gambar harus jpg,jpeg,atau png",
                    "max_size"=>"Ukuran maksimum 5mb"
                ]
            ],
            "foto_selfie"=>[
                "rules"=>"uploaded[foto_selfie]|mime_in[foto_selfie,image/jpg,image/jpeg,image/png]|max_size[foto_selfie,5020]",
                "errors"=>[
                    "uploaded"=>"Mohon Lampirkan Foto Selfie",
                    "mime_in"=>"Format gambar harus jpg,jpeg,atau png",
                    "max_size"=>"Ukuran maksimum 5mb"
                ]
            ],
            "foto_ttd"=>[
                "rules"=>"uploaded[foto_ttd]|mime_in[foto_ttd,image/jpg,image/jpeg,image/png]|max_size[foto_ttd,5020]",
                "errors"=>[
                    "uploaded"=>"Mohon Lampirkan Foto Tanda Tangan",
                    "mime_in"=>"Format gambar harus jpg,jpeg,atau png",
                    "max_size"=>"Ukuran maksimum 5mb"
                ]
            ]
        ]);
        $data_valid=$valid->withRequest($this->request)->run();
        if($data_valid){
            $cek_ktp=$this->model->cek_ktp($this->request->getPost('ktp'));
            $cek_email=$this->model->cek_email($this->request->getPost('email'));
            if($cek_ktp[0]->jumlah_data>0){
                return $this->respond(["error"=>true,"message"=>"KTP ini sudah dipakai"],502);    
            }
            else if($cek_email[0]->jumlah_data>0){
                return $this->respond(["error"=>true,"message"=>"Email sudah dipakai"],502);    
            }
            else{
                $namafile=$string=strtotime(date("Y-m-d h:i:s"));
                $nama_ktp="KTP_".$namafile.".".explode(".",$_FILES['foto_ktp']['name'])[1];
                $nama_selfie="FOSEL_".$namafile.".".explode(".",$_FILES['foto_selfie']['name'])[1];
                $nama_ttd="TTD_".$namafile.".".explode(".",$_FILES['foto_ttd']['name'])[1];
                $data=[
                    $this->request->getPost('ktp'),
                    $this->request->getPost('nama'),
                    $this->request->getPost('email'),
                    password_hash($this->request->getPost('pass'),PASSWORD_BCRYPT),
                    $this->request->getPost('jk'),
                    $this->request->getPost('tgl_lahir'),
                    $this->request->getPost('npwp'),
                    $this->request->getPost('alamat'),
                    $this->request->getPost('rt'),
                    $this->request->getPost('provinsi'),
                    $this->request->getPost('pendidikan'),
                    $this->request->getPost('pekerjaan'),
                    $nama_ktp,
                    $nama_selfie,
                    $nama_ttd,
                    "0",
                    $this->request->getPost('no_hp'),
                ];
                $simpan=$this->model->create($data);
                if($simpan){
                    move_uploaded_file($_FILES['foto_ktp']['tmp_name'],"uploads/Foto_ktp/".$nama_ktp);
                    move_uploaded_file($_FILES['foto_selfie']['tmp_name'],"uploads/Foto_selfie/".$nama_selfie);
                    move_uploaded_file($_FILES['foto_ttd']['tmp_name'],"uploads/Foto_ttd/".$nama_ttd);
                    return $this->respond(["error"=>false,"message"=>"DATA SUDAH DISIMPAN"],200);
                }
                else{
                    return $this->respond(["error"=>true,"message"=>"TERJADI KESALAHAN"],502);
                }
            }
            
        }
        else{
            return $this->respond(["error"=>true,"message"=>$valid->getErrors()],502);
        }
        
    }
}
