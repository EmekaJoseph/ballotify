<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class SendMail extends Controller
{
    public function sendMail($to, $subject, $message)
    {
        $email = \Config\Services::email();
        $email->setTo($to);
        $email->setFrom('support@ballotify.com', 'noreply');
        $email->setSubject($subject);
        $email->setMessage($message);

        //$email->setCC('another@emailHere'); 
        // $email->setBCC('thirdEmail@emialHere'); 
        //$filename = '/img/yourPhoto.jpg'; 
        //$email->attach($filename);

        try {
            $email->send();
        } catch (\Throwable $th) {
            //throw $th;
        }
    }
}
