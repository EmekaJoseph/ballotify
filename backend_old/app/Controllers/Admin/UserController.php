<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;

use App\Controllers\Admin\OrgController as Org;

class UserController extends ResourceController
{
    use ResponseTrait;


    public function registerNew($recieved)
    {
        $data = json_decode(base64_decode($recieved));
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

            $this->storeToDB($userProfile);
            (new Org())->storeToDB($orgProfile);

            $response = 1;
        } catch (\Throwable $th) {
            //throw $th;
            $response = 0;
        }
        return $this->respond($response);
    }

    public function login($data)
    {
        $recv = json_decode(base64_decode($data));
        $email = $recv->email;
        $password = $recv->password;
        $rArray = array();

        $admin = $this->getUserWithEmail($email);

        if ($admin == null) {
            $rArray['status'] = 0;
        } else {
            if (!password_verify($password, $admin['password'])) {
                $rArray['status'] = 0;
            } else {
                $rArray['status'] = 1;
                $rArray['data'] = array(
                    'user_id' => $admin['id'],
                    'org_id' => $admin['org_id'],
                    'user_name' => $admin['firstname'] . ' ' . $admin['lastname'],
                    'user_role' => $admin['role'],
                    'org_name' => (new Org())->getUserOrg($admin['org_id'])['org_name']
                );
            }
            $message = 'Hello, ' . $admin['firstname'] . ' ' . $admin['lastname'] . ', 
            You just signed in!, if this is not you, call this number <i>+2348139590011</i> immediately';
            //$mailSender = new SendMail();
            //$mailSender->sendMail($email, 'You just Logged in', $message);
        }
        return $this->respond($rArray);
    }

    public function ifEmailIsFound($email)
    {
        $usersTable = new UserModel();
        $exists = $usersTable->where('email', $email)->countAllResults();
        $val = false;
        if ($exists != 0) {
            $val = true;
        }
        return $this->respond($val);
    }


    public function getUserDetails()
    {
        $id = $this->request->getVar('id');
        $org_id = $this->request->getVar('org_id');
        $table = new UserModel();
        $admin = $table->where('org_id', $org_id)->where('id', $id)->first();
        $rArray['data'] = array(
            'id' => $admin['id'],
            'org_id' => $admin['org_id'],
            'firstname' => $admin['firstname'],
            'lastname' => $admin['lastname'],
            'email' => $admin['email'],
            'isActive' => $admin['isActive'],
            'role' => $admin['role']
        );
        return $this->respond($rArray);
    }


    public function updateUser()
    {
        $data = [
            'id' => $this->request->getVar('id'),
            'firstname' => $this->request->getVar('firstname'),
            'lastname' => $this->request->getVar('lastname')
        ];
        $table = new UserModel();
        $table->save($data);
        return $this->respond(1);
    }







    function storeToDB($data)
    {
        $table = new UserModel();
        $table->save($data);
    }

    function getUserWithEmail($email)
    {
        $table = new UserModel();
        $x = $table->where('email', $email)->first();
        return ($x);
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
