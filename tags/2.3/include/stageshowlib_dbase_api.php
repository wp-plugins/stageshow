<?php
/* 
Description: Core Library Database Access functions

Copyright 2012 Malcolm Shergold

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

if (!class_exists('StageShowLibDBaseClass'))
{
	if (!defined('STAGESHOWLIB_EVENTS_PER_PAGE'))
		define('STAGESHOWLIB_EVENTS_PER_PAGE', 20);
	
	define('STAGESHOWLIB_CAPABILITY_SYSADMIN', 'manage_options');

	class StageShowLibDBaseClass // Define class
	{
		const MYSQL_DATE_FORMAT = 'Y-m-d';
		const MYSQL_DATETIME_FORMAT = 'Y-m-d H:i:s';
		
		const ForReading = 1;
		const ForWriting = 2;
		const ForAppending = 8;
		
		const ADMIN_SETTING = 1;
		const DEBUG_SETTING = 2;
		
		var $ordersDBTableID;
		var $optionsID;
		
		var $adminOptions;
		var $dbgOptions;
		var $pluginInfo;
		var $opts;
		
		function __construct($opts = null) //constructor		
		{
			$this->opts = $opts;
			$this->getOptions();
			
			if (defined('STAGESHOWLIB_RUNASDEMO'))
			{
				$this->GetLoginID();
			}
		}

	    function uninstall()
	    {
		}
		
		function WPNonceField($referer = '')
		{
			if ($referer == '')
			{
				$caller = $this->opts['Caller'];
				$referer = plugin_basename($caller);
			}
			
			if ( function_exists('wp_nonce_field') ) 
			{
				if ($this->getDbgOption('Dev_ShowWPOnce'))
					echo "<!-- wp_nonce_field($referer) -->\n";
				wp_nonce_field($referer);
				echo "\n";
			}
		}
		
		static function AddParamAdminReferer($caller, $theLink)
		{
			if (function_exists('add_query_arg'))
			{
				$theLink = add_query_arg( '_wpnonce', wp_create_nonce( plugin_basename($caller) ), $theLink );
			}
			return $theLink;
		}
		
		function CheckAdminReferer($referer = '')
		{
			if ($referer == '')
			{
				$caller = $this->opts['Caller'];
				$referer = plugin_basename($caller);
			}
			
			if ($this->getDbgOption('Dev_ShowWPOnce'))
			{
				echo "<!-- check_admin_referer($referer) -->\n";
				if (!wp_verify_nonce($_REQUEST['_wpnonce'], $referer))
					echo "<br><strong>check_admin_referer FAILED - verifyResult: $verifyResult - Referer: $referer </strong></br>\n";
				return;
			}
			
			check_admin_referer($referer);
		}

		function HasSettings()
		{
			return false;
		}
		
		function GetLoginID()
		{
			if (isset($this->loginID))
				return $this->loginID;
				
			if (!function_exists('get_currentuserinfo'))
			{
				require_once( ABSPATH . WPINC . '/pluggable.php' );
			}
			global $current_user;
				
      		get_currentuserinfo();

			$this->loginID = $current_user->user_login;
			return $this->loginID;
		}
		
		function DeleteCapability($capID)
		{
			if (!isset($wp_roles))
			{
				$wp_roles = new WP_Roles();
				$wp_roles->use_db = true;
			}
			
			// Get all roles
			global $wp_roles;
			$roleIDs = $wp_roles->get_names();
			foreach ($roleIDs as $roleID => $publicID) 
			$wp_roles->remove_cap($roleID, $capID);
		}
		
		function checkVersion()
		{
			// Check if updates required
			
			// Get current version from Wordpress API
			$currentVersion = $this->get_name().'-'.$this->get_version();
			
			// Get last known version from adminOptions
			$lastVersion = $this->adminOptions['LastVersion'];
			
			// Compare versions
			if ($currentVersion === $lastVersion)
				return false;
			
			// Save current version to options
			$this->adminOptions['LastVersion'] = $currentVersion;
			$this->saveOptions();
			return true;
		}
		
		function get_pluginInfo($att = '')
		{
			if (!isset($this->pluginInfo))
			{
				if (!function_exists('get_plugins'))
					require_once(ABSPATH . 'wp-admin/includes/plugin.php');
				$allPluginsInfo = get_plugins();				
				if (isset($this->opts['PluginFolder']))
				{
					$basename = $this->opts['PluginFolder'];
				}
				else
				{
					$basename = plugin_basename(__FILE__);
					for ($i = 0; ($i < 10) && strpos($basename, '/'); $i++)
						$basename = dirname($basename);
				}
								
				foreach ($allPluginsInfo as $pluginPath => $pluginInfo)
				{
					if ($basename == dirname($pluginPath))
					{
						$this->pluginInfo = $pluginInfo;
						break;
					}
				}
			}
			
			if ($att == '')
				return $this->pluginInfo;
			
			return isset($this->pluginInfo[$att]) ? $this->pluginInfo[$att] : '';
		}
		
		function GetURL($optionURL)
		{
			// If URL contains a : treat is as an absolute URL
			if (strpos($optionURL, ':') !== false)
				$rtnVal = $optionURL;
			else if (strpos($optionURL, '{pluginpath}') !== false)
			{
				$pluginName = basename(dirname(dirname(__FILE__)));
				$rtnVal = str_replace('{pluginpath}', WP_PLUGIN_URL.'/'.$pluginName, $optionURL);
			}
			else
				$rtnVal = get_site_url().'/'.$optionURL;
			return $rtnVal;
		}
						
		function get_domain()
		{
			// This function returns a default profile (for translations)
			// Descendant classes can override this if required)
			return basename(dirname(dirname(__FILE__)));
		}
		
		function get_name()
		{
			return str_replace('Plus', '+', $this->get_pluginInfo('Name'));
		}
		
		function get_version()
		{
			return $this->get_pluginInfo('Version');
		}
		
		function get_author()
		{
			return $this->get_pluginInfo('Author');
		}
		
		function get_pluginURI()
		{
			return $this->get_pluginInfo('PluginURI');
		}
		
		function ShowCallStack()
		{
			StageShowLibUtilsClass::ShowCallStack(true, $this->getDbgOption('Dev_CallStackParams'));
		}
		
		function ShowSQL($sql, $values = null)
		{			
			if ($this->getDbgOption('Dev_ShowSQL') <= 0)
				return;
			
			if (function_exists('wp_get_current_user')) 
			{
				if (!current_user_can(STAGESHOWLIB_CAPABILITY_SYSADMIN))
				return;
			}
				
			if ($this->getDbgOption('Dev_ShowCallStack'))
				$this->ShowCallStack();
			
			$sql = str_replace("\n", "<br>\n", $sql);
			echo "<br>$sql<br>\n";
			if (isset($values))
			{
				print_r($values);
				echo "<br>\n";
			}
		}
		
		function createDBTable($table_name, $tableIndex, $dropTable = false)
		{
			if ($dropTable)
				$this->DropTable($table_name);

			$sql  = "CREATE TABLE ".$table_name.' (';
			$sql .= $tableIndex.' INT UNSIGNED NOT NULL AUTO_INCREMENT, ';
			$sql .= $this->getTableDef($table_name);
			$sql .= 'UNIQUE KEY '.$tableIndex.' ('.$tableIndex.')
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;';
			
			//excecute the query
			$this->dbDelta($sql);	
		}
					
		function getTableDef($tableName)
		{
			$sql = "";
			
			if (defined('STAGESHOWLIB_RUNASDEMO'))
			{
				$sql = '
					loginID VARCHAR(50) NOT NULL DEFAULT "",';
			}	
						
			return $sql;
		}
		
		function dbDelta($sql)
		{
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			
			// Remove any blank lines - dbDelta is fussy and doesn't like them ...'
			$sql = preg_replace('/^[ \t]*[\r\n]+/m', '', $sql);
			$this->ShowSQL($sql);
			dbDelta($sql);
		}
		
		function query($sql)
		{
			global $wpdb;
			
			if (defined('STAGESHOWLIB_RUNASDEMO'))
			{
				$sql = $this->SQLForDemo($sql);
			}	
			
			$this->ShowSQL($sql);
			return $wpdb->query($sql);			
		}
		
		function CheckVersionNumber($stockRec)
		{
		}
			
		function getColumnSpec($table_name, $colName)
		{
			$sql = "SHOW COLUMNS FROM $table_name WHERE field = '$colName'";			 

			$typesArray = $this->get_results($sql);

			return isset($typesArray[0]) ? $typesArray[0] : '';
		}
		
		function deleteColumn($table_name, $colName)
		{
 			$sql = "ALTER TABLE $table_name DROP $colName";

			$this->query($sql);	
			return "OK";							
		}
		
		function IfColumnExists($table_name, $colName)
		{
			$colSpec = $this->getColumnSpec($table_name, $colName);
			return (isset($colSpec->Field));
		}
		
		function DropTable($table_name)
		{
			global $wpdb;
			
			$sql = "DROP TABLE IF EXISTS $table_name";
			$this->ShowSQL($sql);
			$wpdb->query($sql);			
		}
		
		function has_results($sql)
		{
			$results = $this->get_results($sql);
			return true;
			
			return count($results > 0);
		}
		
		function get_results($sql, $debugOutAllowed = true, $sqlFilters = array())
		{
			global $wpdb;
			
			if (defined('STAGESHOWLIB_RUNASDEMO'))
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
			if ($this->getDbgOption('Dev_ShowDBOutput') != 1)
				return;
				
			if (function_exists('wp_get_current_user'))
			{
				if (!current_user_can(STAGESHOWLIB_CAPABILITY_SYSADMIN))
					return;				
			}
				
			echo "<br>Database Results:<br>\n";
			for ($i = 0; $i < count($results); $i++)
				echo "Array[$i] = " . print_r($results[$i], true) . "<br>\n";
		}
		
		function SQLForDemo($sql)
		{
			if (!defined('STAGESHOWLIB_RUNASDEMO'))
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
				
				case 'LOCK':
				case 'UNLOCK':
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
		
		//Returns an array of admin options
		function getOptions($childOptions = array())
		{
			if (!isset($this->opts['CfgOptionsID']))
			{
				echo 'CfgOptionsID must be defined<br>';
				exit;
			}
			
			if (!isset($this->opts['DbgOptionsID']))
			{
				echo 'DbgOptionsID must be defined<br>';
				exit;
			}
			
			// Initialise settings array with default values
			
			$ourOptions = array(
				'ActivationCount' => 0,
				'LastVersion' => '',
				
				'OrganisationID' => get_bloginfo('name'),
				
				'AdminID' => '',
				'AdminEMail' => get_bloginfo('admin_email'),
				'BccEMailsToAdmin' => true,
				'UseCurrencySymbol' => false,
				
				'EMailTemplatePath' => '',
				
				'LogsFolderPath' => '../logs',
				'PageLength' => STAGESHOWLIB_EVENTS_PER_PAGE,
				
				'Unused_EndOfList' => ''
			);
			
			$ourOptions         = array_merge($ourOptions, $childOptions);
			$this->adminOptions = $ourOptions;
			
			// Get current values from MySQL
			$currOptions = get_option($this->opts['CfgOptionsID']);
			$this->dbgOptions = get_option($this->opts['DbgOptionsID']);
			
			// Now update defaults with values from DB
			if (!empty($currOptions))
			{
				foreach ($currOptions as $key => $option)
					$this->adminOptions[$key] = $option;
			}
			
			if (defined('STAGESHOWLIB_RUNASDEMO'))
			{				
				global $current_user;
				get_currentuserinfo();
				$this->adminOptions['AdminID'] = $current_user->display_name;
				$this->adminOptions['AdminEMail'] = $current_user->user_email;
			}
			
			$this->saveOptions();
			return $this->adminOptions;
		}
		
		function GetAllSettingsList()
		{			
			$ourOptions = $this->getOptions();
			//StageShowLibUtilsClass::print_r($ourOptions, 'ourOptions');
			
			$current = new stdClass;

			foreach ($ourOptions as $key => $value)
			{
				$current->$key = $value;				
			}
			
			$settingsList[0] = $current;
			//StageShowLibUtilsClass::print_r($settingsList, 'settingsList');
			return $settingsList;
		}
		
		function getDbgOption($optionID)
		{
			return $this->getOption($optionID, StageShowLibDBaseClass::DEBUG_SETTING);
		}
		
		function setDbgOption($optionID, $optionValue)
		{
			return $this->setOption($optionID, $optionValue, StageShowLibDBaseClass::DEBUG_SETTING);
		}
		
		function getOption($optionID, $optionClass = StageShowLibDBaseClass::ADMIN_SETTING)
		{
			switch ($optionClass)
			{
				case StageShowLibDBaseClass::ADMIN_SETTING: 
					$options = $this->adminOptions;
					break;

				case StageShowLibDBaseClass::DEBUG_SETTING: 
					$options = $this->dbgOptions;
					break;
				
				default:
					return;					
			}
			
			if (!isset($options[$optionID]))
				return '';
			
			$optionVal = $options[$optionID];
			return $optionVal;
		}
		
		function setOption($optionID, $optionValue, $optionClass = StageShowLibDBaseClass::ADMIN_SETTING)
		{
			switch ($optionClass)
			{
				case StageShowLibDBaseClass::ADMIN_SETTING: 
					$this->adminOptions[$optionID] = $optionValue;
					break;

				case StageShowLibDBaseClass::DEBUG_SETTING: 
					$this->dbgOptions[$optionID] = $optionValue;
					break;
				
				default:
					return;					
			}
			
			return $optionVal;
		}
		
		function isDbgOptionSet($optionID)
		{
			return $this->isOptionSet($optionID, StageShowLibDBaseClass::DEBUG_SETTING);
		}
		
		function isOptionSet($optionID, $optionClass = StageShowLibDBaseClass::ADMIN_SETTING)
		{
			$value = $this->getOption($optionID, $optionClass);
			if ($value == '')
				return false;
			
			return $value;
		}
		
		// Saves the admin options to the options data table
		function saveOptions($newOptions = null)
		{
			// Update admin Options first?
			if ($newOptions != null)
				$this->adminOptions = $newOptions;
			
			update_option($this->opts['CfgOptionsID'], $this->adminOptions);
			update_option($this->opts['DbgOptionsID'], $this->dbgOptions);
		}
		
		function createDB($dropTable = false)
		{
		}
		
	}
}

?>