<?php

	require 'tmhOAuth/tmhOAuth.php';
	require 'tmhOAuth/tmhUtilities.php';
	include("feed_generator/FeedWriter.php"); // include the feed generator feedwriter file
	require_once( './config.php' );

	$tmhOAuth = new tmhOAuth($twitter_oauth_setting);

	$lang = "de";
	$count = 20;
	$user = $twitter_user;
	$apiurl = "http://api.twitter.com/1.1/statuses/user_timeline.json";
	$embedcode = true;
	
	$tmhOAuth->request(
    'GET',
    $apiurl,
		array(
//			'max_id'				=> '',
			'lang'				=> $lang,
			'count'				=> $count,
			'screen_name'		=> $user,
			'include_rts' 		=> "false",
			'exclude_replies'	=> "false",
			'include_entities' 	=> "true"
		),
	  	true
  	);

//print_r($tmhOAuth);
//echo ':::'.$tmhOAuth->response['response'];

  if ($tmhOAuth->response['code'] == 200) {
	$data = json_decode($tmhOAuth->response['response'],1);
/*
	echo $data[0]['created_at'];
	echo $data[0]['id_str'];
	echo $data[0]['text'];
	echo $data[0]['source'];
	echo $data[0]['entities']['urls'][0]['url'];
	echo $data[0]['entities']['urls'][0]['expanded_url'];
	echo $data[0]['entities']['urls'][0]['display_url'];
	echo $data[0]['source'];
*/
//  	print_r(json_decode($tmhOAuth->response['response'],0));
//		return json_decode($tmhOAuth->response['response'], false);

$feed = new FeedWriter(RSS2);

$feed->setTitle('@'.$user.'s Twitter Timeline'); // set your title
$feed->setLink('http://'.$_SERVER['SERVER_NAME'].$_SERVER["SCRIPT_NAME"]); // set the url to the feed page you're generating

$feed->setChannelElement('updated', date(DATE_ATOM , time()));
$feed->setChannelElement('author', $user_name); // set the author name

// iterate through the facebook response to add items to the feed
if ( is_array( $data ) ) {
foreach($data as $entry){
			$post_date_gmt = strtotime( $entry['created_at'] );
			$post_date_gmt = gmdate( 'Y-m-d H:i:s', $post_date_gmt );
			$post_date     = gmdate( 'd.m.Y H:i', strtotime( $entry['created_at'] ));
			
			$tweetlink = 'http://twitter.com/'.$user.'/status/'.$entry["id_str"];
			
			$post_content = $entry['text'];
//			$post_content = ( html_entity_decode( trim( $post_content ) ) );
			$post_content = ( html_entity_decode( $post_content ) ); // ohne trim?
			//links einsetzen/auflösen
			if ( count( $entry['entities']['urls'] ) ) {
				foreach ( $entry['entities']['urls'] as $url ) {
					$post_content = str_replace( $url['url'], '<a href="'.$url['expanded_url'].'">'.$url['display_url'].'</a>', $post_content );
				}
			}
			$image_url = "";
			$image_html = "";
			if ( count( $entry['entities']['media'] ) ) {
				foreach ( $entry['entities']['media'] as $media ) {
					$post_content = str_replace( $media['url'], '<a href="'.$media['expanded_url'].'">'.$media['display_url'].'</a>', $post_content );
					if ($media['type']=="photo") {
						$image_url = $media['media_url'];
						$image_html = '<div class="twitter-image"><a href="'.$media['expanded_url'].'"><img src="'.$image_url.'" alt=""></a></div>';
					}
				}
			}

//			$post_content = preg_replace('"\b(http://\S+)"', '<a href="$1">$1</a>', $post_content)."!";
//		    $post_content = preg_replace("/(?<!http:\/\/)www\./","http://www.",$post_content);
		    $post_content = preg_replace( "/\s((http|ftp)+(s)?:\/\/[^<>\s]+)/i", " <a href=\"\\0\" target=\"_blank\">\\0</a>",$post_content);
		    $post_content = preg_replace('/[@]+([A-Za-z0-9-_]+)/', '<a href="http://twitter.com/\\1" target="_blank">\\0</a>', $post_content );  

	        // Autolink hashtags (wordpress funktion)
    	    $post_content = preg_replace(
                '/(^|[^0-9A-Z&\/]+)(#|\xef\xbc\x83)([0-9A-Z_]*[A-Z_]+[a-z0-9_\xc0-\xd6\xd8-\xf6\xf8\xff]*)/iu',
                '${1}<a href="http://twitter.com/search?q=%23${3}" title="#${3}">${2}${3}</a>',
                $post_content);


			$post_content_original = $post_content;
			//embedcode konstruieren
			if ($embedcode) {
				$post_content = '<blockquote class="twitter-tweet imported"><p>'.$post_content.'</p>'.$image_html.'&mdash; '.$entry['user']['name'].' (<a href="https://twitter.com/'.$entry['user']['screen_name'].'/">@'.$entry['user']['screen_name'].'</a>) <a href="'.$tweetlink.'">'.$post_date.'</a></blockquote><script async src="//platform.twitter.com/widgets.js" charset="utf-8"></script>';
			}
			
			$tags = array();
			if ( preg_match_all( '/(^|[(\[\s])#(\w+)/', $post_content, $tag ) )
				$tags = $tag[2];

			$in_reply_to_user_id     = !empty( $entry['in_reply_to_user_id_str'] ) ? $entry['in_reply_to_user_id_str'] : '';
			$in_reply_to_screen_name = !empty( $entry['in_reply_to_screen_name'] ) ? $entry['in_reply_to_screen_name'] : '';
			$in_reply_to_status_id   = !empty( $entry['in_reply_to_status_id_str'] ) ? $entry['in_reply_to_status_id_str'] : '';

			$item = $feed->createNewItem();

//            $item->setTitle($post_date);
            $item->setTitle(strip_tags($post_content_original));
            $item->setDate($entry["created_at"]);
            $item->setLink($tweetlink);
            $item->setDescription($post_content);
			$item->addElement('guid', $tweetlink, array('isPermaLink'=>'true'));

			//metagedoens
//  			$item->addElement('in_reply_to_user_id', $in_reply_to_user_id);
//  			$item->addElement('in_reply_to_screen_name', $in_reply_to_screen_name);
//  			$item->addElement('in_reply_to_status_id', $in_reply_to_status_id);

			//nach bildern suchen und enclosen
//  			$item->setEncloser('http://www.attrtest.com', '1283629', 'audio/mpeg');

			//hashtags zu kategorien
				foreach ( $tags as $tag ) {
		  			$item->addElement('category', $tag);
				}

            $feed->addItem($item);
        }
// that's it... don't echo anything else, just call this method
$feed->genarateFeed();
  } else {
//    return NULL;
  }
}
