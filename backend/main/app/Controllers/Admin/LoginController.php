<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Controllers\Admin\UserController as User;
use App\Controllers\Admin\OrgController as Org;
use App\Controllers\SendMail;
use CodeIgniter\API\ResponseTrait;

class LoginController extends BaseController
{
    use ResponseTrait;

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
                    'user_id' => $admin['id'],
                    'org_id' => $admin['org_id'],
                    'user_name' => $admin['firstname'] . ' ' . $admin['lastname'],
                    'user_role' => $admin['role'],
                    'org_name' => (new Org())->getUserOrg($admin['org_id'])['org_name']
                );
            }
            $message = 'Hello, ' . $admin['firstname'] . ' ' . $admin['lastname'] . ', 
            You just signed in!, if this is not you, call this number <i>+2348139590011</i> immediately';
            $mailSender = new SendMail();
            $mailSender->sendMail($email, 'You just Logged in', $message);
        }
        return $this->respond($rArray);
    }
}
