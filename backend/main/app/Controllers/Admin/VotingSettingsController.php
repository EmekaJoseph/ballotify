<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PositionsModel;


class VotingSettingsController extends BaseController
{
    public function saveNewPosition()
    {
        $org_id = $this->request->getVar('org_id');
        $name = $this->request->getVar('name');
        $event_id = $this->request->getVar('event_id');

        $table = new PositionsModel();
        $exists = $table->where('event_id', $event_id)->where('name', $name)->countAllResults();
        if ($exists != 0) {
            $val = 0;
        } else {
            $data = [
                'org_id' => $org_id,
                'name' => $name,
                'event_id' => $event_id
            ];
            $table->save($data);
            $val = 1;
        }
        return $this->response->setJSON($val);
    }

    public function getPositions($event_id)
    {
        $table = new PositionsModel();
        $positions = $table->where('event_id', $event_id)->findAll();
        return $this->response->setJSON(array('positions' => $positions));
    }

    public function removePosition($id)
    {
        $table = new PositionsModel();
        $table->where('id', $id)->delete($id);
        return $this->response->setJSON(1);
    }


    // public function deleteGroup()
    // {
    //     try {
    //         $org_id = $this->request->getVar('org_id');
    //         $id = $this->request->getVar('id');
    //         $table = new GroupsModel();
    //         $table->where('org_id', $org_id)->delete($id);
    //         return $this->response->setJSON(1);
    //     } catch (\Throwable $th) {
    //         //throw $th;
    //         return $this->response->setJSON(0);
    //     }
    // }







    // function countGroupsInOrg($org_id)
    // {
    //     $table = new GroupsModel();
    //     $dataSize = $table->where('org_id', $org_id)->countAllResults();
    //     return ($dataSize);
    // }
}
