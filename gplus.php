<?php

	include("feed_generator/FeedWriter.php"); // include the feed generator feedwriter file
	require_once( './config.php' );

	$lang = "de";
	$count = 20;
	$userid = $googleplus_user; //?

	$apiurl = "https://www.googleapis.com/plus/v1/people/".$userid."/activities/public/?key=".$gapikey."&maxResults=".$count."&pageToken=";

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

  if ($response['kind']=="plus#activityFeed") {
	$data = $response['items'];

$feed = new FeedWriter(RSS2);

$feed->setTitle($user_name.'s g+ Activity'); // set your title
$feed->setLink('http://stream.wirres.net/proxy/gplus.php'); // set the url to the feed page you're generating

$feed->setChannelElement('updated', date(DATE_ATOM , time()));
$feed->setChannelElement('author', $user_name); // set the author name

// iterate through the facebook response to add items to the feed
if ( is_array( $data ) ) {
foreach($data as $entry){

			$post_date_gmt = strtotime( $entry['published'] );
			$post_date_gmt = gmdate( 'Y-m-d H:i:s', $post_date_gmt );
			$post_date     = $post_date_gmt;
			
			$entrylink = htmlentities($entry["url"]);
			
			$post_content 		= ($entry['object']['content']);
			$title 				= $entry['title'];
//			echo $post_content;
			$extracted_title = (preg_match( "/<b>(.*?)<\/b>/",$post_content, $matches));
		    $post_content = (preg_replace( "/<b>(.*?)<\/b>/", "",$post_content));
		    $post_content = (preg_replace( "/\A<br \/><br \/>/", "",$post_content));
			$extracted_title = $matches[1];
			if ($extracted_title=="") {$extracted_title = $title;}
//			print_r($matches);
//			$extracted_title = str_replace( $url['url'], '<a href="'.$url['expanded_url'].'">'.$url['display_url'].'</a>', $post_content );

			
			$post_content = ( html_entity_decode( trim( $post_content ) ) );
		    $post_content = preg_replace( "/\s((http|ftp)+(s)?:\/\/[^<>\s]+)/i", " <a href=\"\\0\" target=\"_blank\">\\0</a>",$post_content);

//		    $post_content = preg_replace('/[@]+([A-Za-z0-9-_]+)/', '<a href="http://twitter.com/\\1" target="_blank">\\0</a>', $post_content );  
//		    $post_content = preg_replace('/[#]+([A-Za-z0-9-_]+)/', '<a href="http://twitter.com/search?q=%23\\1" target="_blank">\\0</a>', $post_content );  

			//embedcode konstruieren
			//content & bild?
			if ($embedcode) {
//			print_r($entry['object']['attachments'][0]);
//attachments
			if ($entry['object']['attachments'][0]['objectType']=="photo") {
				$post_content = '
				<div class="gimage gplus"><a href="'.$entry['object']['attachments'][0]['url'].'">
				<img src="'.$entry['object']['attachments'][0]['image']['url'].'" alt="'.$entry['object']['attachments'][0]['content'].'">
				</a></div>'.
				'<div class="gcontent gplus">'.$post_content.'</div>';
				}
			}

			if (($entry['object']['attachments'][0]['objectType'] == "article") AND ($entry['object']['attachments'][0]['content']!="")) {
//				echo "article ";
				$articleimage = $entry['object']['attachments'][0]['image']['url'];
				$articleimage_html = '<div class="gplusimage"><img src="'.$entry['object']['attachments'][0]['image']['url'].'" alt="" class="gpreview-img attachment articleimage"></div>';
				$post_content .= '<blockquote>
				'.$articleimage_html.'
				<h3 class="garticle attachment"><a href="'.$entry['object']['attachments'][0]['url'].'">'.$entry['object']['attachments'][0]['displayName'].'</a></h3>
				'.$entry['object']['attachments'][0]['content'].'</blockquote>';
  				}
			if ($entry['object']['attachments'][0]['objectType'] == "video") {
				$post_content = '<div class="gimage gplus video"><a href="'.$entry['object']['attachments'][0]['url'].'"><img src="'.$entry['object']['attachments'][0]['image']['url'].'" alt="'.$entry['object']['attachments'][0]['displayName'].'"></a></div>'.'<div class="gcontent gplus">'.$post_content.'</div>';
  				}
			

			$item = $feed->createNewItem();

            $item->setTitle($extracted_title);
            $item->setDate($post_date);
            $item->setLink($entrylink);
            $item->setDescription($post_content);
			$item->addElement('guid', $entrylink, array('isPermaLink'=>'true'));
			$item->addElement('category', $entry['board']);

			//nach bildern suchen und enclosen
			if ($entry['object']['attachments'][0]['image']['url']) {
//  				$item->setEncloser(urlencode($entry['object']['attachments'][0]['image']['url']), 500, $entry['object']['attachments'][0]['image']['type']);
					$item->addElement('gplusimage', $entry['object']['attachments'][0]['image']['url'], '');

  				if ($entry['object']['attachments'][0]['fullImage']['url']) {
//	  				$item->setEncloser(urlencode($entry['object']['attachments'][0]['fullImage']['url']), 500, $entry['object']['attachments'][0]['fullImage']['type']);
					$item->addElement('gplusimage', $entry['object']['attachments'][0]['fullImage']['url'], '');
  				}
			}
            $feed->addItem($item);
        }
// that's it... don't echo anything else, just call this method
$feed->genarateFeed();
  } else {
//    return NULL;
  }
}
