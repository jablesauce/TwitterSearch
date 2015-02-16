<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>My Search</title>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<style type="text/css">
body {
    background: #87CEEB url("images/twitter.png") no-repeat right top;
    margin-right: 200px;
}
h2 {
    color: #191970;
}
h2.thick {
    font-weight: bold;
}
form {
    color: #191970;
}
form.thicker {
    font-weight: 900;
}
img {
	border-width: 0
}

* {
	font-family: 'Helvetica', sans-serif;
}
</style>
</head>
<body>
	<div>
		<h2>Welcome to TwitterSearch</h2>

		<p>
			Enter your search query in the box below. If you are having issues
			try <a href='../clearsessions.php'>clearing your session</a>.
		</p>

		<hr />
	</div>

	<p>
		<form action="twitteroauth-master/search.php" method="get">
			<strong>Enter search query:</strong>
			<br> <input type="text" name="query"> <br> <input type="submit"
						value="Search">
		
		</form>
		<pre>
        <strong><?php print_r($content); ?></strong>
        <strong><?php print_r($time); ?></strong>
      </pre>
	</p>
	
</body>
</html>