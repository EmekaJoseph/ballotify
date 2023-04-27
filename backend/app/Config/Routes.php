<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

/*
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('HomeController');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override(
    function () {
        return view('index');
    }
);
// The Auto Routing (Legacy) is very dangerous. It is easy to create vulnerable apps
// where controller filters or CSRF protection are bypassed.
// If you don't want to define all routes, please use the Auto Routing (Improved).
// Set `$autoRoutesImproved` to true in `app/Config/Feature.php` and set the following to true.
// $routes->setAutoRoute(false);

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
$routes->add('/registerNew/(:any)', 'Admin\UserController::registerNew/$1');

//admin login
$routes->add('/login/(:any)', 'Admin\UserController::login/$1');


// overview-dashboard
$routes->get('/getOverview/(:any)', 'Admin\OrgController::getOverview/$1');

//settings
$routes->get('/getOrgDetails/(:any)', 'Admin\OrgController::getOrgDetails/$1');
$routes->post('/getUserDetails', 'Admin\UserController::getUserDetails');
$routes->post('/updateOrg', 'Admin\OrgController::updateOrg');
$routes->post('/updateUser', 'Admin\UserController::updateUser');


// Groups
$routes->resource('groups', ['controller' => 'Admin\GroupsController']);
$routes->add('/updateMembersGroup/(:any)', 'Admin\MembersController::updateMembersGroup/$1');


// members
$routes->resource('members', ['controller' => 'Admin\MembersController']);



// Admin Vote Settings - events
$routes->resource('events', ['controller' => 'Admin\EventsController']);

$routes->add('/getEventDetails/(:any)', 'Admin\EventsController::getEventDetails/$1');


// Admin Vote Settings - positions
$routes->post('/saveNewPosition', 'Admin\VotingSettingsController::saveNewPosition');
$routes->get('/getPositions/(:any)', 'Admin\VotingSettingsController::getPositions/$1');
$routes->get('/removePosition/(:any)', 'Admin\VotingSettingsController::removePosition/$1');

$routes->post('/saveCandidate', 'Admin\VotingSettingsController::saveCandidate');
$routes->get('/getCandidates/(:any)', 'Admin\VotingSettingsController::getCandidates/$1');
$routes->get('/removeCandidate/(:any)', 'Admin\VotingSettingsController::removeCandidate/$1');

$routes->post('/saveVoter', 'Admin\VotingSettingsController::saveVoter');
$routes->get('/getVoters/(:any)', 'Admin\VotingSettingsController::getVoters/$1');
$routes->get('/removeVoter/(:any)', 'Admin\VotingSettingsController::removeVoter/$1');

// main voting
$routes->get('/votingDataQuery/(:any)',  'VotingController::votingDataQuery/$1');
$routes->get('/checkVotingCode/(:any)', 'VotingController::checkVotingCode/$1');
$routes->post('/submitVote', 'VotingController::submitVote');


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
if (is_file(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
