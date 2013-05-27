<?php
	require_once( './config.php' );

//read more: 
// https://developers.facebook.com/docs/howtos/login/server-side-login/
session_start();
   $my_url = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER["SCRIPT_NAME"];  // redirect url
   $redirect_uri = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

   $code = $_REQUEST["code"];

   if(empty($code)) {
     // Redirect to Login Dialog
     $_SESSION['state'] = md5(uniqid(rand(), TRUE)); // CSRF protection
     $dialog_url = "https://www.facebook.com/dialog/oauth?client_id=" 
       . $app_id . "&redirect_uri=" . urlencode($my_url) . "&state="
       . $_SESSION['state'] . "&scope=publish_stream,user_status,user_activities,read_stream,user_likes,read_friendlists,email";


     echo("<script> top.location.href='" . $dialog_url . "'</script>");
   }
//   echo $_SESSION['state']."|".$_REQUEST['state'];
if($_SESSION['state'] && ($_SESSION['state'] === $_REQUEST['state'])) {
     $token_url = "https://graph.facebook.com/oauth/access_token?"
       . "client_id=" . $app_id . "&redirect_uri=" . urlencode($my_url)
       . "&client_secret=" . $app_secret . "&code=" . $code;

    $timeout = 15;

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $token_url);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
   	curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
	$output = curl_exec($ch);
	curl_close($ch);
	$response = trim($output);

     $params = null;
     parse_str($response, $params);
     $longtoken=$params['access_token'];
	echo $longtoken;

//save it to database    
}
?>