<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Controllers\Admin\OrgController as Org;
use App\Controllers\Admin\UserController as User;
use CodeIgniter\API\ResponseTrait;

class SignupController extends BaseController
{
    use ResponseTrait;

    public function registerNew($recievedJSON)
    {
        $data = json_decode($recievedJSON);
        $response = 0;
        $org_id = $this->getOrgId();
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

            (new User())->storeToDB($userProfile);
            (new Org())->storeToDB($orgProfile);

            $response = 1;
        } catch (\Throwable $th) {
            //throw $th;
            $response = 0;
        }
        return $this->respond($response);
    }


    private function getOrgId()
    {
        $org_id = $this->generateOrgId();
        $exists =  (new Org())->orgIdExists($org_id);
        if ($exists != 0) {
            $this->getOrgId();
        }
        return $org_id;
    }

    private function generateOrgId()
    {
        //retain only characters###########################
        //$name = preg_replace('/[\W]/', '', $name);
        //convert to uppercase###############################
        //$name = strtoupper($name);
        //remove duplicate characters ##########################
        //$name = count_chars($name, 3);

        $str = 'AC56789DEFGHJKLMNPQRTUVWXYZ234';
        $a = substr(str_shuffle($str), 0, 3);
        // $b = substr(str_shuffle('23456789'), 0, 2);
        return 'B' . $a;
    }
}
