<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\GroupsModel;
use CodeIgniter\I18n\Time;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;

class GroupsController extends ResourceController
{
    use ResponseTrait;

    public function create()
    {
        $org_id = $this->request->getVar('org_id');
        $group_name = $this->request->getVar('group_name');

        $table = new GroupsModel();
        $exists = $table->where('org_id', $org_id)->where('group_name', $group_name)->countAllResults();
        if ($exists != 0) {
            $val = 0;
        } else {
            $data = [
                'org_id' => $org_id,
                'group_name' => $group_name,
                'created' => new Time('now')
            ];
            $table->save($data);
            $val = 1;
        }
        return $this->respond($val);
    }

    public function show($org_id = null)
    {
        $table = new GroupsModel();
        $groups = $table->where('org_id', $org_id)->findAll();

        $Groups = array();

        foreach ($groups as $object) {
            $thisTime = Time::parse($object['created'], 'America/Chicago');
            array_push($Groups, (object)[
                'id' => $object['id'],
                'group_name' =>  $object['group_name'],
                'created' =>   $thisTime->humanize(),
            ]);
        }


        return $this->respond(array('groups' => $Groups));
    }


    public function delete($id = null)
    {
        try {
            $table = new GroupsModel();
            $table->delete($id);
            return $this->respond(1);
        } catch (\Throwable $th) {
            //throw $th;
            return $this->respond(0);
        }
    }

    public function update($id = null)
    {
        $id = $this->request->getVar('id');
        $org_id = $this->request->getVar('org_id');
        $group_name = $this->request->getVar('group_name');
        $table = new GroupsModel();
        $exists = $table->where('org_id', $org_id)->where('group_name', $group_name)->countAllResults();
        if ($exists != 0) {
            $val = 0;
        } else {
            $data = [
                'id' => $id,
                'org_id' => $org_id,
                'group_name' => $group_name
            ];
            $table->save($data);
            $val = 1;
        }
        return $this->respond($val);
    }








    function countGroupsInOrg($org_id)
    {
        $table = new GroupsModel();
        $dataSize = $table->where('org_id', $org_id)->countAllResults();
        return ($dataSize);
    }
}
