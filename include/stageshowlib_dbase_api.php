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

if (!function_exists('get_option'))
{
	// Wordpress not loaded - add Wordpress simulation functions
	define('STAGESHOWLIB_WP_NOTLOADED', 1);
	
	function wp_get_current_user()
	{
		$current_user = new stdClass();
		
		$current_user->user_login = '';
	    $current_user->user_email = '';
	    $current_user->user_firstname = '';
	    $current_user->user_lastname = '';
	    $current_user->display_name = '';
	    $current_user->ID = 0;
		
		return $current_user;
	}

	function current_user_can($cap)
	{
		//echo "Check current_user_can($cap)<br>\n";
		return isset($_SESSION['Capability_'.$cap]) && $_SESSION['Capability_'.$cap];
	}

	function get_option( $option, $default = false )
	{
		global $stageshowlibGlobalOptions;
		
		if (!isset($stageshowlibGlobalOptions[$option]))
		{
			global $wpdb;

			// Get values from Database
			$optionsTable = $wpdb->prefix.'options';
			$sql  = "SELECT option_value FROM $optionsTable ";
			$sql .= 'WHERE option_name="'.$option.'"';
			
			$optionsInDB = $wpdb->get_results($sql);
			if (count($optionsInDB) == 1)
			{
				$optionsVal = $optionsInDB[0]->option_value;
				$unserializedVal = @unserialize($optionsVal);
				if ($unserializedVal !== false)
					$stageshowlibGlobalOptions[$option] = $unserializedVal;
				else
					$stageshowlibGlobalOptions[$option] = $optionsVal;
			}
		}

		return isset($stageshowlibGlobalOptions[$option]) ? $stageshowlibGlobalOptions[$option] : '';
	}
	
	function add_filter()
	{		
	}
	
	function add_action()
	{		
	}
	
	function add_shortcode()
	{		
	}
	
	function register_activation_hook()
	{		
	}

	function register_deactivation_hook()
	{		
	}

	function get_bloginfo()
	{
		return '';
	}
	
	function shortcode_atts( $pairs, $atts, $shortcode = '' )
	{
		// TODO - JQUERY Trolley - Deal with shortcode atts
		$atts = array_merge($atts, $pairs);
		return $atts;
	}
}

require_once STAGESHOWLIB_INCLUDE_PATH.'stageshowlib_utils.php';
require_once STAGESHOWLIB_INCLUDE_PATH.'stageshowlib_dbase_base.php';

if (!class_exists('StageShowLibDBaseClass'))
{
	if (!defined('STAGESHOWLIB_EVENTS_PER_PAGE'))
		define('STAGESHOWLIB_EVENTS_PER_PAGE', 20);
	
	if (!defined('STAGESHOWLIB_CAPABILITY_SYSADMIN'))
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
		
		var	$buttonImageURLs = array();
	
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
					
			if ( defined('STAGESHOWLIB_WP_NOTLOADED') )
			{
				$this->pluginInfo = $this->adminOptions['pluginInfo'];
			}
					
			$dbPrefix = $this->getTablePrefix();
			$this->DBTables = $this->getTableNames($dbPrefix);
		}

	    function uninstall()
	    {
		}
		
		function AllUserCapsToServervar()
		{
			$this->UserCapToServervar(STAGESHOWLIB_CAPABILITY_SYSADMIN);
		}
		
		function UserCapToServervar($capability)
		{
			$_SESSION['Capability_'.$capability] = current_user_can($capability);
		}
		
		function IfButtonHasURL($buttonID)
		{
			$ourButtonURL = $this->ButtonURL($buttonID);
			if ($ourButtonURL == '')
				return false;
			
			return true;
		}
		
		function ButtonHasURL($buttonID, &$buttonURL)
		{
			$ourButtonURL = $this->ButtonURL($buttonID);
			if ($ourButtonURL == '')
				return false;
			
			$buttonURL = $ourButtonURL;	
			return true;
		}
		
		function ButtonURL($buttonID)
		{
			if (self::IsInWP())
			{
				if (!isset($this->buttonImageURLs[$buttonID])) return '';				
				return $this->buttonImageURLs[$buttonID];	
			}
			else
			{
				global	$stageshowlibButtonURLs;
				
				if (!isset($stageshowlibButtonURLs[$buttonID])) return '';				
				return $stageshowlibButtonURLs[$buttonID];	
			}
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

		function ActionButtonHTML($buttonText, $caller, $domainId, $buttonClass, $elementId, $buttonAction, $extraParams = '', $target = '')
		{
			//if ($buttonAction == '') $buttonAction = strtolower(str_replace(" ", "", $buttonText));
			$buttonText = __($buttonText, $domainId);
			$page = $_GET['page'];
			
			$buttonId = $domainId.'-'.$buttonAction.'-'.$elementId;
			
			$editLink = 'admin.php?page='.$page.'&action='.$buttonAction;
			if ($elementId !== 0) $editLink .= '&id='.$elementId;
			$editLink = $this->AddParamAdminReferer($caller, $editLink);
			if ($extraParams != '') $editLink .= '&'.$extraParams;
			if ($target != '') $target = 'target='.$target;
			
			$editControl = "<a id=$buttonId name=$buttonId $target".' class="button-secondary" href="'.$editLink.'">'.$buttonText.'</a>'."\n";  
			if ($buttonClass != '')
			{
				$editControl = '<div class='.$buttonClass.'>'.$editControl.'</div>'."\n";  
			}
			return $editControl;    
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
		
		function GetInsertId()
		{
			global $wpdb;
			
			return $wpdb->insert_id;
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
		
		function getOptionsFromDB()
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
			
			// Get current values from MySQL
			$currOptions = get_option($this->opts['CfgOptionsID']);
			$this->dbgOptions = get_option($this->opts['DbgOptionsID']);
			
			return $currOptions;
		}
		
		function getOptions($childOptions = array())
		{			
			if (defined('STAGESHOWLIB_WP_NOTLOADED'))
			{
				$currOptions = $this->getOptionsFromDB();
				$this->adminOptions = $currOptions;
				return $currOptions;
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
			$currOptions = $this->getOptionsFromDB();
			
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
				if (!function_exists('get_currentuserinfo'))
				{
					require_once( ABSPATH . WPINC . '/pluggable.php' );
				}
				global $current_user;
				get_currentuserinfo();
				$ourOptions['AdminID'] = $current_user->display_name;
				$ourOptions['AdminEMail'] = $current_user->user_email;
			}
			
			$this->pluginInfo['Name'] = $this->get_name();
			$this->pluginInfo['Version'] = $this->get_version();
			$this->pluginInfo['Author'] = $this->get_author();
			$this->pluginInfo['PluginURI'] = $this->get_pluginURI();
			$ourOptions['pluginInfo'] = $this->pluginInfo;
			
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
			return $this->getOption($optionID, self::DEBUG_SETTING);
		}
		
		function getOption($optionID, $optionClass = self::ADMIN_SETTING)
		{
			$isInWP = self::IsInWP();

			switch ($optionClass)
			{
				case self::ADMIN_SETTING: 
					$options = $this->adminOptions;
					break;

				case self::DEBUG_SETTING: 
					$options = $this->dbgOptions;
					break;
				
				default:
					return;					
			}
			
			$optionVal = '';		
			if (isset($options[$optionID]))
			{
				$optionVal = $options[$optionID];
			}

			return $optionVal;
		}
		
		function setOption($optionID, $optionValue, $optionClass = self::ADMIN_SETTING)
		{
			switch ($optionClass)
			{
				case self::ADMIN_SETTING: 
					$this->adminOptions[$optionID] = $optionValue;
					break;

				case self::DEBUG_SETTING: 
					$this->dbgOptions[$optionID] = $optionValue;
					break;
				
				default:
					return '';					
			}
			
			return $optionValue;
		}
		
		function isDbgOptionSet($optionID)
		{
			$rtnVal = $this->isOptionSet($optionID, self::DEBUG_SETTING);
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
		
		function isOptionSet($optionID, $optionClass = self::ADMIN_SETTING)
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
		
		function clearAll()
		{
			delete_option($this->opts['CfgOptionsID']);
			delete_option($this->opts['DbgOptionsID']);
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
	
			if (defined('CORONDECK_RUNASDEMO')) $defines .= "
	if (!defined('CORONDECK_RUNASDEMO'))
		define('CORONDECK_RUNASDEMO', '".CORONDECK_RUNASDEMO."');
	";
	
			if (defined('STAGESHOWLIB_DATETIME_BOXOFFICE_FORMAT')) $defines .= "
	if (!defined('STAGESHOWLIB_DATETIME_BOXOFFICE_FORMAT'))
		define('STAGESHOWLIB_DATETIME_BOXOFFICE_FORMAT', '".STAGESHOWLIB_DATETIME_BOXOFFICE_FORMAT."');
	";
	
			return $defines;
		}
		
		function ArrayValsToDefine($optionsList, $indent = '    ')
			{
			$defines = " array(\n";
				foreach ($optionsList as $optionID => $optionValue)
				{
				if (is_array($optionValue))
				{
					$optionValue = $this->ArrayValsToDefine($optionValue, $indent.'    ');			
				}				
				else
				{
					$optionValue = "'$optionValue'";
				}
				$defines .= "$indent'$optionID' => $optionValue,\n";
			}
			
			$defines .= "$indent)";			
								
			return $defines;
		}
		
		function OptionsToDefines($globalVarId, $optionsList)
		{
			$optionID = '$'.$globalVarId;
			
			$defines = '$'.$globalVarId." = ";
					
			$defines .= $this->ArrayValsToDefine($optionsList).";\n\n";			

			return $defines;
		}
		
		function SaveDBCredentials($forceNew = false)
		{
			$credsFolder = __FILE__;
			$credsFolder = str_replace('plugins', 'uploads', $credsFolder);
			$endPosn = strrpos($credsFolder, 'include');
			$credsFolder = substr($credsFolder, 0, $endPosn-1);
			
			if (!is_dir($credsFolder))
			{
				mkdir($credsFolder);
				$forceNew = true;
			}
				
			$credsFile = 'wp-config-db.php';
			$credsPath = $credsFolder.'/'.$credsFile;

			// Get last modified date/time of wp-config.php file 
			// ... then use it to check if config may have changed
			$endPosn = strrpos($credsFolder, 'wp-content');
			$wpconfigPath = substr($credsFolder, 0, $endPosn).'wp-config.php';
			
			// Get Wordpress Date and Time Format
			$globalOptions = array(
				'date_format' => self::GetDateFormat(),
				'time_format' => self::GetTimeFormat(),
				'datetime_format' => self::GetDateTimeFormat(),
			);
			
			$configMarker = filemtime($wpconfigPath); 
			
			foreach ($globalOptions as $globalVal)		
			{
				$configMarker .= '-'.$globalVal;
			}
			
			if (!$forceNew && file_exists($credsPath))
			{
				if (!defined('STAGESHOWLIB_CONFIG_STAMP'))
				include $credsPath;
				if ($configMarker == STAGESHOWLIB_CONFIG_STAMP)
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
	
	/** Composite of all Config Elements - Used to check if they have changed ... */
	define('STAGESHOWLIB_CONFIG_STAMP', '".$configMarker."');
	";
			
			if (defined('STAGESHOWLIB_TROLLEYID'))
			{
				
			$phpText .= "	
	if (!defined('STAGESHOWLIB_TROLLEYID'))
	{
		/** The name of the database for WordPress */
		define('STAGESHOWLIB_TROLLEYID', '".STAGESHOWLIB_TROLLEYID."');
	}
	";
	
			}
			
			global $table_prefix;			

			if ($table_prefix != '')
			{
				$phpText .= '
	$table_prefix = "'.$table_prefix.'";
				';				
			}

			$phpText .= $this->GetDBCredentials();
			$phpText .= "\n";

			$phpText .= $this->OptionsToDefines('stageshowlibGlobalOptions', $globalOptions);
			$phpText .= $this->OptionsToDefines('stageshowlibButtonURLs', $this->buttonImageURLs);

			$phpText .= "\n";

			$phpText .= "\n".'?'.'>'."\n";
			
			$logFileObj = new StageShowLibLogFileClass($credsFolder);
			$logFileObj->LogToFile($credsFile, $phpText, StageShowLibDBaseClass::ForWriting);
		}
		
		static function GetTimeFormat()
		{
			if (defined('STAGESHOWLIB_TIME_BOXOFFICE_FORMAT'))
				$timeFormat = STAGESHOWLIB_TIME_BOXOFFICE_FORMAT;
			else
				// Use Wordpress Time Format
				$timeFormat = get_option( 'time_format' );
				
			return $timeFormat;
		}

		static function GetDateFormat()
		{
			if (defined('STAGESHOWLIB_DATE_BOXOFFICE_FORMAT'))
				$dateFormat = STAGESHOWLIB_DATE_BOXOFFICE_FORMAT;
			else
				// Use Wordpress Date Format
				$dateFormat = get_option( 'date_format' );
				
			return $dateFormat;
		}

		static function GetDateTimeFormat()
		{
			if (defined('STAGESHOWLIB_DATETIME_BOXOFFICE_FORMAT'))
				$dateFormat = STAGESHOWLIB_DATETIME_BOXOFFICE_FORMAT;
			else
				// Use Wordpress Date and Time Format
				$dateFormat = get_option( 'date_format' ).' '.get_option( 'time_format' );
				
			return $dateFormat;
		}
		
		function enqueue_style( $handle, $src = false, $deps = array(), $ver = false, $media = 'all' )
		{
			if ($this->isDbgOptionSet('Dev_DisableJSCache')) $ver = time();			
			
			wp_enqueue_style($handle, $src, $deps, $ver, $media);
		}
		
		function enqueue_script($handle, $src = false, $deps = array(), $ver = false, $in_footer = false)
		{
			if ($this->isDbgOptionSet('Dev_DisableJSCache')) $ver = time();			
			
			wp_enqueue_script($handle, $src, $deps, $ver, $in_footer);
		}
	}
}

?>