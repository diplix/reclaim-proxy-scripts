<?php

	include("feed_generator/FeedWriter.php"); // include the feed generator feedwriter file
	require_once( './config.php' );

//dynamisch?
	$lang = "de";
	$count = 100;
	$userid = $instagram_user; //?

	$apiurl = "https://api.instagram.com/v1/users/".$userid."/media/recent/?access_token=".$instagram_accesstoken."&count=".$count;

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
	$response = json_decode($rawData, true);
//	print_r($response);

  if ($response['meta']['code']==200) {
	$data = $response['data'];
//	print_r($data);	

$feed = new FeedWriter(RSS2);

$feed->setTitle($user_name.'s Instagrams'); // set your title
$feed->setLink('http://'.$_SERVER['SERVER_NAME'].$_SERVER["SCRIPT_NAME"]); // set the url to the feed page you're generating

$feed->setChannelElement('updated', date(DATE_ATOM , time()));
$feed->setChannelElement('author', $user_name); // set the author name

// iterate through the facebook response to add items to the feed
if ( is_array( $data ) ) {
foreach($data as $entry){
//	print_r($entry);	

//			$post_date_gmt = strtotime( $entry['created_time'] );
			$post_date_gmt = ( $entry['created_time'] ); // instagram liefer unixtime
			$post_date_gmt = gmdate( 'Y-m-d H:i:s', $post_date_gmt );
			$post_date     = $post_date_gmt;
			
			$entrylink = htmlentities($entry["link"]);
			
			$location = $entry['location'];
			$caption = $entry['caption']['text'];
			$image_url = $entry['images']['standard_resolution']['url'];
			$post_content = '<a href="'.$entrylink.'"><img src="'.$image_url.'" alt="'.$location.'"></a>';
			if ($caption) {
			$title 				= $caption;
			$post_content 		.= '<p><a href="'.$entrylink.'">'.$caption.'</a>'; 
			}
			elseif ($location) {
			$title 				= $location;
			$post_content 		.= '<br /><a href="'.$entrylink.'">'.$location.'</a>';
			}
			else {
			$title 				= 'Instagram Photo';
			$post_content 		.= '<a href="'.$entrylink.'">Instagram Photo</a>';
			}
			$post_content .= '<br />Filter: '.$entry['filter'].'</p>'; 
			
			$item = $feed->createNewItem();
            $item->setTitle($title);
            $item->setDate($post_date);
            $item->setLink($entrylink);
            $item->setDescription($post_content);
			$item->addElement('guid', $entrylink, array('isPermaLink'=>'true'));

            $feed->addItem($item);
        }
// that's it... don't echo anything else, just call this method
$feed->genarateFeed();
  } else {
//    return NULL;
  }
}
