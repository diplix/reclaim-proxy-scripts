<?php
	require_once( './config.php' );

	$lang = "de";
	$count = 50;
	$userid = $flickr_user; //
	$user = $flickr_user_name; //
	$apiurl = "http://api.flickr.com/services/rest/?method=flickr.people.getPublicPhotos"
	."&user_id=".$userid
	."&per_page=".$count."&page=1&format=feed-atom_10"
	."&api_key=".$flickr_api_key;
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
	$data = str_replace( '_m.jpg&quot;', '_z.jpg&quot;', $data );
	$data = str_replace( '&lt;p&gt;&lt;a href=&quot;http://www.flickr.com/people/'.$userid.'/&quot;&gt;'.$user.'&lt;/a&gt; posted a photo:&lt;/p&gt;', '', $data );
		
	echo $data;
	}
