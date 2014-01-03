<?php

require('facebook-php-sdk/src/facebook.php'); 	// require your facebook php sdk
include("feed_generator/FeedWriter.php"); 		// include the feed generator feedwriter file
require_once( './config.php' );


$user_id = $fb_user_id;
$limit = 40;

	$apiurl= 'https://graph.facebook.com/'.$user_id.'/feed/?limit='.$limit.'&locale=de&access_token='.$oauth_token;
    $timeout = 15;

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $apiurl);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
   	curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
	$output = curl_exec($ch);
	curl_close($ch);
	$rawData = trim($output);

// OK? 
	$response = json_decode($rawData, true);
//print_r($response);

// create the feedwriter object (we're using ATOM but there're other options like rss, etc)
$feed = new FeedWriter(ATOM);

$feed->setTitle($user_name.'s Facebook Stream'); 				// set your title
$feed->setLink('http://'.$_SERVER['SERVER_NAME'].$_SERVER["SCRIPT_NAME"]); // set the url to the feed page you're generating

$feed->setChannelElement('updated', date(DATE_ATOM , time()));
$feed->setChannelElement('author', array('name'=>$user_name)); // set the author name

// iterate through the facebook response to add items to the feed
foreach($response['data'] as $entry){
        if(isset($entry["id"])){
			if (
			    ($entry['application']['name']      != "Twitter") 				// no tweets
			AND ($entry['application']['namespace'] != "rssgraffiti") 			// no blog stuff
			AND ($entry['application']['namespace'] != "ifthisthenthat") 		// no instagrams and ifttt
			AND ($entry['status_type'] != "approved_friend") 					// no new friend anouncements
			AND ( ($entry['privacy']['value'] == "") OR ($entry['privacy']['value'] == "EVERYONE") )	// privacy OK? is it public?
			AND ($entry['from']['name'] == $user_name)							// only own stuff $user_namestuff
			) {


            $item = $feed->createNewItem();

			// is there a link? if not, link fb-post
            if (isset($entry["link"])) {
				$link = htmlentities($entry["link"]);
                $item->setLink($link);
            } else {
				$id = substr(strstr($entry['id'], '_'),1);
				$link = "https://www.facebook.com/$user_id/posts/".$id;
                $item->setLink($link);
            }
			$id = substr($entry['id'], 10);
			$facebooklink = "https://www.facebook.com/$user_id/posts/".$id;
			$item->addElement('facebooklink', $facebooklink, '');

			$image = $entry['picture'];
			$parse_image_url = parse_url($image);

			if ( 
				($image!='')
				AND ($parse_image_url['host'] != "s-platform.ak.fbcdn.net") 
				) {
			// _s. und _q. -> _n. (normalgrosses bild)
				$image = str_replace( '_s.', '_n.', $image );
				$image = str_replace( '_q.', '_n.', $image );
//				$image_html = '<div class="fbimage"><img src="'.$image.'" alt="'.$entry["caption"].'"></div>';
				$image_html = '<div class="fbimage"><img src="'.$image.'"></div>';
			} else {
				$image_html = '';
			}
			
			// use date as title
//            $item->setTitle(gmdate( 'd.m.Y H:i', strtotime($entry["created_time"])) );
			

            $item->setDate($entry["created_time"]);
// defaults
				$description = "";
// message?
				if (isset($entry["message"])) {
//					$description = '<blockquote class="fbmessage">'.$entry["message"].'</blockquote>';
					$description = '<div class="fbmessage"><a href="'.$link.'">'.$entry["message"].'</a></div>';
				}
				$description .= $image_html
				. '<div class="clearfix fbname"><a href="'.$link.'">'.$entry["name"].'</a></div>';
				if (isset($entry["caption"])) {
					$description .=	'<blockquote class="clearfix fbcaption">'.$entry["caption"].'</blockquote>';
				}
				$title = $entry['message'];
// description?
				if (isset($entry["description"])) {
					$description .= '<blockquote>'.$entry["description"].'</blockquote>';
				}

// story? rebuid description and title
				if ($entry["story"] != "") {
	            	$description = '<a href="'.$link.'">'.$entry["story"].'</a>';
					if (isset($entry["message"])) {
						$description .= '<blockquote>'.$entry["message"].'</blockquote>';
					}

					$description .= $image_html
					. '<div class="clearfix fbname"><a href="'.$link.'">'.$entry["name"].'</a></div>';
					if (isset($entry['properties'])) {
//						$description .= '<a href="'.$entry['properties'][0]['href'].'">'.$entry['properties'][0]['name'].' '.$entry['properties'][0]['text'].'</a>:<br />';
						$description .= '<a href="'.$entry['properties'][0]['href'].'">'.$entry['properties'][0]['text'].'</a><br />';
					}
					if (isset($entry["caption"])) {
						$description .=	'<div class="clearfix fbcaption">'.$entry["caption"].'</div>';
					}
					if (isset($entry["description"])) {
						$description .= '<blockquote class="fbdescription">'.$entry["description"].'</blockquote>';
					}
					$title = $entry['story'];

	        	}
				
				if ($title=='') { $title='Aktivität auf Facebook';}
				
            $item->setTitle(text_add_more(text_excerpt($title, 50, 0, 1, 0),' …', ''));
            
            if ($entry['application']['name']=='Likes') {
// likes?
            	if ($entry['name']!="") { 
            		$entry_name = $entry['name']; 
            	} else {
            		$entry_name = "mehrere Dinge.";  // manchmal liefert fb nix (nochmal id checken?)
            	}
				$description = $user_name . ' mochte <a href="'.$link.'">'.$entry_name.'</a>';
				$description .= $image_html
				. '<div class="clearfix fbcaption">'.$entry["caption"].'</div>';
				if (isset($entry["description"])) {
					$description .= '<blockquote>'.$entry["description"].'</blockquote>';
				}
				$item->addElement('category', "likes");
				
            }
            elseif ($entry["type"]=='status') {
// no story?
            if ($entry["story"] == "") {
				$description = "";
				if (isset($entry["message"])) {
//					$description = '<blockquote>'.$entry["message"].'</blockquote>';
//					$description = '<blockquote><a href="'.$link.'">'.$entry["message"].'</a></blockquote>';
					$description = '<a href="'.$link.'">'.$entry["message"].'</a>';
				}
				$description .= $image_html
				. '<div class="clearfix fbname"><a href="'.$link.'">'.$entry["name"].'</a></div>'
				. '<div class="clearfix fbcaption">'.$entry["caption"].'</div>';
				if (isset($entry["description"])) {
					$description .= '<blockquote>'.$entry["description"].'</blockquote>';
				}
            } else {
// story?
	            $description = '<a href="'.$link.'">'.$entry["story"].'</a>';
				if (isset($entry["message"])) {
					$description = '<blockquote>'.$entry["message"].'</blockquote>';
				}
				$description .= $image_html
				. '<div class="clearfix fbname"><a href="'.$link.'">'.$entry["name"].'</a></div>';
				if (isset($entry["caption"])) {
					$description .=	'<blockquote class="clearfix fbcaption">'.$entry["caption"].'</blockquote>';
				}
				if (isset($entry["description"])) {
					$description .= '<blockquote>'.$entry["description"].'</blockquote>';
				}
				$item->addElement('category', "links");
	        }
            }
			
			$item->setDescription($description);
            
			$item->addElement('guid', $entry["id"]);

            $feed->addItem($item);
        	}
        }
}

// that's it... don't echo anything else, just call this method
$feed->genarateFeed();


    function text_excerpt($text, $length, $use_words, $finish_word, $finish_sentence)
    {
      $tokens = array();
      $out = '';
      $w = 0;
      
      // Divide the string into tokens; HTML tags, or words, followed by any whitespace
      // (<[^>]+>|[^<>\s]+\s*)
      preg_match_all('/(<[^>]+>|[^<>\s]+)\s*/u', $text, $tokens);
      foreach ($tokens[0] as $t)
      { // Parse each token
        if ($w >= $length && !$finish_sentence)
        { // Limit reached
          break;
        }
        if ($t[0] != '<')
        { // Token is not a tag
          if ($w >= $length && $finish_sentence && preg_match('/[\?\.\!]\s*$/uS', $t) == 1)
          { // Limit reached, continue until ? . or ! occur at the end
            $out .= trim($t);
            break;
          }
          if (1 == $use_words)
          { // Count words
            $w++;
          } else
          { // Count/trim characters
            $chars = trim($t); // Remove surrounding space
            $c = strlen($chars);
            if ($c + $w > $length && !$finish_sentence)
            { // Token is too long
              $c = ($finish_word) ? $c : $length - $w; // Keep token to finish word
              $t = substr($t, 0, $c);
            }
            $w += $c;
          }
        }
        // Append what's left of the token
        $out .= $t;
      }
      
      return trim(strip_tags($out));
    }
    function text_add_more($text, $ellipsis, $read_more)
    {
      // New filter in WP2.9, seems unnecessary for now
      //$ellipsis = apply_filters('excerpt_more', $ellipsis);
      
      if ($read_more)
        $ellipsis .= sprintf(' <a href="%s" class="read_more">%s</a>', get_permalink(), $read_more);

      $pos = strrpos($text, '</');
      if ($pos !== false)
        // Inside last HTML tag
        $text = substr_replace($text, $ellipsis, $pos, 0);
      else
        // After the content
        $text .= $ellipsis;
      
      return $text;
    }


function SimpleRssFeedsWidget_Widget_desc_Register()
{
	load_plugin_textdomain( 'simplerssfeedswidgetdesc', false, dirname( plugin_basename( __FILE__ ) ) );
	return register_widget( "SimpleRssFeedsWidget_Widget_desc" );
}
