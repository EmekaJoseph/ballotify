<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\GroupsModel;

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
                'created' => $this->request->getVar('created')
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

        return $this->response->setJSON(array('data' => $groups));
    }

    // function orgIdExists($id)
    // {
    //     $table = new OrgModel();
    //     $exists = $table->where('org_id', $id)->countAllResults();
    //     return ($exists);
    // }

    // function storeToDB($data)
    // {
    //     $table = new OrgModel();
    //     $table->save($data);
    // }
}
