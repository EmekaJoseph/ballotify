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
