<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\OrgModel;

class SignupController extends BaseController
{

    public function ifEmailIsFound($email)
    {
        $usersTable = new UserModel();
        $exists = $usersTable->where('email', $email)->countAllResults();
        $val = false;
        if ($exists != 0) {
            $val = true;
        }
        return $this->response->setJSON($val);
    }

    public function ifOrgIsFound($orgName)
    {
        $orgTable = new OrgModel();
        $exists = $orgTable->where('org_name', $orgName)->countAllResults();
        $val = false;
        if ($exists != 0) {
            $val = true;
        }
        return $this->response->setJSON($val);
    }

    public function registerNew($recievedJSON)
    {
        $usersTable = new UserModel();
        $orgTable = new OrgModel();
        $data = json_decode($recievedJSON);
        $response = 0;
        $org_id = $this->getOrgId($data->org_name);
        try {
            $userProfile = [
                'firstname' => $data->firstname,
                'lastname' => $data->lastname,
                'email' => $data->email,
                'org_id' => $org_id,
                'password' => password_hash($data->password, PASSWORD_DEFAULT),
                'role' => 'master',
                'isActive' => 1
            ];

            $orgProfile = [
                'org_id' => $org_id,
                'org_name' => $data->org_name,
                'org_address' => $data->org_address,
                'org_events' => 0,
            ];
            $usersTable->save($userProfile);
            $orgTable->save($orgProfile);

            $response = 1;
        } catch (\Throwable $th) {
            //throw $th;
            $response = 0;
        }
        return $this->response->setJSON($response);
    }


    private function getOrgId($name)
    {
        $org_id = $this->generateOrgId($name);
        $orgTable = new OrgModel();
        $exists = $orgTable->where('org_id', $org_id)->countAllResults();
        if ($exists != 0) {
            $this->getOrgId($name);
        }
        return $org_id;
    }

    private function generateOrgId($name)
    {
        $string = $name;
        //retain only characters
        $string = preg_replace('/[\W]/', '', $string);
        //convert to uppercase
        $string = strtoupper($string);
        //remove duplicate characters 
        $string = count_chars($string, 3);
        $a = substr(str_shuffle($string), 0, 4);
        $b = substr(str_shuffle('23456789'), 0, 3);
        return 'B-' . $a . $b;
    }
}