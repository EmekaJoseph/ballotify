<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PositionsModel;
use App\Models\CandidatesModel;
use App\Models\VotersModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\I18n\Time;

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
        $table->delete($id);
        return $this->respond(1);
    }









    public function saveCandidate()
    {
        $event_id = $this->request->getVar('event_id');
        $member_id = $this->request->getVar('member_id');
        $position_id = $this->request->getVar('position_id');
        //$file = $this->request->getFile('image');


        // if ($file !== null) {
        //     $timestamp = time();
        //     $fileName = $timestamp;
        //     if ($file->isValid() && !$file->hasMoved()) {
        //         $file->move('images/uploads/', $fileName);
        //         $image = \Config\Services::image();
        //         $image->withFile('images/uploads/' . $fileName)
        //             ->convert(IMAGETYPE_JPEG)
        //             ->save('images/uploads/' . $fileName . '_thumb.jpg', 20);
        //         unlink('images/uploads/' . $fileName);
        //     }
        // } else {
        //     $fileName = null;
        // }

        $data = [
            'event_id' => $event_id,
            'member_id' => $member_id,
            'position_id' => $position_id,
            // 'photo' => $fileName
        ];

        $table = new CandidatesModel();
        $exists = $table->where('event_id', $event_id)
            ->where('member_id', $member_id)
            ->where('position_id', $position_id)
            ->countAllResults();
        if ($exists != 0) {
            $val = 0;
        } else {
            $table->save($data);
            $val = 1;
        }
        return $this->respond($val);
    }

    public function getCandidates($event_id)
    {
        $table = new CandidatesModel();
        $Candidates = $table->where('event_id', $event_id)->findAll();
        return $this->respond(array('candidates' => $Candidates));
    }

    public function removeCandidate($id)
    {
        $table = new CandidatesModel();
        $table->delete($id);
        return $this->respond(1);
    }





    public function saveVoter()
    {
        $event_id = $this->request->getVar('event_id');
        $member_id = $this->request->getVar('member_id');

        $table = new VotersModel();
        $exists = $table->where('event_id', $event_id)
            ->where('member_id', $member_id)
            ->countAllResults();
        if ($exists != 0) {
            $val = 0;
        } else {
            $data = [
                'event_id' => $event_id,
                'member_id' => $member_id,
                'voted_status' => 0,
                'voted_date' => new Time('now'),
                'code' => $this->votingCode($event_id),
            ];

            $table->save($data);
            $val = 1;
        }
        return $this->respond($val);
    }

    public function getVoters($event_id)
    {
        $table = new VotersModel();
        $voters = $table->where('event_id', $event_id)->findAll();
        return $this->respond(array('voters' => $voters));
    }


    public function removeVoter($id)
    {
        $table = new VotersModel();
        $table->delete($id);
        return $this->respond(1);
    }






    private function votingCode($event_id)
    {
        $code = $this->generateCode($event_id);
        $table = new VotersModel();
        $exists = $table->where('event_id', $event_id)
            ->where('code', $code)
            ->countAllResults();
        if ($exists != 0) {
            $this->votingCode($event_id);
        }
        return $code;
    }

    private function generateCode($event_id)
    {
        $str = 'ABC56789DEFGHJKLMNPQRTUWXYZ234';
        $a = substr(str_shuffle($str), 0, 4);
        return $event_id . '-V' . $a;
    }
}
