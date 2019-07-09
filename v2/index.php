<?php


require_once '../include/DbHandler2.php';
require_once '../include/PassHash.php';
require '.././libs/Slim/Slim.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

// User id from db - Global Variable
$user_id = NULL;

/**
 * Adding Middle Layer to authenticate every request
 * Checking if the request has valid api key in the 'Authorization' header
 */
function authenticate(\Slim\Route $route) {
    // Getting request headers
    $headers = apache_request_headers();
    $response = array();
    $app = \Slim\Slim::getInstance();

    // Verifying Authorization Header
    if (isset($headers['Authorization'])) {
        $db = new DbHandler();

        // get the api key
        $api_key = $headers['Authorization'];
        // validating api key
        if (!$db->isValidApiKey($api_key)) {
            // api key is not present in users table
            $response["error"] = true;
            $response["message"] = "Access Denied. Invalid Api key";
            echoRespnse(401, $response);
            $app->stop();
        } else {
            global $user_id;
            // get user primary key id
            $user_id = $db->getUserId($api_key);
        }
    } else {
        // api key is missing in header
        $response["error"] = true;
        $response["message"] = "Api key is misssing";
        echoRespnse(400, $response);
        $app->stop();
    }
}

// v2- phase-2
//checkTrialStatus
$app->post('/checkTrialStatus', function() use ($app) {

	$db = new DbHandler();
	$response = array();
	$r = json_decode($app->request->getBody());
	$response= $db->checkTrialStatus($r);
	echoRespnse(201, $response);
});
//trialSubscription
$app->post('/trialSubscription', function() use ($app) {

	$db = new DbHandler();
	$response = array();
	$r = json_decode($app->request->getBody());
	$response= $db->trialSubscription($r);
	echoRespnse(201, $response);
});
//subscriptionByUser
$app->post('/subscriptionByUser', function() use ($app) {

	$db = new DbHandler();
	$response = array();
	$r = json_decode($app->request->getBody());
	$response= $db->subscriptionByUser($r);
	echoRespnse(201, $response);
});
//updateDemoUser
$app->post('/updateDemoUser', function() use ($app) {

	$db = new DbHandler();
	$response = array();
	$r = json_decode($app->request->getBody());
	$response= $db->updateDemoUser($r);
	echoRespnse(201, $response);
});
//getStandardFee
$app->post('/getStandardFee', function() use ($app) {

	$db = new DbHandler();
	$response = array();
	$r = json_decode($app->request->getBody());
	$response= $db->getStandardFee($r);
	echoRespnse(201, $response);
});
//check installation cashback promocode
$app->post('/checkInstallCashback', function() use ($app) {

	$db = new DbHandler();
	$response = array();
	$r = json_decode($app->request->getBody());	
	$response= $db->checkInstallCashback($r);
	echoRespnse(201, $response);
});
//if available promocode update PromoCode in to TopUserInfo table
$app->post('/updateInstallCashback', function() use ($app) {

	$db = new DbHandler();
	$response = array();
	$r = json_decode($app->request->getBody());	
	$response= $db->updateInstallCashback($r);
	echoRespnse(201, $response);
});
//Report for My Earnings
$app->post('/checkMyEarnings', function() use ($app) {

	$db = new DbHandler();  
	$response = array();
	$r = json_decode($app->request->getBody());	
	$response= $db->checkMyEarnings($r);
	echoRespnse(201, $response);
});
// Report for Dealer
$app->post('/reportDealer', function() use ($app) {

	$db = new DbHandler();  
	$response = array();
	$r = json_decode($app->request->getBody());	
	$response= $db->reportDealer($r);
	echoRespnse(201, $response);
});
// Report for Dealer
$app->post('/reportsDealer', function() use ($app) {

	$db = new DbHandler();  
	$response = array();
	$r = json_decode($app->request->getBody());	
	$response= $db->reportsDealer($r);
	echoRespnse(201, $response);
});
// check Location
$app->post('/checkLocation', function() use ($app) {

	$db = new DbHandler();  
	$response = array();
	$r = json_decode($app->request->getBody());	
	$response= $db->checkLocation($r);
	echoRespnse(201, $response);
});
//Location update
$app->post('/locationUpdate', function() use ($app) {

	$db = new DbHandler();  
	$response = array();
	$r = json_decode($app->request->getBody());	
	$response= $db->locationUpdate($r);
	echoRespnse(201, $response);
});
//lookup
$app->post('/reportsDistributor', function() use ($app) {

	$db = new DbHandler();  
	$response = array();
	$r = json_decode($app->request->getBody());	
	$response= $db->reportsDistributor($r);
	echoRespnse(201, $response);
});
//Dealers
$app->post('/dealers', function() use ($app) {

	$db = new DbHandler();  
	$response = array();
	$r = json_decode($app->request->getBody());	
	$response= $db->dealers($r);
	echoRespnse(201, $response);
});
// v2- phase-2
// Get board details	
$app->post('/showlang', function() use ($app) {

	$db = new DbHandler();
	$response = array();
	$response= $db->getLang();
	echoRespnse(201, $response);
});

$app->post('/getMsgFilename', function() use ($app) {
	
	$db = new DbHandler();
	$response = array();
	$r = json_decode($app->request->getBody());
	$response= $db->getMsgFilename($r);
	echoRespnse(201, $response);
});

// SMS API  AND Save Mobile number

$app->post('/savemobile', function() use ($app) {
	
	$db = new DbHandler();
	$response = array();
	$r = json_decode($app->request->getBody());
	
	$response= $db->saveMobile($r);
	
	echoRespnse(201, $response);
});

// SMS API  AND Save Mobile number

$app->post('/verifyopt', function() use ($app) {
	
	$db = new DbHandler();
	$response = array();
	$r = json_decode($app->request->getBody());
	
	$response= $db->verifyOpt($r->otp, $r->device_id);	
	echoRespnse(201, $response);
});

$app->post('/verifydevice', function() use ($app) {

	$db = new DbHandler();
	$response = array();
	$r = json_decode($app->request->getBody());	
	$response= $db->verifyDevice($r->device_id, $r->device_type);	
	echoRespnse(201, $response);
});

$app->post('/getboard', function() use ($app) {
	$db = new DbHandler();
	$response = array();
	$r = json_decode($app->request->getBody());
	
	$response= $db->getboard($r);
	
	echoRespnse(201, $response);
	
	
});
$app->post('/updateuser', function() use ($app) {

	$db = new DbHandler();
	$response = array();
	$r = json_decode($app->request->getBody());
	
	$response= $db->updateUser($r);
	
	echoRespnse(201, $response);
});

$app->post('/verifyserialnumber', function() use ($app) {

	$db = new DbHandler();
	$response = array();
	$r = json_decode($app->request->getBody());
	
	$response= $db->verifyserialnumber($r);
	
	echoRespnse(201, $response);
});
//UpdateCoupNo
$app->post('/UpdateCoupNo', function() use ($app) {

	$db = new DbHandler();
	$response = array();
	$r = json_decode($app->request->getBody());
	
	$response= $db->UpdateCoupNo($r);
	
	echoRespnse(201, $response);
});
//SMSUsers another project
//save SMS Users and sent SMS
$app->post('/saveSMSUser', function() use ($app) {

	$db = new DbHandler();
	$response = array();
	$r = json_decode($app->request->getBody());
	
	$response= $db->saveSMSUser($r);
	
	echoRespnse(201, $response);
});
//  Summary - In this, the API should return the sms count date wise (Ref image SMS Live Summary).
$app->post('/getsummary', function() use ($app) {
	$db = new DbHandler();
	$response = array();	
	$r = json_decode($app->request->getBody());
	$response= $db->getsummary($r);
	
	echoRespnse(201, $response);
});
//Date Details - In this, the app will send the date and the API should return mobile number and the datetime (Ref image SMS Live DateDetails).
$app->post('/getdatedetails', function() use ($app) {

	$db = new DbHandler();
	$response = array();
	$r = json_decode($app->request->getBody());	
	$response= $db->getdatedetails($r);	
	echoRespnse(201, $response);
});
//Find Sender - In this, the app will send the phone number and the API should return the datetime and the SMS count(i.e. primary key of the respective phone number).
$app->post('/getfindsender', function() use ($app) {

	$db = new DbHandler();
	$response = array();
	$r = json_decode($app->request->getBody());	
	$response= $db->getfindsender($r);	
	echoRespnse(201, $response);
});
 //Get Recharge
 $app->post('/getrecharge', function() use ($app) {

	$db = new DbHandler();
	$response = array();
	$r = json_decode($app->request->getBody());	
	$response= $db->getrecharge($r);	
	echoRespnse(201, $response);
});
//Send Recharge
 $app->post('/sendrecharge', function() use ($app) {

	$db = new DbHandler();
	$response = array();
	$r = json_decode($app->request->getBody());	
	$response= $db->sendrecharge($r);	
	echoRespnse(201, $response);
});

//Wallet Details - Coupons​
$app->post('/getcouponsdetails', function() use ($app) {

	$db = new DbHandler();
	$response = array();
	$r = json_decode($app->request->getBody());	
	$response= $db->getcouponsdetails($r);	
	echoRespnse(201, $response);
});
//Wallet Details - Get Recharge​
$app->post('/getrechargedetails', function() use ($app) {

	$db = new DbHandler();
	$response = array();
	$r = json_decode($app->request->getBody());	
	$response= $db->getrechargedetails($r);	
	echoRespnse(201, $response);
});
//getnetbankingdetails
$app->post('/getnetbankingdetails', function() use ($app) {

	$db = new DbHandler();
	$response = array();
	$r = json_decode($app->request->getBody());	
	$response= $db->getnetbankingdetails($r);	
	echoRespnse(201, $response);
});

//Wallet Details - Send Recharge
$app->post('/sendrechargedetails', function() use ($app) {

	$db = new DbHandler();
	$response = array();
	$r = json_decode($app->request->getBody());	
	$response= $db->sendrechargedetails($r);	
	echoRespnse(201, $response);
});

// Fetch active standards
$app->post('/activestandards', function() use ($app) {

	$db = new DbHandler();
	$response = array();	
	$r = json_decode($app->request->getBody());	
	$response= $db->activestandards($r);	
	echoRespnse(201, $response);
});
// On selection of the Standard we shall go to another screen  'Subscription Std Click' We shall display details of that standard : 
$app->post('/standarddetails', function() use ($app) {

	$db = new DbHandler();
	$response = array();
	$r = json_decode($app->request->getBody());	
	$response= $db->standarddetails($r);	
	echoRespnse(201, $response);
});
//When a user click the Pay button, we need to verify  
//Subscription
$app->post('/subscription', function() use ($app) {

	$db = new DbHandler();
	$response = array();
	$r = json_decode($app->request->getBody());	
	$response= $db->subscription($r);	
	echoRespnse(201, $response);
});
//netSubscription
$app->post('/netSubscription', function() use ($app) {

	$db = new DbHandler();
	$response = array();
	$r = json_decode($app->request->getBody());	
	$response= $db->netSubscription($r);	
	echoRespnse(201, $response);
});
//Subscription History
//We shall display subscription transaction as shown in 'Subscription History' graphic  in descending order
$app->post('/subscriptionhistory', function() use ($app) {

	$db = new DbHandler();
	$response = array();
	$r = json_decode($app->request->getBody());	
	$response= $db->subscriptionhistory($r);	
	echoRespnse(201, $response);
});
//First     Screen :  List the unique State         - Index  Alphabetic Ascending    graphic (01-NearMe-State)
$app->post('/getstate', function() use ($app) {

	$db = new DbHandler();
	$response = array();
	//$r = json_decode($app->request->getBody());	
	$response= $db->getstate();
	echoRespnse(201, $response);
});

//Second Screen :  List the unique City           - Index  Alphabetic Ascending    graphic (02-NearMe-Location) 
$app->post('/getcity', function() use ($app) {

	$db = new DbHandler();
	$response = array();
	$r = json_decode($app->request->getBody());	
	$response= $db->getcity($r);
	echoRespnse(201, $response);
});
//Third    Screen :  List the unique Area          - Index  Alphabetic Ascending    graphic (03-NearMe-Area) 
$app->post('/getarea', function() use ($app) {

	$db = new DbHandler();
	$response = array();
	$r = json_decode($app->request->getBody());	
	$response= $db->getarea($r);
	echoRespnse(201, $response);
}); 
//Forth    Screen :  List of Dealers in Details    - Index  Alphabetic Ascending    graphic (04-NearMe-Dealer)   
$app->post('/getdealer', function() use ($app) {

	$db = new DbHandler();
	$response = array();
	$r = json_decode($app->request->getBody());	
	$response= $db->getdealer($r);
	echoRespnse(201, $response);
});
// check PromoCode wheather is available or not
$app->post('/checkpromocode', function() use ($app) {
	$db = new DbHandler();
	$response = array();
	$r = json_decode($app->request->getBody());	
	$response= $db->checkpromocode($r);
	echoRespnse(201, $response);
	
});
//beforePayment
$app->post('/beforePayment', function() use ($app) {
	$db = new DbHandler();
	$response = array();
	$r = json_decode($app->request->getBody());	
	$response= $db->beforePayment($r);
	echoRespnse(201, $response);
	
});
//after payment
$app->post('/afterpayment', function() use ($app) {
	$db = new DbHandler();
	$response = array();
	$r = json_decode($app->request->getBody());	
	$response= $db->afterpayment($r);
	echoRespnse(201, $response);
	
});
// Payment 
$app->post('/paymentorder', function() use ($app) {
	$db = new DbHandler();
	$response = array();
	$r = json_decode($app->request->getBody());		
	//echo "<pre>"; print_r($r); die;
		$url = "https://secure.ccavenue.com/transaction/getRSAKey";
		$fields = array(
				'access_code'=>"AVXD80FJ78AD99DXDA",
				'order_id'=>$r->order_id
		);

		$postvars='';
		$sep='';
		foreach($fields as $key=>$value)
		{
				$postvars.= $sep.urlencode($key).'='.urlencode($value);
				$sep='&';
		}

		$ch = curl_init();

		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_POST,count($fields));
		curl_setopt($ch, CURLOPT_CAINFO, '/cacert.pem');
		curl_setopt($ch,CURLOPT_POSTFIELDS,$postvars);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);

		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$result = curl_exec($ch);
        $response['rsakey'] = $result;
		//echo $result;
	echoRespnse(201, $response);
});

/**
 * ----------- METHODS WITHOUT AUTHENTICATION ---------------------------------
 */
/**
 * User Registration
 * url - /register
 * method - POST
 * params - name, email, password
 */
$app->post('/register', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('name', 'email', 'password'));

            $response = array();

            // reading post params
            $name = $app->request->post('name');
            $email = $app->request->post('email');
            $password = $app->request->post('password');

            // validating email address
            validateEmail($email);

            $db = new DbHandler();
            $res = $db->createUser($name, $email, $password);

            if ($res == USER_CREATED_SUCCESSFULLY) {
                $response["error"] = false;
                $response["message"] = "You are successfully registered";
            } else if ($res == USER_CREATE_FAILED) {
                $response["error"] = true;
                $response["message"] = "Oops! An error occurred while registereing";
            } else if ($res == USER_ALREADY_EXISTED) {
                $response["error"] = true;
                $response["message"] = "Sorry, this email already existed";
            }
            // echo json response
            echoRespnse(201, $response);
        });

/**
 * User Login
 * url - /login
 * method - POST
 * params - email, password
 */
$app->post('/login', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('email', 'password'));

            // reading post params
            $email = $app->request()->post('email');
            $password = $app->request()->post('password');
            $response = array();

            $db = new DbHandler();
            // check for correct email and password
            if ($db->checkLogin($email, $password)) {
                // get the user by email
                $user = $db->getUserByEmail($email);

                if ($user != NULL) {
                    $response["error"] = false;
                    $response['name'] = $user['name'];
                    $response['email'] = $user['email'];
                    $response['apiKey'] = $user['api_key'];
                    $response['createdAt'] = $user['created_at'];
                } else {
                    // unknown error occurred
                    $response['error'] = true;
                    $response['message'] = "An error occurred. Please try again";
                }
            } else {
                // user credentials are wrong
                $response['error'] = true;
                $response['message'] = 'Login failed. Incorrect credentials';
            }

            echoRespnse(200, $response);
        });

/*
 * ------------------------ METHODS WITH AUTHENTICATION ------------------------
 */

/**
 * Listing all tasks of particual user
 * method GET
 * url /tasks          
 */
$app->get('/tasks', 'authenticate', function() {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            // fetching all user tasks
            $result = $db->getAllUserTasks($user_id);

            $response["error"] = false;
            $response["tasks"] = array();

            // looping through result and preparing tasks array
            while ($task = $result->fetch_assoc()) {
                $tmp = array();
                $tmp["id"] = $task["id"];
                $tmp["task"] = $task["task"];
                $tmp["status"] = $task["status"];
                $tmp["createdAt"] = $task["created_at"];
                array_push($response["tasks"], $tmp);
            }

            echoRespnse(200, $response);
        });

/**
 * Listing single task of particual user
 * method GET
 * url /tasks/:id
 * Will return 404 if the task doesn't belongs to user
 */
$app->get('/tasks/:id', 'authenticate', function($task_id) {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            // fetch task
            $result = $db->getTask($task_id, $user_id);

            if ($result != NULL) {
                $response["error"] = false;
                $response["id"] = $result["id"];
                $response["task"] = $result["task"];
                $response["status"] = $result["status"];
                $response["createdAt"] = $result["created_at"];
                echoRespnse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "The requested resource doesn't exists";
                echoRespnse(404, $response);
            }
        });

/**
 * Creating new task in db
 * method POST
 * params - name
 * url - /tasks/
 */
$app->post('/tasks', 'authenticate', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('task'));

            $response = array();
            $task = $app->request->post('task');

            global $user_id;
            $db = new DbHandler();

            // creating new task
            $task_id = $db->createTask($user_id, $task);

            if ($task_id != NULL) {
                $response["error"] = false;
                $response["message"] = "Task created successfully";
                $response["task_id"] = $task_id;
                echoRespnse(201, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to create task. Please try again";
                echoRespnse(200, $response);
            }            
        });

/**
 * Updating existing task
 * method PUT
 * params task, status
 * url - /tasks/:id
 */
$app->put('/tasks/:id', 'authenticate', function($task_id) use($app) {
            // check for required params
            verifyRequiredParams(array('task', 'status'));

            global $user_id;            
            $task = $app->request->put('task');
            $status = $app->request->put('status');

            $db = new DbHandler();
            $response = array();

            // updating task
            $result = $db->updateTask($user_id, $task_id, $task, $status);
            if ($result) {
                // task updated successfully
                $response["error"] = false;
                $response["message"] = "Task updated successfully";
            } else {
                // task failed to update
                $response["error"] = true;
                $response["message"] = "Task failed to update. Please try again!";
            }
            echoRespnse(200, $response);
        });

/**
 * Deleting task. Users can delete only their tasks
 * method DELETE
 * url /tasks
 */
$app->delete('/tasks/:id', 'authenticate', function($task_id) use($app) {
            global $user_id;

            $db = new DbHandler();
            $response = array();
            $result = $db->deleteTask($user_id, $task_id);
            if ($result) {
                // task deleted successfully
                $response["error"] = false;
                $response["message"] = "Task deleted succesfully";
            } else {
                // task failed to delete
                $response["error"] = true;
                $response["message"] = "Task failed to delete. Please try again!";
            }
            echoRespnse(200, $response);
        });

/**
 * Verifying required params posted or not
 */
function verifyRequiredParams($required_fields) {
    $error = false;
    $error_fields = "";
    $request_params = array();
    $request_params = $_REQUEST;
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }

    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["error"] = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
 * Validating email address
 */
function validateEmail($email) {
    $app = \Slim\Slim::getInstance();
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response["error"] = true;
        $response["message"] = 'Email address is not valid';
        echoRespnse(400, $response);
        $app->stop();
    }
}

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

$app->run();
?>