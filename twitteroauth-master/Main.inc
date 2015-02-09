<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>My Search</title>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<style type="text/css">
img {
	border-width: 0
}

* {
	font-family: 'Lucida Grande', sans-serif;
}
</style>
</head>
<body>
	<div>
		<h2>Welcome to My Search</h2>

		<p>
			Enter your search query in the box below. If you are having issues
			try <a href='./clearsessions.php'>clearing your session</a>.
		</p>

		<hr />
	</div>

	<p>
		<form action="search.php" method="get">
			Enter search query:
			<br> <input type="text" name="query"> <br> <input type="submit"
						value="Search">
		
		</form>
		<pre>
        <?php print_r($content); ?>
        <?php print_r($time); ?>
      </pre>
	</p>
	
</body>
</html>