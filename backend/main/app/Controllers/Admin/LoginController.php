<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Controllers\Admin\UserController as User;
use App\Controllers\Admin\OrgController as Org;

class LoginController extends BaseController
{
    public function login()
    {
        $email = $this->request->getVar('email');
        $password = $this->request->getVar('password');
        $rArray = array();

        $admin = (new User())->getUserWithEmail($email);

        if ($admin == null) {
            $rArray['status'] = 0;
        } else {
            if (!password_verify($password, $admin['password'])) {
                $rArray['status'] = 0;
            } else {
                $rArray['status'] = 1;
                $rArray['data'] = array(
                    'id' => $admin['id'],
                    'org_id' => $admin['org_id'],
                    'name' => $admin['firstname'] . ' ' . $admin['lastname'],
                    'role' => $admin['role'],
                    'org_name' => (new Org())->getUserOrg($admin['org_id'])['org_name']
                );
            }
        }
        return $this->response->setJSON($rArray);
    }
}
