<?php

	include("feed_generator/FeedWriter.php"); // include the feed generator feedwriter file
	require_once( './config.php' );

	$lang = "de";
	$count = 10;
	$user = $youtube_user;
	$apiurl = "https://gdata.youtube.com/feeds/api/users/".$user."/uploads?alt=json&prettyprint=true&orderby=published&racy=include&v=2&client=ytapi-youtube-profile";

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

  if ($response['version']=="1.0") {
	$data = $response['feed']['entry'];

	$feed = new FeedWriter(RSS2);

	$feed->setTitle($user.'s Youtube Videos'); // set your title
	$feed->setLink('http://'.$_SERVER['SERVER_NAME'].$_SERVER["SCRIPT_NAME"]); // set the url to the feed page you're generating

	$feed->setChannelElement('updated', date(DATE_ATOM , time()));
	$feed->setChannelElement('author', $user_name); // set the author name

// iterate through the facebook response to add items to the feed
	if ( is_array( $data ) ) {
	foreach($data as $entry){

			$post_date_gmt = strtotime( $entry['published']['$t'] );
			$post_date_gmt = gmdate( 'Y-m-d H:i:s', $post_date_gmt );
			$post_date     = $post_date_gmt;
			
			$entrylink = htmlentities($entry["link"][0]['href']);
			
//			$post_content 		= ($entry['content']['$t']);
			$title 				= $entry['title']['$t'];
			
			$author = $entry['author'][0]['name']['$t'];
			$authorurl = $entry['author'][0]['uri']['$t'];
			$thumbnail = $entry['media$thumbnail'][0]['url'];
			$video_id = 0;
			if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $entrylink, $match)) {
			    $video_id = $match[1];
//			    $post_content .= " -  id: ".$video_id;
			}

			$post_content = ( html_entity_decode( trim( $post_content ) ) );
		    $post_content = preg_replace( "/\s((http|ftp)+(s)?:\/\/[^<>\s]+)/i", " <a href=\"\\0\" target=\"_blank\">\\0</a>",$post_content);

			//embedcode konstruieren
			//content & bild?
			if (($embedcode) AND ($video_id)) {
				$post_content = '
				<div class="ytembed yt">
				<iframe width="625" height="352" src="http://www.youtube.com/embed/'.$video_id.'" frameborder="0" allowfullscreen></iframe>
				</div>';
// 
//				'<div class="ytcontent yt">(Video von <a href="'.$authorurl.'">'.$author.'</a>)</div>';
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
			$articleimage = $entry['media$group']['media$thumbnail'][2]['url'];
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
