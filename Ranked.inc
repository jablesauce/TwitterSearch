<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<style type="text/css">
body {
    background: #87CEEB url("images/twitter.png") no-repeat right top;
    margin-right: 200px;
}
pre {
    color: #191970;
}
pre.thick {
    font-weight: 900;
}
img {
	border-width: 0
}

* {
	font-family: 'Helvetica', sans-serif;
}
</style>
<script src="http://maps.googleapis.com/maps/api/js"></script>
<script>
var flag = <?php echo $_SESSION['flag']; ?>;
var lat = <?php echo $_SESSION['lat']; ?>;
var lon = <?php echo $_SESSION['lon']; ?>;
function initialize() {
	//if (flag == 0){
		var mapProp = {
		   center:new google.maps.LatLng(lat, lon),
		   zoom:5,
		   mapTypeId:google.maps.MapTypeId.ROADMAP
		};
		var map=new google.maps.Map(document.getElementById("googleMap"),mapProp);
}
google.maps.event.addDomListener(window, 'load', initialize);
</script>
</head>
<body>
	<div>
	<p>
		<pre>
		<strong>You searched for:</strong> <?php print_r($raw); ?>
		<strong>Location:</strong> <?php echo $place ?>
		<?php print_r($time); ?>	
      </pre>
	</p>
	
	<hr />
	</div>
	<p>
		<pre>
		<u><strong>Results:</strong></u> <br><br>
        <?php if ($flag == 1){
        	foreach ($content as $key => $tweet){
        		echo '<strong>Result #: </strong> ' . $key; echo "<br>";
        		echo '<strong>Tweet:</strong> ' . $tweet['Tweet']; echo "<br>";
        		echo '<strong>Tweet ID:</strong> ' . $tweet['Tweet ID']; echo "<br>";
        		echo '<strong>Rank:</strong> ' . $tweet['Rank']; echo "<br>";
        		echo '<strong>Positvity:</strong> ' . $tweet['Positivity']; echo "<br>";
        		echo '<strong>Location:</strong> ' . $tweet['Location']; echo "<br>";
        		echo "<br><br>";
        	}        	
        } else {
        	print_r($content);
        }        		
		?>
		</pre>
      </p>
	<div id="googleMap" style="width: 500px; height: 380px;"></div>

</body>
</html>