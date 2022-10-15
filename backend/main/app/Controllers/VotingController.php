<?php

namespace App\Controllers;

use App\Controllers\BaseController;
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

    public function votingDataQuery($event_id)
    {
        $candidates = $this->db->table('tbl_members mem')
            ->join('tbl_candidates cand', 'cand.member_id = mem.id')
            ->join('tbl_positions post', 'post.position_id = cand.position_id')
            ->where('cand.event_id', $event_id)
            ->get()
            ->getResult();

        $positions = array();
        $vote_data = [];

        if (sizeof($candidates) > 0) {
            foreach ($candidates as $cand) {
                array_push($positions, (object)[
                    'position_id' =>  $cand->position_id,
                    'position_name' =>  $cand->position_name,
                ]);

                unset(
                    $cand->verified,
                    $cand->email,
                    $cand->phone,
                    $cand->group_id,
                    $cand->birthday,

                );

                $cand->votes = base64_encode($cand->votes);
            }
            $positions = array_unique($positions, SORT_REGULAR);
        }


        if (sizeof($positions) > 0) {
            foreach ($positions as $post) {
                $tempRecord = (object)[];
                $tempRecord->position_id = $post->position_id;
                $tempRecord->position_name = $post->position_name;
                $tempRecord->candidates = array();

                foreach ($candidates as $cand) {
                    if ($cand->position_id == $post->position_id) {
                        array_push($tempRecord->candidates, $cand);
                    }
                }
                array_push($vote_data, $tempRecord);
            }
        }

        $votersTable = new VotersModel();
        $voters = $votersTable->where('event_id', $event_id)->where('voted_status', '1')->findAll();


        return $this->respond(array('vote_data' => $vote_data, 'voters' => sizeof(($voters))));
    }

    public function checkVotingCode($code = null)
    {
        $votersTable = new VotersModel();
        $thisVoter = $votersTable->where('code', $code)->find();

        $thisVoterId = '';
        $event_id = '';

        if (sizeof($thisVoter) == 0) {
            $status = 0;
        } else {
            $thisVoter = $thisVoter[0];
            if ($thisVoter['voted_status'] == 1) {
                $status = -1;
            } else {
                $status = 1;
                $thisVoterId = $thisVoter['id'];
                $event_id = $thisVoter['event_id'];
            }
        }
        return $this->respond(array('status' => $status, 'voter_id' => $thisVoterId, 'event_id' => $event_id));
    }

    public function submitVote()
    {
        $voter_id = $this->request->getVar('voter_id');
        $choicesString = $this->request->getVar('choices');
        $choicesArray =  explode(",", $choicesString);

        $votersTable = new VotersModel();
        $votersTable->save([
            'id' => $voter_id,
            'voted_status' => 1,
            'voted_date' => new Time('now')
        ]);

        $candidatesTable = new CandidatesModel();
        $candidatesTable->whereIn('id', $choicesArray)->set('votes', '`votes`+ 1', FALSE)->update();

        return $this->respond(1);
    }
}
