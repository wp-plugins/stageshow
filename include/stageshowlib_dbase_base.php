<?php
/* 
Description: Core Library Database Access functions

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

require_once "stageshowlib_utils.php";

if (!class_exists('StageShowLibGenericDBaseClass'))
{
	class StageShowLibGenericDBaseClass // Define class
	{
		// This class does nothing when running under WP
		// Overload this class with DB access functions for non-WP access
		function __construct() //constructor		
		{
			$this->GetLoginID();
			$this->SetMySQLGlobals();
		}	
		
		static function IsInWP()
		{
			// Use a WP define to find out if WP is loaded
			return defined('WPINC');
		}
		
		function GetLoginID()
		{
			if (!defined('CORONDECK_RUNASDEMO'))	// Get Current User ID in Demo mode
				return '';
				
			if (isset($this->loginID))
				return $this->loginID;
				
			if (isset($_SESSION['stageshowlib_loginID']))
			{
				$this->loginID = $_SESSION['stageshowlib_loginID'];	
				return $this->loginID;
			}
				
			if (!function_exists('get_currentuserinfo'))
			{
				require_once( ABSPATH . WPINC . '/pluggable.php' );
			}
			global $current_user;
				
      		get_currentuserinfo();

			if (isset($current_user->user_login))
				$this->loginID = $current_user->user_login;	
			else
				$this->loginID = '';
							
			$_SESSION['stageshowlib_loginID'] = $this->loginID;
			
			return $this->loginID;
		}
		
		function SetMySQLGlobals()
		{			
			$this->query("SET OPTION SQL_BIG_SELECTS=1");
		}
		
		function ShowSQL($sql, $values = null)
		{			
			if (!$this->isDbgOptionSet('Dev_ShowSQL'))
			{
				return;				
			}
			
			if ($this->isDbgOptionSet('Dev_ShowCallStack'))
			{
				StageShowLibUtilsClass::ShowCallStack();
			}
			
			$sql = str_replace("\n", "<br>\n", $sql);
			echo "<br>$sql<br>\n";
			if (isset($values))
			{
				print_r($values);
				echo "<br>\n";
			}
		}
		
		function queryWithPrepare($sql, $values)
		{
			global $wpdb;
			
			$sql = $wpdb->prepare($sql, $values);
			
			return $this->query($sql);
		}
		
		function query($sql)
		{
			global $wpdb;
			
			if (defined('CORONDECK_RUNASDEMO'))
			{
				$sql = $this->SQLForDemo($sql);
			}	
			
			$this->ShowSQL($sql);
			
			$this->queryResult = $wpdb->query($sql);
			$rtnStatus = ($this->queryResult !== false);	
			
			return $rtnStatus;		
		}

		function GetInsertId()
		{
			global $wpdb;
			
			return $wpdb->insert_id;
		}

		function getresultsWithPrepare($sql, $values)
		{
			global $wpdb;
			
			$sql = $wpdb->prepare($sql, $values);
			
			return $this->get_results($sql);
		}
		
		function get_results($sql, $debugOutAllowed = true, $sqlFilters = array())
		{
			global $wpdb;
			
			if (defined('CORONDECK_RUNASDEMO'))
			{
				$sql = $this->SQLForDemo($sql);
			}	
			
			$this->ShowSQL($sql);
			$results = $wpdb->get_results($sql);
			if ($debugOutAllowed) $this->show_results($results);
			
			return $results;
		}
		
		function show_results($results)
		{
			if (!$this->isDbgOptionSet('Dev_ShowDBOutput'))
				return;
				
			if (function_exists('wp_get_current_user'))
			{
				if (!$this->isSysAdmin())
					return;				
			}
				
			echo "<br>Database Results:<br>\n";
			for ($i = 0; $i < count($results); $i++)
				echo "Array[$i] = " . print_r($results[$i], true) . "<br>\n";
		}
		
		function SQLForDemo($sql)
		{
			if (!defined('CORONDECK_RUNASDEMO'))
				return $sql;
			
			if (strpos($sql, 'loginID') !== false)
				return $sql;
				
			$sqlDemo = $sql;
				
			// First get the command type (first word) ...
			if (preg_match('/(\w*)/', $sql, $matches) == 0) 
			{
				return $sql;
			}
			
			$sqlCmd = $matches[0];
			
			switch ($sqlCmd)
			{
				case 'SELECT':
				case 'DELETE':
					// SELECT query .... add the loginID to the query
					//$posnStart  = strrpos($sqlDemo, ' FROM ');
					$posnStart = 0;
					preg_match('/FROM\s*([a-zA-Z_]*)\s*([a-z_]*)/', $sqlDemo, $matches, 0, $posnStart);
					//TODO - Remove - echo "<br>";print_r($matches);echo "<br>";
					
					if ( (count($matches) > 2) && (strlen(trim($matches[2])) > 0) )
						$tableName = $matches[2];
					else
						$tableName = $matches[1];
										
					$where = ' WHERE '.$tableName.'.loginID = "'.$this->loginID.'" ';
					
					if (strpos($sqlDemo, 'WHERE') !== false)
					{
						$sqlDemo = str_replace("WHERE", "$where AND", $sqlDemo);
					}
					else if (strpos($sqlDemo, 'GROUP BY') !== false)
					{
						$sqlDemo = str_replace("GROUP BY", "$where GROUP BY", $sqlDemo);
					}
					else if (strpos($sqlDemo, 'ORDER BY') !== false)
					{
						$sqlDemo = str_replace("ORDER BY", "$where ORDER BY", $sqlDemo);
					}
					else
					{
						$sqlDemo .= $where;
					}
					break;
				
				case 'UPDATE':
					// SELECT query .... add the loginID to the query
					$where = ' WHERE loginID = "'.$this->loginID.'" ';
					
					if (strpos($sqlDemo, 'WHERE') !== false)
					{
						$sqlDemo = str_replace("WHERE", "$where AND", $sqlDemo);
					}
					break;
				
				case 'INSERT':
					$sqlDemo = preg_replace('/\(/', '(loginID, ', $sqlDemo, 1);
					$sqlDemo = str_replace('VALUES(', 'VALUES("'.$this->loginID.'", ', $sqlDemo);
					break;
				
				case 'ALTER':
				case 'LOCK':
				case 'UNLOCK':
				case 'SET':
					return $sql;
				
				case 'SHOW':
					return $sql;
			}
								
			if ($sqlDemo === $sql)
			{
				echo "<br><strong>ERROR: SQL not processed for DEMO mode: </strong><br>SQL: $sql<br>\n";
				die;				
			}
			
			return $sqlDemo;
		}
		
		function isDbgOptionSet($optionID)
		{
			return false;
		}
       
		static function FormatDateForAdminDisplay($dateInDB)
		{
			// Convert time string to UNIX timestamp
			$timestamp = strtotime( $dateInDB );
			
			// Get Time & Date formatted for display to user
			return date(STAGESHOWLIB_DATETIME_ADMIN_FORMAT, $timestamp);
		}
		
	}
}

?>