<?php

namespace App\Models;

use CodeIgniter\Model;

class VotersModel extends Model
{
    protected $table      = 'tbl_voters';
    protected $primaryKey = 'id';
    protected $allowedFields = ['event_id', 'member_id', 'voted_status', 'voted_date', 'code'];
}
