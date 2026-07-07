<?php


namespace core\user\controllers;
use core\user\models\Model;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;


class SendMailController extends BaseUser
{
    private $_body = '';

    private $_ErrorInfo = '';

    protected function inputData(){
        parent::inputData;
    }

    public function setMailBody($body){

        if (is_array($body)){

            $body = implode("\n", $body);
        }

        $this->_body .= $body;

        return $this;

    }

    public function send($email=null, $subject=null){

        !$this->model&& $this->model = Model::instance();

        $to = [];

        if(!$this->set){

            $this->set = $this->model->get('settings', [
                'order' => ['id'],
                'limit' => 1
            ]);

            $this->set && $to[] = $this->set[0]['email'];
        }

        if ($email){

            $to[] = $email;
        }

        $mailConfigFile = dirname(__DIR__, 3) . '/mail.local.php';
        $mailConfig = is_file($mailConfigFile) ? require $mailConfigFile : [];
        if (!is_array($mailConfig)) {
            $mailConfig = [];
        }

        $mail = new PHPMailer(true);

        try {
            //Server settings
//            $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
            $mail->isSMTP();                                            //Send using SMTP
            $mail->Host       = $mailConfig['host'] ?? 'localhost';                     //Set the SMTP server to send through
            $mail->SMTPAuth   = (bool)($mailConfig['smtp_auth'] ?? false);                                   //Enable SMTP authentication
            $mail->Username   = $mailConfig['username'] ?? '';                     //SMTP username
            $mail->Password   = $mailConfig['password'] ?? '';                               //SMTP password
            $mail->SMTPSecure = $mailConfig['smtp_secure'] ?? PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
            $mail->Port       = (int)($mailConfig['port'] ?? 465);                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

            //Recipients
            $mail->setFrom(
                $mailConfig['from_email'] ?? 'no-reply@example.local',
                ($mailConfig['from_name'] ?? 'Website request') . ' - ' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
            );
//            $mail->addAddress('joe@example.net', 'Joe User');     //Add a recipient


//            $mail->addAddress('ellen@example.com');               //Name is optional

            foreach ($to as $address){

                $mail->addAddress($address);

            }
            $mail->addReplyTo($mailConfig['reply_to'] ?? ($mailConfig['from_email'] ?? 'no-reply@example.local'));
//            $mail->addCC('cc@example.com');
//            $mail->addBCC('bcc@example.com');

            //Attachments
//            $mail->addAttachment('/var/tmp/file.tar.gz');         //Add attachments
//            $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    //Optional name

            //Content
            $mail->isHTML(true);                                  //Set email format to HTML
            $mail->Subject = $subject  ?: 'Замовлення від друкарні Smile - ' . $_SERVER['HTTP_HOST'];
            $mail->Body    = $this->_body;
//            $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

            $mail->send();
            return true;
        } catch (Exception $e) {

            $this->_ErrorInfo = $mail->ErrorInfo;

        }
        return false;

    }

    public function getMailError (){

        return $this->_ErrorInfo;
    }
}
