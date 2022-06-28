<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\OrgModel;
use App\Models\EventsModel;
use CodeIgniter\I18n\Time;
use DateTime;

class EventsController extends BaseController
{
    public function getEvents($org_id)
    {
        $events = new EventsModel();
        $rr = $events->where('org_id', $org_id)->orderBy('id', 'desc')->findAll(1, 0);
        $rr = $events->where('org_id', $org_id)->findAll();
        return $this->response->setJSON($rr);
    }

    public function saveNewEvent()
    {
        $eventsTable = new EventsModel();
        $orgTable = new OrgModel();

        $org_id = $this->request->getVar('org_id');
        $event_name = $this->request->getVar('event_name');

        $data = array();

        $exists = $eventsTable->where('org_id', $org_id)->where('event_name', $event_name)->countAllResults();
        if ($exists != 0) {
            $resp = 0;
            $event_id = null;
        } else {
            $thisOrg = $orgTable->where('org_id', $org_id)->find();
            $event_id = $org_id . '-E' . ($thisOrg[0]['org_events'] + 1);
            $data = [
                'org_id' => $org_id,
                'event_name' => $event_name,
                'event_id' => $event_id,
                'event_description' => $this->request->getVar('event_description'),
                'event_start' => $this->request->getVar('event_start'),
                'event_expiry' => $this->request->getVar('event_expiry'),
                'event_type' => $this->request->getVar('event_type'),
                'created' => new Time('now')
            ];

            $orgTable->where('org_id', $org_id)->set('org_events', '`org_events`+ 1', FALSE)->update();
            $eventsTable->save($data);
            $resp = 1;
        }

        return $this->response->setJSON(array('state' => $resp, 'id' => $event_id));
    }
}
