<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

// Load the system's routing file first, so that the app and ENVIRONMENT
// can override as needed.
if (file_exists(SYSTEMPATH . 'Config/Routes.php')) {
    require SYSTEMPATH . 'Config/Routes.php';
}

/*
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('HomeController');
$routes->setDefaultMethod('start');
$routes->setTranslateURIDashes(false);
$routes->setAutoRoute(true);
$routes->set404Override(
    function () {
        return view('start');
    }
);

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// We get a performance increase by specifying the default
// route since we don't have to scan directories.
$routes->get('/', 'HomeController::index');

// admin signUp
$routes->add('/checkIfEmailExists/(:any)', 'Admin\UserController::ifEmailIsFound/$1');
$routes->add('/checkIfOrgExists/(:any)', 'Admin\OrgController::ifOrgIsFound/$1');
$routes->add('/registerNew/(:any)', 'Admin\SignupController::registerNew/$1');

//admin login
$routes->add('/login', 'Admin\LoginController::login');


// overview-dashboard
$routes->add('/getOverview/(:any)', 'Admin\OrgController::getOverview/$1');

//settings
$routes->add('/getOrgDetails/(:any)', 'Admin\OrgController::getOrgDetails/$1');
$routes->add('/getUserDetails', 'Admin\UserController::getUserDetails');
$routes->add('/updateOrg', 'Admin\OrgController::updateOrg');
$routes->add('/updateUser', 'Admin\UserController::updateUser');


// Groups
$routes->add('/saveNewGroup', 'Admin\GroupsController::saveNewGroup');
$routes->add('/getGroupNames/(:any)', 'Admin\GroupsController::getGroupNames/$1');
$routes->add('/getGroupDetails', 'Admin\GroupsController::getGroupDetails');
$routes->add('/deleteGroup', 'Admin\GroupsController::deleteGroup');
$routes->add('/renameGroup', 'Admin\GroupsController::renameGroup');


// members
$routes->add('/saveNewMember', 'Admin\MembersController::saveNewMember');
$routes->add('/getMembers/(:any)', 'Admin\MembersController::getMembers/$1');
$routes->add('/deleteMember', 'Admin\MembersController::deleteMember');
$routes->add('/updateMember', 'Admin\MembersController::updateMember');




/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (file_exists(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
