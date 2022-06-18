<?php

namespace App\Controllers\No_login;
use App\Models\userM;
use App\Models\loginM;
use App\Libraries\Parse_date_time;
use App\Libraries\Send_email;
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
        $this->userM=new userM();
        $this->loginM=new loginM();
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
    public function Login()
    {
        $valid=\Config\Services::validation();
        $valid->setRules([
            "email"=>[
                "rules"=>"required",
                "errors"=>[
                    "required"=>"Email tidak boleh kosong",
                    ]
                ],
            "password"=>[
                    "rules"=>"required",
                    "errors"=>[
                        "required"=>"Password tidak boleh kosong",
                        ]
                    ],
            ]);
            $data_valid=$valid->withRequest($this->request)->run();
            if($data_valid){
                $cek_email=$this->userM->cek_email($this->request->getPost('email'));
                if($cek_email[0]->jumlah_data<=0){
                    return $this->respond(["status"=>'0',"message"=>"Email Tidak Terdaftar"],502);
                }
                else{
                    $read_email=$this->userM->read_email($this->request->getPost('email'));
                    $password=password_verify($this->request->getPost('password'),$read_email[0]->pass);
                    if($password){
                        if($read_email[0]->status=="1"){
                            // $data_login=[
                            //     $this->request->getPost('id_device'),
                            //     $read_email[0]->ktp,
                            //     date("Y-m-d H:i:s")
                            // ];
                            // $cre_login=$this->loginM->create($data_login);
                            return $this->respond(["status"=>'1',"message"=>"Login Berhasil","ktp"=>$read_email[0]->ktp],200);
                        }                        
                        else{
                            return $this->respond(["status"=>'3',"message"=>"Email Belum Diverifikasi"],502);
                        }
                    }
                    else{
                        return $this->respond(["status"=>'2',"message"=>"Password Salah"],502);
                    }
                }
            }
            else{
                return $this->respond(["message"=>$valid->getErrors()],502);
            }
    }

    public function Logout($id)
    {
        $cek_login=$this->loginM->read($id);
        if(count($cek_login)>0){
            $hapus=$this->loginM->delete($id);
            if($hapus){
                return $this->respond(["pesan"=>"logout berhasil"],200);
            }
            else{
                return $this->respond(["pesan"=>"logout gagal"],502);
            }
        }
        else{
            return $this->respond(["pesan"=>"anda tidak sedang login"],200);
        }
    }

    public function Sign_up()
    {
        $valid=\Config\Services::validation();
        $valid->setRules([
            "ktp"=>[
                "rules"=>"required|exact_length[16]",
                "errors"=>[
                    "required"=>"KTP tidak boleh Kosong",
                    "exact_length"=>"Jumlah nomor ktp kurang"
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
                    "required"=>"Alamat tidak boleh Kosong",
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
                date('Y-m-d h:i:s'),
                $this->request->getPost('npwp'),
                $this->request->getPost('alamat'),
                $this->request->getPost('rt'),
                $this->request->getPost('provinsi'),
                $this->request->getPost('pendidikan'),
                $this->request->getPost('pekerjaan'),
                $nama_ktp,
                $nama_selfie,
                $nama_ttd,
                $this->request->getPost('no_hp'),
                "1"
            ];
            $simpan=$this->userM->create($data);
            if($simpan){
                move_uploaded_file($_FILES['foto_ktp']['tmp_name'],"uploads/Foto_ktp/".$nama_ktp);
                move_uploaded_file($_FILES['foto_selfie']['tmp_name'],"uploads/Foto_selfie/".$nama_selfie);
                move_uploaded_file($_FILES['foto_ttd']['tmp_name'],"uploads/Foto_ttd/".$nama_ttd);
                return $this->respond(["status"=>"true","message"=>"Pendaftaran Berhasil","ktp"=>$this->request->getPost('ktp')],200);
            }
            else{
                return $this->respond(["status"=>"false","message"=>"Terjadi Kesalahan"],502);
            }
            
        }
        else{
            return $this->respond(["status"=>"false","message"=>$valid->getErrors()],502);
        }
        
    }
    public function Cek_email()
    {
        $cek_email=$this->userM->read_email($_GET['email']);
        if(count($cek_email)<=0){
            return $this->respond(["status"=>"false","message"=>"Email Siap Digunakan"],200);
        }
        else{
            if($cek_email[0]->status=='0'){
                return $this->respond(["status"=>"true","message"=>"Email Belum Diverifikasi"],502);    
            }
            else if($cek_email[0]->status=='1'){
                return $this->respond(["status"=>"true","message"=>"Email Sudah Terdaftar"],502);    
            }
            else if($cek_email[0]->status=='2'){
                return $this->respond(["status"=>"false","message"=>"Email Siap Digunakan"],200);    
            }
        }
    }

    public function Cek_ktp()
    {
        $cek_ktp=$this->userM->read_ktp($_GET['ktp']);
        if(count($cek_ktp)<=0){
            return $this->respond(["status"=>"false"],200);
        }
        else{
            if($cek_ktp[0]->status=='0'){
                return $this->respond(["status"=>"true"],502);
            }
            else if($cek_ktp[0]->status=='1'){
                return $this->respond(["status"=>"true"],502);
            }
            else if($cek_ktp[0]->status=='2'){
                return $this->respond(["status"=>"false"],200);
            }
        }
    }

    public function Cek_login($id) {
        $data = $this->loginM->read($id);
        if(count($data)>0) {
            return $this->respond(["status"=>"true"],200);
        } else {
            return $this->respond(["status"=>"false"],502);
        }
    }

    public function kirim_otp()
    {
        $otp=$this->gr->rannum(5);
        $this->se->tujuan=$this->request->getPost('email');
        $this->se->subject="Verifikasi Email";
        $this->se->body="<p style='font-size:24px; font-weight:bold;'>Kode OTP anda adalah</p>
        <p style='font-size:48px; font-weight:bold;'>$otp<p>";
        $kirim_email=$this->se->send();
        if($kirim_email){
            return $this->respond(["status"=>"true","message"=>"OTP Berhasil dikirim", "otp" => $otp],200);
        }
        else{
            return $this->respond(["status"=>"false","message"=>"OTP Gagal dikirim", "otp" => ""],502);
        }
    }
    
}
