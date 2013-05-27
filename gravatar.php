<?php
	$email = $_REQUEST['email'];

	$email = trim( $email ); // "MyEmailAddress@example.com"
	$email = strtolower( $email ); // "myemailaddress@example.com"
	$email = md5( $email );

	$apiurl = "http://gravatar.com/avatar/".$email."?s=50";
	
    $timeout = 15;
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $apiurl);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
   	curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
	$output = curl_exec($ch);
	curl_close($ch);
	$rawData = trim($output);

//if OK?		
	$response = $rawData;
	header("Content-type: image/jpg");
	echo $response;