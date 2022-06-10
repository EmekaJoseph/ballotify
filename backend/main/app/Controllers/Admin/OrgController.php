<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\OrgModel;
use App\Controllers\Admin\MembersController as Member;
use App\Controllers\Admin\GroupsController as Group;

class OrgController extends BaseController
{
    public function getOverview($org_id)
    {
        $membersCount = (new Member)->countMembersInOrg($org_id);
        $groupsCount = (new Group)->countGroupsInOrg($org_id);
        $birthdays = (new Member)->getBirthdays($org_id);

        $data2 = array(
            'members' => $membersCount,
            'groups' => $groupsCount,
            'birthdays' => $birthdays
        );
        return $this->response->setJSON($data2);
    }


    public function getOrgDetails($org_id)
    {
        $orgTable = new OrgModel();
        $details = $orgTable->where('org_id', $org_id)->first();
        return $this->response->setJSON($details);
    }

    public function ifOrgIsFound($orgName)
    {
        $table = new OrgModel();
        $exists = $table->where('org_name', $orgName)->countAllResults();
        $val = false;
        if ($exists != 0) {
            $val = true;
        }
        return $this->response->setJSON($val);
    }

    public function updateOrg()
    {
        $data = [
            'id' => $this->request->getVar('id'),
            'org_name' => $this->request->getVar('org_name'),
            'org_address' => $this->request->getVar('org_address')
        ];
        $table = new OrgModel();
        $table->save($data);
        return $this->response->setJSON(1);
    }




    function orgIdExists($id)
    {
        $table = new OrgModel();
        $exists = $table->where('org_id', $id)->countAllResults();
        return ($exists);
    }

    function storeToDB($data)
    {
        $table = new OrgModel();
        $table->save($data);
    }
}
