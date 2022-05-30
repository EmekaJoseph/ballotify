<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;

class UserController extends BaseController
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
        return $this->response->setJSON($rArray);
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
        return $this->response->setJSON(1);
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
}
