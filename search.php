<?php
require_once ('./twitteroauth/twitteroauth.php');
require_once ('./config.php');

include ('./index.php');

set_time_limit ( 300 );

//////////////////////// TWITTEROAUTH ////////////////////////////////////////

/* If access tokens are not available redirect to connect page. */
if (empty ( $_SESSION ['access_token'] ) || empty ( $_SESSION ['access_token'] ['oauth_token'] ) || empty ( $_SESSION ['access_token'] ['oauth_token_secret'] )) {
	header ( 'Location: ./clearsessions.php' );
}

/* Get user access tokens out of the session. */
$access_token = $_SESSION ['access_token'];

/* Create a TwitterOauth object with consumer/user tokens. */
$connection = new TwitterOAuth ( CONSUMER_KEY, CONSUMER_SECRET, $access_token ['oauth_token'], $access_token ['oauth_token_secret'] );

//////////////////////////////////////////////////////////////////////////////

$t_start = microtime(true); // start searching

// get raw query from form and process
$raw = $_GET ['query'];
$query = processQuery ( $raw );

$index = decodeIndex ();
$n = getIndexSize ( $index );

// get intersection set of query and index
$intersection = intersect ( $query, $index );
if (! empty ( $intersection )) {
	// build vector space of tweets
	$space = buildSpace ( $intersection, $query );
	
	// rank tweets appropriately
	$ranks = rankTweets ( $space, $query, $n );
	
	$content = getFirstNResults($ranks, 10);
	
	// get highest available location
	$loc = getTopLocation ( $ranks );
	$place = setLocationSessionVars ( $loc );
	
	$t_end = microtime(true); // end searching
	$time = 'Total search time: ' . ($t_end - $t_start)/60;	// total searching time
	$flag = 1;
} else {
	$flag = 0;
	$content =  "No relevant tweets found!";
	$place = 'No location specified';
	$t_end = microtime(true); // end searching
	$time = 'Total search time: ' . 0 . ' seconds';	// total searching time
}

/////////////////////// FUNCTIONS //////////////////////////////////////////////

function processQuery($raw) {
	$query = explode ( ' ', $raw );
	$query = expandAcronyms ( $query );
	$query = removeStopWords ( $query );
	$query = array_map ( 'strtolower', $query );
	sort ( $query, SORT_NATURAL );
	$query = array_count_values ( $query );
	return $query;
}

function getIndexSize($index) {
	if (empty ( $_SESSION ['index_size'] )) {
		$_SESSION ['index_size'] = countIndex ( $index );
	}
	$n = $_SESSION ['index_size'];
	
	return $n;
}

function intersect($query, $index) {
	$intersection = [ ];
	$type = array_keys ( $query );
	foreach ( $index as $item ) {
		$item ['type'] = strtolower ( $item ['type'] );
		$temp = $item;
		// array_intersect will fail to work if array elements are present
		unset ( $temp ['location'] );
		unset ( $temp ['sentiment'] );
		if (array_intersect ( $type, $temp )) {
			$intersection [] = $item;
		} // else irrelevant to query
	}
	return $intersection;
}

function buildSpace($intersection, $query) {
	$dim = count ( $query );
	$vectorspc = [ ];
	// store already constructed vectors in $keys
	$keys = [ ];
	for($i = 0; $i < count ( $intersection ); $i ++) {
		$tweet_id = $intersection [$i] ['tweet_id'];
		if (array_search ( $tweet_id, $keys ) === false) {
			$tweet = [ ];
			$keys [] = $tweet_id;
			$tweet [] = $intersection [$i];
			for($j = $i + 1; $j < count ( $intersection ); $j ++) {
				if ($tweet_id == $intersection [$j] ['tweet_id']) {
					$tweet [] = $intersection [$j];
				}
			}
				
			$tweet = sortIndex ( $tweet );
			// if tweet vector has less components than query, add dummies
			if (count ( $tweet ) < $dim) {
				$vector = setDim ( $tweet, $query, $dim );
			} else {
				$vector = $tweet;
			}
			$vectorspc [] = $vector;
		}
	}
	return $vectorspc;
}

function setDim($tweet, $query) {
	$q_types = array_keys ( $query );
	// $id and $sentiment used to predefine dummy vector components
	$id = $tweet [0] ['tweet_id'];
	$sentiment = $tweet [0] ['sentiment'];
	for($i = 0; $i < count ( $q_types ); $i ++) {
		$found = false;
		$j = 0;
		while ( $found == false && $j != count ( $tweet ) ) {
			$term = $tweet [$j] ['type'];
			if (strcasecmp ( $term, $q_types [$i] ) == 0) {
				$found = true;
			}
			$j ++;
		}
		if ($found == false) {
			$splice = array (
					'type' => $q_types [$i],
					'frequency' => 0,
					'tweet_id' => $id,
					'location' => array (),
					'text' => '',
					'sentiment' => $sentiment
			);
			$tweet [] = $splice;
		}
	}
	$tweet = sortIndex ( $tweet );

	return $tweet;
}

function rankTweets($space, $query, $index_size) {
	$rankSpace = [ ];
	foreach ( $space as $vector ) {
		$rank = score ( $vector, $query, $space, $index_size );
		$rankSpace [] = $rank;
	}

	// sort by rank and id
	$ranks = array ();
	$ids = array ();
	foreach ( $rankSpace as $key => $row ) {
		$ranks [$key] = $row ['rank'];
		$ids [$key] = $row ['tweet_id'];
	}
	array_multisort ( $ranks, SORT_DESC, $ids, SORT_DESC, $rankSpace );

	return $rankSpace;
}

// get score of vector with respect to query
function score($vector, $query, $space, $index_size) {

	// set pre-defined constants of rank entry
	$id = $vector [0] ['tweet_id'];
	$pos = $vector [0] ['sentiment'] ['pos'];
	$neg = $vector [0] ['sentiment'] ['neg'];

	// due to dummy additions location and text may be empty
	// hence set both variables appropriately
	$found = false;
	$loc = $vector [0] ['location'];
	$tweet = $vector [0] ['text'];
	while ( current ( $vector ) && $found == false ) {
		if (current ( $vector )['text'] != '') {
			$tweet = current ( $vector )['text'];
			$found = true;
		}
		if (! empty ( current ( $vector )['location'] )) {
			$loc = current ( $vector )['location'];
		}
		next ( $vector );
	}

	// preset vars for ranking
	$weight = 0;
	$q_mag = 0;
	$v_mag = 0;

	// set tf-idf for all terms
	foreach ( $vector as $term ) {
		$type = $term ['type'];
		$v_freq = $term ['frequency'];
		$q_freq = $query [$type];
		$idf = idf ( $type, $space, $index_size );
		$v_weight = tfidf ( tf ( $v_freq ), $idf );
		$q_weight = tfidf ( tf ( $q_freq ), $idf );
		$v_mag += pow ( $v_weight, 2 );
		$q_mag += pow ( $q_weight, 2 );
		$weight += ($v_weight * $q_weight);
	}
	// calculate magnitudes
	$q_mag = sqrt ( $q_mag );
	$v_mag = sqrt ( $v_mag );
	$divisor = $q_mag * $v_mag;

	// get score
	$score = $weight / $divisor;

	// make rank entry
	$rank = array (
			'tweet_id' => $id,
			'tweet' => $tweet,
			'rank' => $score,
			'positive' => $pos,
			'negative' => $neg,
			'location' => $loc
	);
	return $rank;
}

function tf($freq) {
	if ($freq == 0) {
		return 0;
	} else {
		return 1 + log10 ( $freq );
	}
}

function idf($term, $space, $index_size) {
	$freq = 0;

	for($i = 0; $i < count ( $space ); $i ++) {
		$j = 0;
		$found = false;
		while ( $found == false && $j < count ( $space [$i] ) ) {
			$type = $space [$i] [$j] ['type'];
			if (strcasecmp ( $term, $type ) == 0) {
				$freq ++;
				$found = true;
			}
			$j ++;
		}
	}

	$idf = log10 ( $index_size / $freq );
	return $idf;
}

function tfidf($tf, $idf) {
	return $tf * $idf;
}

function getFirstNResults($ranks, $n){
	$first_n = [];
	while ($n > 0 && current ($ranks)){
		if (!current ($ranks)['location']){
			$loc = 'No location specified!';
		} else {
			$loc = current ($ranks)['location']; 
		}
		$first_n[] = array('Tweet' => current ($ranks)['tweet'],
						   'Tweet ID' => current ($ranks)['tweet_id'],
						   'Rank' => current ($ranks)['rank'],
						   'Positivity' => current ($ranks)['positive'],
						   'Location' => $loc
					 );
		
		$n--;
		next ($ranks);
	}
	return $first_n;
}

function getTopLocation($rankSpace) {
	$i = 0;
	$found = false;
	if (! empty ( $rankSpace [$i] ['location'] )) {
		$found = true;
	} else {
		while ( $found == false && $i != count ( $rankSpace ) - 1 ) {
			if (! empty ( $rankSpace [$i] ['location'] )) {
				$found = true;
			}
			$i ++;
		}
	}
	if (! $found) {
		return 0;
	} else {
		return $rankSpace [$i] ['location'];
	}
}

function setLocationSessionVars($loc) {
	if ($loc) {
		$_SESSION ['flag'] = 0;
		$_SESSION ['lat'] = $loc ['latitude'];
		$_SESSION ['lon'] = $loc ['longitude'];
		$place = $loc ['place'];
	} else {
		$_SESSION ['flag'] = 1;
		$place = 'No location specified';
	}
	return $place;
}

include ('Ranked.inc');
?>