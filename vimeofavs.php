<?php

	include("feed_generator/FeedWriter.php"); // include the feed generator feedwriter file
	require_once( './config.php' );

	$lang = "de";
	$count = 10;
	$user = $vimeo_user;
	$apiurl = "http://vimeo.com/api/v2/".$user."/likes.json?page=1";

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

  if (count($response)) {
	$data = $response;

	$feed = new FeedWriter(RSS2);

	$feed->setTitle($user.'s Vimeo Favourites'); // set your title
	$feed->setLink('http://'.$_SERVER['SERVER_NAME'].$_SERVER["SCRIPT_NAME"]); // set the url to the feed page you're generating

	$feed->setChannelElement('updated', date(DATE_ATOM , time()));
	$feed->setChannelElement('author', $user_name); // set the author name

// iterate through the facebook response to add items to the feed
	if ( is_array( $data ) ) {
	foreach($data as $entry){

			$post_date_gmt = strtotime( $entry['liked_on'] );
			$post_date_gmt = gmdate( 'Y-m-d H:i:s', $post_date_gmt );
			$post_date     = $post_date_gmt;
			
			$entrylink = htmlentities($entry["url"]);
			
//			$post_content 		= ($entry['description']);
			$title 				= $entry['title'];
			
			$author = $entry['user_name'];
			$authorurl = $entry['user_url'];
			$thumbnail = $entry['thumbnail_large'];
			$video_id = $entry['id'];

			$post_content = ( html_entity_decode( trim( $post_content ) ) );
		    $post_content = preg_replace( "/\s((http|ftp)+(s)?:\/\/[^<>\s]+)/i", " <a href=\"\\0\" target=\"_blank\">\\0</a>",$post_content);

			//embedcode konstruieren
			//content & bild?
			if (($embedcode) AND ($video_id)) {
				$post_content = '
				<div class="vimeoembed vimeo">
				<iframe src="http://player.vimeo.com/video/'.$video_id.'?badge=0" width="625" height="352" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>
				</div>';
// 
				$post_content .= '<div class="ytcontent yt">(<a href="'.$entrylink.'">Video</a> von <a href="'.$authorurl.'">'.$author.'</a>)</div>';
			}

			$item = $feed->createNewItem();

            $item->setTitle($title);
            $item->setDate($post_date);
            $item->setLink($entrylink);
            $item->setDescription($post_content);
			$item->addElement('guid', $entrylink, array('isPermaLink'=>'true'));
			$item->addElement('category', $entry['board']);

			//nach bildern suchen und enclosen
			$articleimage = "";
			$articleimage = $thumbnail;
			if (($articleimage != "")) {
//				$post_content .= '<img src="'.$articleimage.'" alt="'.$title.'" class="ytpreview-img attachment articleimage">';
			  	$item->setEncloser($articleimage, "", "");

  				}
            $feed->addItem($item);
        }
// that's it... don't echo anything else, just call this method
$feed->genarateFeed();
  } else {
//    return NULL;
  }
}
