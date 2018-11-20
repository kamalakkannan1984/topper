<?php include('Crypto.php')?>
<?php
    
	error_reporting(1);
	
	$workingKey='E396C9F03E1F92CC58D3956FF83BF71B';		//Working Key should be provided here.
	$encResponse= $_POST["encResp"]; //"78d20c413706c6c3401b2ffb58507bff86bd353a4f4666f155bd7741b889f8799a8f212dd6cf0de12102c2cd11d05e078e9e71b9f1084238715de4ca3bb45de425612c9b70e2347c4c12336c6f1cc0df1f58aee65a2eda56dbaa51686ee03629a8641aa09c72ffd35c23445f30908c6734d2043ca9fa48da0da8b4e0604e9e76b2f6b0e4ccc3a757aa20ce97c3ce46dabbc1949d304f6d01a3ef49707a4a6858672b574520fe057fd28db73048523ad95ef8cd2e27076a4c722afb5180121b1ad32aa3bb903a844e769760eae497488f3bbd7d17c96d77bb2fd229e9f89623c13d1c0268aa864ce6d2a7ed3e8e3d61846ebe82addca7d48b40baa811e75d8a1a3df7a8a900195d5e9e8a1109ec5aa1af76fc102107a1cd4bf69990391959d1c1335be00be82f57d02c3699e0f2c879ca0bb10689277d0c55bf2766ab82e063001281a26ebefa241c5832d45d5730d3e3d554f020af379d28ea64f206ad34c31c12484b79b60a39b17201e9fb54970ba36518faa8d82dae63d5fad702bbbc53fc832a3c20b9745d901534d87da16f14f28c14c0477137cbaeb352b1ccad289a28d680a23eb6334660f25d51bac484adc3e1b40fa930ff6e9c083023414a415a932feda54d800f24cd57e0b6c4c84ddaea0f383c6ae81b58b8fbee06fa284e920a7501217baefc1a28ba60497713c5f40623e248335671e7f00af18aa1bf07180d9be24bd19ada4a8e4ea8490e532906637d3658f26098af1fc892071568abf3a7f82ddb4a789377c412c88ab6e9f60fc03a569cf98be00f4c5b046db59b27c5ba5a2193243797bf7f4c722c48c46d8a7faf519757d369bb2f1a3acf3bc8455dd4298ef8967bcd550726cd709e2848fbd499375c72fb570dd9db3303c1b3da0c01f31f8de791ce9cfc0bffabec10f6338e44b5e03e0381547e438f43d2fb30fc24b83e7fac0cd652803375375301bd3cb6f17c949be56dee145abad24b0fca12a9c53a7803589a7ff59b3d5c135dcd9ea93d5662db9dab85d79819576c7bd8961e7be392385d89c12e44e3c31f49adec2ce6f18e492efe158310028d761b50984e69aa9cb9c8bc043c2005888dde9cd3147c61ea96566762edb8388164e67bf5fb296a3b54cf1ddd92eb59ac90d7e98aa6fd51d6610f91e21ad010cee133ca1827f474e9f07444540dcdcec8a17c67d7eabd51ecf3d620c1dbfc7ac68de3d829d8";     //$_POST["encResp"];			//This is the response sent by the CCAvenue Server
	$rcvdString=decrypt($encResponse,$workingKey);		//Crypto Decryption used as per the specified working key.
	
	$order_status="";
	$decryptValues=explode('&', $rcvdString);
	$dataSize=sizeof($decryptValues);
	echo "<center>";

	for($i = 0; $i < $dataSize; $i++) 
	{
		$information=explode('=',$decryptValues[$i]);
		if($i==3)	$order_status=$information[1];
	}

	if($order_status==="Success")
	{
		echo "<br>Thank you for shopping with us. Your credit card has been charged and your transaction is successful. We will be shipping your order to you soon.";
		
	}
	else if($order_status==="Aborted")
	{
		echo "<br>Thank you for shopping with us.We will keep you posted regarding the status of your order through e-mail";
	
	}
	else if($order_status==="Failure")
	{
		echo "<br>Thank you for shopping with us.However,the transaction has been declined.";
	}
	else
	{
		echo "<br>Security Error. Illegal access detected";
	
	}
     
	echo "<br><br>";

	echo "<table cellspacing=4 cellpadding=4>";
	for($i = 0; $i < $dataSize; $i++) 
	{
		$information=explode('=',$decryptValues[$i]);
	    	echo '<tr><td>'.$information[0].'</td><td>'.$information[1].'</td></tr>';
	}

	echo "</table><br>";
	echo "</center>";
?>
