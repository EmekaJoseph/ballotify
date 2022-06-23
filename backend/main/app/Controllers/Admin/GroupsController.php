<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\GroupsModel;
use App\Controllers\Admin\MembersController as Members;
use CodeIgniter\I18n\Time;

class GroupsController extends BaseController
{
    public function saveNewGroup()
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
        return $this->response->setJSON($val);
    }

    public function getGroupNames($org_id)
    {
        $table = new GroupsModel();
        $groups = $table->where('org_id', $org_id)->findAll();
        // $memberGroups = (new Members())->getGroupsCount($org_id);
        // $counted = null;
        // if (sizeof($memberGroups) > 0) {
        //     $counted = array_count_values($memberGroups);
        // }
        $Groups = array();

        foreach ($groups as $object) {
            $thisTime = Time::parse($object['created'], 'America/Chicago');
            array_push($Groups, (object)[
                'id' => $object['id'],
                'group_name' =>  $object['group_name'],
                'created' =>   $thisTime->humanize(),
            ]);
        }


        return $this->response->setJSON(array('groups' => $Groups));
    }


    public function deleteGroup()
    {
        try {
            $org_id = $this->request->getVar('org_id');
            $id = $this->request->getVar('id');
            $table = new GroupsModel();
            $table->where('org_id', $org_id)->delete($id);
            return $this->response->setJSON(1);
        } catch (\Throwable $th) {
            //throw $th;
            return $this->response->setJSON(0);
        }
    }

    public function renameGroup()
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
        return $this->response->setJSON($val);
    }








    function countGroupsInOrg($org_id)
    {
        $table = new GroupsModel();
        $dataSize = $table->where('org_id', $org_id)->countAllResults();
        return ($dataSize);
    }
}
