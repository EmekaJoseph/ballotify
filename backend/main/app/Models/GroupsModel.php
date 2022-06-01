<?php

namespace App\Models;

use CodeIgniter\Model;

class GroupsModel extends Model
{
    protected $table      = 'tbl_groups';
    protected $primaryKey = 'id';
    protected $allowedFields = ['org_id', 'group_name', 'created'];
}
