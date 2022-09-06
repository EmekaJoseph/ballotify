<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\MembersModel;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;

class MembersController extends ResourceController
{
    use ResponseTrait;

    public function show($org_id = null)
    {
        $table = new MembersModel();
        $members = $table->where('org_id', $org_id)->findAll();

        return $this->response->setJSON(array('data' => $members));
    }

    public function create()
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
        return $this->respond($val);
    }



    public function update($idd = null)
    {
        $table = new MembersModel();
        $data = [
            'id' => $this->request->getVar('id'),
            'firstname' => $this->request->getVar('firstname'),
            'lastname' => $this->request->getVar('lastname'),
            'phone' => $this->request->getVar('phone'),
            'birthday' => $this->request->getVar('birthday'),
            'group_id' => $this->request->getVar('group_id'),
            'gender' => $this->request->getVar('gender'),
        ];
        $table->save($data);
        return $this->respond(1);
    }



    public  function delete($ids = null)
    {
        try {
            //$id = $this->request->getVar('id');
            $exploded = explode(",", $ids);
            $table = new MembersModel();
            $table->delete($exploded);
            return $this->response->setJSON(1);
        } catch (\Throwable $th) {
            //throw $th;
            return $this->respond(0);
        }
    }


    public function updateMembersGroup($data)
    {
        $table = new MembersModel();
        $enco = json_decode($data);
        $dataToSave = array();
        foreach ($enco as $objects) {
            array_push($dataToSave, (object)[
                'id' =>  $objects->id,
                'group_id' =>  $objects->group_id,
            ]);
        }
        $table->updateBatch($dataToSave, 'id');
        return $this->respond(1);
    }











    function countMembersInOrg($org_id)
    {
        $table = new MembersModel();
        $membersSize = $table->where('org_id', $org_id)->countAllResults();
        return ($membersSize);
    }


    function getBirthdays($org_id)
    {
        $table = new MembersModel();
        $members = $table->where('org_id', $org_id)->findAll();
        $val = array();
        if (sizeof($members) > 0) {
            foreach ($members as $mem) {
                array_push($val, $mem['birthday']);
            }
        }
        return ($val);
    }
}
