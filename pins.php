<?php
	require_once( './config.php' );

	$lang = "de";
	$count = 10;
	$user = $pinterest_user;
	$userid = ""; //?
	$apiurl = "http://pinterest.com/".$user."/feed.rss";
	$embedcode = true;
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
//	print_r($response);

  if ($response != "") {
	$data = $response;
	$data = str_replace( '192x', '550x', $data );
	echo $data;
	}
