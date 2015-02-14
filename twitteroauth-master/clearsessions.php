<?php
/**
 * @file
 * Clears PHP sessions and redirects to the connect page.
 */
 
/* Load and clear sessions */
//session_save_path(home/users/web/b2940/ipg.uomtwittersearchnet/cgi-bin/tmp);
session_start();
session_destroy();
 
/* Redirect to page with the connect to Twitter option. */
header('Location: ./connect.php');
