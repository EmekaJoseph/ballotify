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

    private $db;

    public function __construct()
    {
        $this->db = db_connect(); // Loading database
        // OR $this->db = \Config\Database::connect();
    }

    public function saveNewPosition()
    {
        $org_id = $this->request->getVar('org_id');
        $name = $this->request->getVar('name');
        $event_id = $this->request->getVar('event_id');

        $table = new PositionsModel();
        $exists = $table->where('event_id', $event_id)->where('position_name', $name)->countAllResults();
        if ($exists != 0) {
            $val = 0;
        } else {
            $data = [
                'org_id' => $org_id,
                'position_name' => $name,
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
        $candidatesJoin = $this->db->table('tbl_members mem')
            ->join('tbl_candidates cand', 'cand.member_id = mem.id')
            ->join('tbl_positions post', 'post.position_id = cand.position_id')
            ->where('cand.event_id', $event_id)
            ->get()
            ->getResultArray();
        return $this->respond(array('candidates' => $candidatesJoin));
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
        $ids = $this->request->getVar('member_ids');
        $ids_array = explode(",", $ids);
        $table = new VotersModel();

        foreach ($ids_array as $id) {
            $data = [
                'event_id' => $event_id,
                'member_id' => $id,
                'voted_status' => 0,
                'voted_date' => '',
                'code' => $this->votingCode($event_id),
            ];
            $table->save($data);
        }
        $val = 1;
        return $this->respond($val);
    }


    public function getVoters($event_id)
    {
        $members = $this->db->table("tbl_members as mems");
        $votersJoin = $members->where('event_id', $event_id)
            ->select('*')->join('tbl_voters as voter', 'mems.id = voter.member_id')
            ->get()->getResult();

        foreach ($votersJoin as &$object) {
            unset(
                $object->code,
                $object->email,
                $object->verified,
                $object->phone,
                $object->group_id,
                $object->birthday,
                $object->member_id,
            );
        }
        return $this->respond(array('voters' => $votersJoin));
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
