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

if(!isset($_SESSION)) 
{
	// MJS - SC Mod - Register to use SESSIONS
	session_start();
}	

require_once "stageshowlib_utils.php";
require_once "stageshowlib_dbase_base.php";

if (!class_exists('StageShowLibDBaseClass'))
{
	if (!defined('STAGESHOWLIB_EVENTS_PER_PAGE'))
		define('STAGESHOWLIB_EVENTS_PER_PAGE', 20);
	
	define('STAGESHOWLIB_CAPABILITY_SYSADMIN', 'manage_options');

	class StageShowLibDBaseClass extends StageShowLibGenericDBaseClass // Define class
	{
		const MYSQL_DATE_FORMAT = 'Y-m-d';
		const MYSQL_TIME_FORMAT = 'H:i:s';
		const MYSQL_DATETIME_FORMAT = 'Y-m-d H:i:s';
		const MYSQL_DATETIME_NOSECS_FORMAT = 'Y-m-d H:i';
		
		const ForReading = 1;
		const ForWriting = 2;
		const ForAppending = 8;
		
		const ADMIN_SETTING = 1;
		const DEBUG_SETTING = 2;
		
		const SessionDebugPrefix = 'stageshowlib_debug_';
		
		var $optionsID;
		
		var $adminOptions;
		var $dbgOptions;
		var $pluginInfo;
		var $opts;
		
		function __construct($opts = null) //constructor		
		{
			if ( defined('STAGESHOWLIB_DEVELOPER') )
			{
				if ( !isset($_REQUEST['debugmodes']) )
					$_REQUEST['debugmodes'] = STAGESHOWLIB_DEVELOPER;
			}
			
			if ( isset($_REQUEST['debugmodes']) )
			{
				$clearDebugModes = false;
				if ($_REQUEST['debugmodes'] != '')
				{
					$debugModes = explode(',', $_REQUEST['debugmodes']);
					foreach ($debugModes as $debugMode)
					{
						switch ($debugMode)
						{
							case 'menu':
							case 'test':
							case 'trolley':
							case 'stack':
							case 'blockgateway':
								$debugMode = strtolower(self::SessionDebugPrefix.$debugMode);
								$_SESSION[$debugMode] = true;
								break;
							
							default:
								$clearDebugModes = true;
								break;
						}
					}
				}			
				else
				{
					$clearDebugModes = true;
				}
				
				if ($clearDebugModes)
				{
					$debugFlagsArray = $this->getDebugFlagsArray();
					foreach ($debugFlagsArray as $debugMode)
					{
						unset($_SESSION[$debugMode]);
					}
				}
			}

			parent::__construct($opts);
			
			$this->opts = $opts;
			$this->getOptions();
			
			$dbPrefix = $this->getTablePrefix();
			$this->DBTables = $this->getTableNames($dbPrefix);
		}

	    function uninstall()
	    {
		}
		
		function getDebugFlagsArray()
		{
			$debugFlagsArray = array();
			
			$len = strlen(self::SessionDebugPrefix);
			foreach ($_SESSION as $key => $debugMode)
			{
				if (substr($key, 0, $len) != self::SessionDebugPrefix)
					continue;
				$debugFlagsArray[] = $key;
			}
			return $debugFlagsArray;
		}
		
		function getTablePrefix()
		{
			global $wpdb;
			return $wpdb->prefix;
		}
		
		function getTableNames($dbPrefix)
		{
			$DBTables = new stdClass();
			
			return $DBTables;
		}
	
		function AddGenericFields($EMailTemplate)
		{
			$EMailTemplate = str_replace('[organisation]', $this->adminOptions['OrganisationID'], $EMailTemplate);			
			$EMailTemplate = str_replace('[url]', get_option('siteurl'), $EMailTemplate);
			
			return $EMailTemplate;
		}
		
		function GetWPNonce($referer = '')
		{
			if ($referer == '')
			{
				$caller = $this->opts['Caller'];
				$referer = plugin_basename($caller);
			}
			
			return wp_create_nonce($referer);
		}
		
		function GetWPNonceField($referer = '', $name = '_wpnonce')
		{
			return $this->WPNonceField($referer, $name, false);
		}
		
		function WPNonceField($referer = '', $name = '_wpnonce', $echo = true)
		{
			$nonceField = '';
			
			if ($referer == '')
			{
				$caller = $this->opts['Caller'];
				$referer = plugin_basename($caller);
			}
			
			if ( function_exists('wp_nonce_field') ) 
			{
				if ($this->getDbgOption('Dev_ShowWPOnce'))
					$nonceField .= "<!-- wp_nonce_field($referer) -->\n";
				$nonceField .= wp_nonce_field($referer, $name, false, false);
				$nonceField .=  "\n";
			}
			
			if ($echo) echo $nonceField;
			return $nonceField;
		}
		
		function AddParamAdminReferer($caller, $theLink)
		{
			if (!function_exists('add_query_arg'))
				return $theLink;
			
			$baseName = plugin_basename($caller);
			$nonceVal = wp_create_nonce( $baseName );

			if ($this->getDbgOption('Dev_ShowWPOnce'))
			{
				echo "\n<!-- AddParamAdminReferer  caller:$caller  baseName:$baseName  NOnce:$nonceVal -->\n";
			}
			
			$theLink = add_query_arg( '_wpnonce', $nonceVal, $theLink );
			
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
				if (!isset($_REQUEST['_wpnonce']))
					echo "<br><strong>check_admin_referer FAILED - _wpnonce NOT DEFINED</strong></br>\n";
				else 
				{
					$nOnceVal = $_REQUEST['_wpnonce'];
					if (!wp_verify_nonce($nOnceVal, $referer))
					echo "<br><strong>check_admin_referer FAILED - Referer: $referer  NOnce:$nOnceVal </strong></br>\n";
				}
				return;
			}
			
			check_admin_referer($referer);
		}

		function ActionButtonHTML($buttonText, $caller, $domainId, $buttonClass, $elementId, $buttonAction)
		{
			//if ($buttonAction == '') $buttonAction = strtolower(str_replace(" ", "", $buttonText));
			$buttonText = __($buttonText, $domainId);
			$page = $_GET['page'];
				
			$editLink = 'admin.php?page='.$page.'&action='.$buttonAction;
			if ($elementId !== 0) $editLink .= '&id='.$elementId;
			$editLink = $this->AddParamAdminReferer($caller, $editLink);
			$editControl = '<a class="button-secondary" href="'.$editLink.'">'.$buttonText.'</a>'."\n";  
			if ($buttonClass != '')
			{
				$editControl = '<div class='.$buttonClass.'>'.$editControl.'</div>'."\n";  
			}
			return $editControl;    
		}
		
		function HasSettings()
		{
			return false;
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
			
			return ($lastVersion != '');
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
		
		function get_domain()
		{
			// This function returns a default profile (for translations)
			// Descendant classes can override this if required)
			return basename(dirname(dirname(__FILE__)));
		}
		
		function get_pluginName()
		{
			return $this->get_name();
		}
		
		function get_name()
		{
			return $this->get_pluginInfo('Name');
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
		
		function ShowDebugModes()
		{
			$debugFlagsArray = $this->getDebugFlagsArray();
			asort($debugFlagsArray);
			if (count($debugFlagsArray) > 0)
			{
				echo  '<strong>'.__('Session Debug Modes', $this->get_domain()).':</strong> ';	
				$comma = '';		
				foreach ($debugFlagsArray as $debugMode)
				{
					$debugMode = str_replace(self::SessionDebugPrefix, '', $debugMode);
					echo "$comma$debugMode";
					$comma = ', ';
				}
				echo "<br>\n";
				$hasDebug = true;			
			}
			else
			{
				$hasDebug = false;			
			}
			
			if (defined('STAGESHOWLIB_BLOCK_HTTPS'))
			{
				echo  '<strong>'.__('SSL over HTTP', $this->get_domain()).":</strong> Blocked<br>\n";	
			}
			
			return $hasDebug;
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
			
			if (defined('CORONDECK_RUNASDEMO'))
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
		
		function tableExists($table_name)
		{
			global $wpdb;
			
			return ( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name );			
		}
		
		static function GetSafeString($paramId, $defaultVal = '')
		{
			$rtnVal = StageShowLibHTTPIO::GetRequestedString($paramId, $defaultVal);
			$rtnVal = self::_real_escape($rtnVal);
			return $rtnVal;
		}
		
		static function _real_escape($string) 
		{
			global $wpdb;
			return $wpdb->_real_escape($string);
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
				
				'LogsFolderPath' => 'logs',
				'PageLength' => STAGESHOWLIB_EVENTS_PER_PAGE,
				
				'Unused_EndOfList' => ''
			);
			
			$ourOptions = array_merge($ourOptions, $childOptions);
			
			// Get current values from MySQL
			$currOptions = get_option($this->opts['CfgOptionsID']);
			$this->dbgOptions = get_option($this->opts['DbgOptionsID']);
			
			// Now update defaults with values from DB
			if (!empty($currOptions))
			{
				$saveToDB = false;
				foreach ($currOptions as $key => $option)
					$ourOptions[$key] = $option;
			}
			else
			{
				// New options ... save to DB
				$saveToDB = true;
			}

			if (defined('CORONDECK_RUNASDEMO'))	// Set AdminID and EMail to current user
			{				
				global $current_user;
				get_currentuserinfo();
				$ourOptions['AdminID'] = $current_user->display_name;
				$ourOptions['AdminEMail'] = $current_user->user_email;
			}
			
			$this->adminOptions = $ourOptions;
			
			if ($saveToDB)
				$this->saveOptions();// Saving Options - in getOptions functions
				
			if ($this->getDbgOption('Dev_ShowOptions'))
			{
				StageShowLibUtilsClass::print_r($ourOptions, 'Options');
			}
			
			return $ourOptions;
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
					return '';					
			}
			
			return $optionValue;
		}
		
		function isDbgOptionSet($optionID)
		{
			$rtnVal = $this->isOptionSet($optionID, StageShowLibDBaseClass::DEBUG_SETTING);
			if ($rtnVal)
			{
				if (!defined('STAGESHOWLIB_ALWAYS_ALLOW_DEBUGOUT'))
				{
					switch ($optionID)
					{
						case 'Dev_ShowGET';
						case 'Dev_ShowPOST';
						case 'Dev_ShowSESSION':
						case 'Dev_ShowSQL';
						case 'Dev_DBOutput';
						case 'Dev_ShowTrolley';
							$rtnVal = $this->isSysAdmin();
							break;
							
						default:
							break;
					}
				}
			}
			
			return $rtnVal;
		}
		
		function isOptionSet($optionID, $optionClass = StageShowLibDBaseClass::ADMIN_SETTING)
		{
			$value = $this->getOption($optionID, $optionClass);
			if ($value == '')
				return false;
			
			return $value;
		}
		
		// Saves the admin options to the options data table
		function saveOptions()
		{
			update_option($this->opts['CfgOptionsID'], $this->adminOptions);
			update_option($this->opts['DbgOptionsID'], $this->dbgOptions);
			
			$this->SaveDBCredentials(true);
		}
		
		function dev_ShowTrolley()
		{
			$rtnVal = false;
			
			if ($this->isDbgOptionSet('Dev_ShowTrolley') || isset($_SESSION['stageshowlib_debug_trolley']))
			{
				if ($this->getDbgOption('Dev_ShowCallStack') || isset($_SESSION['stageshowlib_debug_stack']))
				{
					StageShowLibUtilsClass::ShowCallStack();
				}
				$rtnVal = true;
			}
			
			return $rtnVal;
		}
		
		function isSysAdmin()
		{
			if (!function_exists('wp_get_current_user')) 
			{
				return false;
			}
			
			if (current_user_can(STAGESHOWLIB_CAPABILITY_SYSADMIN))
			{
				return true;
			}
				
			if (defined('STAGESHOWLIB_CAPABILITY_DEVUSER') && current_user_can(STAGESHOWLIB_CAPABILITY_DEVUSER))
			{
				return true;
			}
	
			return false;
		}
		
		function createDB($dropTable = false)
		{
		}
		
		function GetDBCredentials()
		{
			$pluginURI = $this->get_pluginURI();
			
			$defines = "
	define('STAGESHOWLIB_PLUGINS_URI', '$pluginURI');
	";
	
			return $defines;
		}
		
		function AddADefine($defName, $devVal)
		{
			return "    define('$defName', '$devVal');\n";
		}
		
		function OptionsToDefines($globalVarId, $optionsList)
		{
			$globalVarId = '$'.$globalVarId;
			
			$defines = "
	global	$globalVarId; 	
	$globalVarId = array(";
			if ($optionsList != '')
			{
				foreach ($optionsList as $optionID => $optionValue)
				{
				$defines .= "
		'$optionID' => '$optionValue',";
				}				
			}
			
			$defines .= ');
			';

			return $defines;
		}
		
		function SaveDBCredentials($forceNew = false)
		{
			$credsPath = __FILE__;
			$credsPath = str_replace('plugins', 'uploads', $credsPath);
			$endPosn = strrpos($credsPath, 'include');
			$credsPath = substr($credsPath, 0, $endPosn);
			
			$dirPath = substr($credsPath, 0, $endPosn-1);
			if (!is_dir($dirPath))
			{
				mkdir($dirPath);
			}
				
			$credsPath .= 'wp-config-db.php';

			// Get Wordpress Date and Time Format
			$globalOptions = array(
				'date_format' => get_option( 'date_format' ),
				'time_format' => get_option( 'time_format' ),
			);
			
			$dbCreds = DB_NAME."-".DB_USER."-".DB_PASSWORD."-".DB_HOST."-".NONCE_KEY; 
			
			global $table_prefix;			
			if ($table_prefix != '')
				$dbCreds .= '-'.$table_prefix;
					
			foreach ($globalOptions as $globalVal)		
			{
				$dbCreds .= '-'.$globalVal;
			}
			
			if (!$forceNew && file_exists($credsPath))
			{
				if (!defined('DB_CREDS'))
				include $credsPath;
				if ($dbCreds == DB_CREDS)
					return false;
			}
			
			include 'stageshowlib_logfile.php';
			
			$phpText = '<'."?php
	
	if (!defined('DB_NAME'))
	{
		/** The name of the database for WordPress */
		define('DB_NAME', '".DB_NAME."');

		/** MySQL database username */
		define('DB_USER', '".DB_USER."');

		/** MySQL database password */
		define('DB_PASSWORD', '".DB_PASSWORD."');

		/** MySQL hostname */
		define('DB_HOST', '".DB_HOST."');		

		/** NONCE_KEY salt value */
		define('NONCE_KEY', '".NONCE_KEY."');		
	}
	
	/** Composite of all DB Credentials - Used to check if they have changed ... */
	define('DB_CREDS', '".$dbCreds."');
	";
			
			if ($table_prefix != '')
			{
				$phpText .= '
	$table_prefix = "'.$table_prefix.'";
				';				
			}

			$phpText .= $this->GetDBCredentials();
	
			$phpText .= "\n";

			$phpText .= "\n".'?'.'>'."\n";
			
			$this->LogToFile($credsPath, $phpText, StageShowLibLogFileClass::ForWriting);
		}
	}
}

?>