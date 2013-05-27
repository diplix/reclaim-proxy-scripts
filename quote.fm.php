<?php
// Include the SimplePie library
// For 1.3+:
//require_once('SimplePie/autoloader.php');
require_once('./SimplePie.compiled.php');
require_once( './config.php' );
 
// Create a new SimplePie object
$feed = new SimplePie();

// Instead of only passing in one feed url, we'll pass in an array of three
$feed->set_feed_url('http://quote.fm/'.$quotefm_user.'/feed');
 
// We'll use favicon caching here (Optional)
//$feed->set_favicon_handler('handler_image.php');

$feed->set_cache_duration ( 3600 );
$feed->set_timeout ( 10 );
$feed->set_stupidly_fast( true );
//$feed->set_autodiscovery_level(SIMPLEPIE_LOCATOR_NONE);
$feed->force_feed(true);

 
// Initialize the feed object
$feed->init();
 
// This will work if all of the feeds accept the same settings.
$feed->handle_content_type();
 
// Begin our XHTML markup
header("Content-Type: application/rss+xml");
echo '<?xml version="1.0" encoding="UTF-8"?>';
?><rss xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:atom="http://www.w3.org/2005/Atom" version="2.0">

<channel>
<title>ix's recent recommendations</title>
<link>https://quote.fm/<?php echo $user_id; ?></link>
<description>ix's recent recommendations</description>
<lastBuildDate><?php echo date(DATE_ATOM , time()); ?></lastBuildDate>
<language>en</language>
<atom:link href="<?php echo 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME']; ?>" rel="self" type="application/rss+xml" />
<generator>http://quote.fm</generator>

<?php if ($feed->error): ?>
<?php //echo $feed->error; ?>
<?php print_r( $feed->error); ?>
<?php endif; ?>

<?php foreach ($feed->get_items() as $item): ?>

<item>
<title><![CDATA[<?php echo html_entity_decode($item->get_title()); ?>]]></title>
<link><?php echo $item->get_permalink(); ?></link>
<pubDate><?php echo $item->get_date('D, j M Y H:i:s O'); ?></pubDate>
<dc:creator><?php $author = $item->get_author(0); if ($author != "") {echo $author->get_name(); } ?></dc:creator>
<category><![CDATA[<?php $category = $item->get_category(0); if ($category != "") { echo $category->get_label();} ?>]]></category>
<guid isPermaLink="true"><?php echo $item->get_permalink(); ?></guid>
<?php
//&raquo;
//&laquo;
$c = $item->get_content();
//$c = str_replace('&raquo;','',$c); 
//$c = str_replace('&laquo;','',$c); 
?>
<description><![CDATA[<?php echo html_entity_decode($c); ?>]]></description>
</item>
<?php endforeach; ?>
</channel>
</rss>