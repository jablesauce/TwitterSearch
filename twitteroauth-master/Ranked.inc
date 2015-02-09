<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<style type="text/css">
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

	<p>
		<pre>
		You searched for: <?php print_r($raw); ?>
		Location: <?php echo $place ?>
		<?php print_r($time); ?>	
      </pre>
	</p>
	<pre>
        <?php print_r($content); ?>
      </pre>
	<div id="googleMap" style="width: 500px; height: 380px;"></div>

</body>
</html>