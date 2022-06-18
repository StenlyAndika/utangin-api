<?php
namespace App\Libraries;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Send_email{
    function __construct(){
        $this->tujuan="";
        $this->subject = "";
		$this->body = "";
    }
    function send(){
        require 'vendor/autoload.php';

		$mail = new PHPMailer;
		$mail->isSMTP();
		$mail->SMTPOptions = array(
			'ssl' => array(
			'verify_peer' => false,
			'verify_peer_name' => false,
			'allow_self_signed' => true
			)
			);
        $mail->Host="utangin.com";
		// $mail->SMTPDebug = 1;
		$mail->Port = 587;
		$mail->SMTPSecure = 'tls';
		$mail->SMTPAuth = true;
		$mail->Username = "contact@utangin.com";
		$mail->Password = "Ut4ng1n.com";
		$mail->setFrom("contact@utangin.com", "Utangin.com Support Center");
		$mail->addAddress($this->tujuan);
		$mail->IsHTML(true);
		$mail->Subject = $this->subject;
		$mail->Body = $this->body;

		if (!$mail->send()) {
		    return false;
		} else {
		    return true;
		}	
    }
}

?>