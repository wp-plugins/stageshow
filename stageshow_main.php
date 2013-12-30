<?php
/* 
Description: StageShow Plugin Top Level Code
 
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

include 'stageshow_sales.php';
	
if (!class_exists('StageShowPluginClass')) 
{
	class StageShowPluginClass extends StageShowSalesPluginClass 
	{
		var $ourPluginName;
		var $myDBaseObj;
		var	$env;
		
		var	$adminClassFilePrefix;
		var $adminClassPrefix;
		
		function __construct($caller)		 
		{
			$myDBaseObj = $this->CreateDBClass($caller);
			
			$myDBaseObj->testModeEnabled = file_exists(STAGESHOW_TEST_PATH.'stageshow_testsettings.php');
			$this->myDBaseObj = $myDBaseObj;
					
			parent::__construct();
			
			//Actions
			register_activation_hook( $caller, array(&$this, 'activate') );
			register_deactivation_hook( $caller, array(&$this, 'deactivate') );
				
			add_action('wp_print_styles', array(&$this, 'load_user_styles') );
			add_action('wp_print_scripts', array(&$this, 'load_user_scripts') );
			
			//add_action('wp_enqueue_scripts', array(&$this, 'load_user_scrips') );
			add_action('admin_enqueue_scripts', array(&$this, 'load_admin_styles') );
			
			// Add a reference to the header
			add_action('wp_head', array(&$this, 'OutputMetaTag'));
			
			$this->myDBaseObj->pluginSlug = 'stageshow';
			$this->adminClassFilePrefix = 'stageshow';
			$this->adminClassPrefix = 'StageShow';
			
			$this->env = array(
			    'caller' => $caller,
			    'PluginObj' => $this,
			    'DBaseObj' => $this->myDBaseObj,
			    'Domain' => $this->myDomain,
			);

			$this->getStageshowOptions();
			
			$myDBaseObj = $this->myDBaseObj;
			//$this->pluginName = $myDBaseObj->get_name();
			
			//Actions
			add_action('admin_menu', array(&$this, 'StageShow_ap'));
		  
			add_action('init', array(&$this, 'init'));
		  
			if ($myDBaseObj->checkVersion())
			{
				// FUNCTIONALITY: Main - Call "Activate" on plugin update
				// Versions are different ... call activate() to do any updates
				$this->activate();
			}			
		}
		
		function CreateDBClass($caller)
		{					
			return new StageShowDBaseClass($caller);		
		}
		
		function load_user_styles() 
		{
			//Add Style Sheet
			wp_enqueue_style(STAGESHOW_CODE_PREFIX, STAGESHOW_STYLESHEET_URL); // StageShow core style
		}
		
		//Returns an array of admin options
		function getStageshowOptions() 
		{
			$myDBaseObj = $this->myDBaseObj;
			
			$myDBaseObj->setPayPalCredentials(STAGESHOW_PAYPAL_IPN_NOTIFY_URL);
			
			return $myDBaseObj->adminOptions;
		}
		// Saves the admin options to the options data table
		
		function saveStageshowOptions()
		{
			$myDBaseObj = $this->myDBaseObj;
			$myDBaseObj->setPayPalCredentials(STAGESHOW_PAYPAL_IPN_NOTIFY_URL);
			$myDBaseObj->saveOptions();
		}
		
		// ----------------------------------------------------------------------
		// Activation / Deactivation Functions
		// ----------------------------------------------------------------------
		
		function activate()
		{
			$myDBaseObj = $this->myDBaseObj;
      
	  		// FUNCTIONALITY: Activate - Add defaults to options that are not set
			$defaultOptions = $myDBaseObj->GetDefaultOptions();
			foreach ($defaultOptions as $optionKey => $optionValue)
			{
				// Add default values to settings that are not already set
				if (!isset($myDBaseObj->adminOptions[$optionKey]) || ($myDBaseObj->adminOptions[$optionKey] == ''))
					$myDBaseObj->adminOptions[$optionKey] = $optionValue;
			}
			
			// Bump the activation counter
			$myDBaseObj->adminOptions['ActivationCount']++;
			
			$LogsFolder = ABSPATH . '/' . $myDBaseObj->adminOptions['LogsFolderPath'];
			if (!is_dir($LogsFolder))
				mkdir($LogsFolder, 0644, TRUE);

	  		// FUNCTIONALITY: Activate - Set EMail template to file name ONLY
			// EMail Template defaults to templates folder - remove folders from path
			$myDBaseObj->CheckEmailTemplatePath('EMailTemplatePath', STAGESHOW_ACTIVATE_EMAIL_TEMPLATE_PATH);
			
      		$this->saveStageshowOptions();
      
			$setupUserRole = $myDBaseObj->adminOptions['SetupUserRole'];

	  		// FUNCTIONALITY: Activate - Add Capabilities
			// Add capability to submit events to all default users
			$adminRole = get_role($setupUserRole);
			if ( !empty($adminRole) ) 
			{
				// Adding Manage StageShow Capabilities to Administrator					
				if (!$adminRole->has_cap(STAGESHOW_CAPABILITY_RESERVEUSER))
					$adminRole->add_cap(STAGESHOW_CAPABILITY_RESERVEUSER);
				if (!$adminRole->has_cap(STAGESHOW_CAPABILITY_VALIDATEUSER))
					$adminRole->add_cap(STAGESHOW_CAPABILITY_VALIDATEUSER);
				if (!$adminRole->has_cap(STAGESHOW_CAPABILITY_SALESUSER))
					$adminRole->add_cap(STAGESHOW_CAPABILITY_SALESUSER);
				if (!$adminRole->has_cap(STAGESHOW_CAPABILITY_ADMINUSER))
					$adminRole->add_cap(STAGESHOW_CAPABILITY_ADMINUSER);
				if (!$adminRole->has_cap(STAGESHOW_CAPABILITY_SETUPUSER))
					$adminRole->add_cap(STAGESHOW_CAPABILITY_SETUPUSER);
				if (!$adminRole->has_cap(STAGESHOW_CAPABILITY_VIEWSETTINGS))
					$adminRole->add_cap(STAGESHOW_CAPABILITY_VIEWSETTINGS);
			}				
			
      		$myDBaseObj->upgradeDB();
		}

	    function deactivate()
	    {
	    }

		function init()
		{
			$myDBaseObj = $this->myDBaseObj;
			$myDBaseObj->init($this->env['caller']);
			
		    $domain = 'stageshow';
			
	  		// FUNCTIONALITY: Runtime - Load custom language file in core language folder
		    // The "plugin_locale" filter is also used in load_plugin_textdomain()
		    $locale = apply_filters('plugin_locale', get_locale(), 'stageshow');
			$subFolder = '/';	// '/stageshow/';
			$langFilePath = WP_LANG_DIR.$subFolder.$domain.'-'.$locale.'.mo';
		    load_textdomain($domain, $langFilePath);
	
	  		// FUNCTIONALITY: Runtime - Load common language files
			$langRelPath = STAGESHOW_LANG_RELPATH;
			load_plugin_textdomain($domain, false, $langRelPath);
			
			// Get plugin version number
			wp_update_plugins();

			// TODO - Detect changes to plugin version number			
		}

 		function OutputMetaTag()
		{
			$myDBaseObj = $this->myDBaseObj;
			
	  		// FUNCTIONALITY: Runtime - Output StageShow Meta Tag
			// Get Version Number
			$pluginID = $myDBaseObj->get_name();
			$pluginVer = $myDBaseObj->get_version();
			$boxofficeURL = $myDBaseObj->getOption('boxofficeURL');
			
			echo "\n<meta name='$pluginID' content='$pluginID for WordPress by Malcolm Shergold - Ver:$pluginVer - BoxOfficeURL:$boxofficeURL' />\n";						
		}
		
		function CreateSample()
		{
			$myDBaseObj = $this->myDBaseObj;
			$this->saveStageshowOptions();
			$myDBaseObj->CreateSample();
		}
		
		function printAdminPage() 
		{
			//Outputs an admin page
      			
			$myDBaseObj = $this->myDBaseObj;					
			
			$pageSubTitle = $_GET['page'];			
      		switch ($pageSubTitle)
      		{
				case STAGESHOW_MENUPAGE_ADMINMENU:
				case STAGESHOW_MENUPAGE_OVERVIEW:
				default :
					include 'admin/'.$this->adminClassFilePrefix.'_manage_overview.php';
					$classId = $this->adminClassPrefix.'OverviewAdminClass';
					new $classId($this->env);
					break;
					
        		case STAGESHOW_MENUPAGE_SHOWS:
					include 'admin/'.$this->adminClassFilePrefix.'_manage_shows.php';     
					$classId = $this->adminClassPrefix.'ShowsAdminClass';
					new $classId($this->env);
          			break;
          
        		case STAGESHOW_MENUPAGE_PERFORMANCES :
					include 'admin/'.$this->adminClassFilePrefix.'_manage_performances.php';
					$classId = $this->adminClassPrefix.'PerformancesAdminClass';
					new $classId($this->env);
					break;
					
				case STAGESHOW_MENUPAGE_PRICES :
					include 'admin/'.$this->adminClassFilePrefix.'_manage_prices.php';      
					$classId = $this->adminClassPrefix.'PricesAdminClass';
					new $classId($this->env);
					break;
					
				case STAGESHOW_MENUPAGE_SALES :
					include 'admin/'.$this->adminClassFilePrefix.'_manage_sales.php';
					$classId = $this->adminClassPrefix.'SalesAdminClass';
					new $classId($this->env);
					break;
					
				case STAGESHOW_MENUPAGE_SETTINGS :
					include 'admin/'.$this->adminClassFilePrefix.'_manage_settings.php';
					$classId = $this->adminClassPrefix.'SettingsAdminClass';
					new $classId($this->env);
					break;
          
				case STAGESHOW_MENUPAGE_TOOLS:
					include 'admin/'.$this->adminClassFilePrefix.'_manage_tools.php';
					$classId = $this->adminClassPrefix.'ToolsAdminClass';
					new $classId($this->env);							 
					break;
							
				case STAGESHOW_MENUPAGE_TESTSETTINGS:
					include 'test/'.$this->adminClassFilePrefix.'_testsettings.php';
					$classId = $this->adminClassPrefix.'TestSettingsAdminClass';
					new $classId($this->env);							 
					break;	
					
				case STAGESHOW_MENUPAGE_DEVTEST:
					include STAGESHOW_TEST_PATH.'stageshow_devtestcaller.php';   
					new StageShowDevCalllerClass($this->env);
					break;
							
				case STAGESHOW_MENUPAGE_DEBUG:
		      		include STAGESHOW_ADMIN_PATH.'stageshow_debug.php';    
					new StageShowDebugAdminClass($this->env);
					break;							
			}
		}//End function printAdminPage()	
		
		function load_user_scripts()
		{
			// Add our own Javascript
			wp_enqueue_script( 'stageshow', plugins_url( 'js/stageshow.js', __FILE__ ));
		}	
		
		function load_admin_styles()
		{
			//echo "<!-- load_admin_styles called! ".plugins_url( 'admin/css/stageshow-admin.css', __FILE__ )." -->\n";
			
			// Add our own style sheet
			wp_enqueue_style( 'stageshow', plugins_url( 'admin/css/stageshow-admin.css', __FILE__ ));
			
			// Add our own Javascript
			wp_enqueue_script( 'stageshow-admin', plugins_url( 'admin/js/stageshow-admin.js', __FILE__ ));
			wp_enqueue_script( 'datetimepicker', plugins_url( 'admin/js/datetimepicker_css.js', __FILE__ ));
		}

		function StageShow_ap() 
		{
			$myDBaseObj = $this->myDBaseObj;		
			
			if (!isset($this)) {
				return;
			}

			// Array of Admin capabilities in decreasing order of functionality
			$stageShow_caps = array(
				STAGESHOW_CAPABILITY_DEVUSER,
				STAGESHOW_CAPABILITY_SETUPUSER,
				STAGESHOW_CAPABILITY_ADMINUSER,
				STAGESHOW_CAPABILITY_SALESUSER,
				STAGESHOW_CAPABILITY_VALIDATEUSER,
				STAGESHOW_CAPABILITY_VIEWSETTINGS,
			);
			
			foreach ($stageShow_caps as $stageShow_cap)
			{
				if (current_user_can($stageShow_cap))
				{
					$adminCap = $stageShow_cap;
					break;
				}
			}
			
			if (current_user_can(STAGESHOW_CAPABILITY_SETUPUSER))
			{
				$viewSettingsCap = STAGESHOW_CAPABILITY_SETUPUSER;
			}
			else
			{
				$viewSettingsCap = STAGESHOW_CAPABILITY_VIEWSETTINGS;
			}
			
			if (isset($adminCap) && function_exists('add_menu_page')) 
			{
				$ourPluginName = $myDBaseObj->get_name();
				
				$icon_url = STAGESHOW_ADMIN_IMAGES_URL.'stageshow16grey.png';
				add_menu_page($ourPluginName, $ourPluginName, $adminCap, STAGESHOW_MENUPAGE_ADMINMENU, array(&$this, 'printAdminPage'), $icon_url);
				add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('StageShow Overview', $this->myDomain),__('Overview', $this->myDomain),     $adminCap,                         STAGESHOW_MENUPAGE_ADMINMENU,    array(&$this, 'printAdminPage'));
				if (isset($this->useAllocatedSeats) && $this->useAllocatedSeats)
				{
					add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('Seating Editor', $this->myDomain), __('Seating Plans', $this->myDomain),      STAGESHOW_CAPABILITY_ADMINUSER,    STAGESHOW_MENUPAGE_SEATING,      array(&$this, 'printAdminPage'));					
				}
				add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('Show Editor', $this->myDomain), __('Shows', $this->myDomain),        STAGESHOW_CAPABILITY_ADMINUSER,    STAGESHOW_MENUPAGE_SHOWS,        array(&$this, 'printAdminPage'));
				if ( file_exists(STAGESHOW_ADMIN_PATH.'stageshowplus_manage_priceplans.php') ) 
					add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('Price Plan Editor', $this->myDomain), __('Price Plans', $this->myDomain),STAGESHOW_CAPABILITY_ADMINUSER, STAGESHOW_MENUPAGE_PRICEPLANS,   array(&$this, 'printAdminPage'));
				add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('Performance Editor', $this->myDomain),__('Performances', $this->myDomain), STAGESHOW_CAPABILITY_ADMINUSER,    STAGESHOW_MENUPAGE_PERFORMANCES, array(&$this, 'printAdminPage'));
				add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('Price Edit', $this->myDomain), __('Prices', $this->myDomain),       STAGESHOW_CAPABILITY_ADMINUSER,    STAGESHOW_MENUPAGE_PRICES,       array(&$this, 'printAdminPage'));

				if ( current_user_can(STAGESHOW_CAPABILITY_VALIDATEUSER)
				  || current_user_can(STAGESHOW_CAPABILITY_SALESUSER))
					add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('Sales Admin', $this->myDomain), __('Sales', $this->myDomain),     $adminCap,                        STAGESHOW_MENUPAGE_SALES,        array(&$this, 'printAdminPage'));
				
				if ( current_user_can(STAGESHOW_CAPABILITY_VALIDATEUSER)
				  || current_user_can(STAGESHOW_CAPABILITY_ADMINUSER))
					add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('Admin Tools', $this->myDomain), __('Tools', $this->myDomain),     $adminCap,                        STAGESHOW_MENUPAGE_TOOLS,        array(&$this, 'printAdminPage'));

				add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('Edit Settings', $this->myDomain), __('Settings', $this->myDomain),    $viewSettingsCap,                   STAGESHOW_MENUPAGE_SETTINGS,     array(&$this, 'printAdminPage'));

				// Show test menu if stageshow_testsettings.php is present
				if ($myDBaseObj->InTestMode() && current_user_can(STAGESHOWLIB_CAPABILITY_SYSADMIN))
				{
					add_submenu_page( 'options-general.php', $ourPluginName.' Test', $ourPluginName.' Test', STAGESHOW_CAPABILITY_DEVUSER, STAGESHOW_MENUPAGE_TESTSETTINGS, array(&$this, 'printAdminPage'));
				}
				
				if (!$myDBaseObj->getDbgOption('Dev_DisableTestMenus') 
				  && current_user_can(STAGESHOWLIB_CAPABILITY_SYSADMIN) )
				{
					if ( file_exists(STAGESHOW_TEST_PATH.'stageshow_devtestcaller.php') ) 
						add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('Dev TESTING', $this->myDomain), __('Dev TESTING', $this->myDomain), STAGESHOW_CAPABILITY_DEVUSER, STAGESHOW_MENUPAGE_DEVTEST, array(&$this, 'printAdminPage'));

					if ( file_exists(STAGESHOW_ADMIN_PATH.'stageshow_debug.php') )
						add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('DEBUG', $this->myDomain), __('DEBUG', $this->myDomain), STAGESHOW_CAPABILITY_DEVUSER, STAGESHOW_MENUPAGE_DEBUG, array(&$this, 'printAdminPage'));
				}
			}	
			
		}
		
	}
} //End Class StageShowPluginClass

?>