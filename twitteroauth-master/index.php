<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
session_start ();

require_once ('twitteroauth/twitteroauth.php');
require_once ('config.php');
include ('nlp/stop_words.php');
include ('nlp/acronyms.php');

set_time_limit ( 300 );

//////////////////////// TWITTEROAUTH /////////////////////////////////////

/* If access tokens are not available redirect to connect page. */
if (empty ( $_SESSION ['access_token'] ) || empty ( $_SESSION ['access_token'] ['oauth_token'] ) || empty ( $_SESSION ['access_token'] ['oauth_token_secret'] )) {
	header ( 'Location: ./clearsessions.php' );
}

/* Get user access tokens out of the session. */
$access_token = $_SESSION ['access_token'];



/* Create a TwitterOauth object with consumer/user tokens. */
$connection = new TwitterOAuth ( CONSUMER_KEY, CONSUMER_SECRET, $access_token ['oauth_token'], $access_token ['oauth_token_secret'] );

///////////////////////////////////////////////////////////////////////////

$content = 'HELLOOO';

include ('index.inc');
?>