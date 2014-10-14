<?php

define('STAGESHOW_ACTIVATE_AUTH_ACTIVE', true);

/*
-----------------------------------------------------------------------------------------------
	Add Stubs for any Wordpress functions that are called by StageShow class member functions ...
-----------------------------------------------------------------------------------------------
*/

function plugin_basename($filePath)
{
	$filePath = str_replace('\\', '/', $filePath);
	$startPos = strpos($filePath, 'plugins/');
	$startPos += 8;
	$endPos = strpos($filePath, '/', $startPos);
	
	$filePath = substr($filePath, $startPos, $endPos-$startPos).'/stagehshow.php';
	
	return $filePath;
}

function get_currentuserinfo()
{
	
}

function get_bloginfo()
{
	
}

define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS',   60 * MINUTE_IN_SECONDS );

function current_time( $type, $gmt = 0 ) 
{
	switch ($type)
	{
		case 'mysql': 
			return date('Y-m-d H:i:s');
		case 'timestamp': 
			return date('U');
		default:
			return '';
	}
	
}

function _e($text)
{
	echo $text;
}

function __($text)
{
	return $text;
}

function add_filter()
{
	
}

function is_ssl() 
{
	if ( isset($_SERVER['HTTPS']) ) 
	{
		if ( 'on' == strtolower($_SERVER['HTTPS']) )
			return true;
		if ( '1' == $_SERVER['HTTPS'] )
			return true;
	} 
	elseif ( isset($_SERVER['SERVER_PORT']) && ( '443' == $_SERVER['SERVER_PORT'] ) ) 
	{
		return true;
	}
	return false;
}

// Define empty (unused) classes so includes are not added elsewhere
if (!class_exists('WP_Http')) 
{
  class WP_Http 
  {
  }
}

?>