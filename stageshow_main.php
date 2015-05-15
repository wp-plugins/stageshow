<?php
/* 
Description: StageShow Plugin Top Level Code
 
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

if (!defined('STAGESHOWLIB_DATABASE_FULL')) define('STAGESHOWLIB_DATABASE_FULL', true);

if (!class_exists('StageShowWPOrgCartPluginClass')) 
	include 'stageshow_trolley.php';
	
if (!class_exists('StageShowWPOrgPluginClass')) 
{
	class StageShowWPOrgPluginClass extends StageShowWPOrgCartPluginClass // Define class 
	{
		function __construct($caller)		 
		{
			parent::__construct($caller);
			
			//Actions
			register_activation_hook( $caller, array(&$this, 'activate') );
			register_deactivation_hook( $caller, array(&$this, 'deactivate') );
				
			add_action('wp_print_styles', array(&$this, 'load_user_styles') );
			add_action('wp_print_scripts', array(&$this, 'load_user_scripts') );
			
			//add_action('wp_enqueue_scripts', array(&$this, 'load_user_scripts') );
			add_action('admin_enqueue_scripts', array(&$this, 'load_admin_styles') );
			
			// Add a reference to the header
			add_action('wp_head', array(&$this, 'OutputMetaTag'));
/*			
			// Function to add notification to admin page
			add_action( 'admin_notices', array(&$this, 'AdminUpgradeNotice'));
*/
			
			$myDBaseObj = $this->myDBaseObj;
			
			//Actions
			add_action('admin_menu', array(&$this, 'GenerateMenus'));
		  
			add_action('init', array(&$this, 'init'));
		  
			if ($myDBaseObj->IsInWP() && $myDBaseObj->checkVersion())
			{
				// FUNCTIONALITY: Main - Call "Activate" on plugin update
				// Versions are different ... call activate() to do any updates
				$this->activate();
			}			
		}
		
		static function CreateDBClass($caller)
		{					
			if (!class_exists('StageShowWPOrgDBaseClass')) 
				include STAGESHOW_INCLUDE_PATH.'stageshow_dbase_api.php';
				
			return new StageShowWPOrgDBaseClass($caller);		
		}
		
		function load_user_styles() 
		{
			//Add Style Sheet
			wp_enqueue_style(STAGESHOW_CODE_PREFIX, STAGESHOW_STYLESHEET_URL); // StageShow core style
		}
		
		//Returns an array of admin options
		// Saves the admin options to the options data table
		
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
			
 			if ($myDBaseObj->adminOptions['ActivationCount'] == 1)
			{
				// First time activation ....
				$myDBaseObj->adminOptions['QtySelectTextInput'] = true;
			}
			
			$myDBaseObj->adminOptions['TestModeEnabled'] = file_exists(STAGESHOW_TEST_PATH.'stageshow_testsettings.php');
			
			$LogsFolder = ABSPATH . '/' . $myDBaseObj->adminOptions['LogsFolderPath'];
			if (!is_dir($LogsFolder))
				mkdir($LogsFolder, 0644, TRUE);

	  		// FUNCTIONALITY: Activate - Set EMail template to file name ONLY
			// EMail Template defaults to templates folder - remove folders from path
			$myDBaseObj->CheckEmailTemplatePath('EMailTemplatePath', STAGESHOW_ACTIVATE_EMAIL_TEMPLATE_PATH);
			
      		$this->myDBaseObj->saveOptions();
      
			$setupUserRole = $myDBaseObj->adminOptions['SetupUserRole'];

	  		// FUNCTIONALITY: Activate - Add Capabilities
			// Add capability to submit events to all default users
			$adminRole = get_role($setupUserRole);
			if ( !empty($adminRole) ) 
			{
				// Adding Manage StageShow Capabilities to Administrator					
				if (!$adminRole->has_cap(STAGESHOWLIB_CAPABILITY_RESERVEUSER))
					$adminRole->add_cap(STAGESHOWLIB_CAPABILITY_RESERVEUSER);
				if (!$adminRole->has_cap(STAGESHOWLIB_CAPABILITY_VALIDATEUSER))
					$adminRole->add_cap(STAGESHOWLIB_CAPABILITY_VALIDATEUSER);
				if (!$adminRole->has_cap(STAGESHOWLIB_CAPABILITY_SALESUSER))
					$adminRole->add_cap(STAGESHOWLIB_CAPABILITY_SALESUSER);
				if (!$adminRole->has_cap(STAGESHOWLIB_CAPABILITY_ADMINUSER))
					$adminRole->add_cap(STAGESHOWLIB_CAPABILITY_ADMINUSER);
				if (!$adminRole->has_cap(STAGESHOWLIB_CAPABILITY_SETUPUSER))
					$adminRole->add_cap(STAGESHOWLIB_CAPABILITY_SETUPUSER);
				if (!$adminRole->has_cap(STAGESHOWLIB_CAPABILITY_VIEWSETTINGS))
					$adminRole->add_cap(STAGESHOWLIB_CAPABILITY_VIEWSETTINGS);
			}				
			
			// Add copies of PayPal IPN notification code for historical configurations
			// Note: MixedCase copy of stageshow_ipn_callback.php does nothing on Windows Server
			copy(STAGESHOW_FILE_PATH.'stageshow_ipn_callback.php', STAGESHOW_FILE_PATH.'stageshow_NotifyURL.php');
			copy(STAGESHOW_FILE_PATH.'stageshow_ipn_callback.php', STAGESHOW_FILE_PATH.'StageShow_ipn_callback.php');
			
      		$myDBaseObj->upgradeDB();
      		
      		$myDBaseObj->SaveDBCredentials(true);
		}

	    function deactivate()
	    {
	    }


 		function OutputMetaTag()
		{
			$myDBaseObj = $this->myDBaseObj;
			
	  		// FUNCTIONALITY: Runtime - Output StageShow Meta Tag
			// Get Version Number
			$pluginID = $myDBaseObj->get_pluginName();
			$pluginVer = $myDBaseObj->get_version();
			$boxofficeURL = $myDBaseObj->getOption('boxofficeURL');
			
			echo "\n<meta name='$pluginID' content='$pluginID for WordPress by Malcolm Shergold - Ver:$pluginVer - BoxOfficeURL:$boxofficeURL' />\n";						
		}
		
		function CreateSample($sampleDepth = 0)
		{
			include STAGESHOW_INCLUDE_PATH.STAGESHOW_FOLDER.'_sample_dbase.php'; 
				
			$myDBaseObj = $this->myDBaseObj;
			$this->myDBaseObj->saveOptions();
			
			$sampleClassId = STAGESHOW_PLUGIN_NAME.'SampleDBaseClass';
			$sampleClassObj = new $sampleClassId($myDBaseObj);
			$sampleClassObj->CreateSample($sampleDepth);
		}
		
		function printAdminPage() 
		{
			$this->adminPageActive = true;
		
			$id = isset($_GET['id']) ? $_GET['id'] : '';
			$this->SetTrolleyID($id);

			$this->outputAdminPage();
		}
		
		function outputAdminPage() 
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
					include STAGESHOW_TEST_PATH.'stageshowlib_devtestcaller.php';   
					new StageShowLibDevCallerClass($this->env, 'StageShow');
					break;
							
				case STAGESHOW_MENUPAGE_DIAGNOSTICS:
		      		include STAGESHOW_ADMIN_PATH.'stageshow_debug.php';    
					new StageShowWPOrgDebugAdminClass($this->env);
					break;							
			}
		}//End function printAdminPage()	
		
		function load_user_scripts()
		{
			parent::load_user_scripts();

			$reloadParam = false;
			if (defined('STAGESHOW_JS_NOCACHE')) $reloadParam = time();
			
			// Add our own Javascript
			wp_enqueue_script( $this->adminClassPrefix.'-lib', plugins_url( 'js/stageshowlib_js.js', __FILE__ ), array(), $reloadParam);
			wp_enqueue_script( $this->adminClassPrefix.'', plugins_url( 'js/stageshow.js', __FILE__ ), array(), $reloadParam);

			wp_enqueue_script('jquery');
		}	
		
		function load_admin_styles()
		{
			parent::load_admin_styles();

			// Add our own style sheet
			wp_enqueue_style( 'stageshow', plugins_url( 'admin/css/stageshow-admin.css', __FILE__ ));
			
			// Add our own Javascript
			wp_enqueue_script( $this->adminClassPrefix.'-admin', plugins_url( 'admin/js/stageshow-admin.js', __FILE__ ));
			wp_enqueue_script( $this->adminClassPrefix.'-dtpicker', plugins_url( 'admin/js/datetimepicker_css.js', __FILE__ ));
		}

		function GenerateMenus() 
		{
			$myDBaseObj = $this->myDBaseObj;		
			
			if (!isset($this)) {
				return;
			}

			// Array of Admin capabilities in decreasing order of functionality
			$stageShow_caps = array(
				STAGESHOWLIB_CAPABILITY_DEVUSER,
				STAGESHOWLIB_CAPABILITY_SETUPUSER,
				STAGESHOWLIB_CAPABILITY_ADMINUSER,
				STAGESHOWLIB_CAPABILITY_SALESUSER,
				STAGESHOWLIB_CAPABILITY_VALIDATEUSER,
				STAGESHOWLIB_CAPABILITY_VIEWSETTINGS,
			);
			
			foreach ($stageShow_caps as $stageShow_cap)
			{
				if (current_user_can($stageShow_cap))
				{
					$adminCap = $stageShow_cap;
					break;
				}
			}
			
			if (current_user_can(STAGESHOWLIB_CAPABILITY_SETUPUSER))
			{
				$viewSettingsCap = STAGESHOWLIB_CAPABILITY_SETUPUSER;
			}
			else
			{
				$viewSettingsCap = STAGESHOWLIB_CAPABILITY_VIEWSETTINGS;
			}
			
			if (isset($adminCap) && function_exists('add_menu_page')) 
			{
				$ourPluginName = $myDBaseObj->get_pluginName();
				
				$icon_url = STAGESHOW_ADMIN_IMAGES_URL.'stageshow16grey.png';
				add_menu_page($ourPluginName, $ourPluginName, $adminCap, STAGESHOW_MENUPAGE_ADMINMENU, array(&$this, 'printAdminPage'), $icon_url);
				add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('Overview', $this->myDomain),__('Overview', $this->myDomain),     $adminCap,                         STAGESHOW_MENUPAGE_ADMINMENU,    array(&$this, 'printAdminPage'));
				if (isset($this->useAllocatedSeats))
				{
					add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('Seating Plans', $this->myDomain), __('Seating Plans', $this->myDomain),      STAGESHOWLIB_CAPABILITY_ADMINUSER,    STAGESHOW_MENUPAGE_SEATING,      array(&$this, 'printAdminPage'));					
				}
				add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('Shows', $this->myDomain), __('Shows', $this->myDomain),        STAGESHOWLIB_CAPABILITY_ADMINUSER,    STAGESHOW_MENUPAGE_SHOWS,        array(&$this, 'printAdminPage'));
				if ( file_exists(STAGESHOW_ADMIN_PATH.'stageshowplus_manage_priceplans.php') ) 
					add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('Price Plans', $this->myDomain), __('Price Plans', $this->myDomain),STAGESHOWLIB_CAPABILITY_ADMINUSER, STAGESHOW_MENUPAGE_PRICEPLANS,   array(&$this, 'printAdminPage'));
				add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('Performances', $this->myDomain),__('Performances', $this->myDomain), STAGESHOWLIB_CAPABILITY_ADMINUSER,    STAGESHOW_MENUPAGE_PERFORMANCES, array(&$this, 'printAdminPage'));
				add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('Prices', $this->myDomain), __('Prices', $this->myDomain),       STAGESHOWLIB_CAPABILITY_ADMINUSER,    STAGESHOW_MENUPAGE_PRICES,       array(&$this, 'printAdminPage'));

				if ( current_user_can(STAGESHOWLIB_CAPABILITY_VALIDATEUSER)
				  || current_user_can(STAGESHOWLIB_CAPABILITY_SALESUSER))
					add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('Sales', $this->myDomain), __('Sales', $this->myDomain),     $adminCap,                        STAGESHOW_MENUPAGE_SALES,        array(&$this, 'printAdminPage'));
				
				if ( current_user_can(STAGESHOWLIB_CAPABILITY_VALIDATEUSER)
				  || current_user_can(STAGESHOWLIB_CAPABILITY_ADMINUSER))
					add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('Tools', $this->myDomain), __('Tools', $this->myDomain),     $adminCap,                        STAGESHOW_MENUPAGE_TOOLS,        array(&$this, 'printAdminPage'));

				add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('Settings', $this->myDomain), __('Settings', $this->myDomain),    $viewSettingsCap,                   STAGESHOW_MENUPAGE_SETTINGS,     array(&$this, 'printAdminPage'));

				// Show test menu if stageshow_testsettings.php is present
				if ($myDBaseObj->InTestMode() && current_user_can(STAGESHOWLIB_CAPABILITY_DEVUSER))
				{
					add_submenu_page( 'options-general.php', $ourPluginName.' Test', $ourPluginName.' Test', STAGESHOWLIB_CAPABILITY_DEVUSER, STAGESHOW_MENUPAGE_TESTSETTINGS, array(&$this, 'printAdminPage'));
				}
				
				if (!$myDBaseObj->getDbgOption('Dev_DisableTestMenus') 
				  && (current_user_can(STAGESHOWLIB_CAPABILITY_SYSADMIN) || current_user_can(STAGESHOWLIB_CAPABILITY_DEVUSER)))
				{
					if ( isset($_SESSION['stageshowlib_debug_test']) && file_exists(STAGESHOW_TEST_PATH.'stageshowlib_devtestcaller.php') ) 
					{
						include STAGESHOW_TEST_PATH.'stageshowlib_devtestcaller.php';   
						$devTestFiles = StageShowLibDevCallerClass::DevTestFilesList(STAGESHOW_TEST_PATH, 'StageShow');
						if (count($devTestFiles) > 0)
							add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('Dev TESTING', $this->myDomain), __('Dev TESTING', $this->myDomain), STAGESHOWLIB_CAPABILITY_DEVUSER, STAGESHOW_MENUPAGE_DEVTEST, array(&$this, 'printAdminPage'));
					}

					if ( isset($_SESSION['stageshowlib_debug_menu']) )
					{
						add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('Diagnostics', $this->myDomain), __('Diagnostics', $this->myDomain), STAGESHOWLIB_CAPABILITY_DEVUSER, STAGESHOW_MENUPAGE_DIAGNOSTICS, array(&$this, 'printAdminPage'));
					}
				}
			}	
			
		}
		
	}
}

?>
