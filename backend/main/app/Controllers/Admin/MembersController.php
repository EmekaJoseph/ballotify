<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\MembersModel;

class MembersController extends BaseController
{

    public function getMembers($org_id)
    {
        $table = new MembersModel();
        $members = $table->where('org_id', $org_id)->findAll();

        return $this->response->setJSON(array('data' => $members));
    }

    public function saveNewMember()
    {
        $table = new MembersModel();
        $email = $this->request->getVar('email');
        $existing = $table->where('email', $email)->findAll();
        $val = 1;
        if (sizeof($existing) > 0) {
            $val = 0;
        } else {
            $data = [
                'org_id' => $this->request->getVar('org_id'),
                'firstname' => $this->request->getVar('firstname'),
                'lastname' => $this->request->getVar('lastname'),
                'email' => $this->request->getVar('email'),
                'phone' => $this->request->getVar('phone'),
                'birthday' => $this->request->getVar('birthday'),
                'group_id' => $this->request->getVar('group_id'),
                'gender' => $this->request->getVar('gender'),
                'verified' => 1
            ];
            $table->save($data);
            $val = 1;
        }
        return $this->response->setJSON($val);
    }


    public  function deleteMember()
    {
        try {
            $org_id = $this->request->getVar('org_id');
            $id = $this->request->getVar('id');
            $ids = explode(",", $id);
            $table = new MembersModel();
            $table->where('org_id', $org_id)->delete($ids);
            return $this->response->setJSON(1);
        } catch (\Throwable $th) {
            //throw $th;
            return $this->response->setJSON(0);
        }
    }








    function getGroupsCount($org_id)
    {
        $table = new MembersModel();
        $members = $table->where('org_id', $org_id)->findAll();
        $val = array();
        if (sizeof($members) > 0) {
            foreach ($members as $mem) {
                array_push($val, $mem['group_id']);
            }
        }
        return ($val);
    }

    function membersInThisGroup($group_id, $org_id)
    {
        $table = new MembersModel();
        $members = $table->where('org_id', $org_id)->where('group_id', $group_id)->findAll();
        return ($members);
    }
}
