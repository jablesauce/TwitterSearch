<?php
//session_start ();
require_once ('twitteroauth/twitteroauth.php');
require_once ('config.php');
include ('stop_words.php');
include ('acronyms.php');

set_time_limit ( 300 );

/* If access tokens are not available redirect to connect page. */
if (empty ( $_SESSION ['access_token'] ) || empty ( $_SESSION ['access_token'] ['oauth_token'] ) || empty ( $_SESSION ['access_token'] ['oauth_token_secret'] )) {
	header ( 'Location: ./clearsessions.php' );
}

/* Get user access tokens out of the session. */
$access_token = $_SESSION ['access_token'];

/* Create a TwitterOauth object with consumer/user tokens. */
$connection = new TwitterOAuth ( CONSUMER_KEY, CONSUMER_SECRET, $access_token ['oauth_token'], $access_token ['oauth_token_secret'] );

// /////////////////////////////////////////////////////////////////////////

//create the multiple cURL handle
$mh = curl_multi_init();
$requests = array();

//$xml = getXML('Yo wassup this is BIG in da house nigga');

//$sent = parseSentiment($xml);
//print_r($sent);

addSentimentHandle ( 'Yo wassup this is BIG in da house nigga', $mh, $requests );
addSentimentHandle ( 'I am a very sexy boy :)', $mh, $requests );

executeHandles($mh);
getXML($mh, $requests);

function addSentimentHandle($tweet, $mh, &$requests) {
	$url = makeURLForAPICall($tweet);
	array_push($requests, curl_init ($url));
	print_r($requests);
}
	//$requests[] = curl_init ($url);	
	//$calls[] = $ch;
	//curl_setopt ( end($requests), CURLOPT_RETURNTRANSFER, 1 );
	//curl_multi_add_handle ( $mh, end($requests));
	//curl_setopt ( $ch, CURLOPT_URL, $url );

	//curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, 5 );

	//$xml = curl_exec($ch);
	//curl_close ( $ch );
	//print_r($calls);
	//print_r($xml);
	// return $xml;
//}

function makeURLForAPICall($tweet){
	$tweet = str_replace ( ' ', '+', $tweet );
	$prefix = 'http://uclassify.com/browse/uClassify/Sentiment/ClassifyText?';
	$key = 'readkey=' . CLASSIFY_KEY . '&';
	$text = 'text=' . $tweet . '&';
	$version = 'version=1.01';
	$url = $prefix . $key . $text . $version;
	return $url;
}

function executeHandles($mh) {
	if (! empty ( $mh )) {
		$active = null;
		// execute the handles
		do {
			$mrc = curl_multi_exec ( $mh, $active );
		} while ( $mrc === CURLM_CALL_MULTI_PERFORM || $active );
		while ( $active && $mrc == CURLM_OK ) {
			if (curl_multi_select ( $mh ) == - 1) {
				usleep ( 100 );
			}
			do {
				$mrc = curl_multi_exec ( $mh, $active );
			} while ( $mrc == CURLM_CALL_MULTI_PERFORM );
		}
	}
}

function getXML($mh, $requests){	
	$xml = array();
	print_r($requests);
	foreach ($requests as $call) {
		
		$xml[] = curl_multi_getcontent($call);
		curl_multi_remove_handle($mh, $call);
		curl_close($call);
		
	}
	curl_multi_close($mh);
	foreach($xml as $item){
		$sentiment = parseSentiment($item);
		
		print_r($sentiment);
	}
}

function parseSentiment($xml) {
	$p = xml_parser_create ();
	xml_parse_into_struct ( $p, $xml, $vals, $index );
	xml_parser_free ( $p );
	print_r($index);
	$positivity = $vals [8] ['attributes'] ['P'];
	$negativity = 1 - $positivity;
	$sentiment = array (
			'pos' => $positivity,
			'neg' => $negativity
	);
	return $sentiment;
}



function getXML2($tweet) {
	$url = makeURLForAPICall($tweet);
	$ch = curl_init ();
	$timeout = 5;
	curl_setopt ( $ch, CURLOPT_URL, $url );
	curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );

	//curl_multi_add_handle ( $mh, $ch );
	$xml = curl_exec($ch);
	//$xml = file_get_contents($url);
	//print_r($xml);
	curl_close ( $ch );
	return $xml;
}






/**
$user_handle = 'sultanwing';

$content = $connection->get ( 'statuses/user_timeline', array (
		'screen_name' => $user_handle,
		'count' => 200 
) );

$latest_id = $content [0]->id_str;
$most_recent = getMostRecentTweet ();

if ($latest_id > $most_recent) {
	$s_index = microtime ( true );	
	$json_index = decodeIndex ();	
	$json_index = updateIndex ( $content, $connection, $user_handle, $json_index, $most_recent );	
	$json_index = sortIndex ( $json_index );	
	$json = encodeIndex ( $json_index );
	$_SESSION ['index_size'] = countIndex ( $json_index );
	updateMostRecentTweet ( $latest_id );
	$e_index = microtime ( true );
	//echo 'Time to encode index: ' . $e_index - $s_index;
} else {
	echo 'No new tweets to index!';
}

function parseTweet($tweet, $tweet_id, $mh, $calls) {
	
	addSentimentHandle ( $tweet, $mh, $calls );
	
	// find urls in tweet and remove (HTTP ONLY CURRENTLY)
	$tweet = preg_replace ( '/(http:\/\/[^\s]+)/', "", $tweet );
	
	// split tweet into tokens and clean
	$words = preg_split ( "/[^A-Za-z0-9]+/", $tweet );
	// /[\s,:.@#?!()-$%&^*;+=]+/
	// /[^A-Za-z0-9]+/
	
	$expansion = expandAcronyms ( $words );
	$tokens = removeStopWords ( $expansion );
	
	// convert to type-frequency array
	$tokens = array_filter ( $tokens );
	$tokens = array_count_values ( $tokens );
	
	// $entry = makeEntry($tokens, $tweet_id, $sentiment);
	$entry = makeEntry ( $tokens, $tweet_id );
	
	return $entry;
}

function removeStopWords($words) {
	$tokens = [ ];
	for($i = 0; $i < count ( $words ); $i ++) {
		$is_stopword = false;
		$j = 0;
		while ( $is_stopword == false && $j != count ( $GLOBALS ['stop_words'] ) ) {
			if (strcasecmp ( $words [$i], $GLOBALS ['stop_words'] [$j] ) == 0) {
				$is_stopword = true;
			} else
				$j ++;
		}
		if (! $is_stopword) {
			$tokens [] = $words [$i];
		}
	}
	return $tokens;
}

function makeEntry($tokens, $tweet_id) { // , $sentiment){
	$types = array ();
	while ( current ( $tokens ) ) {
		$key = key ( $tokens );
		array_push ( $types, array (
				'type' => $key,
				'frequency' => $tokens [$key],
				'tweet_id' => $tweet_id 
		)
		// 'tweet_mood' => $sentiment
		 );
		next ( $tokens );
	}
	return $types;
}

function expandAcronyms($terms) {
	$words = [ ];
	$acrok = array_keys ( $GLOBALS ['acronyms'] );
	$acrov = array_values ( $GLOBALS ['acronyms'] );
	for($i = 0; $i < count ( $terms ); $i ++) {
		$is_acronym = false;
		$j = 0;
		while ( $is_acronym == false && $j != count ( $acrok ) ) {
			if (strcasecmp ( $terms [$i], $acrok [$j] ) == 0) {
				$is_acronym = true;
				$expansion = $acrov [$j];
			}
			$j ++;
		}
		if ($is_acronym) {
			$expansion = preg_split ( "/[^A-Za-z0-9]+/", $expansion );
			foreach ( $expansion as $term ) {
				$words [] = $term;
			}
		} else {
			$words [] = $terms [$i];
		}
	}
	return $words;
}
**/

/**
function parseSentiment($xml) {
	$p = xml_parser_create ();
	xml_parse_into_struct ( $p, $xml, $vals, $index );
	xml_parser_free ( $p );
	// print_r($index);
	$positivity = $vals [8] ['attributes'] ['P'];
	$negativity = 1 - $positivity;
	$sentiment = array (
			'pos' => $positivity,
			'neg' => $negativity 
	);
	return $sentiment;
}

function decodeIndex() {
	$string = file_get_contents ( INDEX_PATH );
	if ($string) {
		$json_index = json_decode ( $string, true );
	} else {
		$json_index = [ ];
	}
	return $json_index;
}

function countIndex($json_index) {
	$tweets = [ ];
	$count = 0;
	for($i = 0; $i < count ( $json_index ); $i ++) {
		$id = $json_index [$i] ['tweet_id'];
		if (in_array ( $id, $tweets )) {
		} else {
			$tweets [] = $id;
			$count ++;
		}
	}
	return $count;
}

function lookup($array, $key, $val) {
	foreach ( $array as $item ) {
		if (isset ( $item [$key] ) && $item [$key] == $val) {
			return true;
		} else {
			return false;
		}
	}
}

function updateIndex($timeline, $connection, $user_handle, $json_index, $most_recent) {
	// $index = fopen ( INDEX_PATH, 'a+' );
	$halt = false;
	$j = 0;
	$count = 0;
	
	$mh = curl_multi_init ();
	$calls = [];
	
	while ( (count ( $timeline ) != 1 || $j == 0) && $halt == false ) {
		$x = 0;
		$n = $j;
		while ( ($n < count ( $timeline )) && $halt == false ) {
			
			$text = $timeline [$n]->text;
			$tweet_id = $timeline [$n]->id_str;
			// GEO CONTENT....................?
			
			// if current_id > latest_id in index => add
			if ($tweet_id > $most_recent) {
				$keywords = parseTweet ( $text, $tweet_id, $mh, $calls );
				foreach ( $keywords as $type ) {
					$json_index [] = $type;
				}
				$n ++;
				$x ++;
			} else {
				$halt = true;
			}
		}
		
		if ($halt == false) {
			$tweet_id = $timeline [$n - 1]->id_str;
			
			$timeline = $connection->get ( 'statuses/user_timeline', array (
					'screen_name' => $user_handle,
					'count' => 200,
					'max_id' => $tweet_id 
			) );
			
			$j = 1;
		}
		
		$count += $x;
	}
	
	// fclose ( $index );
	executeHandles($mh);
	getSentiments($mh, $calls);
	
	echo 'Number of tweets indexed: ' . $count;
	
	return $json_index;
}





function sortIndex($json_index) {
	$type = array ();
	$freq = array ();
	$id = array ();
	
	foreach ( $json_index as $key => $row ) {
		$type [$key] = $row ['type'];
		$freq [$key] = $row ['frequency'];
		$id [$key] = $row ['tweet_id'];
	}
	
	array_multisort ( $type, SORT_ASC | SORT_NATURAL | SORT_FLAG_CASE, $freq, SORT_DESC, $id, SORT_ASC, $json_index );
	
	return $json_index;
}

function encodeIndex($json_index) {
	$json = json_encode ( $json_index, JSON_FORCE_OBJECT | JSON_PRETTY_PRINT );
	
	$index = fopen ( INDEX_PATH, 'w' );
	fwrite ( $index, $json );
	fclose ( $index );
	
	// print_r($json);
	return $json;
}

function getMostRecentTweet() {
	$file = fopen ( 'latest.txt', 'r' );
	$most_recent = fgets ( $file );
	if (! $most_recent) {
		$most_recent = 0;
	}
	fclose ( $file );
	
	return $most_recent;
}

function updateMostRecentTweet($latest_id) {
	$file = fopen ( 'latest.txt', 'w' );
	fwrite ( $file, $latest_id . PHP_EOL );
	fclose ( $file );
}

/* Some example calls */
// $content = $connection->get('account/verify_credentials');
// $content = $connection->get('account/rate_limit_status');
// $content = $connection->get('users/show', array('screen_name' => 'sultanwing'));
// $content = $connection->post('statuses/update', array('status' => date(DATE_RFC822)));
// $content = $connection->post('statuses/destroy', array('id' => 5437877770));
// $content = $connection->post('friendships/create', array('id' => 9436992));
// $content = $connection->post('friendships/destroy', array('id' => 9436992));

include ('Main.inc');
?>


