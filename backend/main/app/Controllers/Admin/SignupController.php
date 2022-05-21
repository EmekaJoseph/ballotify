<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class SignupController extends BaseController
{

    public function checkIfEmailExists($email)
    {
        $val = false;
        if ($email == 'emeka@mail.com') {
            $val = true;
        }
        return $this->response->setJSON($val);
    }

    public function checkIfOrgExists($orgName)
    {
        $val = false;
        if ($orgName == 'emeka') {
            $val = true;
        }
        return $this->response->setJSON($val);
    }

    public function registerNew($recievedJSON)
    {
        $data = json_decode($recievedJSON);
        $response = 1;
        return $this->response->setJSON($response);
    }
}


//  $email = $this->request->getVar('email');
//$encodedVotersList = json_decode($votersList);