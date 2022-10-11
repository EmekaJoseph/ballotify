<?php

namespace App\Models;

use CodeIgniter\Model;

class PositionsModel extends Model
{
    protected $table      = 'tbl_positions';
    protected $primaryKey = 'position_id';
    protected $allowedFields = ['org_id', 'event_id', 'position_name'];
}
