<?php

namespace App\Models;

use CodeIgniter\Model;

class MembersModel extends Model
{
    protected $table      = 'tbl_members';
    protected $primaryKey = 'id';
    protected $allowedFields = ['org_id', 'firstname', 'lastname', 'email', 'phone', 'group_id', 'verified', 'gender'];
}
