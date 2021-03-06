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
		var $hideSQLErrors = false;
		
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
			
			return $this->loginID;
		}
		
		function SetMySQLGlobals()
		{			
			$this->hideSQLErrors = true;
			$rtnVal = $this->query("SET SQL_BIG_SELECTS=1");
			$this->hideSQLErrors = false;
			if (!$rtnVal)
			{
				// Use the old version of the query if it fails
				$rtnVal = $this->query("SET OPTION SQL_BIG_SELECTS=1");
			}
			
			return $rtnVal;
		}
		
		function ShowDBErrors()
		{
			if ($this->hideSQLErrors)
				return;
				
			global $wpdb;
			if ($wpdb->last_error == '')
				return;
				
			echo '<div id="message" class="error"><p>'.$wpdb->last_error.'</p></div>';
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

			if ($this->hideSQLErrors)
			{
				$suppress_errors = $wpdb->suppress_errors;
				$wpdb->suppress_errors = true;
			}
			$this->queryResult = $wpdb->query($sql);
			$rtnStatus = ($this->queryResult !== false);	
			if ($this->hideSQLErrors)
			{
				$wpdb->suppress_errors = $suppress_errors;				
			}
			else
			{
				$this->ShowDBErrors();
			}		
			
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
			
			$this->ShowDBErrors();
			
			return $results;
		}
		
		function show_results($results)
		{
			if (!$this->isDbgOptionSet('Dev_ShowDBOutput'))
			{				
				if ($this->isDbgOptionSet('Dev_ShowSQL'))
				{
					$entriesCount = count($results);
					echo "Database Result Entries: $entriesCount<br>\n";
					return;				
				}
				return;
			}
				
			if (function_exists('wp_get_current_user'))
			{
				if (!$this->isSysAdmin())
					return;				
			}
				
			echo "<br>Database Results:<br>\n";
			for ($i = 0; $i < count($results); $i++)
				echo "Array[$i] = " . print_r($results[$i], true) . "<br>\n";
		}
		
		function GetSQLBlockEnd($sql, $startPosn, $startChar = '(', $endChar = ')')
		{
			$posn = $startPosn;
			$len = strlen($sql);
			$matchCount = 0;
			
			while ($posn < $len)
			{
				$nxtChar = $sql[$posn];
				if ($nxtChar == $startChar)
				{
					$matchCount++;
				}
				else if ($nxtChar == $endChar)
				{
					$matchCount--;
					if ($matchCount == 0)
					{
						return $posn;
					}
				}
				$posn++;
			}
			
			return -1;
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
					
					$srchpos = 0;
					if (($bpos = strrpos($sqlDemo, '(SELECT')) !== false)
					{
						$srchpos = $this->GetSQLBlockEnd($sqlDemo, $bpos);
					}
					
					$sqlSrchCmd = '';
					if (strpos($sqlDemo, 'WHERE', $srchpos) !== false)
					{
						$sqlSrchCmd = 'WHERE';
						$sqlReplCmd = 'AND';
					}
					else if (strpos($sqlDemo, 'GROUP BY', $srchpos) !== false)
					{
						$sqlSrchCmd = $sqlReplCmd = 'GROUP BY';
					}
					else if (strpos($sqlDemo, 'ORDER BY', $srchpos) !== false)
					{
						$sqlSrchCmd = $sqlReplCmd = 'ORDER BY';
					}

					if ($sqlSrchCmd != '')
					{
						if ($srchpos > 0)
						{
							$sqlDemo = substr($sqlDemo, 0, $srchpos).str_replace($sqlSrchCmd, "$where $sqlReplCmd", substr($sqlDemo, $srchpos));
						}
						else
						{
							$sqlDemo = str_replace($sqlSrchCmd, "$where $sqlReplCmd", $sqlDemo);
						}						
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