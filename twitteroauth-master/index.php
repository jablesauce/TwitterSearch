<?php
//session_save_path(home/users/web/b2940/ipg.uomtwittersearchnet/cgi-bin/tmp);
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

///// UNCOMMENT BELOW TO AUTOMATICALLY SPECIFY CURRENTLY LOGGED IN USER
//$user = $connection->get('account/verify_credentials');
//$user_handle = $user->screen_name;

$user_handle = 'AngeloDalli';

$timeline = getContent ( $connection, $user_handle, 1 );

$latest_id = $timeline [0]->id_str;
$most_recent = getMostRecentTweet ();

if ($latest_id > $most_recent) {
	$t_start = microtime(true); // start indexing
	//$timeline = getContent ( $connection, $user_handle, 200 );
	//$json_index = decodeIndex ();
	//$json_index = updateIndex ( $timeline, $connection, $user_handle, $json_index, $most_recent );
	//$json_index = sortIndex ( $json_index );
	//$json = encodeIndex ( $json_index );
	//updateMostRecentTweet ( $latest_id );
	//$_SESSION ['index_size'] = countIndex ( $json_index );
	$t_end = microtime(true); // finish indexing
	$content = 'New tweets indexed! Number of tweets in index: ';// . $_SESSION ['index_size'];
	// total indexing time
	$time = 'Total time of indexing: ' . ($t_end - $t_start)/60 . ' seconds';
} else {
	$content = 'No new tweets indexed!';
	$time = '';
}

/////////////////////// FUNCTIONS //////////////////////////////////////////////

function getContent($connection, $user_handle, $n) {
	$content = $connection->get ( 'statuses/user_timeline', array (
			'screen_name' => $user_handle,
			'count' => $n 
	) );
	return $content;
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

function updateIndex($timeline, $connection, $user_handle, $json_index, $most_recent) {
	// URL arrays for uClassify API calls
	$urls = [ ];
	$urls_id = [ ];
	
	// halt if no more new tweets are found
	$halt = false;
	// set to 1 to skip first tweet after 1st batch
	$j = 0;
	// count number of new tweets indexed
	$count = 0;
	while ( (count ( $timeline ) != 1 || $j == 0) && $halt == false ) {
		$no_of_tweets_in_batch = 0;
		$n = $j;
		while ( ($n < count ( $timeline )) && $halt == false ) {
			$tweet_id = $timeline [$n]->id_str;
			if ($tweet_id > $most_recent) {
				$text = $timeline [$n]->text;
				$tokens = parseTweet ( $text );
				$coord = extractLocation ( $timeline, $n );
				addSentimentURL ( $text, $tweet_id, $urls, $urls_id );
				$keywords = makeEntry ( $tokens, $tweet_id, $coord, $text );
				foreach ( $keywords as $type ) {
					$json_index [] = $type;
				}
				$n ++;
				$no_of_tweets_in_batch ++;
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
			// skip 1st tweet after 1st batch
			$j = 1;
		}
		$count += $no_of_tweets_in_batch;
	}
	
	$json_index = extractSentiments ( $urls, $urls_id, $json_index );
	
	echo 'Number of tweets indexed: ' . ($count);
	return $json_index;
}

function parseTweet($tweet) {
	// find urls in tweet and remove (HTTP ONLY CURRENTLY)
	$tweet = preg_replace ( '/(http:\/\/[^\s]+)/', "", $tweet );
	
	// split tweet into tokens and clean
	$words = preg_split ( "/[^A-Za-z0-9]+/", $tweet );
	// /[\s,:.@#?!()-$%&^*;+=]+/ ------ Alternative regex
	
	$expansion = expandAcronyms ( $words );
	$tokens = removeStopWords ( $expansion );
	
	// convert to type-frequency array
	$tokens = array_filter ( $tokens );
	$tokens = array_count_values ( $tokens );
	
	return $tokens;
}

function expandAcronyms($terms) {
	$words = [ ];
	$acrok = array_keys ( $GLOBALS ['acronyms'] );
	$acrov = array_values ( $GLOBALS ['acronyms'] );
	for($i = 0; $i < count ( $terms ); $i ++) {
		$j = 0;
		$is_acronym = false;
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

function extractLocation($timeline, $n) {
	$geo = $timeline [$n]->place;
	if (! empty ( $geo )) {
		$place = $geo->full_name;
		$long = $geo->bounding_box->coordinates [0] [1] [0];
		$lat = $geo->bounding_box->coordinates [0] [1] [1];
		$coord = array (
				'place' => $place,
				'latitude' => $lat,
				'longitude' => $long 
		);
	} else {
		$coord = [ ];
	}
	return $coord;
}

function addSentimentURL($text, $tweet_id, &$urls, &$urls_id) {
	$urls_id [] = $tweet_id;
	$url = makeURLForAPICall ( $text );
	$urls [] = $url;
}

function makeURLForAPICall($tweet) {
	$tweet = str_replace ( ' ', '+', $tweet );
	$prefix = 'http://uclassify.com/browse/uClassify/Sentiment/ClassifyText?';
	$key = 'readkey=' . CLASSIFY_KEY . '&';
	$text = 'text=' . $tweet . '&';
	$version = 'version=1.01';
	$url = $prefix . $key . $text . $version;
	return $url;
}

function makeEntry($tokens, $tweet_id, $coord, $text) {
	$types = array ();
	while ( current ( $tokens ) ) {
		$key = key ( $tokens );
		array_push ( $types, array (
				'type' => $key,
				'frequency' => $tokens [$key],
				'tweet_id' => $tweet_id,
				'location' => $coord,
				'text' => $text 
		) );
		next ( $tokens );
	}
	return $types;
}

function extractSentiments($urls, $urls_id, &$json_index) {
	$responses = multiHandle ( $urls );
	// add sentiments to all index entries
	foreach ( $json_index as $i => $term ) {
		$tweet_id = $term ['tweet_id'];
		foreach ( $urls_id as $j => $id ) {
			if ($tweet_id == $id) {
				$sentiment = parseSentiment ( $responses [$j] );
				$json_index [$i] ['sentiment'] = $sentiment;
			}
		}
	}
	return $json_index;
}

// - Without sentiment, indexing is performed at reasonable speed
// - With sentiment, very frequent API calls greatly reduce indexing speed
// - filegetcontents() for Sentiment API calls is too slow, therefore considered cURL
// - cURL is still too slow and indexing performance is still not good enough
// - therefore considered using multi cURL which is much faster than by just using cURL
// on its own and significantly improved sentiment extraction which in turn greatly
// improved indexing with sentiment
function multiHandle($urls) {
	
	// curl handles
	$curls = array ();
	
	// results returned in xml
	$xml = array ();
	
	// init multi handle
	$mh = curl_multi_init ();
	
	foreach ( $urls as $i => $d ) {
		// init curl handle
		$curls [$i] = curl_init ();
		
		$url = (is_array ( $d ) && ! empty ( $d ['url'] )) ? $d ['url'] : $d;
		
		// set url to curl handle
		curl_setopt ( $curls [$i], CURLOPT_URL, $url );
		
		// on success, return actual result rather than true
		curl_setopt ( $curls [$i], CURLOPT_RETURNTRANSFER, 1 );
		
		// add curl handle to multi handle
		curl_multi_add_handle ( $mh, $curls [$i] );
	}
	
	// execute the handles
	$active = null;
	do {
		curl_multi_exec ( $mh, $active );
	} while ( $active > 0 );
	
	// get xml and flush handles
	foreach ( $curls as $i => $ch ) {
		$xml [$i] = curl_multi_getcontent ( $ch );
		curl_multi_remove_handle ( $mh, $ch );
	}
	
	// close multi handle
	curl_multi_close ( $mh );
	
	return $xml;
}

// SENTIMENT VALUES ON INDEX.JSON FOR THIS ASSIGNMENT ARE NOT CORRECT SINCE THE
// NUMBER OF API CALLS EXCEEDED 5000 ON THE DAY OF HANDING IN. ONCE THE API CALLS
// ARE ALLOWED AGAIN IT CLASSIFIES AS REQUIRED
function parseSentiment($xml) {
	$p = xml_parser_create ();
	xml_parse_into_struct ( $p, $xml, $vals, $index );
	xml_parser_free ( $p );
	$positivity = $vals [8] ['attributes'] ['P'];
	$negativity = 1 - $positivity;
	$sentiment = array (
			'pos' => $positivity,
			'neg' => $negativity 
	);
	return $sentiment;
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
	
	array_multisort ( $type, SORT_ASC | SORT_NATURAL | SORT_FLAG_CASE, 
					  $freq, SORT_DESC, 
					  $id, SORT_ASC, 
					  $json_index );
	
	return $json_index;
}

function encodeIndex($json_index) {
	$json = json_encode ( $json_index, JSON_FORCE_OBJECT | JSON_PRETTY_PRINT );
	
	$index = fopen ( INDEX_PATH, 'w' );
	fwrite ( $index, $json );
	fclose ( $index );
	
	return $json;
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

include ('index.inc');
?>