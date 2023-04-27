<?php

namespace App\Models;

use CodeIgniter\Model;

class OrgModel extends Model
{
    protected $table      = 'tbl_org';
    protected $primaryKey = 'id';
    protected $allowedFields = ['org_id', 'org_name', 'org_logo', 'org_address', 'org_events'];
}
