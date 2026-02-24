<?php

namespace App\Models;

use CodeIgniter\Model;

class ActivitiesModel extends Model
{
    protected $table      = 'tbl_activities';
    protected $primaryKey = 'id';
    protected $allowedFields = ['org_id', 'activity', 'date_done'];
}
