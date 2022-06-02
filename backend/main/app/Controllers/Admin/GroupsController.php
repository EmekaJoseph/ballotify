<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\GroupsModel;
use App\Controllers\Admin\MembersController as Members;

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
        $memberGroups = (new Members())->getGroupsCount($org_id);
        $counted = null;
        if (sizeof($memberGroups) > 0) {
            $counted = array_count_values($memberGroups);
        }
        return $this->response->setJSON(array('groups' => $groups, 'counted' => $counted));
    }

    public function getGroupDetails()
    {
        $org_id = $this->request->getVar('org_id');
        $id = $this->request->getVar('id');
        $table = new GroupsModel();
        $details = $table->where('org_id', $org_id)->where('id', $id)->first();
        $members = (new Members())->membersInThisGroup($id, $org_id);
        $data = array(
            'details' => $details,
            'members' => $members
        );
        return $this->response->setJSON($data);
    }
}
