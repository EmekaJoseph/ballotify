<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ActivitiesModel;
use CodeIgniter\I18n\Time;

class ActivitiesController extends BaseController
{

    function getActivities($org_id)
    {
        $table = new ActivitiesModel();
        $list = $table->where('org_id', $org_id)->findAll();
        $data = array();

        foreach ($list as $object) {
            $thisTime = Time::parse($object['date_done'], 'America/Chicago');
            array_push($data, (object)[
                'id' => $object['id'],
                'activity' =>  $object['activity'],
                'date' =>   $thisTime->humanize(),
            ]);
        }
        return ($data);
    }

    function saveNewActivity($org_id, $activity)
    {
        $table = new ActivitiesModel();
        $data = [
            'org_id' => $org_id,
            'activity' => $activity,
            'date_done' => new Time('now')
        ];
        $table->save($data);
    }
}
