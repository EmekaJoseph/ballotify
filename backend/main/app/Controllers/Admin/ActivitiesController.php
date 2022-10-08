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
        $list = $table->where('org_id', $org_id)->orderBy('id', 'desc')->findAll();

        foreach ($list as &$object) {
            $thisTime = Time::parse($object['date_done'], 'America/Chicago');
            $object['date'] = $thisTime->humanize();
        }

        return ($list);
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
