<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;

class LoginController extends BaseController
{
    public function login()
    {
        $usersTable = new UserModel();

        $email = $this->request->getVar('email');
        $password = $this->request->getVar('password');
        $rArray = array();

        $emailExists = $usersTable->where('email', $email)->countAllResults();

        if ($emailExists == 0) {
            $rArray['status'] = 0;
        } else {
            $admin = $usersTable->where('email', $email)->first();
            if (!password_verify($password, $admin['password'])) {
                $rArray['status'] = 0;
            } else {
                $rArray['status'] = 1;
                $rArray['data'] = array(
                    'id' => $admin['id'],
                    'org_id' => $admin['org_id'],
                    'name' => $admin['firstname'] . ' ' . $admin['lastname']
                );
            }
        }
        return $this->response->setJSON($rArray);
    }
}
