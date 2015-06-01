<?php
/* 
Description: Code for TBD
 
Copyright 2014 Malcolm Shergold

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

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

function __($text, $domain = 'default')
{
	return $text;
}

function _e($text, $domain = 'default')
{
	echo __($text, $domain);
}

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

// Basic version of WP remove_query_arg function
function remove_query_arg($key, $query)
{	
	$regex = '|([\?\&])('.$key.'[\=][^\&\#]*)|';
	if (preg_match($regex, $query, $matches) == 0)
	{
		return $query;
	}
	
	$param = $matches[2];
	if ($matches[1] == '?')
	{
		$query = str_replace($matches[0], '?', $query);
		$query = str_replace('?&', '?', $query);
	}
	else
	{
		$query = str_replace($matches[0], '', $query);
	}
	
	return $query;
}

function add_query_arg($key, $value, $query)
{
	$newArg = $key.'='.$value;
	if (strpos($query, '?') != false)
	{
		$query = str_replace('?', '?'.$newArg.'&', $query);
	}
	else if (strpos($query, '#') != false)
	{
		$query = str_replace('#', '?'.$newArg.'#', $query);
	}
	else
	{
		$query .= '?'.$newArg;
	}
	
	return $query;
}

?>