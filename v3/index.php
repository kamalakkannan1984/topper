<?php


require_once '../include/DbHandler3.php';
require_once '../include/PassHash.php';
require '.././libs/Slim/Slim.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

// User id from db - Global Variable
$user_id = NULL;


// Start: v3 - phase-3
$app->post('/getFireBaseId', function() use ($app) {
    
	$db = new DbHandler3();	
	$response = array();
	$r = json_decode($app->request->getBody());
	$response= $db->fireBaseId($r);
	echoRespnse(201, $response);
});
/**
 * Echoing json response to client
 * @param String $status_code Http response code
 * @param Int $response Json response
 */
function echoRespnse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);

    // setting response content type to json
    $app->contentType('application/json');

    echo json_encode($response);
}
// End: v3 - phase-3

$app->run();
?>