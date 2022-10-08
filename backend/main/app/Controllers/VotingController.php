<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
// use App\Models\PositionsModel;
use App\Models\CandidatesModel;
use App\Models\VotersModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\I18n\Time;

class VotingController extends BaseController
{

    use ResponseTrait;



    // public function voting_getPositions($event_id)
    // {
    //     $table = new PositionsModel();
    //     $positions = $table->where('event_id', $event_id)->findAll();
    //     return $this->response->setJSON(array('positions' => $positions));
    // }

    public function voting_getCandidates($event_id)
    {
        $table = new CandidatesModel();

        $Candidates = $table->where('event_id', $event_id)
            ->select('*')
            ->join('tbl_positions as posts', 'tbl_candidates.member_id = posts.id')
            ->get()
            ->getResultArray();
        return $this->respond(array('candidates' => $Candidates));
    }


    public function voting_getVoters($event_id)
    {
        $table = new VotersModel();

        $voters = $table->where('event_id', $event_id)
            ->select('*')
            ->join('tbl_members as mems', 'tbl_voters.member_id = mems.id')
            ->get()
            ->getResultArray();

        foreach ($voters as &$object) {
            $object->code = '';
        }
        return $this->respond(array('voters' => $voters));
    }
}
