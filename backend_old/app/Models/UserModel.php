<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table      = 'tbl_users';
    protected $primaryKey = 'id';
    protected $allowedFields = ['org_id', 'firstname', 'lastname', 'email', 'username', 'password', 'role', 'image', 'isActive'];
}
