<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PositionsModel;
use CodeIgniter\API\ResponseTrait;

class VotingSettingsController extends BaseController
{

    use ResponseTrait;

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
        return $this->respond($val);
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
        return $this->respond(1);
    }
}
