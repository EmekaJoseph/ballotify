<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\EventsModel;

class EventsController extends BaseController
{
    public function testEvent($org_id)
    {
        $events = new EventsModel();
        $rr = $events->where('org_id', $org_id)->orderBy('id', 'desc')->findAll(1, 0);
        $rr = $events->where('org_id', $org_id)->findAll();
        return $this->response->setJSON($rr);
    }
}
