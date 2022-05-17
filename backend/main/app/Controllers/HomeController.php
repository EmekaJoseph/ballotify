<?php

namespace App\Controllers;

class HomeController extends BaseController
{
    public function index()
    {
        return view('start');
    }

    public function testApi($test)
    {
        return $this->response->setJSON($test);;
    }
}
