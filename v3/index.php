<?php


require_once '../include/DbHandler3.php';
require_once '../include/PassHash.php';
require '.././libs/Slim/Slim.php';


\Slim\Slim::registerAutoloader();
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\UploadedFile;

//$app = new \Slim\App();

$app = new \Slim\Slim();

// User id from db - Global Variable
$user_id = NULL;


// Start: v3 - phase-3
$app->post('/getFireBaseId', function() use ($app) {
    
	$db = new DbHandler3();	
	$response = array();
	$r = json_decode($app->request->getBody());
	$response= $db->fireBaseId($r);
	echoResponse(201, $response);
});
//saveMessenger
$app->post('/saveMessenger', function() use ($app) {
	$response               = array();
	$r 						= json_decode($app->request->getBody());	
	$db 					= new DbHandler3();
	$response 				= $db->saveMessenger($r); 	
	echoResponse(201, $response);
});	
/*function fileupload () {
   
    $response               = array();  
	$db 					= new DbHandler3();
	$response 				= $db->saveMessenger(); 	
	echoResponse(201, $response);
	
}*/
//saveMessenger
/*$app->post('/saveMessenger', function() use ($app) {
    
	$db = new DbHandler3();	
	$response = array();
	echo "<pre>"; print_r($app); die;
	//echo "<pre>"; print_r($app->request->files); die;
	$r = json_decode($app->request->getBody());
	$response= $db->fireBaseId($r);
	echoRespnse(201, $response);
});
*/
/*  Get list messenger subject
 @param promo name
 */
$app->post('/getMsgrSubject', function() use ($app) {
	$response               = array();
	$r 						= json_decode($app->request->getBody());	
	$db 					= new DbHandler3();
	$response 				= $db->getMsgrSubject($r); 	
	echoResponse(201, $response);
});	

/*  Get list of messenger
 @param device id
 */
$app->post('/getMessengerList', function() use ($app) {
	$response               = array();
	$r 						= json_decode($app->request->getBody());	
	$db 					= new DbHandler3();
	$response 				= $db->getMessengerList($r); 	
	echoResponse(201, $response);
});	

/*  Get list of messenger
 @param codeName
 */
$app->post('/getBroadcastStdList', function() use ($app) {
	$response               = array();
	$r 						= json_decode($app->request->getBody());	
	$db 					= new DbHandler3();
	$response 				= $db->getBroadcastStdList($r); 	
	echoResponse(201, $response);
});	

/**
 * Echoing json response to client
 * @param String $status_code Http response code
 * @param Int $response Json response
 */
function echoResponse($status_code, $response) {
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