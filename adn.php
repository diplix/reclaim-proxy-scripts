<?php

	require 'tmhOAuth/tmhOAuth.php';
	require 'tmhOAuth/tmhUtilities.php';
	include("feed_generator/FeedWriter.php"); // include the feed generator feedwriter file
	include("../wp-includes/formatting.php"); // 
	require_once( './config.php' );

	$lang = "de";
	$count = 10;
	$userid = $adn_userid; 
	$apiurl = "https://alpha-api.app.net/stream/0/users/".$userid."/posts?count=".$count."";
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

// OK?		
	$response = json_decode($rawData, true);
//	print_r($response);

  if ($response['meta']['code'] == 200) {
	$data = $response['data'];

$feed = new FeedWriter(RSS2);

$feed->setTitle($user_name.'s app.net Timeline'); // set your title
$feed->setLink('http://'.$_SERVER['SERVER_NAME'].$_SERVER["SCRIPT_NAME"]); // set the url to the feed page you're generating

$feed->setChannelElement('updated', date(DATE_ATOM , time()));
$feed->setChannelElement('author', $user_name); // set the author name

// iterate through the facebook response to add items to the feed
if ( is_array( $data ) ) {
foreach($data as $entry){
			$post_date_gmt = strtotime( $entry['created_at'] );
			$post_date_gmt = gmdate( 'Y-m-d H:i:s', $post_date_gmt );
			$post_date     = gmdate( 'd.m.Y H:i', strtotime( $entry['created_at'] ));
			
			$tweetlink = $entry["canonical_url"];
			
			$post_content 		= $entry['text'];
			$post_content_html 	= $entry['html'];
			$post_content = ( html_entity_decode( trim( $post_content ) ) );
			//links einsetzen/auflösen
			if ( count( $entry['entities']['links'] ) ) {
				foreach ( $entry['entities']['links'] as $url ) {
					$post_content = str_replace( $url['text'], '<a href="'.$url['url'].'">'.$url['text'].'</a>', $post_content );
				}
			}
//			$post_content = preg_replace('"\b(http://\S+)"', '<a href="$1">$1</a>', $post_content)."!";
//		    $post_content = preg_replace("/(?<!http:\/\/)www\./","http://www.",$post_content);
		    $post_content = preg_replace( "/\s((http|ftp)+(s)?:\/\/[^<>\s]+)/i", " <a href=\"\\0\" target=\"_blank\">\\0</a>",$post_content);

		    $post_content = preg_replace('/[@]+([A-Za-z0-9-_]+)/', '<a href="http://alpha.app.net/\\1" target="_blank">\\0</a>', $post_content );  
		    $post_content = preg_replace('/[#]+([A-Za-z0-9-_]+)/', '<a href="http://alpha.app.net/hashtags/\\1" target="_blank">\\0</a>', $post_content );  
			$avatar_image_url = $entry['user']['avatar_image']['url'];

			//embedcode konstruieren
			if ($embedcode) {

			$post_content = 
			'<div class="tmb_adn_frame">
				<div class="tmb_adn_post">
					<a href="http://alpha.app.net/'.$entry['user']['username'].'" target="_blank" class="tmb_adn_user">
						<img height="80" width="80" src="'.$avatar_image_url.'" alt="@'.$entry['user']['username'].'" class="tmb_adn_user_image"></a>
						<div class="tmb_adn_post_body">
							<span itemscope="https://app.net/schemas/Post">
								<blockquote>
								'.$post_content.'
								<div class="tmb_adn_meta">
									&mdash; '.$entry['user']['name'].' (@<a href="http://alpha.app.net/'.$entry['user']['username'].'" target="_blank" class="tmb_adn_user_link">'.$entry['user']['username'].'</a>) 
									<a href="'.$tweetlink.'" target="_blank" class="tmb_adn_post_date">'.$post_date.'</a> 
									via <a href="'.$entry['source']['link'].'" target="_blank">'.$entry['source']['name'].'</a>
								</div>
								</blockquote>
							</span>						
						</div>
				</div>
			</div>';

//				$post_content = '<blockquote class="twitter-tweet imported"><p>'.$post_content.'</p>
//				&mdash; '.$entry['user']['name'].' (@'.$entry['user']['username'].') <a href="'.$tweetlink.'">'.$post_date.'</a></blockquote><script async src="//platform.twitter.com/widgets.js" charset="utf-8"></script>';
			}
			
			$tags = array();
			if ( preg_match_all( '/(^|[(\[\s])#(\w+)/', $post_content, $tag ) )
				$tags = $tag[2];

			$reply_to     = !empty( $entry['reply_to'] ) ? $entry['reply_to'] : '';
			$thread_id = !empty( $entry['thread_id'] ) ? $entry['thread_id'] : '';
			$in_reply_to_status_id   = !empty( $entry['in_reply_to_status_id_str'] ) ? $entry['in_reply_to_status_id_str'] : '';

			$item = $feed->createNewItem();

            $item->setTitle($post_date);
            $item->setDate($entry["created_at"]);
            $item->setLink($tweetlink);
            $item->setDescription($post_content);
			$item->addElement('guid', $tweetlink, array('isPermaLink'=>'true'));

			//metagedoens
  			$item->addElement('reply_to', $reply_to);
  			$item->addElement('thread_id', $thread_id);
  			$item->addElement('in_reply_to_status_id', $in_reply_to_status_id);

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
