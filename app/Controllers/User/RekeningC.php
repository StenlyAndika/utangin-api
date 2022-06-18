<?php

namespace App\Controllers\User;
use App\Models\userM;
use App\Models\rekeningM;
use App\Libraries\Parse_date_time;
use App\Libraries\Send_email;
use App\Libraries\generate_random;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
class RekeningC extends ResourceController
{
    use ResponseTrait;
    public function __construct(){
        $this->pdt=new Parse_date_time();
        $this->userM=new userM();
        $this->rekeningM=new rekeningM();
        $this->gr=new generate_random();
        $this->se=new Send_email();
        $this->session=\Config\Services::session();
    }
    public function index()
    {
        $data=$this->userM->read_all();
        for($i=0;$i<count($data);$i++){
            $tgl=date_create($data[$i]->tgl_lahir);
            $hari=$this->pdt->parse_day(date_format($tgl,"D"));
            $bulan=$this->pdt->parse_month(date_format($tgl,"M"));
            $data[$i]->tgl_lahir_parse=$hari." ".date_format($tgl,"d")." ".$bulan." ".date_format($tgl,"Y");
        }
        return $this->respond($data,200);
    }
    public function Baca_ktp($a)
    {
        $data=$this->userM->cek_ktp($a);
        if($data[0]->jumlah_data>0){
            $data=$this->rekeningM->read_ktp($a);
            return $this->respond($data,200);
        }
        else{
            return $this->respond(["pesan"=>"KTP Tidak ada"],502);
        }
    }
    public function Tambah()
    {
        $data_valid=\Config\Services::validation();
        $data_valid->setRules([
        "ktp"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"KTP Tidak Boleh Kosong",
                ]
            ],
        "no_rek"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Silahkan Diisi Nomor Rekening",
                ]
            ],
        "bank"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Pilih Bank",
                ]
            ]
        ]);
        $cek_valid=$data_valid->withRequest($this->request)->run();
        if($cek_valid){
            $ktp=$this->request->getPost('ktp');
            $cek_data=$this->userM->read($ktp);
            if(count($cek_data)>0){
                if($cek_data[0]->status=="0"){
                    return $this->respond(["pesan"=>"Akun Belum Diverifikasi"],502);
                }
                else{
                    $cek_rek=$this->rekeningM->cek_rek([$this->request->getPost('no_rek'),$this->request->getPost('bank')]);
                    if($cek_rek[0]->jumlah_data<=0){
                        $d=[
                            $this->gr->ranstring(10),
                            $ktp,
                            $this->request->getPost('no_rek'),
                            $this->request->getPost('bank')
                        ];
                        $create=$this->rekeningM->create($d);
                        if($create){
                            return $this->respond(["pesan"=>"Rekening Berhasil Ditambahkan"],200);
                        }
                        else{
                            return $this->respond(["pesan"=>"Terjadi Kesalahan"],502);
                        }
                    }
                    else{
                        return $this->respond(["pesan"=>"Rekening Sudah Ada"],502);
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
    public function Hapus($id_rekening)
    {
            $hapus=$this->rekeningM->delete([$id_rekening]);
            if($hapus){
                return $this->respond(["pesan"=>"Rekening Berhasil Dihapus"],200);
            }
            else{
                return $this->respond(["pesan"=>"Terjadi Kesalahan"],502);
            }
    }
    public function Ubah($id_rekening)
    {
        $data_valid=\Config\Services::validation();
        $data_valid->setRules([
        "no_rek"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Masukkan Nomor Rekening Baru",
                ]
            ],
        "bank"=>[
            "rules"=>"required",
            "errors"=>[
                "required"=>"Pilih Bank",
                ]
            ]
        ]);
        $cek_valid=$data_valid->withRequest($this->request)->run();
        if($cek_valid){
            $cek_data=$this->rekeningM->read_one([$id_rekening]);
            $cek_rekening=$this->rekeningM->cek_rek([$this->request->getPost('no_rek'),$this->request->getPost('bank')]);
            echo $cek_rekening[0]->jumlah_data;
            if(($cek_rekening[0]->jumlah_data>0)){
                return $this->respond(["pesan"=>"No. Rek sudah dipakai"],502);
            }
            else{
                $d=[
                    $this->request->getPost('no_rek'),
                    $this->request->getPost('bank'),
                    $id_rekening
                ];
                $create=$this->rekeningM->update($d);
                    if($create){
                        return $this->respond(["pesan"=>"Rekening Berhasil Diupdate"],200);
                    }
                    else{
                        return $this->respond(["pesan"=>"Terjadi Kesalahan"],502);
                    }
            }
        }
        else{
            return $this->respond(["message"=>$data_valid->getErrors()],502);
        }
    }
 }
