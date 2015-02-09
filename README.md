Twitter Search
------------

A simple user-specific search engine for the user Twitter timeline 

Overview
=============

1. Build TwitterOAuth object using client credentials using https://github.com/abraham/twitteroauth.git
2. New tweets discovered on user timeline are handled and indexed by [index.php](index.php) and stored in [index.json](index.json)
3. When user inputs queries, [search.php](search.php) implements a simple search engine which traverses [index.json](index.json) to retrieve relevant information
