<?php

namespace App\Controllers\User;
use App\Models\userM;
use App\Libraries\Parse_date_time;
use App\Libraries\Send_email;
use App\Libraries\generate_random;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
class Data_userC extends ResourceController
{
    use ResponseTrait;
    public function __construct(){
        $this->pdt=new Parse_date_time();
        $this->userM=new userM();
        $this->gr=new generate_random();
        $this->se=new Send_email();
        $this->session=\Config\Services::session();
    }
    // public function Read_email()
    // {
    //     $data=$this->userM->read_email($_GET['email']);
    //     for($i=0;$i<count($data);$i++){
    //         $newdata=["ktp"=>$data[$i]->ktp,"nama"=>$data[$i]->nama];
    //         $data[$i]=$newdata;
    //     }
    //     return $this->respond($data,200);
    // }

    public function Read_email()
    {
        $data=$this->userM->read_email($_GET['email']);
        return $this->respond($data,200);
    }
    public function Read_ktp()
    {
        $data=$this->userM->read_ktp($_GET['ktp']);
        return $this->respond($data,200);
    }
    
}