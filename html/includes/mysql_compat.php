<?php

if (!function_exists('mysql_connect')) {
	$private_link_identifier=null;
	function mysql_connect($db_host,$db_user,$db_pwd)
	{
		global $private_link_identifier;
		
//		echo 'Bonjour mysql_connect !<br>';
		$private_link_identifier = mysqli_connect($db_host,$db_user,$db_pwd);
		return $private_link_identifier;
	}
}

if (!function_exists('mysql_select_db')) {
	function mysql_select_db($dbname,$dbicon)
	{
//		echo 'Bonjour mysql_select_db !<br>';
		return mysqli_select_db($dbicon,$dbname);
	}
}

if (!function_exists('mysql_query')) {
	function mysql_query($query, $link_identifier = null)
	{
//		echo 'Bonjour mysql_query ! <br>';
		return mysqli_query($link_identifier,$query);
	}
}

if (!function_exists('mysql_error')) {
	function mysql_error($link_identifier = null)
	{
		global $private_link_identifier;
//		echo 'Bonjour mysql_error ! <br>';
		if (is_null($link_identifier))
			return mysqli_error($private_link_identifier);
		else
			return mysqli_error($link_identifier);
	}
}

if (!function_exists('mysql_num_rows')) {
	function mysql_num_rows($result)
	{
//		echo 'Bonjour mysql_num_rows ! <br>';
		return mysqli_num_rows($result);
	}
}

if (!function_exists('mysql_fetch_row')) {
	function mysql_fetch_row($result)
	{
//		echo 'Bonjour mysql_fetch_row ! <br>';
		$resultset =  mysqli_fetch_row($result);
		if (is_null($resultset))
			return FALSE;
		else
			return $resultset;
	}
}

if (!function_exists('mysql_real_escape_string')) {
	function mysql_real_escape_string($unescaped_string,$link_identifier = NULL )
	{
		global $private_link_identifier;
//		echo 'Bonjour mysql_real_escape_string ! <br>';
		if (is_null($link_identifier))
			return mysqli_real_escape_string($private_link_identifier,$unescaped_string);
		else
			return mysqli_error($link_identifier,$unescaped_string);
	}
}

if (!function_exists('mysql_affected_rows')) {
	function mysql_affected_rows($link_identifier = NULL )
	{
		global $private_link_identifier;
//		echo 'Bonjour mysql_real_escape_string ! <br>';
		if (is_null($link_identifier))
			return mysqli_affected_rows($private_link_identifier);
		else
			return mysqli_affected_rows($link_identifier);
	}
}

if (!function_exists('mysql_insert_id')) {
	function mysql_insert_id($link_identifier = NULL )
	{
		global $private_link_identifier;
//		echo 'Bonjour mysql_real_escape_string ! <br>';
		if (is_null($link_identifier))
			return mysqli_insert_id($private_link_identifier);
		else
			return mysqli_insert_id($link_identifier);
	}
}


?>