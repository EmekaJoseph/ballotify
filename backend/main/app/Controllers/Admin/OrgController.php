<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\OrgModel;
use App\Controllers\Admin\MembersController as memberTable;
use App\Controllers\Admin\GroupsController as groupTable;
use App\Controllers\Admin\EventsController as eventsTable;

class OrgController extends BaseController
{
    public function getOverview($org_id)
    {
        $membersCount = (new memberTable)->countMembersInOrg($org_id);
        $groupsCount = (new groupTable)->countGroupsInOrg($org_id);
        $birthdays = (new memberTable)->getBirthdays($org_id);
        $eventCount = (new eventsTable)->eventsCount($org_id);

        $data2 = array(
            'members' => $membersCount,
            'groups' => $groupsCount,
            'birthdays' => $birthdays,
            'events' => $eventCount,
            'messages' => 0,
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



    function getUserOrg($org_id)
    {
        $orgTable = new OrgModel();
        $details = $orgTable->where('org_id', $org_id)->first();
        return ($details);
    }
    function orgIdExists($id)
    {
        $table = new OrgModel();
        $count = $table->where('org_id', $id)->countAllResults();
        return ($count);
    }

    function storeToDB($data)
    {
        $table = new OrgModel();
        $table->save($data);
    }
}
