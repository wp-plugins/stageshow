<?php
/* 
Description: MJS Library Database Access functions
 
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

include "mjslib_utils.php";

if (!class_exists('MJSLibDBaseClass')) 
{
	if (!defined('MJSLIB_EVENTS_PER_PAGE'))
		define('MJSLIB_EVENTS_PER_PAGE', 20);
	
  class MJSLibDBaseClass // Define class
  {
		const MYSQL_DATE_FORMAT = 'Y-m-d';	
		const MYSQL_DATETIME_FORMAT = 'Y-m-d H:i:s';	

		const ForReading = 1;
		const ForWriting = 2;
		const ForAppending = 8;

		var $ordersDBTableID;
		var $optionsID;
		
		var $adminOptions;
		var $pluginInfo;		
		var $opts;
		
		function __construct($opts = null)		//constructor		
		{
			$this->opts = $opts;
			$this->getOptions();			
		}
		
		function get_pluginInfo($att = '') 
		{
			if (!isset($this->pluginInfo)) 
			{
				if ( ! function_exists( 'get_plugins' ) )
					require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
				$allPluginsInfo = get_plugins();
				if (isset($this->opts['PluginRootFilePath']))
					$basename = plugin_basename($this->opts['PluginRootFilePath']);
				else
					$basename = plugin_basename(__FILE__);
				
				for ($i=0; ($i<10) && strpos($basename, '/'); $i++)				
					$basename = dirname($basename);
				
				foreach ($allPluginsInfo as $pluginPath => $pluginInfo)
				{
					if ($basename == dirname($pluginPath))
					{
						$this->pluginInfo = $pluginInfo;
						break;
					}
				}				
			}
			
			if ($att == '') return $this->pluginInfo;
			
			return isset($this->pluginInfo[$att]) ? $this->pluginInfo[$att] : '';
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
			MJSLibUtilsClass::ShowCallStack(true, $this->getOption('Dev_CallStackParams'));
		}
		
    function ShowSQL($sql, $values = null)
    {
			if ($this->getOption('Dev_ShowSQL') <= 0) return;
			
			if ($this->getOption('Dev_ShowCallStack'))
				$this->ShowCallStack();
			
			echo "<br>$sql<br>\n"; 
			if (isset($values))
			{
				print_r($values);
				echo "<br>\n"; 
			}
		}

		function get_results($sql)
		{
			global $wpdb;
      
			$results = $wpdb->get_results($sql);			
			$this->show_results($results);
			
			return $results;
		}
		
		function show_results($results)
		{
			if ($this->getOption('Dev_ShowDBOutput') == 1) 
			{
				echo "<br>Database Results:<br>\n"; 
				for ($i=0; $i<count($results); $i++)
					echo "Array[$i] = ".print_r($results[$i], true)."<br>\n"; 
			}
		}

		//Returns an array of admin options
		function getOptions($childOptions = array()) 
		{
			// Initialise settings array with default values
			
			$ourOptions = array( 
				'ActivationCount' => 0,
				       
				'OrganisationID' => get_bloginfo('name'),
				       
        'AdminID' => '',        
        'AdminEMail' => get_bloginfo('admin_email'),
        'BccEMailsToAdmin' => true,
        'UseCurrencySymbol' => false,
        
        'EMailTemplatePath' => '',        
        
        'LogsFolderPath' => '../logs',
        'PageLength' => MJSLIB_EVENTS_PER_PAGE,
        
        'Unused_EndOfList' => ''
      );
				
			$ourOptions = array_merge($ourOptions, $childOptions);
			$this->adminOptions = $ourOptions;
			
			// Get current values from MySQL
			$currOptions = get_option($this->opts['CfgOptionsID']);
			
			// Now update defaults with values from DB
			if (!empty($currOptions)) {
				foreach ($currOptions as $key => $option)
					$this->adminOptions[$key] = $option;
			}				
			
			$this->saveOptions();
			return $this->adminOptions;
		}
    
    function getOption($optionID)
    {
			if (!isset($this->adminOptions[$optionID]))
				return '';
				
			return $this->adminOptions[$optionID];
    }
    
		// Saves the admin options to the options data table
		function saveOptions($newOptions = null) 
		{			
			if ($newOptions != null)
				$this->adminOptions = $newOptions;
				
			update_option($this->opts['CfgOptionsID'], $this->adminOptions);
		}
    
		function createDB($dropTable = false)
		{
		}
		
		function GetOurButtonsList()
		{
			return array();
		}

	}
}

?>