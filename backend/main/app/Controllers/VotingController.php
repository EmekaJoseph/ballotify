<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\PositionsModel;
use App\Models\CandidatesModel;
use App\Models\VotersModel;
use CodeIgniter\I18n\Time;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;

class VotingController extends ResourceController
{

    use ResponseTrait;

    private $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    public function votingMasterQuery($event_id)
    {

        $candidates = $this->db->table('tbl_members mem')
            ->join('tbl_candidates cand', 'cand.member_id = mem.id')
            ->join('tbl_positions post', 'post.position_id = cand.position_id')
            ->where('cand.event_id', $event_id)
            ->get()
            ->getResultArray();

        $positionsExtract = array();
        if (sizeof($candidates) > 0) {
            foreach ($candidates as $cand) {
                array_push($positionsExtract, (object)[
                    'position_id' =>  $cand['position_id'],
                    'position_name' =>  $cand['position_name'],
                ]);
            }
        }

        $positions = array_unique($positionsExtract, SORT_REGULAR);



        $masterData = [];

        foreach ($positions as $post) {
            $tempRecord = (object)[];
            $tempRecord->position_id = $post->position_id;
            $tempRecord->position_name = $post->position_name;
            $tempRecord->candidates = array();

            foreach ($candidates as $cand) {
                if ($cand['position_id'] == $post->position_id) {
                    array_push($tempRecord->candidates, $cand);
                }
            }

            array_push($masterData, $tempRecord);
        }


        return $this->respond(array('vote_data' => $masterData));
    }
}
