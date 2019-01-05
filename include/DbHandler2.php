 <?php

/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author Ravi Tamada
 * @link URL Tutorial link
 */
class DbHandler {

    private $conn;

    function __construct() {
        require_once dirname(__FILE__) . '/DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }

    /* ------------- `users` table method ------------------ */

    /**
     * Creating new user
     * @param String $name User full name
     * @param String $email User login email id
     * @param String $password User login password
     */
    public function createUser($name, $email, $password) {
        require_once 'PassHash.php';
        $response = array();

        // First check if user already existed in db
        if (!$this->isUserExists($email)) {
            // Generating password hash
            $password_hash = PassHash::hash($password);

            // Generating API key
            $api_key = $this->generateApiKey();

            // insert query
            $stmt = $this->conn->prepare("INSERT INTO users(name, email, password_hash, api_key, status) values(?, ?, ?, ?, 1)");
            $stmt->bind_param("ssss", $name, $email, $password_hash, $api_key);

            $result = $stmt->execute();

            $stmt->close();

            // Check for successful insertion
            if ($result) {
                // User successfully inserted
                return USER_CREATED_SUCCESSFULLY;
            } else {
                // Failed to create user
                return USER_CREATE_FAILED;
            }
        } else {
            // User with same email already existed in the db
            return USER_ALREADY_EXISTED;
        }

        return $response;
    }

    /**
     * Checking user login
     * @param String $email User login email id
     * @param String $password User login password
     * @return boolean User login status success/fail
     */
    public function checkLogin($email, $password) {
        // fetching user by email
        $stmt = $this->conn->prepare("SELECT password_hash FROM users WHERE email = ?");

        $stmt->bind_param("s", $email);

        $stmt->execute();

        $stmt->bind_result($password_hash);

        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Found user with the email
            // Now verify the password

            $stmt->fetch();

            $stmt->close();

            if (PassHash::check_password($password_hash, $password)) {
                // User password is correct
                return TRUE;
            } else {
                // user password is incorrect
                return FALSE;
            }
        } else {
            $stmt->close();

            // user not existed with the email
            return FALSE;
        }
    }
//v2- phase -2
//checkTrialStatus
	public function checkTrialStatus($r){
		$res = array();
		$BoardID = $r->BoardID;
		$DeviceId = $r->DeviceId;		
		$stmt = $this->conn->prepare("SELECT SubscriptionStatus from TopUserInfo WHERE  DeviceId = ?");
		$stmt->bind_param("i", $DeviceId);
		$stmt->execute();
        $stmt->bind_result($SubscriptionStatus);
        $stmt->fetch();
        $stmt->close();
		if($SubscriptionStatus == 'DEMO'){
			$stmt = $this->conn->prepare("SELECT PromoName from Subscription WHERE  DeviceId = ?");
			$stmt->bind_param("i", $DeviceId);
			$stmt->execute();
			$stmt->bind_result($PromoName);
			$stmt->fetch();
			$stmt->close();
			if($PromoName != 'FirstInst'){
				$stmt = $this->conn->prepare("SELECT FreeSubPeriod from AppInfo WHERE  BoardID = ?");
				$stmt->bind_param("i", $BoardID);
				$stmt->execute();
				$stmt->bind_result($FreeSubPeriod);
				$stmt->fetch();
				$stmt->close();
				$res['message'] = "success";
				$res['FreeSubPeriod'] = $FreeSubPeriod;
				$res['status']  = 1;
			}else{
				$res['message'] = "Already Trial period over";
				$res['status']  = 2;
			}
		}	

		
		return $res;
	}
	public function trialSubscription($r){

			/*
			Table : Subscription

			SubscriptionID
			DeviceId
			DateofSub
			BoardId
			Lang
			Std
			Fees
			EndDateTime
						*/
			//echo $Std  	 = $r->Std; die;
			
			
			date_default_timezone_set('Asia/Kolkata');	
			$DeviceId 		= $r->DeviceId;
			$BoardId 		= $r->BoardId;
			$Lang    		= $r->Lang;
			$Std  	 		= $r->Std;			
			$promoCode 		= $r->promoCode;
			$EndDateTime 	= $r->EndDateTime;			
			$Mode			= 1;
			$res = array();
			$stdArr = $this->standarddetails($r);
			//$WalletArr = $this->verifyDeviceID($DeviceId);
			//echo "stdArr<pre>"; print_r($stdArr);
			//echo "WalletArr<pre>"; print_r($WalletArr); die;
			
				$Fee = $stdArr['monthlySubscriptionFees'];
				$Discount		= $Fee;
				$DateofSub = date("Y-m-d H:i:s");
				$EndDateTime = $EndDateTime;
				$stmt = $this->conn->prepare("INSERT INTO Subscription(DeviceId, DateofSub, BoardId, Lang, Std, Fees, EndDateTime, Discount, PromoName, Mode) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
				$stmt->bind_param("issssisssi", $DeviceId, $DateofSub, $BoardId, $Lang, $Std, $Fee, $EndDateTime, $Discount, $promoCode, $Mode);					
				  $result = $stmt->execute();					 
					if (false === $result) {
						die('execute() failed: ' . htmlspecialchars($stmt->error));
					}
					$stmt->close();
				if($result)
					{ 
					 $this->updateTopUserInfoByTrail($DeviceId);
					 
					 $res['message'] = "Subscription successfully";					 
					 $res['status']  = 1;
					 $res['subscriptionUpto'] = $EndDateTime;					 
					 $res['subscriptionStandard'] = $Std;
					 $res['StdPWD'] = $stdArr['StdPWD'];
					 
					}else{
					 $res['message'] = "Update Error";
					 $res['status']  = 0;
					}
				
							
		return $res;	
}
private function checkIsCashback($promoCode){
		$stmt = $this->conn->prepare("SELECT Type from PromoCode WHERE CodeName = ?");
        $stmt->bind_param("s", $promoCode);			
		$stmt->execute();
        $stmt->bind_result($Type);
        $stmt->fetch();
        $stmt->close();
        return $Type;
        
}
private function UpdateTopCouponInfo($r, $WalletCashBack, $result){
	
	/*
	TopCouponInfo

		BatchId = 9999999999
		Coupon  = C1
		CouponSR =CB1
		CouponAmt = CASH BACK Value
		Istatus  = Used
		UsedDateTime = Now
		Type  = C
		DeviceId   = Device no 
		Remark  = Subscription ID
	*/
	$BatchId 		= '9999999999';
	$Coupon			= ($this->autoIncr())? 'C'.$this->autoIncr() : 'C1';
	$CouponSR		= ($this->autoIncr())? 'CB'.$this->autoIncr() : 'CB1';
	$CouponAmt		= $WalletCashBack;
	$Istatus		= 'Used';
	$UsedDateTime	= date("Y-m-d H:i:s");
	$Type  			= 'C';
	$DeviceId 		= $r->DeviceId;
	$Remark         = $result;
	$stmt = $this->conn->prepare("INSERT INTO TopCouponInfo(BatchId, CoupNo, CoupSRNo, CouponAmt, Istatus, UsedDateTime, Type, DeviceId, Remark) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)");
			$stmt->bind_param("ississsii", $BatchId, $Coupon, $CouponSR, $CouponAmt, $Istatus, $UsedDateTime, $Type, $DeviceId, $Remark);					
			$result = $stmt->execute();					 
			if (false === $result) {
				die('execute() failed: ' . htmlspecialchars($stmt->error));
			}
			$stmt->close();
		return 1;	
	
}
private function updateWalletCashback($DeviceId, $walletCB, $Fee){
	
		$stmt = $this->conn->prepare("SELECT Wallet, WalletCashBack from TopUserInfo WHERE DeviceId = ?");
		$stmt->bind_param("i", $DeviceId);		
		$stmt->execute();
        $stmt->bind_result($Wallet, $WalletCashBack);
        $stmt->fetch();
        $stmt->close();
		if($WalletCashBack == 0){
			$WCB = 	$WalletCashBack + $walletCB;
		}	
		else if($WalletCashBack > 0){
			$WCB = 0;
			$Wallet = $Wallet - ($Fee - $WalletCashBack);
		}/*else if($wallet > $WalletCashBack){
			$NW = $Wallet - $WalletCashBack;
			$WalletCashBack = 0;
			$Wallet = $Wallet - $NW;
		}*/		
		
		
		$stmt = $this->conn->prepare("UPDATE TopUserInfo set Wallet = ?, WalletCashBack = ? WHERE DeviceId = ?");			 
		$stmt->bind_param("iis", $Wallet, $WCB, $DeviceId);
		$stmt->execute();
		$num_affected_rows = $stmt->affected_rows;
		$stmt->close();
		if($num_affected_rows > 0)
		{			 
			return $Wallet;
						 
		}else{
			return 0;
		} 
		
}	
private function autoIncr(){
	
	//
	//$param = "%{$_POST['user']}%";
//$stmt = $db->prepare("SELECT id,Username FROM users WHERE Username LIKE ?");
//$stmt->bind_param("s", $param);

		$sql = "SELECT CoupNo FROM TopCouponInfo WHERE CoupNo LIKE '%C%' ORDER BY id DESC";
		//echo $sql; die;
		$a_data = array();   
  		$res = $this->conn->query($sql);
		
		if($res === false) {
  				$this->last_error = 'Wrong SQL: ' . $sql . ' Error: ' . $conn->ErrorMsg();
		} else {
  				$res->data_seek(0);
 				 while($row = $res->fetch_assoc()) {
   					 array_push($a_data, $row);
  					}
		}
		//print_r($a_data[0]['CoupNo']); die;
		/*$param = "C";
		$stmt = $this->conn->prepare("SELECT CoupNo from TopCouponInfo WHERE CoupNo LIKE CONCAT('%', ?, '%')");
        $stmt->bind_param("s", $param);			
		$stmt->execute();
        $stmt->bind_result($Coupon);
        $stmt->fetch();
        $stmt->close */
		if(!empty($a_data)){			
			preg_match("/([a-zA-Z]+)(\\d+)/", $a_data[0]['CoupNo'], $matches);				
			return $matches[2] + 1;
		}else{
			return 0;
		}	
}	
private function updateTopUserInfoByTrail($DeviceId){
	   
				$SubscriptionStatus = "Subscribe";
				
			$stmt = $this->conn->prepare("UPDATE TopUserInfo set SubscriptionStatus = ? WHERE DeviceId = ?");
			 
						$stmt->bind_param("ss", $SubscriptionStatus, $DeviceId);
						$stmt->execute();
						$num_affected_rows = $stmt->affected_rows;
						$stmt->close();
						if($num_affected_rows > 0)
						{			 
						   return 1;
						 
						}else{
						   return 0;
						} 
}
public function updateDemoUser($r){
				$DeviceId 		= $r->DeviceId;
				//echo $DeviceId; die;
				$res = array();
				$SubscriptionStatus = "DEMO";
				
				$stmt = $this->conn->prepare("UPDATE TopUserInfo set SubscriptionStatus = ? WHERE DeviceId = ?");
			 
						$stmt->bind_param("sis", $SubscriptionStatus, $DeviceId);
						$stmt->execute();
						$num_affected_rows = $stmt->affected_rows;
						$stmt->close();
						if($num_affected_rows > 0)
						{			 
						    $res['message'] = "Subscription successfully";					 
							$res['status']  = 1;
						 
						}else{
						   $res['message'] = "Update Error";
							$res['status']  = 0;
						} 
	return $res;					
}
public function getStandardFee($r){
	
	    $BoardId = $r->BoardId;
		$Lang	 = $r->Lang;
		$Std	 = $r->Std;
		
		$stmt = $this->conn->prepare("SELECT Fees, Monthly, HalfYearly, IStatus, BroadCastMsg, StdPWD from Standard WHERE BoardId = ? AND Lang = ? AND Std = ?");
		$stmt->bind_param("ssi", $BoardId, $Lang, $Std);
				
       if ($stmt->execute()) {
            // $user = $stmt->get_result()->fetch_assoc();
            $stmt->bind_result($Fees, $Monthly, $HalfYearly, $IStatus, $BroadCastMsg, $StdPWD);
            $stmt->fetch();
            $user = array();
            $user["Fees"] 			= $Fees;
            $user["Monthly"] 		= $Monthly;
            $user["HalfYearly"]		= $HalfYearly;
            $user["IStatus"] 		= $IStatus;
            $user["BroadCastMsg"]   = $BroadCastMsg;
			$user["StdPWD"]   		= $StdPWD;
            $stmt->close();
            return $user;
        } else {
            return NULL;
        }
}

//
public function subscriptionByUser($r){

			/*
			Table : Subscription

			SubscriptionID
			DeviceId
			DateofSub
			BoardId
			Lang
			Std
			Fees
			EndDateTime
						*/
			//echo $Std  	 = $r->Std; die;
			date_default_timezone_set('Asia/Kolkata');	
			$DeviceId = $r->DeviceId;
			$BoardId = $r->BoardId;
			$Lang    = $r->Lang;
			$Std  	 = $r->Std;
			$discount = $r->discount;
			$promoCode = $r->promoCode;
			$WalletAmt = $r->WalletAmt;
			$EndDateTime = $r->EndDateTime;
			$Fee		 = $r->Fee;
			
			$res = array();
			$stdArr = $this->standarddetails($r);
			
			$WalletArr = $this->verifyDeviceID($DeviceId);
					
			
				
				$DateofSub = date("Y-m-d H:i:s");
				
				$stmt = $this->conn->prepare("INSERT INTO Subscription(DeviceId, DateofSub, BoardId, Lang, Std, Fees, EndDateTime, Discount, PromoName) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)");
				$stmt->bind_param("issssisis", $DeviceId, $DateofSub, $BoardId, $Lang, $Std, $Fee, $EndDateTime, $discount, $promoCode);					
				$result = $stmt->execute();
				$insert_id = $stmt->insert_id;				  
					if (false === $result) {
						die('execute() failed: ' . htmlspecialchars($stmt->error));
					}
					$stmt->close();
				if($result)
					{
					 // update user Wallet;
					 $walletCB = 0;
					 $wallet   = 0;
					 $NW = 0;
					 
					 if($WalletArr['WalletCashBack'] > 0){
						 if($WalletArr['WalletCashBack'] == $WalletAmt){
							 $walletCB = $WalletArr['WalletCashBack'] - $WalletAmt;
							 $wallet = $WalletArr['Wallet'];
						 }else{
							 $NW =  $WalletAmt - $WalletArr['WalletCashBack'];
							 $wallet = $WalletArr['Wallet'] - $NW;
						 }
						
					 }else{
						$wallet = $WalletArr['Wallet'] - $WalletAmt; 
					 }						 
					 
					 $this->updateTopUserInfoBySub($wallet, $walletCB, $DeviceId);
					 if($this->checkIsCashback($promoCode) == 'C'){
						
						$this->UpdateTopCouponInfo($r, $walletCB + $discount, $insert_id);
						$this->updateTopUserInfoBySub($wallet, $walletCB + $discount , $DeviceId);						
						//$this->updateWalletCashback($DeviceId, $WalletAmt);
					}	
					 $res['message'] = "Subscription successfully";					 
					 $res['status']  = 1;
					 $res['subscriptionUpto'] = $EndDateTime;
					 $res['remainingWalletAmount'] = $wallet + $walletCB;
					 $res['subscriptionStandard'] = $Std;
					 $res['StdPWD'] = $stdArr['StdPWD'];
					}else{
					 $res['message'] = "Update Error";
					 $res['status']  = 0;
					}
				
							
		return $res;	
}
//check installation cashback promocode
public function checkInstallCashback($r){
	
	$deviceId = $r->DeviceId;
	$res = array();
	$stmt = $this->conn->prepare("SELECT PromoName from TopUserInfo WHERE DeviceId = ?");
	$stmt->bind_param("i", $deviceId);
	$stmt->execute();
	$stmt->bind_result($PromoName);
    $stmt->fetch();
    $stmt->close();
	$res['PromoName'] = $PromoName;
    return $res;
}
////if available promocode update PromoCode in to TopUserInfo table
public function updateInstallCashback($r){
	$deviceId 	= $r->DeviceId;
	$promoCode 	= $r->PromoCode;	
	$res = array();
	$stmt = $this->conn->prepare("SELECT CodeId, Type, Value, EndDate, Status from PromoCode WHERE CodeName = ?");
	$stmt->bind_param("s", $promoCode);
	$stmt->execute();
	$stmt->bind_result($CodeId, $Type, $Value, $EndDate, $Status);
    $stmt->fetch();
    $stmt->close();
	if($CodeId != null){			
		
		$stmt = $this->conn->prepare("UPDATE TopUserInfo set PromoName = ? WHERE DeviceId = ?");
		$stmt->bind_param("si",$promoCode, $deviceId);
		$stmt->execute();
		$num_affected_rows = $stmt->affected_rows;
		$stmt->close();
		if($num_affected_rows > 0){
			$res['CodeId'] 		= $CodeId;
			$res['Type']   		= $Type;
			$res['Value']  		= $Value;
			$res['EndDate'] 	= $EndDate;
			$res['Status']  	= $Status;
			$res['message']		= 'success';				 
		}else{
			$res['status'] = 'TopUserInfo update Error!';
		}
		
	}else{
		$res['message'] = 'Invalid Promocode';
	}	
    
	return $res;
}	
//v2- phase -2
    /**
     * Checking for duplicate user by email address
     * @param String $email email to check in db
     * @return boolean
     */
    private function isUserExists($imei_1 = '', $imei_2 = '', $mac = '', $type) {
       
		$res = array();
		if($type == 'Mobile'){
			
				$stmt = $this->conn->prepare("SELECT IMEI_1 from TopUserInfo WHERE IMEI_1 = ? OR IMEI_2 = ?");
				$stmt->bind_param("ii", $imei_1, $imei_2);
			
		}else if($type == 'Tablet'){
		    $stmt = $this->conn->prepare("SELECT IMEI_1 from TopUserInfo WHERE MAC = ?");
			$stmt->bind_param("i", $mac);
		}else{
				$res['message'] = "The Device type did not match!";
				$res['status']  = 3;
				return $res;
		}		
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;		
        $stmt->close();
        return $num_rows > 0;
    }
	
	private function getLastDeviceId($imei_1 = '', $imei_2 = '', $mac = '', $type = "") {
        $res = array();
		if($type == 'Mobile'){
		
				$stmt = $this->conn->prepare("SELECT DeviceId from TopUserInfo WHERE IMEI_1 = ? OR IMEI_2 = ?");
				$stmt->bind_param("ii", $imei_1, $imei_2);
			
		}else if($type == 'Tablet'){
		    $stmt = $this->conn->prepare("SELECT DeviceId from TopUserInfo WHERE MAC = ?");
			$stmt->bind_param("i", $mac);
		}else{
				$stmt = $this->conn->prepare("SELECT DeviceId from TopUserInfo WHERE 1 ORDER BY DeviceId DESC"); 
		}
						      
        $stmt->execute();
        $stmt->bind_result($DeviceId);
        $stmt->fetch();
        $stmt->close();
        return $DeviceId;
    }
	public function verifyDevice($id, $type){
        //echo $id; die;
		$res = array();		
		if($type == 'Mobile'){
			$stmt = $this->conn->prepare("SELECT IStatus, UserMbNo, DeviceId, Wallet, WalletCashBack, SubscriptionStatus, UserType, MasterWallet, PromoName from TopUserInfo WHERE IMEI_1 = ? OR IMEI_2 = ?");
			$stmt->bind_param("ii", $id, $id);
		}else if($type == 'Tablet'){
		    $stmt = $this->conn->prepare("SELECT IStatus, UserMbNo, DeviceId, Wallet, WalletCashBack, SubscriptionStatus, UserType, MasterWallet, PromoName from TopUserInfo WHERE MAC = ?");
			$stmt->bind_param("i", $id);
		}else{
				$res['message'] = "The Device type did not match!";
				$res['status']  = 3;
				return $res;
		}		
        $stmt->execute();
        $stmt->bind_result($IStatus, $UserMbNo, $DeviceId, $Wallet, $WalletCashBack,  $SubscriptionStatus, $UserType, $MasterWallet, $PromoName);
        $stmt->fetch();       
		$stmt->close();
        if($IStatus == 'Verified'){	
                if($SubscriptionStatus == 'Subscribe'){
						
					/*Table : Subscription

					SubscriptionID
					DeviceId
					DateofSub
					BoardId
					Lang
					Std
					Fees
					EndDateTime*/
					$sql = "SELECT sub.DateofSub, sub.BoardId, sub.Lang, sub.Std, sub.Fees, sub.EndDateTime, std.StdPWD, sub.Mode FROM Subscription sub INNER JOIN Standard std ON std.Std = sub.Std WHERE sub.DeviceId = '$DeviceId' GROUP BY sub.SubscriptionID ORDER by SubscriptionID DESC";
					
					
					$a_data = array();   
					$ress = $this->conn->query($sql);
					
					if($ress === false) {
							$this->last_error = 'Wrong SQL: ' . $sql . ' Error: ' . $conn->ErrorMsg();
					} else {
							$ress->data_seek(0);
							 while($row = $ress->fetch_assoc()) {
								 array_push($a_data, $row);
								}
					}
					//echo "<pre>"; print_r($a_data); die;
					$res['std'] = $a_data;
					
				}else{
					$res['std'] = NULL;
				}
				$res['UserMbNo'] = $UserMbNo;
				$res['DeviceId'] = $DeviceId;
				if($Wallet == NULL){				
					$res['Wallet'] = 0 + ($WalletCashBack != NULL)? $WalletCashBack : 0;
				}else{
					$res['Wallet'] = $Wallet + $WalletCashBack;
				}
				$res['SubscriptionStatus'] = $SubscriptionStatus;				
				$res['UserType'] = $UserType;				
				if($MasterWallet == null){
					$res['MasterWallet'] = 0;
				}else{
					$res['MasterWallet'] = $MasterWallet;
				}
				$res['PromoName'] = $PromoName;
				$res['message'] = "The Device is Registered and Verified";
				$res['status']  = 1;
		}else{
				$res['message'] = "The Device is NOT Registered or unVerified";
			    $res['status']  = 2;
		}
		
	   return $res;
	}
	private function getRandomDeviceId($mobile_no, $resDeviceId=''){
				$DeviceId = mt_rand(100000,999999);
				$DeviceId = $DeviceId.substr($mobile_no, -4);
				
				if($resDeviceId != '' && $DeviceId == $resDeviceId){
					$this->getRandomDeviceId($mobile_no, $resDeviceId);
				}
				return $DeviceId;
	}
	
	private function isUserVerified($imei_1 = '', $imei_2 = '', $mac = '', $type) {
         
		 $res = array();
		if($type == 'Mobile'){
		
			$stmt = $this->conn->prepare("SELECT IStatus from TopUserInfo WHERE IMEI_1 = ? OR IMEI_2 = ?");
			$stmt->bind_param("ii", $imei_1, $imei_2);
			
		}else if($type == 'Tablet'){
		    $stmt = $this->conn->prepare("SELECT IStatus from TopUserInfo WHERE MAC = ?");
			$stmt->bind_param("i", $mac);
		}else{
				$res['message'] = "The Device type did not match!";
				$res['status']  = 3;
				return $res;
		}		
        	
        $stmt->execute();
        $stmt->bind_result($IStatus);
        $stmt->fetch();
        $stmt->close();
        return $IStatus;
    }
//verifyOpt
 private function isVerifyOpt($otp, $device_id) {

        $stmt = $this->conn->prepare("SELECT IStatus from TopUserInfo WHERE OTPNo = ? AND DeviceId = ?");
        $stmt->bind_param("ii", $otp, $device_id);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
		$stmt->close();				
        return $num_rows > 0;
    }
	
  public function verifyOpt($otp, $device_id)
  {
	   $res = array();
	   if($this->isVerifyOpt($otp, $device_id))
	   {
		$IStatus = 'Verified';
		$stmt = $this->conn->prepare("UPDATE TopUserInfo set IStatus = ? WHERE DeviceId = ? AND OTPNo = ? ");
        $stmt->bind_param("ssi", $IStatus, $device_id, $otp);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        //return $num_affected_rows > 0;
		 if($num_affected_rows > 0)
		 {
			 	$res['message'] = "successfully OTP Verified";
				$res['status']  = 1;
		 }else{
			 $res['message'] = "Update Error";
			 $res['status']  = 3;
		 }
	   }else{
		   		$res['message'] = "Invalid OTP";
				$res['status']  = 2;
	   }
	   return $res;
  }
    /**
     * Fetching user by email
     * @param String $email User email id
     */
    public function getUserByEmail($email) {
        $stmt = $this->conn->prepare("SELECT name, email, api_key, status, created_at FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            // $user = $stmt->get_result()->fetch_assoc();
            $stmt->bind_result($name, $email, $api_key, $status, $created_at);
            $stmt->fetch();
            $user = array();
            $user["name"] = $name;
            $user["email"] = $email;
            $user["api_key"] = $api_key;
            $user["status"] = $status;
            $user["created_at"] = $created_at;
            $stmt->close();
            return $user;
        } else {
            return NULL;
        }
    }

   /** Topper table **/
   public function getLang(){
	   //echo "yes"; die;
	    $sql = "SELECT * FROM AppInfo WHERE IStatus = 'Active'";
		$a_data = array();	       
  		$res = $this->conn->query($sql);
		if($res === false) {
  				$this->last_error = 'Wrong SQL: ' . $sql . ' Error: ' . $conn->ErrorMsg();
		} else {
  				$res->data_seek(0);
 				 while($row = $res->fetch_assoc()) {
   					 array_push($a_data, $row);
  					}
		}
		return $a_data;
  }
  public function  saveMobile($r) {
	   
	    //echo "<pre>"; print_r($r); die;
		$res = array();
        $mobile_no = $r->mobile_no;
		$machine_type = $r->machine_type;
        $device_desc  = $r->device_desc;
		$location     = $r->location;
		$imei_1		  = $r->imei_1;
		$osinfo       = $r->osinfo;
		$imei_2       = (isset($r->imei_2) && $r->imei_2 != '')? $r->imei_2 : "";
		if($machine_type == 'Tablet')
			$mac          = $r->mac;
		else
			$mac          = "";
			
		if (!$this->isUserExists($imei_1, $imei_2, $mac, $machine_type)) {
		    $resDeviceId = $this->getLastDeviceId();
				
			//echo "<pre>fgfhf"; print_r($resDeviceId); die;
			//echo count($resDeviceId); die;
			if($resDeviceId !=''){
				$DeviceId    = $this->getRandomDeviceId($mobile_no, $resDeviceId);
			}else{			
				$DeviceId    = $this->getRandomDeviceId($mobile_no);
			}
			
			$OTPNo = $this->smsgatewaycenter_com_OTP();		
			$sendmessage = 'Your%20eTen%20verification%20code%20is%20'.$OTPNo.'.';
			$sendApi = $this->smsgatewaycenter_com_Send($mobile_no, $sendmessage);
			//echo $sendApi; die;
			$IStatus = 'UnVerified';
			//if ( @date_default_timezone_set(date_default_timezone_get()) === false ){ 
				date_default_timezone_set('Asia/Kolkata');
			//}			
			$DateofInst = date('Y-m-d H:i:s');
			//echo $DeviceId; 
			//echo $DateofInst; die;
			$stmt = $this->conn->prepare("INSERT INTO TopUserInfo(UserMbNo, DeviceId, DateofInst, IStatus, OTPNo, MachineType, DeviceDesc, Location, IMEI_1, IMEI_2, MAC, OSInfo) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
			$stmt->bind_param("iississsssss", $mobile_no, $DeviceId, $DateofInst, $IStatus, $OTPNo, $machine_type, $device_desc, $location, $imei_1, $imei_2, $mac, $osinfo);
			
			$result = $stmt->execute();
			 
			if (false === $result) {
				die('execute() failed: ' . htmlspecialchars($stmt->error));
			}
			$stmt->close();
			if($result  == 1){
				$res['message'] = "OTP sent successfully";
				$res['DeviceId'] = $DeviceId;
				$res['status']  = 1;
            
			}else{
				$res['message'] = "OTP sent Error";
				$res['status']  = 4;
            
			}
			
		} else if($this->isUserExists($imei_1, $imei_2, $mac, $machine_type) && $this->isUserVerified($imei_1, $imei_2, $mac, $machine_type) == 'UnVerified') {
			$DeviceId = $this->getLastDeviceId($imei_1, $imei_2, $mac, $machine_type);
			//Resend SMS
			$OTPNo = $this->smsgatewaycenter_com_OTP();		
			$sendmessage = 'Your%20eTEN%20verification%20code%20is%20'.$OTPNo.'.';
			$sendApi = $this->smsgatewaycenter_com_Send($mobile_no, $sendmessage);
			
			$stmt = $this->conn->prepare("UPDATE TopUserInfo set UserMbNo = ?, MachineType = ?, DeviceDesc = ?, Location = ?, IMEI_1 = ?, IMEI_2 = ?, MAC = ?, OSInfo = ?, OTPNo = ? WHERE DeviceId = ?");
			$stmt->bind_param("isssssssii",$mobile_no, $machine_type, $device_desc, $location, $imei_1, $imei_2, $mac, $osinfo, $OTPNo, $DeviceId);
			$stmt->execute();
			$num_affected_rows = $stmt->affected_rows;
			$stmt->close();
			
			if($num_affected_rows > 0)
			{			 
			 $res['message'] = "OTP Resent successfully";
			 $res['DeviceId'] = $DeviceId;
			 $res['status']  = 3;
			}else{
			 $res['message'] = "Update Error";
			 $res['status']  = 0;
			}

		}
		else {
            // User with same mobile no already existed in the db
			$res = array();
			$res['message'] = "User Already registered";
			$res['status']  =  USER_ALREADY_EXISTED;
            
        }
        return $res;
    }
	public function getboard($r){
		/*
		BoardID
		BoardName
		Lang
		Language
		StartDateTime
		EndDateTime
		Standards
		IStatus
		BroadCastMsg
		VerNo
		Remark*/
		$res = array();
		$BoardID = $r->board_id;        	
        //echo $BoardID; die;		
		$stmt = $this->conn->prepare("SELECT BoardID, BoardName, Lang, Language, StartDateTime, EndDateTime, Standards, IStatus, BroadCastMsg, VerNo, Remark from AppInfo WHERE BoardID = ? ");
		$stmt->bind_param("s", $BoardID);	
        
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($BoardID, $BoardName, $Lang, $Language, $StartDateTime, $EndDateTime, $Standards, $IStatus, $BroadCastMsg, $VerNo, $Remark);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();
			$res["BoardID"] = $BoardID;
            $res["BoardName"] = $BoardName;
            $res["Lang"] = $Lang;
			$res["Language"] = $Language;
            $res["StartDateTime"] = $StartDateTime;
            $res["EndDateTime"] = $EndDateTime;
			$res["Standards"] = $Standards;
			$res["IStatus"] = $IStatus;
			$res["BroadCastMsg"] = $BroadCastMsg;
			$res["VerNo"]        = $VerNo;
			$res["Remark"] = $Remark;
            $stmt->close();
            return $res;
        } else {
            return NULL;
        }
		
	}
    private function isVerifySNO($CoupSRNo)
	{
		$res = array();
		 //echo $CoupSRNo; die;   	
        //echo $BoardID; die;		
		$stmt = $this->conn->prepare("SELECT CoupSRNo, IStatus, ExpireDateTime, CoupNo, CouponAmt from TopCouponInfo  WHERE BINARY CoupSRNo = ? ");
		$stmt->bind_param("s", $CoupSRNo);	
        
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($CoupSRNo, $IStatus, $ExpireDateTime, $CoupNo, $CouponAmt);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();			
			$res["CoupSRNo"] = $CoupSRNo;
            $res["IStatus"] = $IStatus;
			$res["ExpireDateTime"] = $ExpireDateTime;
			$res["CoupNo"] = $CoupNo;
			$res["CouponAmt"] = $CouponAmt;			
            $stmt->close();
            return $res;
        } else {
            return NULL;
        }
		
	}
   //verifyserialnumber
   public function verifyserialnumber($r){
	   /*
			   Table name: TopCouponInfo
		BatchID
		CoupNo
		CoupSRNo
		CouponAmt
		IStatus
		ExpireDateTime 
		UsedDateTime 
		DeviceId DeviceId
		Remark
			   */
		$res = array();	   
		$CoupSRNo = $r->CoupSRNo; 
        $coupnInfo = $this->isVerifySNO($CoupSRNo);		
	     if($coupnInfo != NULL){
			 if($coupnInfo['IStatus'] == 'New'){
				 
				 date_default_timezone_set('Asia/Kolkata');					
				 $currDate = date('Y-m-d H:i:s');
				 $currentDate = strtotime($currDate);
				 $futureDate = $currentDate+(60*5);
				 $ExpireDateTime = date("Y-m-d H:i:s", $futureDate);
				 //echo $ExpireDateTime;
				 $CoupSRNo = $coupnInfo['CoupSRNo'];
				 
				 //echo $CoupSRNo; die;
				 $stmt = $this->conn->prepare("UPDATE TopCouponInfo set ExpireDateTime = ? WHERE CoupSRNo = ?");
				$stmt->bind_param("ss",$ExpireDateTime, $CoupSRNo);
				$stmt->execute();
				$num_affected_rows = $stmt->affected_rows;
				$stmt->close();
				if($num_affected_rows > 0)
				{			 
				 
				   $res['status'] = 'Valid';
				   $res['CouponAmt'] = $coupnInfo['CouponAmt'];
				}else{
					$res['status'] = 'Valid but update Error!';
                }					
				 				 
			 }else{
				$res['status'] = 'Invalid'; 
             }				 
		 }else{
			 $res['status'] = 'Invalid'; 
         }			 
	   
	   return $res;
   }
   private function dateDiffernce($datetime){
	    date_default_timezone_set('Asia/Kolkata');		
	    $datetime1 = new DateTime();
		$datetime2 = new DateTime($datetime);
		$elapsed = 0;
		if($datetime1 <= $datetime2){
			$elapsed = 1;
		}
		//$interval = $datetime1->diff($datetime2);
		//$elapsed = $interval->format('%y years %m months %a days %h hours %i minutes %s seconds');
		return $elapsed;

   }   
//UpdateCoupNo
   public function UpdateCoupNo($r){
	   
	   $res = array();	   
		$CoupSRNo = $r->CoupSRNo;
        $DeviceId = $r->DeviceId;		
        $CoupNo   = $r->CoupNo;	
        
        $coupnInfo = $this->isVerifySNO($CoupSRNo);
        $deviceInfo = $this->verifyDeviceID($DeviceId);	
		
	     if($coupnInfo != NULL && $deviceInfo != NULL){
			 if(($coupnInfo['CoupNo'] == $CoupNo) && ($coupnInfo['IStatus'] == 'New')){
				 
				 //$diff = $this->dateDiffernce($coupnInfo['ExpireDateTime']);			
				// if($diff){
						// UPDATE TopUserInfo TABLE Wallet FIELD
						$Wallet = $deviceInfo['Wallet'] + $coupnInfo['CouponAmt'];						
						$DeviceId = $deviceInfo['DeviceId']; 
						
						if($this->updateTopUserInfo($Wallet, $DeviceId)){
						
							$CoupSRNo = $coupnInfo['CoupSRNo'];
							date_default_timezone_set('Asia/Kolkata');		
							$UsedDateTime = date('Y-m-d H:i:s');
							$IStatus  = 'Used';
							$stmt = $this->conn->prepare("UPDATE TopCouponInfo set UsedDateTime = ?, DeviceId = ?, IStatus = ? WHERE CoupSRNo = ?");
							$stmt->bind_param("ssss",$UsedDateTime, $DeviceId, $IStatus, $CoupSRNo);
							$stmt->execute();
							$num_affected_rows = $stmt->affected_rows;
							$stmt->close();
							if($num_affected_rows > 0)
							{			 
								$res['status']  = '1';
								$res['WalletAmt']  = $Wallet;
								$res['message'] = 'successfully updated';
								
							 
							}else{
								$res['status'] = 'TopCouponInfo update Error!';
							}
						}else{
							
							$res['status'] = 'TopUserInfo update Error!';
							
						}							
			     /*}else{
					$res['status'] = 'ExpireDateTime exceeded';  
                 }	*/				 
				 			 					
				 				 
			 }else{
				$res['status'] = 'Invalid'; 
             }				 
		 }else{
			 $res['status'] = 'Invalid'; 
         }			 
	   
	   return $res;
   }
   private function updateTopUserInfo($Wallet, $DeviceId){
	   

			$stmt = $this->conn->prepare("UPDATE TopUserInfo set Wallet = ? WHERE DeviceId = ?");
			
						$stmt->bind_param("is",$Wallet, $DeviceId);
						$stmt->execute();
						$num_affected_rows = $stmt->affected_rows;
						$stmt->close();
						if($num_affected_rows > 0)
						{			 
						   return 1;
						 
						}else{
							return 0;
						}
   }
	private function updateTopUserInfoBySub($Wallet, $WalletCB, $DeviceId){
	   
				$SubscriptionStatus = "Subscribe";
			$stmt = $this->conn->prepare("UPDATE TopUserInfo set Wallet = ?, WalletCashBack = ?, SubscriptionStatus = ? WHERE DeviceId = ?");
			 
						$stmt->bind_param("iiss",$Wallet, $WalletCB, $SubscriptionStatus, $DeviceId);
						$stmt->execute();
						$num_affected_rows = $stmt->affected_rows;
						$stmt->close();
						if($num_affected_rows > 0)
						{			 
						   return 1;
						 
						}else{
						   return 0;
						}
   }
   private function updateTopUserInfoMasterWallet($Wallet, $DeviceId){	   
	    
			$stmt = $this->conn->prepare("UPDATE TopUserInfo set MasterWallet = ? WHERE DeviceId = ?");	
			
						$stmt->bind_param("is",$Wallet, $DeviceId);
						$stmt->execute();
						$num_affected_rows = $stmt->affected_rows;
						$stmt->close();
						if($num_affected_rows > 0)
						{			 
						   return 1;
						 
						}else{
							return 0;
						}
   }   
   private function verifyDeviceID($DeviceId){

			 
			$stmt = $this->conn->prepare("SELECT DeviceId, IStatus, Wallet, WalletCashBack from TopUserInfo WHERE DeviceId = ?");
			$stmt->bind_param("i", $DeviceId);
				
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($DeviceId, $IStatus, $Wallet, $WalletCashBack);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();			
			$res["DeviceId"] = $DeviceId;
            $res["IStatus"] = $IStatus;
			$res["Wallet"] = $Wallet;
			$res["WalletCashBack"] = $WalletCashBack;
            $stmt->close();			
            return $res;

        } else {
            return NULL;
        }
		
		
		
	   
	}
	 public function updateUser($r){
	   /*
		    UserMbNo    Varchar
			DeviceId    INT
			BoardId     TEXT
			Lang        TEXT
			Standards    TEXT
			SubscriptionStaus  TEXT
			ExpireDateTime   TEXT
			UseDateTime     TEXT
			Wallet       INT
			WalletUsed   INT
			IStatus    VarCha
			OTPNo      INT
			MachineType   TEXT
			DeviceDesc    TEXT
			Location    TEXT
			Remark      TEXT
			IMEI_1     TEXT
			IMEI_2     TEXT
			MAC        TEXT
	   */
	   //echo "<pre>"; print_r($r); die;
	     $mobile_no  				= $r->mobile_no;
		 $device_id  				= $r->device_id;
		 $board_id  				= $r->board_id;
		 $lang  					= $r->lang;
		 $standards  				= $r->standards;
		 $subscription_status  		= $r->subscription_status;
		 $expire_date_time  		= $r->expire_date_time;
		 $use_date_time  			= $r->use_date_time;
		 $wallet  					= $r->wallet;
		 $wallet_used  				= $r->wallet_used;
		 $machine_type  			= $r->machine_type;
		 $device_desc  				= $r->device_desc;
		 $location  				= $r->location;
		 $remark  					= $r->remark;
		 $imei_1  					= $r->imei_1;
		 $imei_2  					= $r->imei_2;
		 $mac  						= $r->mac;
		 
		$res = array();
		if ($this->isUserExists($mobile_no) && $this->isUserVerified($mobile_no) == 'Verified') {
			$stmt = $this->conn->prepare("UPDATE TopUserInfo set DeviceId = ?, BoardId = ?, Lang = ?, Standards = ?, SubscriptionStatus = ?, ExpireDateTime = ?, UseDateTime = ?, Wallet = ?, WalletUsed = ?,
			MachineType = ?, DeviceDesc = ?, Location = ?, Remark = ?, IMEI_1 = ?, IMEI_2 = ?, MAC = ?  WHERE UserMbNo = ?");
			$stmt->bind_param("issssssiisssssssi",$device_id, $board_id, $lang, $standards, $subscription_staus, $expire_date_time, $use_date_time, $wallet, $wallet_used, $machine_type, $device_desc, $location, $remark, $imei_1, $imei_2, $mac, $mobile_no);
			$stmt->execute();
			$num_affected_rows = $stmt->affected_rows;
			$stmt->close();
			if($num_affected_rows > 0)
			{			 
			 $res['message'] = "User details successfully";
			 $res['status']  = 1;
			}else{
			 $res['message'] = "Update Error";
			 $res['status']  = 0;
			}
		}else{
			$res['message'] = "User did not exist or the user mobile number UnVerified";
			$res['status']  =  2;
		}
		return $res;
	}  
  public function smsgatewaycenter_com_OTP($length = 4, $chars = '0123456789'){
		
		$chars_length = (strlen($chars) - 1);
		$string = $chars{rand(0, $chars_length)};
		for ($i = 1; $i < $length; $i = strlen($string)){
			$r = $chars{rand(0, $chars_length)};
			if ($r != $string{$i - 1}) $string .=  $r;
		}
		return $string;
	}
	
	public function smsgatewaycenter_com_Send($mobile, $sendmessage, $debug=true){		
		$parameters = 'username=topperds';
		$parameters.= '&password=Admin@18';
		$parameters.= '&sender=TOPPER';		
		$parameters.= '&to='.$mobile;
		$parameters.= '&message='.$sendmessage; //Your EON verification code is 9999999.
		$smsgatewaycenter_com_url = "http://hpsms.dial4sms.com/api/web2sms.php?"; //SMS Gateway Center API URL
		$apiurl =  $smsgatewaycenter_com_url.$parameters;
		//echo $apiurl; die;
		if (! function_exists ( 'curl_version' )) {
    		exit ( "Enable cURL in PHP" );
		}
		//$agent= 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';
		$ch = curl_init();
		//API call
		
		curl_setopt($ch, CURLOPT_URL, $apiurl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);		
		//curl_setopt($ch, CURLOPT_USERAGENT, $agent);		
		$curl_scraped_page = curl_exec($ch);
		
		curl_close($ch);
				
		//$data = curl_exec($ch);
		if ($curl_scraped_page === FALSE) {
    		die("Curl failed: " . curL_error($ch));
			}
		return($curl_scraped_page);
	}
	//internal project
	private function verifyUserMobileNo($UserMobileNo){

			
			$stmt = $this->conn->prepare("SELECT UserMobileNo, SMSText, URLStr, Status from SMSUserInfo WHERE UserMobileNo = ?");
			$stmt->bind_param("i", $UserMobileNo);
				
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($UserMobileNo, $SMSText, $URLStr, $Status);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();			
			$res["UserMobileNo"] 	= $UserMobileNo;
            $res["SMSText"] 		= $SMSText;
			$res["URLStr"] 			= $URLStr;
			$res["Status"] 			= $Status;			
            $stmt->close();
            return $res;

        } else {
            return NULL;
        }
		
			   
	}
	/*private function checkAvailable($UserMobileNo, $SMSToMobileNo){
		$stmt = $this->conn->prepare("SELECT SMSCount from SMSInfo WHERE UserMobileNo = ? AND SMSToMobileNo = ?");
			$stmt->bind_param("ii", $UserMobileNo, $SMSToMobileNo);
				
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($SMSCount);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();			
			$res["SMSCount"] 	= $SMSCount;           		
            $stmt->close();
            return $res;

        } else {
            return NULL;
        }
		
	}*/
	//saveSMSUser
	public function saveSMSUser($r){
		/*
		SMSInfo table
		
		UserMobileNo
		SMSToMobileNo
		SMSCount
		DateTime
		SMSText

		SMSUserInfo table

		UserMobileNo
		SMSText
		URLStr
		Status
		*/
		 $ress = array();
		 $UserMobileNo  				= $r->mobileno1;
		 $SMSToMobileNo  				= $r->mobileno2;
		 $datetime  					= $r->datetime;
		 $res = $this->verifyUserMobileNo($UserMobileNo);
		 if($res != NULL && $res['Status'] =='Active'){
			 //$countRes = $this->checkAvailable($UserMobileNo, $SMSToMobileNo);
			// insert
				  $sendmessage = $res['SMSText'];
				  $sendApi = $this->smsgatewaycenter_com_Send($SMSToMobileNo, $sendmessage);
				  $new_date = date('Y-m-d H:i:s', strtotime($datetime)); 
				  $stmt = $this->conn->prepare("INSERT INTO SMSInfo(UserMobileNo, SMSToMobileNo, DateTime, SMSText) VALUES(?, ?, ?, ?)");
				  $stmt->bind_param("iiss", $UserMobileNo, $SMSToMobileNo, $new_date, $sendmessage);					
				  $result = $stmt->execute();					 
					if (false === $result) {
						die('execute() failed: ' . htmlspecialchars($stmt->error));
					}
					$stmt->close();
				if($result)
					{			 
					 $ress['message'] = "updated successfully";
					 $ress['status']  = 1;
					}else{
					 $ress['message'] = "Update Error";
					 $ress['status']  = 0;
					}
					
								 
		 }else{ // status INACTIVE OR NULL
			  // insert
				  $new_date = date('Y-m-d H:i:s', strtotime($datetime));
				  $stmt = $this->conn->prepare("INSERT INTO SMSInfo(UserMobileNo, SMSToMobileNo, DateTime) VALUES(?, ?, ?)");
				  $stmt->bind_param("iis", $UserMobileNo, $SMSToMobileNo, $new_date);					
				  $result = $stmt->execute();					 
					if (false === $result) {
						die('execute() failed: ' . htmlspecialchars($stmt->error));
					}
					$stmt->close();
				if($result)
					{			 
					 $ress['message'] = "updated successfully";
					 $ress['status']  = 1;
					}else{
					 $ress['message'] = "Update Error";
					 $ress['status']  = 0;
					}
         			 
	 } 
		 return $ress;
	}	
	
	public function getsummary($r){
		
		/*
		SMSInfo table
		
		UserMobileNo
		SMSToMobileNo
		SMSCount
		DateTime
		SMSText
		*/
		$ress = array();
		$userMbNo = $r->userMbNo;
		$sql = "SELECT DATE_FORMAT(DateTime, '%d-%m-%Y') as DateTime, count(SMSCount) as SMSCount  FROM SMSInfo WHERE UserMobileNo = '$userMbNo' group by  DATE_FORMAT(DateTime, '%Y-%m-%d') desc";
		$a_data = array();   
  		$res = $this->conn->query($sql);
		if($res === false) {
  				$this->last_error = 'Wrong SQL: ' . $sql . ' Error: ' . $conn->ErrorMsg();
		} else {
  				$res->data_seek(0);
 				 while($row = $res->fetch_assoc()) {
   					 array_push($a_data, $row);
  					}
		}
		//echo "<pre>"; print_r($a_data); die;
		$ress['getsummary'] = $a_data;
		$ress['status']  = 1;
		return $ress;
		/*$ress = array();
		$stmt = $this->conn->prepare("SELECT DateTime, count(SMSCount) as SMSCount  FROM SMSInfo WHERE 1 group by DateTime ");
      
        if ($stmt->execute()) {            
            $stmt->bind_result($DateTime, $SMSCount);
            $stmt->close();
           
			$ress['DateTime'] = $DateTime;
			$ress['SMSCount']  = $SMSCount;
			return $ress;
        } else {
            return NULL;
        }*/
	}
	
	public function getdatedetails($r){
		$ress = array();
		$dates = $r->dates;
		$userMbNo = $r->userMbNo;
		//echo $dates; die;
		$sql = "SELECT DATE_FORMAT(DateTime, '%r') as Time, SMSToMobileNo  FROM SMSInfo WHERE DATE_FORMAT(DateTime, '%d-%m-%Y') = '$dates' AND UserMobileNo = '$userMbNo' ";
		$a_data = array();	       
  		$res = $this->conn->query($sql);
		if($res === false) {
  				$this->last_error = 'Wrong SQL: ' . $sql . ' Error: ' . $conn->ErrorMsg();
		} else {
  				$res->data_seek(0);
 				 while($row = $res->fetch_assoc()) {
   					 array_push($a_data, $row);
  					}
		}
		//echo "<pre>"; print_r($a_data); die;
		$ress['getdatedetails'] = $a_data;
		$ress['status']  = 1;
		return $ress;
	}
    public function getfindsender($r){
	$ress = array();
	$userMbNo = $r->userMbNo;
	$SMSToMobileNo = $r->SMSToMobileNo;
		$sql = "SELECT DATE_FORMAT(DateTime, '%d-%m-%Y %r') as DateTime, count(SMSCount) as SMSCount  FROM SMSInfo WHERE UserMobileNo = '$userMbNo' AND SMSToMobileNo = '$SMSToMobileNo' group by  DATE_FORMAT(DateTime, '%Y-%m-%d')";
		$a_data = array();   
  		$res = $this->conn->query($sql);
		if($res === false) {
  				$this->last_error = 'Wrong SQL: ' . $sql . ' Error: ' . $conn->ErrorMsg();
		} else {
  				$res->data_seek(0);
 				 while($row = $res->fetch_assoc()) {
   					 array_push($a_data, $row);
  					}
		}
		//echo "<pre>"; print_r($a_data); die;
		$ress['getfindsender'] = $a_data;
		$ress['status']  = 1;
		return $ress;
	
    }
	
	//getcouponsdetails
	public function getcouponsdetails($r){
	$ress = array();
	$DeviceId = $r->DeviceId;	
		$sql = "SELECT DATE_FORMAT(UsedDateTime, '%d-%m-%Y %r') as UsedDateTime, CoupSRNo, CouponAmt  FROM TopCouponInfo WHERE DeviceId = '$DeviceId' AND Type = 'B' ORDER by CoupNo DESC";
		$a_data = array();   
  		$res = $this->conn->query($sql);
		if($res === false) {
  				$this->last_error = 'Wrong SQL: ' . $sql . ' Error: ' . $conn->ErrorMsg();
		} else {
  				$res->data_seek(0);
 				 while($row = $res->fetch_assoc()) {
   					 array_push($a_data, $row);
  					}
		}
		//echo "<pre>"; print_r($a_data); die;
		$ress['getcouponsdetails'] = $a_data;
		$ress['status']  = 1;
		return $ress;
	
    }
	//getrechargedetails
	public function getrechargedetails($r){
	$ress = array();
	$DeviceId = $r->DeviceId;	
		$sql = "SELECT DATE_FORMAT(UsedDateTime, '%d-%m-%Y %r') as UsedDateTime, BatchID, CouponAmt  FROM TopCouponInfo WHERE DeviceId = '$DeviceId' AND Type = 'M' ORDER by CoupNo DESC";
		$a_data = array();   
  		$res = $this->conn->query($sql);
		if($res === false) {
  				$this->last_error = 'Wrong SQL: ' . $sql . ' Error: ' . $conn->ErrorMsg();
		} else {
  				$res->data_seek(0);
 				 while($row = $res->fetch_assoc()) {
   					 array_push($a_data, $row);
  					}
		}
		//echo "<pre>"; print_r($a_data); die;
		$ress['getrechargedetails'] = $a_data;
		$ress['status']  = 1;
		return $ress;
	
    }
	//sendrechargedetails
	public function sendrechargedetails($r){
	$ress = array();
	$DeviceId = $r->DeviceId;	
		$sql = "SELECT DATE_FORMAT(UsedDateTime, '%d-%m-%Y %r') as UsedDateTime, DeviceId, CouponAmt  FROM TopCouponInfo WHERE BatchID = '$DeviceId' AND Type = 'M'  ORDER by CoupNo DESC";
		$a_data = array();   
  		$res = $this->conn->query($sql);
		if($res === false) {
  				$this->last_error = 'Wrong SQL: ' . $sql . ' Error: ' . $conn->ErrorMsg();
		} else {
  				$res->data_seek(0);
 				 while($row = $res->fetch_assoc()) {
   					 array_push($a_data, $row);
  					}
		}
		//echo "<pre>"; print_r($a_data); die;
		$ress['sendrechargedetails'] = $a_data;
		$ress['status']  = 1;
		return $ress;
	
    }
	// get mater wallet amt
	private function getMasterWallet($DeviceId){

			
			$stmt = $this->conn->prepare("SELECT MasterWallet from TopUserInfo WHERE DeviceId = ?");
			$stmt->bind_param("i", $DeviceId);
				
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($MasterWallet);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();			
			$res["MasterWallet"] 	= $MasterWallet;            		
            $stmt->close();
            return $res;

        } else {
            return NULL;
        }
		
			   
	}
	
	private function verifyOtpBydeviceId($DeviceId, $otp){

			/*
			Table : TopCouponInfo
					BatchID
					CoupNo
					CoupSRNo
					CouponAmt
					IStatus
					ExpireDateTime
					UsedDateTime
					DeviceId
					Remark
			*/
			$stmt = $this->conn->prepare("SELECT BatchID, CouponAmt, ExpireDateTime, IStatus from TopCouponInfo WHERE DeviceId = ? AND Remark = ?");
			$stmt->bind_param("ii", $DeviceId, $otp);
				
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($BatchID, $CouponAmt, $ExpireDateTime, $IStatus);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();			
			$res["BatchID"] 	= $BatchID; 
			$res["CouponAmt"] 	= $CouponAmt;
			$res["ExpireDateTime"] 	= $ExpireDateTime;
			$res["IStatus"]    = $IStatus;
            $stmt->close();
            return $res;

        } else {
            return NULL;
        }
		
			   
	}
	// check Device ID 
	private function isDeviceIdExists($DeviceId) {
       
					
		$stmt = $this->conn->prepare("SELECT IMEI_1 from TopUserInfo WHERE DeviceId = ?");
		$stmt->bind_param("i", $DeviceId);		
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;		
        $stmt->close();
        return $num_rows > 0;
    }
	
	private function updateTopCouInfo($DeviceId, $otp){
	   
	        $IStatus = 'Used';
			$stmt = $this->conn->prepare("UPDATE TopCouponInfo set IStatus = ? WHERE DeviceId = ? AND Remark = ?");
			
						$stmt->bind_param("ssi",$IStatus, $DeviceId, $otp);
						$stmt->execute();
						$num_affected_rows = $stmt->affected_rows;
						$stmt->close();
						if($num_affected_rows > 0)
						{			 
						   return 1;
						 
						}else{
							return 0;
						}
   }
//getrecharge

public function getrecharge($r){
	
	$otp = $r->otp;
	$gdid = $r->gdID;
	date_default_timezone_set('Asia/Kolkata');	
	$resss = array();
	if($gdid){
		
		$arr = $this->verifyOtpBydeviceId($gdid, $otp);
		//echo "<pre>"; print_r($arr); die;
		$date1 = date("Y-m-d H:i:s");;
		$date2 = $arr['ExpireDateTime'];
		 
		//Convert them to timestamps.
		$date1Timestamp = strtotime($date1);
		$date2Timestamp = strtotime($date2);
		if($date1Timestamp < $date2Timestamp && $arr['IStatus'] == 'New' ){
			$walletArr = $this->verifyDeviceID($gdid);
			if($walletArr['Wallet'] != ''){
				$wallet = $arr['CouponAmt'] + $walletArr['Wallet'];
			}else{
				$wallet = $arr['CouponAmt'];
			}
			$DeviceId = $gdid;
			$this->updateTopUserInfo($wallet, $DeviceId);
			$this->updateTopCouInfo($DeviceId, $otp);
			$sDeviceId = $arr['BatchID'];
			$masterWalletArr = $this->getMasterWallet($sDeviceId);
			if($masterWalletArr['MasterWallet'] != ''){
				$Mwallet =  $masterWalletArr['MasterWallet'] - $arr['CouponAmt'];
			}else{
				$Mwallet = 0;
			}
			
			$re = $this->updateTopUserInfoMasterWallet($Mwallet, $sDeviceId);
			if($re){
				$resss['message'] = "Updated successfully";
				$resss['recived_from']  = $sDeviceId;
				$resss['amount']  = $arr['CouponAmt'];
				$resss['available_balance']  = $wallet;
				$resss['status']  = 1;
			}else{
				$resss['message'] = "Update error";
				$resss['status']  = 0;
			}
			
		}else{
			//Message  -  "Incorrect Device 
				 $resss['message'] = "Time Expired";
				 $resss['status']  = 0;
		}	
		return $resss;
	}	
	
}	

private function genarateCoupNo(){
		$stmt = $this->conn->prepare("SELECT CoupNo from TopCouponInfo WHERE Type = 'M' ORDER BY id DESC");				
        $stmt->execute();
        $stmt->bind_result($CoupNo);
        $stmt->fetch();
        $stmt->close();
        return $CoupNo;
}	
//sendrecharge

public function sendrecharge($r){
	
	$sdid = $r->sdID;
	$gdid = $r->gdID;
	$amt  = $r->amt;
	$ress = array();
	date_default_timezone_set('Asia/Kolkata');	
	if($sdid != null){
		
		$WalletArr = $this->getMasterWallet($sdid);
		if($WalletArr['MasterWallet'] != NULL && $WalletArr['MasterWallet'] >= $amt){
			 //echo $this->isDeviceIdExists($gdid); die;
			if($this->isDeviceIdExists($gdid) != ''){
				
				/*
				    BatchID      =   SR-DId
                    CoupNo      =   (Auto Sequential Number to be generated for ( Length(BatchId)=10 ) and add suffix "A"  eg : "A1"
                    CoupSRNo   =    add suffix "E"+CoupNo      eg : "EA1" 
                    CouponAmt =   Amount entered by  SR-DId
                    IStatus  = "New"
                    UsedDateTime =  Date time of Request from  SR-DId
                    ExpireDateTime =   UsedDateTime  + 5 minutes 
                    DeviceId   =   GR-DId
                    Remark    =  Genereate OTP No and store here.
					Table : TopCouponInfo
					BatchID
					CoupNo
					CoupSRNo
					CouponAmt
					IStatus
					ExpireDateTime
					UsedDateTime
					DeviceId
					Remark
					Type
					*/
					//echo $this->genarateCoupNo(); die;		
				  if($this->genarateCoupNo() != ''){
					  list($alpha,$numeric) = sscanf($this->genarateCoupNo(), "%[A-Z]%d");
						//echo $alpha."<br/>";
						//echo $numeric;
					  $CoupNo = $numeric + 1;
					  $CoupNo = $alpha.$CoupNo;
				  }else{
					  $CoupNo = 'A1';
				  }					  
				  //echo $CoupNo; die;
				  $CoupSRNo = 'E'.$CoupNo;
				  $IStatus  = 'New';				  
				  $datetime = date("Y-m-d H:i:s");
				  //echo $datetime; die;
				  $formatDate = strtotime($datetime) + (60*5);
				  $ExpireDateTime = date('Y-m-d H:i:s', $formatDate); 
				  //echo $ExpireDateTime; die;
				  $Remark = $this->smsgatewaycenter_com_OTP();
				  $Type = 'M';
				  //echo $Remark; die;
				  $stmt = $this->conn->prepare("INSERT INTO TopCouponInfo(BatchID, CoupNo, CoupSRNo, CouponAmt, IStatus, ExpireDateTime, UsedDateTime, DeviceId, Remark, Type) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
				  $stmt->bind_param("issssssiis", $sdid, $CoupNo, $CoupSRNo, $amt, $IStatus, $ExpireDateTime, $datetime, $gdid, $Remark, $Type);					
				  $result = $stmt->execute();					 
					if (false === $result) {
						die('execute() failed: ' . htmlspecialchars($stmt->error));
					}
					$stmt->close();
				if($result)
					{			 
					 $ress['message'] = "inserted successfully";
					 $ress['OTP']     = $Remark;
					 $ress['status']  = 1;
					}else{
					 $ress['message'] = "Update Error";
					 $ress['status']  = 0;
					}
				
				
			}else{
				 //Message  -  "Incorrect Device 
				 $ress['message'] = "Incorrect Device";
				 $ress['status']  = 0;
			}				
			
		}else{
			// message to  Message  -  "Insufficient Balance"
			$ress['message'] = "Insufficient Balance";
			$ress['status']  = 0;
		}
		return $ress;
	}
}

//activestandards
public function activestandards($r){
	/* table : Standard

		BoardId
		Lang
		Std
		Fees
		Discount
		StartDate
		IStatus
		BroadCastMsg
		Remark
		StdPWD
		*/
		//echo "<pre>"; print_r($r); die;
		$BoardId = $r->BoardId;
		$Lang    = $r->Lang;
		
	$ress = array();
	
		$sql = "SELECT BoardId, Lang, Std, IStatus, StdPWD  FROM Standard WHERE IStatus = 'Active' AND BoardId = '$BoardId' AND Lang = '$Lang'";
		//echo $sql; die;
		$a_data = array();   
  		$res = $this->conn->query($sql);
		if($res === false) {
  				$this->last_error = 'Wrong SQL: ' . $sql . ' Error: ' . $conn->ErrorMsg();
		} else {
  				$res->data_seek(0);
 				 while($row = $res->fetch_assoc()) {
   					 array_push($a_data, $row);
  					}
		}
		//echo "<pre>"; print_r($a_data); die;
		$ress['activestandards'] = $a_data;
		$ress['status']  = 1;
		return $ress;
	
    }
//standarddetails
public function standarddetails($r){

			$BoardId = $r->BoardId;
			$Lang    = $r->Lang;
			$Std  	 = $r->Std;
			$IStatus = 'Active';
			$res = array();
			
			$stmt = $this->conn->prepare("SELECT Fees, Monthly, BroadCastMsg, StdPWD from Standard WHERE BoardId = ? AND Lang = ? AND Std = ? AND IStatus = ?");
			$stmt->bind_param("iiis", $BoardId, $Lang, $Std, $IStatus);
				
        if ($stmt->execute()) {
            
            $stmt->bind_result($Fees, $Monthly, $BroadCastMsg, $StdPWD);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();			
			$res["SubscriptionFees"] = $Fees;
			$res["monthlySubscriptionFees"] = $Monthly;
            $res["Message"] = $BroadCastMsg;
			$res["StdPWD"] = $StdPWD;
            $stmt->close();            

        } 
		
		$stmt = $this->conn->prepare("SELECT EndDateTime from AppInfo WHERE BoardId = ? AND Lang = ? AND IStatus = ?");
			$stmt->bind_param("iis", $BoardId, $Lang, $IStatus);
				
        if ($stmt->execute()) {            
            $stmt->bind_result($EndDateTime);            
            $stmt->fetch();			
			$res["SubscriptionUpto"] = $EndDateTime;            						
            $stmt->close();            

        } 
		
		return $res;
	   
	}
//subscription
public function subscription($r){
				//echo $this->UpdateTopCouponInfo($r->DeviceId='4934688893', 150, 123);
				//die;
			/*
			Table : Subscription

			SubscriptionID
			DeviceId
			DateofSub
			BoardId
			Lang
			Std
			Fees
			EndDateTime
						*/
			//echo $Std  	 = $r->Std; die;
			date_default_timezone_set('Asia/Kolkata');	
			$DeviceId = $r->DeviceId;
			$BoardId = $r->BoardId;
			$Lang    = $r->Lang;
			$Std  	 = $r->Std;
			$discount = $r->discount;
			$promoCode = $r->promoCode;
			$EndDateTime = $r->EndDateTime;
			$Fee		 = $r->Fee;
			$res = array();
			$stdArr = $this->standarddetails($r);
			$WalletArr = $this->verifyDeviceID($DeviceId);
			//echo "stdArr<pre>"; print_r($stdArr); die;
			//echo "WalletArr<pre>"; print_r($WalletArr); die;
			/*if($discount != 0){
				$checkValue = $WalletArr['Wallet'] + $discount;
			}else{
				$checkValue = $WalletArr['Wallet'];
            } 				
			if( $Fee <= $checkValue){ */
//$Fee = $stdArr['SubscriptionFees'];
				$DateofSub = date("Y-m-d H:i:s");
				//$EndDateTime = $stdArr['SubscriptionUpto'];
				$stmt = $this->conn->prepare("INSERT INTO Subscription(DeviceId, DateofSub, BoardId, Lang, Std, Fees, EndDateTime, Discount, PromoName) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)");
				$stmt->bind_param("issssisis", $DeviceId, $DateofSub, $BoardId, $Lang, $Std, $Fee, $EndDateTime, $discount, $promoCode);					
				$result = $stmt->execute();
				$insert_id = $stmt->insert_id;					  
					if (false === $result) {
						die('execute() failed: ' . htmlspecialchars($stmt->error));
					}
					$stmt->close();
				if($result)
					{
					 // update user Wallet;
					 $walletCB = 0;
					 $wallet = 0;
					 $WN = 0;
					 if($this->checkIsCashback($promoCode) != 'C'){
					 
							 if($discount != 0){
								  if($WalletArr['WalletCashBack'] >0 && $Fee > $WalletArr['WalletCashBack'] ){
									$WN =  ($Fee - $discount);
									if($WN < $WalletArr['WalletCashBack'] ){
										$WN =	$WalletArr['WalletCashBack'] - $WN;
										$walletCB = $WN;
										$wallet = $WalletArr['Wallet'];
									}else{
										$WN =  $WN - $WalletArr['WalletCashBack'];
										$wallet = $WalletArr['Wallet'] - $WN;
									}									
									
								  }else if($WalletArr['WalletCashBack'] >0 && $Fee < $WalletArr['WalletCashBack']){
									 $walletCB = $WalletArr['WalletCashBack'] - ($Fee - $discount);
								  }else{		
									$wallet = $WalletArr['Wallet'] - ($Fee - $discount); 
								  
									}
							
							 }else{
								 if($WalletArr['WalletCashBack'] >0 && $Fee > $WalletArr['WalletCashBack'] ){
									$WN =  $Fee - $WalletArr['WalletCashBack'];
									$wallet = $WalletArr['Wallet'] - $WN;
								  }else if($WalletArr['WalletCashBack'] >0 && $Fee < $WalletArr['WalletCashBack']){
									 $walletCB = $WalletArr['WalletCashBack'] - $Fee;
								  }else{		
									$wallet = $WalletArr['Wallet'] - $Fee; 
								  
								}
							 } 
						$this->updateTopUserInfoBySub($wallet, $walletCB, $DeviceId);
					 }else{				 
							if($WalletArr['WalletCashBack'] >0 && $Fee > $WalletArr['WalletCashBack'] ){
								$WN =  $Fee - $WalletArr['WalletCashBack'];
								$wallet = $WalletArr['Wallet'] - $WN;
							  }else if($WalletArr['WalletCashBack'] >0 && $Fee < $WalletArr['WalletCashBack']){
								 $walletCB = $WalletArr['WalletCashBack'] - $Fee;
							  }else{		
								$wallet = $WalletArr['Wallet'] - $Fee; 
							  
							}
							$this->UpdateTopCouponInfo($r, $walletCB + $discount, $insert_id);						
							//$wallet = $this->updateWalletCashback($DeviceId, $discount, $Fee);
							$this->updateTopUserInfoBySub($wallet, $walletCB + $discount, $DeviceId);
					}
					
					 $res['message'] = "Subscription successfully";					 
					 $res['status']  = 1;
					 $res['subscriptionUpto'] = $EndDateTime;
					 $res['remainingWalletAmount'] = $wallet + $walletCB;
					 $res['subscriptionStandard'] = $Std;
					 $res['StdPWD'] = $stdArr['StdPWD'];
					}else{
					 $res['message'] = "Update Error";
					 $res['status']  = 0;
					}
				
			/*}else{
				$res['message'] = 'Insufficient Wallet Amount...';
				$res['status']  = 0; 
            }*/				
		return $res;	
}
//subscriptionhistory

public function subscriptionhistory($r){
	$ress = array();
	$DeviceId = $r->DeviceId;	
		$sql = "SELECT DATE_FORMAT(DateofSub, '%d-%m-%Y %r') as DateofSub, Std, Fees  FROM Subscription WHERE DeviceId = '$DeviceId' ORDER by SubscriptionID DESC";
		$a_data = array();   
  		$res = $this->conn->query($sql);
		if($res === false) {
  				$this->last_error = 'Wrong SQL: ' . $sql . ' Error: ' . $conn->ErrorMsg();
		} else {
  				$res->data_seek(0);
 				 while($row = $res->fetch_assoc()) {
   					 array_push($a_data, $row);
  					}
		}
		//echo "<pre>"; print_r($a_data); die;
		$ress['subscriptionhistory'] = $a_data;
		$ress['status']  = 1;
		return $ress;
	
    }
//getstate

public function getstate(){
		$ress = array();	
		$sql = "SELECT State  FROM DealerList WHERE 1 GROUP BY State ORDER by DealerID DESC";
		$a_data = array();   
  		$res = $this->conn->query($sql);
		if($res === false) {
  				$this->last_error = 'Wrong SQL: ' . $sql . ' Error: ' . $conn->ErrorMsg();
		} else {
  				$res->data_seek(0);
 				 while($row = $res->fetch_assoc()) {
   					 array_push($a_data, $row);
  					}
		}
		//echo "<pre>"; print_r($a_data); die;
		$ress['states'] = $a_data;
		$ress['status']  = 1;
		return $ress;
	
    }
	//getcity

public function getcity($r){
	$ress = array();
	    $State = $r->State;
		$sql = "SELECT City FROM DealerList WHERE State = '$State' GROUP BY City ORDER by DealerID DESC";
		$a_data = array();   
  		$res = $this->conn->query($sql);
		if($res === false) {
  				$this->last_error = 'Wrong SQL: ' . $sql . ' Error: ' . $conn->ErrorMsg();
		} else {
  				$res->data_seek(0);
 				 while($row = $res->fetch_assoc()) {
   					 array_push($a_data, $row);
  					}
		}
		//echo "<pre>"; print_r($a_data); die;
		$ress['city'] = $a_data;
		$ress['status']  = 1;
		return $ress;
	
    }
	//getarea
	public function getarea($r){
	$ress = array();
	    $City = $r->City;
		$sql = "SELECT Area FROM DealerList WHERE City = '$City' GROUP BY Area ORDER by DealerID DESC";
		$a_data = array();   
  		$res = $this->conn->query($sql);
		if($res === false) {
  				$this->last_error = 'Wrong SQL: ' . $sql . ' Error: ' . $conn->ErrorMsg();
		} else {
  				$res->data_seek(0);
 				 while($row = $res->fetch_assoc()) {
   					 array_push($a_data, $row);
  					}
		}
		//echo "<pre>"; print_r($a_data); die;
		$ress['area'] = $a_data;
		$ress['status']  = 1;
		return $ress;
	
    }
	//getdealer
	public function getdealer($r){
	$ress = array();
	    $Area = $r->Area;
		$City = $r->City;
		$sql = "SELECT DealerName, ContactName, Address, PinCode, PhNo, MobileNo FROM DealerList WHERE Area = '$Area' AND City = '$City' ORDER by DealerName";
		$a_data = array();   
  		$res = $this->conn->query($sql);
		if($res === false) {
  				$this->last_error = 'Wrong SQL: ' . $sql . ' Error: ' . $conn->ErrorMsg();
		} else {
  				$res->data_seek(0);
 				 while($row = $res->fetch_assoc()) {
   					 array_push($a_data, $row);
  					}
		}
		//echo "<pre>"; print_r($a_data); die;
		$ress['dealer'] = $a_data;
		$ress['status']  = 1;
		return $ress;
	
    }
	//checkpromocode
	/*
	Table name: PromoCode
CodeId
CodeName
Type
value
Status
	*/
	public function checkpromocode($r){
	 $promocode = $r->promocode;	 
			$IStatus = 'Active';
			$res = array();
			
			$stmt = $this->conn->prepare("SELECT CodeId, CodeName, Type, value  from PromoCode WHERE CodeName = ? AND Status = ?");
			$stmt->bind_param("ss", $promocode, $IStatus);
				
        if ($stmt->execute()) {
            
            $stmt->bind_result($CodeId, $CodeName, $Type, $value);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();
			$res["CodeId"] = $CodeId;
			$res["CodeName"] = $CodeName;
            $res["Type"] = $Type;
			$res["value"] = $value;
            $stmt->close();            

        } 
		$response = array();
		if($res['CodeId'] != null){
			$response['status'] = 1;
			$response['message'] = 'valid';
			$response['data'] = $res;
		}else{
			$response['status'] = 0;
			$response['message'] = 'invalid';
			
		}	
		return $response;
	}

//before payment TopCouponInfo insert row
		public function beforePayment($r){
			      				   
				  $amt 		= $r->amt;
				  $IStatus  = 'InProcess';
				  $gdid 	= $r->DeviceId;
				  $CoupNo   = $r->transactionId;
				  
				  
				  date_default_timezone_set('Asia/Kolkata');
				  //$datetime = date("Y-m-d H:i:s");
				  //echo $datetime; die;
				  //$formatDate = strtotime($datetime) + (60*5);
				  //$ExpireDateTime = date('Y-m-d H:i:s', $formatDate); 
				  //echo $ExpireDateTime; die;
				  if($this->isDeviceIdExists($gdid) != ''){
				  $BatchID = 164603;
				  $Type = 'N';
				  $datetime = date("Y-m-d H:i:s");
				  //echo $Remark; die;
				  $stmt = $this->conn->prepare("INSERT INTO TopCouponInfo(BatchID, CoupNo, CouponAmt, IStatus, DeviceId, Type, ExpireDateTime ) VALUES(?, ?, ?, ?, ?, ?, ?)");
				  $stmt->bind_param("isssiss", $BatchID, $CoupNo, $amt, $IStatus, $gdid,  $Type, $datetime);					
				  $result = $stmt->execute();					 
					if (false === $result) {
						die('execute() failed: ' . htmlspecialchars($stmt->error));
					}
					$stmt->close();
				if($result)
					{			 
					 $ress['message'] = "inserted successfully";					 
					 $ress['status']  = 1;
					}else{
					 $ress['message'] = "Update Error";
					 $ress['status']  = 0;
					}
				  }else{
					  //Message  -  "Incorrect Device 
					$ress['message'] = "Incorrect Device";
					$ress['status']  = 0;
                  }					  
				return $ress;
		}
		
	//After payment success / Reject update TopCouponInfo table
   public function afterpayment($r){
	   $status 			= $r->status;
	   $transactionId 	= $r->transactionId;
	   $deviceId		= $r->DeviceId;
	   $amt             = $r->amt;
	   $BoardId          = $r->BoardId;
	   $Lang             = $r->Lang;
	   $Std              = $r->Std;
	   
	   
	   
	   //print_r($r); die;
	   date_default_timezone_set('Asia/Kolkata');
	   $ress = array();
	   if($this->isDeviceIdExists($deviceId) != ''){
	   if($status == 'Success')
	   {    $datetime = date("Y-m-d H:i:s");
			$IStatus = $status;
			$stmt = $this->conn->prepare("UPDATE TopCouponInfo set IStatus = ?, UsedDateTime = ? WHERE DeviceId = ? AND CoupNo = ?");
			
						$stmt->bind_param("ssii",$IStatus, $datetime, $deviceId, $transactionId);
						$stmt->execute();
						$num_affected_rows = $stmt->affected_rows;
						$stmt->close();
						if($num_affected_rows > 0)
						{			 
						    $ress['message'] = "Updated successfully";					 
							$ress['status']  = 1;
							$ress['paymentstatus'] = 'success';

						}else{
							$ress['message'] = "Update Error";
							$ress['status']  = 0;
						}
	   }else if($status == 'reject'){
		   $IStatus = $status;
		   $Remark  = "Payment rejected";
			$stmt = $this->conn->prepare("UPDATE TopCouponInfo set IStatus = ?, Remark = ? WHERE DeviceId = ? AND CoupNo = ?");
			
						$stmt->bind_param("ssii",$IStatus, $Remark, $deviceId, $transactionId);
						$stmt->execute();
						$num_affected_rows = $stmt->affected_rows;
						$stmt->close();
						if($num_affected_rows > 0)
						{			 
						    $ress['message'] = "Updated successfully";					 
							$ress['status']  = 1;
							$ress['paymentstatus'] = 'reject';
						 
						}else{
							$ress['message'] = "Update Error";
							$ress['status']  = 0;
						}
	   }else{
		   $ress['message'] = " Payment Error";
		   $ress['status']  = 0;
       }
	   }else{
		    //Message  -  "Incorrect Device 
					$ress['message'] = "Incorrect Device";
					$ress['status']  = 0;
	   }
		return $ress;
   }

   public function netSubscription($r){

			/*
			Table : Subscription

			SubscriptionID
			DeviceId
			DateofSub
			BoardId
			Lang
			Std
			Fees
			EndDateTime
						*/
			//echo $Std  	 = $r->Std; die;
			date_default_timezone_set('Asia/Kolkata');	
			$DeviceId = $r->DeviceId;
			$BoardId = $r->BoardId;
			$Lang    = $r->Lang;
			$Std  	 = $r->Std;
			$discount = $r->discount;
			$promoCode = $r->promoCode;
			$EndDateTime = $r->EndDateTime;
			$Fee		 = $r->Fee;
			
			
			$res = array();
			$stdArr = $this->standarddetails($r);
			//$WalletArr = $this->verifyDeviceID($DeviceId);
			//echo "stdArr<pre>"; print_r($stdArr);
			//echo "WalletArr<pre>"; print_r($WalletArr); die;
			
				//$Fee = $stdArr['SubscriptionFees'];
				$DateofSub = date("Y-m-d H:i:s");;
				//$EndDateTime = $stdArr['SubscriptionUpto'];
				$stmt = $this->conn->prepare("INSERT INTO Subscription(DeviceId, DateofSub, BoardId, Lang, Std, Fees, EndDateTime, Discount, PromoName) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)");
				$stmt->bind_param("issssisis", $DeviceId, $DateofSub, $BoardId, $Lang, $Std, $Fee, $EndDateTime, $discount, $promoCode);					
				$result = $stmt->execute();
				$insert_id = $stmt->insert_id;				  
					if (false === $result) {
						die('execute() failed: ' . htmlspecialchars($stmt->error));
					}
					$stmt->close();
				if($result)
					{
					 // update user Wallet;
					 
					 if($discount !=0 && $this->checkIsCashback($promoCode) == 'C'){
						
						$this->UpdateTopCouponInfo($r, $discount, $insert_id);						
						$this->updateTopUserInfoBySubNet($DeviceId, $discount);
					 }else{
						 $this->updateTopUserInfoBySubNet($DeviceId, 0);
					 }	 
					  
					 $res['message'] = "Subscription successfully";					 
					 $res['status']  = 1;
					 $res['subscriptionUpto'] = $EndDateTime;					 
					 $res['subscriptionStandard'] = $Std;
					 $res['StdPWD'] = $stdArr['StdPWD'];
					}else{
					 $res['message'] = "Update Error";
					 $res['status']  = 0;
					}
				
							
		return $res;	
}  

private function updateTopUserInfoBySubNet($DeviceId, $walletCB){
	   
				$SubscriptionStatus = "Subscribe";
				if($walletCB != 0){
					$stmt = $this->conn->prepare("UPDATE TopUserInfo set SubscriptionStatus = ?, WalletCashBack = ? WHERE DeviceId = ?");
					$stmt->bind_param("sis", $SubscriptionStatus, $walletCB, $DeviceId);
				}else{
					$stmt = $this->conn->prepare("UPDATE TopUserInfo set SubscriptionStatus = ? WHERE DeviceId = ?");
					$stmt->bind_param("ss", $SubscriptionStatus, $DeviceId);
				}	
						$stmt->execute();
						$num_affected_rows = $stmt->affected_rows;
						$stmt->close();
						if($num_affected_rows > 0)
						{			 
						   return 1;
						 
						}else{
						   return 0;
						} 
}

//getnewtbankingTranscation details
	public function getnetbankingdetails($r){
	$ress = array();
	$DeviceId = $r->DeviceId;	
		$sql = "SELECT DATE_FORMAT(UsedDateTime, '%d-%m-%Y %r') as DateTime, CoupNo as TranID, CouponAmt  FROM TopCouponInfo WHERE DeviceId = '$DeviceId' AND Type = 'N' AND IStatus = 'Success' ORDER by id DESC";
		$a_data = array();   
  		$res = $this->conn->query($sql);
		if($res === false) {
  				$this->last_error = 'Wrong SQL: ' . $sql . ' Error: ' . $conn->ErrorMsg();
		} else {
  				$res->data_seek(0);
 				 while($row = $res->fetch_assoc()) {
   					 array_push($a_data, $row);
  					}
		}
		//echo "<pre>"; print_r($a_data); die;
		$ress['getnetbanking'] = $a_data;
		$ress['status']  = 1;
		return $ress;
	
    }
   /** End Topper table **/
   
    /**
     * Fetching user api key
     * @param String $user_id user id primary key in user table
     */
    public function getApiKeyById($user_id) {
        $stmt = $this->conn->prepare("SELECT api_key FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            // $api_key = $stmt->get_result()->fetch_assoc();
            // TODO
            $stmt->bind_result($api_key);
            $stmt->close();
            return $api_key;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching user id by api key
     * @param String $api_key user api key
     */
    public function getUserId($api_key) {
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        if ($stmt->execute()) {
            $stmt->bind_result($user_id);
            $stmt->fetch();
            // TODO
            // $user_id = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $user_id;
        } else {
            return NULL;
        }
    }

    /**
     * Validating user api key
     * If the api key is there in db, it is a valid key
     * @param String $api_key user api key
     * @return boolean
     */
    public function isValidApiKey($api_key) {
        $stmt = $this->conn->prepare("SELECT id from users WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    /**
     * Generating random Unique MD5 String for user Api key
     */
    private function generateApiKey() {
        return md5(uniqid(rand(), true));
    }

    /* ------------- `tasks` table method ------------------ */

    /**
     * Creating new task
     * @param String $user_id user id to whom task belongs to
     * @param String $task task text
     */
    public function createTask($user_id, $task) {
        $stmt = $this->conn->prepare("INSERT INTO tasks(task) VALUES(?)");
        $stmt->bind_param("s", $task);
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            // task row created
            // now assign the task to user
            $new_task_id = $this->conn->insert_id;
            $res = $this->createUserTask($user_id, $new_task_id);
            if ($res) {
                // task created successfully
                return $new_task_id;
            } else {
                // task failed to create
                return NULL;
            }
        } else {
            // task failed to create
            return NULL;
        }
    }

    /**
     * Fetching single task
     * @param String $task_id id of the task
     */
    public function getTask($task_id, $user_id) {
        $stmt = $this->conn->prepare("SELECT t.id, t.task, t.status, t.created_at from tasks t, user_tasks ut WHERE t.id = ? AND ut.task_id = t.id AND ut.user_id = ?");
        $stmt->bind_param("ii", $task_id, $user_id);
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($id, $task, $status, $created_at);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();
            $res["id"] = $id;
            $res["task"] = $task;
            $res["status"] = $status;
            $res["created_at"] = $created_at;
            $stmt->close();
            return $res;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching all user tasks
     * @param String $user_id id of the user
     */
    public function getAllUserTasks($user_id) {
        $stmt = $this->conn->prepare("SELECT t.* FROM tasks t, user_tasks ut WHERE t.id = ut.task_id AND ut.user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $tasks = $stmt->get_result();
        $stmt->close();
        return $tasks;
    }

    /**
     * Updating task
     * @param String $task_id id of the task
     * @param String $task task text
     * @param String $status task status
     */
    public function updateTask($user_id, $task_id, $task, $status) {
        $stmt = $this->conn->prepare("UPDATE tasks t, user_tasks ut set t.task = ?, t.status = ? WHERE t.id = ? AND t.id = ut.task_id AND ut.user_id = ?");
        $stmt->bind_param("siii", $task, $status, $task_id, $user_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /**
     * Deleting a task
     * @param String $task_id id of the task to delete
     */
    public function deleteTask($user_id, $task_id) {
        $stmt = $this->conn->prepare("DELETE t FROM tasks t, user_tasks ut WHERE t.id = ? AND ut.task_id = t.id AND ut.user_id = ?");
        $stmt->bind_param("ii", $task_id, $user_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /* ------------- `user_tasks` table method ------------------ */

    /**
     * Function to assign a task to user
     * @param String $user_id id of the user
     * @param String $task_id id of the task
     */
    public function createUserTask($user_id, $task_id) {
        $stmt = $this->conn->prepare("INSERT INTO user_tasks(user_id, task_id) values(?, ?)");
        $stmt->bind_param("ii", $user_id, $task_id);
        $result = $stmt->execute();

        if (false === $result) {
            die('execute() failed: ' . htmlspecialchars($stmt->error));
        }
        $stmt->close();
        return $result;
    }

}

?>
