<?php

namespace App\Models;

use CodeIgniter\Model;

class EventsModel extends Model
{
    protected $table      = 'tbl_events';
    protected $primaryKey = 'id';
    protected $allowedFields = ['org_id', 'event_id', 'event_name', 'event_type', 'created', 'expiry'];
}
