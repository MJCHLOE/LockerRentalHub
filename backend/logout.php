<?php
	session_start();

	// remove all session variables
	session_unset();

	// destroy the session
	session_destroy();

	echo "<script>alert('Logout');window.location.replace('../LoginPage.html');</script>";
	echo "Logout";
	header('Refresh: 3; URL=../LoginPage.html');
?>