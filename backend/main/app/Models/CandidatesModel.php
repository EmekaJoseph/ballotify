<?php

namespace App\Models;

use CodeIgniter\Model;

class CandidatesModel extends Model
{
    protected $table      = 'tbl_candidates';
    protected $primaryKey = 'id';
    protected $allowedFields = ['org_id', 'event_id', 'member_id', 'position_id', 'votes', 'photo'];
}
