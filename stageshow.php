<?php
/* 
Plugin Name: StageShow
Plugin URI: http://www.corondeck.co.uk/StageShow
Version: 0.9
Author: <a href="http://www.corondeck.co.uk/">Malcolm Shergold</a>
Description: A Wordpress Plugin to sell theatre tickets online
 
Copyright 2011 Malcolm Shergold

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


$siteurl = get_option('siteurl');
define('STAGESHOW_PLUGINNAME', 'StageShow');
define('STAGESHOW_FOLDER', dirname(plugin_basename(__FILE__)));
define('STAGESHOW_URL', $siteurl.'/wp-content/plugins/' . STAGESHOW_FOLDER .'/');
define('STAGESHOW_IMAGES_URL', STAGESHOW_URL . 'images/');
define('STAGESHOW_ADMIN_URL', STAGESHOW_URL . 'admin/');
define('STAGESHOW_ADMIN_IMAGES_URL', STAGESHOW_ADMIN_URL . 'images/');

define('STAGESHOW_FILE_PATH', dirname(__FILE__).'/');
define('STAGESHOW_DIR_NAME', basename(STAGESHOW_FILE_PATH));
define('STAGESHOW_ADMIN_PATH', STAGESHOW_FILE_PATH . '/admin/');
define('STAGESHOW_ADMINICON_PATH', STAGESHOW_ADMIN_PATH . 'images/');

define('STAGESHOW_CODE_PREFIX', 'sshow');
define('STAGESHOW_DOMAIN_NAME', 'stageshow');

define('STAGESHOW_OPTIONS_NAME', 'stageshowsettings');

if (!defined('STAGESHOW_PAYPAL_IPN_NOTIFY_URL'))
	define('STAGESHOW_PAYPAL_IPN_NOTIFY_URL', get_site_url().'/wp-content/plugins/stageshow/stageshow_NotifyURL.php');

include 'admin/stageshow_paypal_api.php';      
include 'admin/stageshow_dbase_api.php';      
      
if (!defined('STAGESHOW_ACTIVATE_EMAIL_TEMPLATE_PATH'))
	define('STAGESHOW_ACTIVATE_EMAIL_TEMPLATE_PATH', 'templates/stageshow_EMail.php');

if (!class_exists('StageShowPluginClass')) {
	class StageShowPluginClass {
		var $pluginName;
		
		function StageShowPluginClass() { //constructor			
		  // Init options & tables during activation & deregister init option
		  register_activation_hook( __FILE__, array(&$this, 'activate') );
		  register_deactivation_hook( __FILE__, array(&$this, 'deactivate') );	

			$this->getStageshowOptions();
			
			$this->pluginName = STAGESHOW_PLUGINNAME;
		}

		function init() {
			$this->getStageshowOptions();
		}
		
    function GetArrayElement($reqArray, $elementId) {
	    // Get an element from the array ... if it exists
	    if (!is_array($reqArray)) return '';
	    if (!array_key_exists($elementId, $reqArray)) return '';	
	    return $reqArray[$elementId];
    }
    
		//Returns an array of admin options
		function getStageshowOptions() {
			global $myDBaseObj;
			global $myPayPalAPILiveObj;
			global $myPayPalAPITestObj;
			
			$this->setPayPalCredentials();
			
			return $myDBaseObj->adminOptions;
		}
    
		// Saves the admin options to the PayPal object(s)
		function setPayPalCredentials() 
		{
			global $myDBaseObj;
			global $myPayPalAPILiveObj;
			global $myPayPalAPITestObj;
			
			$myPayPalAPITestObj->SetLoginParams(
				$myDBaseObj->adminOptions['PayPalAPITestUser'], 
				$myDBaseObj->adminOptions['PayPalAPITestPwd'], 
				$myDBaseObj->adminOptions['PayPalAPITestSig'], 
				$myDBaseObj->adminOptions['PayPalAPITestEMail'], 
				$myDBaseObj->adminOptions['PayPalCurrency']);
				
			$myPayPalAPILiveObj->SetLoginParams(
				$myDBaseObj->adminOptions['PayPalAPILiveUser'], 
				$myDBaseObj->adminOptions['PayPalAPILivePwd'], 
				$myDBaseObj->adminOptions['PayPalAPILiveSig'], 
				$myDBaseObj->adminOptions['PayPalAPILiveEMail'], 
				$myDBaseObj->adminOptions['PayPalCurrency']);
				
			if ($myDBaseObj->adminOptions['Dev_ShowPayPalIO'] == 1)
			{
				$myPayPalAPITestObj->EnableDebug();
				$myPayPalAPILiveObj->EnableDebug();
			}
		}
    
		// Saves the admin options to the options data table
		function saveStageshowOptions() {
			global $myDBaseObj;
			
			$this->setPayPalCredentials();
			
			$myDBaseObj->saveOptions();
		}
    
    // ----------------------------------------------------------------------
    // Activation / Deactivation Functions
    // ----------------------------------------------------------------------
    
    function activate() {
			global $myDBaseObj;
          
      // Pre-configured PayPal Sandbox settings - can be defined in wp-config.php
      if (defined('STAGESHOW_ACTIVATE_PAYPALAPI_TESTUSER'))
				$myDBaseObj->adminOptions['PayPalAPITestUser'] = STAGESHOW_ACTIVATE_PAYPALAPI_TESTUSER;
      if (defined('STAGESHOW_ACTIVATE_PAYPALAPI_TESTPWD'))
	      $myDBaseObj->adminOptions['PayPalAPITestPwd']  = STAGESHOW_ACTIVATE_PAYPALAPI_TESTPWD;
      if (defined('STAGESHOW_ACTIVATE_PAYPALAPI_TESTSIG'))
	      $myDBaseObj->adminOptions['PayPalAPITestSig']  = STAGESHOW_ACTIVATE_PAYPALAPI_TESTSIG;
      if (defined('STAGESHOW_ACTIVATE_PAYPALAPI_TESTEMAIL'))
	      $myDBaseObj->adminOptions['PayPalAPITestEMail']  = STAGESHOW_ACTIVATE_PAYPALAPI_TESTEMAIL;
            
      // Pre-configured PayPal "Live" settings - can be defined in wp-config.php
      if (defined('STAGESHOW_ACTIVATE_PAYPALAPI_LIVEUSER'))
				$myDBaseObj->adminOptions['PayPalAPILiveUser'] = STAGESHOW_ACTIVATE_PAYPALAPI_LIVEUSER;
      if (defined('STAGESHOW_ACTIVATE_PAYPALAPI_LIVEPWD'))
	      $myDBaseObj->adminOptions['PayPalAPILivePwd']  = STAGESHOW_ACTIVATE_PAYPALAPI_LIVEPWD;
      if (defined('STAGESHOW_ACTIVATE_PAYPALAPI_LIVESIG'))
	      $myDBaseObj->adminOptions['PayPalAPILiveSig']  = STAGESHOW_ACTIVATE_PAYPALAPI_LIVESIG;
      if (defined('STAGESHOW_ACTIVATE_PAYPALAPI_LIVEEMAIL'))
	      $myDBaseObj->adminOptions['PayPalAPILiveEMail']  = STAGESHOW_ACTIVATE_PAYPALAPI_LIVEEMAIL;
      
      // Initialise PayPal target ....
      if ( (strlen($myDBaseObj->adminOptions['PayPalAPILiveUser']) > 0) && 
			     (strlen($myDBaseObj->adminOptions['PayPalAPILivePwd']) > 0) && 
			     (strlen($myDBaseObj->adminOptions['PayPalAPILiveSig']) > 0) )
				$myDBaseObj->adminOptions['PayPalEnv']  = 'live';
			else
				$myDBaseObj->adminOptions['PayPalEnv']  = 'sandbox';
				
      if (defined('STAGESHOW_ACTIVATE_ORGANISATION_ID'))
				$myDBaseObj->adminOptions['OrganisationID'] = STAGESHOW_ACTIVATE_ORGANISATION_ID;
      if (defined('STAGESHOW_ACTIVATE_ADMIN_ID'))
				$myDBaseObj->adminOptions['AdminID'] = STAGESHOW_ACTIVATE_ADMIN_ID;
      if (defined('STAGESHOW_ACTIVATE_ADMIN_EMAIL')) {
				$myDBaseObj->adminOptions['AdminEMail'] = STAGESHOW_ACTIVATE_ADMIN_EMAIL;
				$myDBaseObj->adminOptions['BookingsEMail'] = STAGESHOW_ACTIVATE_ADMIN_EMAIL;
				$myDBaseObj->adminOptions['SentCopyEMail'] = STAGESHOW_ACTIVATE_ADMIN_EMAIL;
      }
      
      $myDBaseObj->adminOptions['EMailTemplatePath'] = STAGESHOW_ACTIVATE_EMAIL_TEMPLATE_PATH;
      
			$LogsFolder = ABSPATH . '/' . $myDBaseObj->adminOptions['LogsFolderPath'];
			if (!is_dir($LogsFolder))
				mkdir($LogsFolder, 0644, TRUE);
						
      $this->saveStageshowOptions();
      
      $myDBaseObj->activate();
		}

    function deactivate()
    {
    }

		function ResetToDefaults()
		{
			global $myDBaseObj;
			global $myPayPalAPILiveObj;
			global $myPayPalAPITestObj;
      
      // Delete any PayPal Hosted buttons in the Performance Table
      $results = $myDBaseObj->GetAllPerformancesList();
      foreach($results as $result)
      {
				$myPayPalAPITestObj->DeleteButton($result->perfPayPalTESTButtonID);
				$myPayPalAPILiveObj->DeleteButton($result->perfPayPalLIVEButtonID);
      }
      
      $myDBaseObj->deactivate();
      
      update_option(STAGESHOW_OPTIONS_NAME, '');
    }

		function CreateSample()
		{
      global $myDBaseObj;
      
      // Add Sample PayPal shopping cart Images and URLs
      if (defined('STAGESHOW_SAMPLE_PAYPALLOGOIMAGE_URL'))
				$myDBaseObj->adminOptions['PayPalLogoImageURL'] = STAGESHOW_SAMPLE_PAYPALLOGOIMAGE_URL;
      if (defined('STAGESHOW_SAMPLE_PAYPALHEADERIMAGE_URL'))
	      $myDBaseObj->adminOptions['PayPalHeaderImageURL'] = STAGESHOW_SAMPLE_PAYPALHEADERIMAGE_URL;

      $this->saveStageshowOptions();
      
      $myDBaseObj->CreateSample();
		}
		
		function IsOptionChanged($optionID1, $optionID2 = '', $optionID3 = '', $optionID4 = '')
		{
      global $myDBaseObj;
      
			if (isset($_POST[$optionID1]) && ($this->GetArrayElement($myDBaseObj->adminOptions, $optionID1) !== trim($_POST[$optionID1])))
				return true;
			
			if ($optionID2 === '') return false;			
			if (isset($_POST[$optionID2]) && ($this->GetArrayElement($myDBaseObj->adminOptions, $optionID2) !== trim($_POST[$optionID2])))
				return true;
			
			if ($optionID3 === '') return false;			
			if (isset($_POST[$optionID3]) && ($this->GetArrayElement($myDBaseObj->adminOptions, $optionID3) !== trim($_POST[$optionID3])))
				return true;
			
			return false;
		}
		
		function ValidateEmail($ourEMail)
		{
			return true;
		}
		
		function OutputContent_BoxOffice( $atts )
		{
			$atts = shortcode_atts(array(
				'id'    => 1,
				'style' => 'normal' 
			), $atts );
        
      $showID = $atts['id'];
      
      include 'admin/stageshow_boxoffice.php';      
    }		     
    
		function printAdminPage() {
			global $myDBaseObj;		
			//Prints out an admin page
      			
      echo '
<script>
function confirmDelete(userMsg) 
{
//  return (confirm("Are you sure you want to delete"));
  return (confirm("Delete "+userMsg+"?"));
}
</script>
';
      		
			$pageSubTitle = $_GET['page'];			
      switch ($pageSubTitle)
      {
				case STAGESHOW_CODE_PREFIX.'_overview':
					include 'admin/stageshow_manage_overview.php';      
					break;
							
        case STAGESHOW_CODE_PREFIX.'_shows':
					include 'admin/stageshow_manage_shows.php';      
          break;
          
        case STAGESHOW_CODE_PREFIX.'_performances' :
					include 'admin/stageshow_manage_performances.php';      
					break;
					
				case STAGESHOW_CODE_PREFIX.'_prices' :
					include 'admin/stageshow_manage_prices.php';      
					break;
					
				case STAGESHOW_CODE_PREFIX.'_sales' :
					include 'admin/stageshow_manage_sales.php';      
					break;
					
				case STAGESHOW_CODE_PREFIX.'_settings' :
					include 'admin/stageshow_manage_settings.php';      
					break;
          
				case STAGESHOW_CODE_PREFIX.'_tools':
					include 'admin/stageshow_manage_tools.php';      
					break;
							
				case STAGESHOW_CODE_PREFIX.'_test':
		      include 'admin/stageshow_test.php';      
					break;
							
				case STAGESHOW_CODE_PREFIX.'_debug':
		      include 'admin/stageshow_debug.php';      
					break;
							
				case STAGESHOW_CODE_PREFIX.'_adminmenu':
				default :
					include 'admin/stageshow_manage_overview.php';      
					break;
			}
		}//End function printAdminPage()	
	}
} //End Class StageShowPluginClass

function AddStyleSheet()
{
	// Add our own style sheet
	wp_enqueue_style( 'stageshow', plugins_url( 'admin/css/admin.css', __FILE__ ));
	
	//do_action('AddStyleSheet');
}

if ( file_exists(STAGESHOW_ADMIN_PATH.'/stageshow_extns.php') )
{
	include(STAGESHOW_ADMIN_PATH.'/stageshow_extns.php');
}
			
if (!isset($myDBaseObj) && class_exists('StageShowDBaseClass')) 
{
	global $myDBaseObj;				
	$myDBaseObj = new StageShowDBaseClass();
}

if (!isset($myShowObj) && class_exists("StageShowPluginClass")) 
{
	global $myShowObj;
	
	$myShowObj = new StageShowPluginClass();
}

//Initialize the admin panel
if (!function_exists("StageShow_ap")) {
	function StageShow_ap() {
		global $myShowObj;		
		if (!isset($myShowObj)) {
			return;
		}

		if (function_exists('add_menu_page')) 
		{
			$icon_url = STAGESHOW_ADMIN_IMAGES_URL.'stageshow16grey.png';
			add_menu_page(STAGESHOW_PLUGINNAME, STAGESHOW_PLUGINNAME, 'manage_options', STAGESHOW_CODE_PREFIX.'_adminmenu', array(&$myShowObj, 'printAdminPage'), $icon_url);
			add_submenu_page( STAGESHOW_CODE_PREFIX.'_adminmenu', __('StageShow Overview', STAGESHOW_DOMAIN_NAME),__('Overview', STAGESHOW_DOMAIN_NAME),    'manage_options', STAGESHOW_CODE_PREFIX.'_adminmenu',    array(&$myShowObj, 'printAdminPage'));
			add_submenu_page( STAGESHOW_CODE_PREFIX.'_adminmenu', __('Show Editor', STAGESHOW_DOMAIN_NAME),       __('Show', STAGESHOW_DOMAIN_NAME),        'manage_options', STAGESHOW_CODE_PREFIX.'_shows',        array(&$myShowObj, 'printAdminPage'));
			add_submenu_page( STAGESHOW_CODE_PREFIX.'_adminmenu', __('Performance Editor', STAGESHOW_DOMAIN_NAME),__('Performance', STAGESHOW_DOMAIN_NAME), 'manage_options', STAGESHOW_CODE_PREFIX.'_performances', array(&$myShowObj, 'printAdminPage'));
			add_submenu_page( STAGESHOW_CODE_PREFIX.'_adminmenu', __('Price Edit', STAGESHOW_DOMAIN_NAME),        __('Price', STAGESHOW_DOMAIN_NAME),       'manage_options', STAGESHOW_CODE_PREFIX.'_prices',       array(&$myShowObj, 'printAdminPage'));
			add_submenu_page( STAGESHOW_CODE_PREFIX.'_adminmenu', __('Sales Admin', STAGESHOW_DOMAIN_NAME),       __('Sales', STAGESHOW_DOMAIN_NAME),       'manage_options', STAGESHOW_CODE_PREFIX.'_sales',        array(&$myShowObj, 'printAdminPage'));
			add_submenu_page( STAGESHOW_CODE_PREFIX.'_adminmenu', __('Admin Tools', STAGESHOW_DOMAIN_NAME),       __('Tools', STAGESHOW_DOMAIN_NAME),       'manage_options', STAGESHOW_CODE_PREFIX.'_tools',        array(&$myShowObj, 'printAdminPage'));
			add_submenu_page( STAGESHOW_CODE_PREFIX.'_adminmenu', __('Edit Settings', STAGESHOW_DOMAIN_NAME),     __('Settings', STAGESHOW_DOMAIN_NAME),    'manage_options', STAGESHOW_CODE_PREFIX.'_settings',     array(&$myShowObj, 'printAdminPage'));
      
      // Show test menu if stageshow_test.php is present
			if ( file_exists(STAGESHOW_ADMIN_PATH.'/stageshow_test.php') )
				add_submenu_page( STAGESHOW_CODE_PREFIX.'_adminmenu', __('TEST', STAGESHOW_DOMAIN_NAME), __('TEST', STAGESHOW_DOMAIN_NAME), 'manage_options', STAGESHOW_CODE_PREFIX.'_test', array(&$myShowObj, 'printAdminPage'));
      
      // Show debug menu if stageshow_debug.php is present
			if ( file_exists(STAGESHOW_ADMIN_PATH.'/stageshow_debug.php') )
				add_submenu_page( STAGESHOW_CODE_PREFIX.'_adminmenu', __('DEBUG', STAGESHOW_DOMAIN_NAME), __('DEBUG', STAGESHOW_DOMAIN_NAME), 'manage_options', STAGESHOW_CODE_PREFIX.'_debug', array(&$myShowObj, 'printAdminPage'));
		}	
	}
}

//Actions and Filters	
if (isset($myShowObj)) {
	//Actions
	add_action('admin_menu', 'StageShow_ap');
	add_action('activate_stageshow/stageshow.php',  array(&$myShowObj, 'init'));
  
  // Add style sheet to page
  add_action( 'admin_print_styles', 'AddStyleSheet' );

	//Filters

  //Add ShortCode for "front end listing"
  add_shortcode(STAGESHOW_CODE_PREFIX."-boxoffice", array(&$myShowObj, 'OutputContent_BoxOffice'));
  
}

?>