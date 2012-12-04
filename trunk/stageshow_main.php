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

require_once 'include/stageshow_dbase_api.php';      
      
if (!class_exists('StageShowPluginClass')) 
{
	class StageShowPluginClass // Define class
	{
		var $ourPluginName;
		var $myDBaseObj;
		var	$env;
		
		var	$adminClassFilePrefix;
		var $adminClassPrefix;
		
		function __construct($caller)		 
		{
			$dbaseObj = $this->CreateDBClass($caller);
			
			//Actions
			register_activation_hook( $caller, array(&$this, 'activate') );
			register_deactivation_hook( $caller, array(&$this, 'deactivate') );	
	
			add_action('wp_enqueue_scripts', array(&$this, 'load_user_styles') );
			add_action('admin_print_styles', array(&$this, 'load_admin_styles') );
			
			// Add a reference to the header
			add_action('wp_head', array(&$this, 'OutputMetaTag'));

			$this->testModeEnabled = file_exists(STAGESHOW_TEST_PATH.'stageshow_test.php');
			$dbaseObj->testModeEnabled = $this->testModeEnabled;
				
			$this->adminClassFilePrefix = 'stageshow';
			$this->adminClassPrefix = 'StageShow';
			
			$this->myDBaseObj = $dbaseObj;
			$this->myDomain = $this->myDBaseObj->get_domain();
			
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
		  
			//Filters
			// FUNCTIONALITY: Main - Shortcode sshow-boxoffice
			//Add ShortCode for "front end listing"
			add_shortcode(STAGESHOW_SHORTCODE_PREFIX."-boxoffice", array(&$this, 'OutputContent_BoxOffice'));
			
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
			$this->getStageshowOptions();
			
			//Add Style Sheet
			wp_enqueue_style(STAGESHOW_CODE_PREFIX, STAGESHOW_STYLESHEET_URL); // StageShow core style
		}
		
		//Returns an array of admin options
		function getStageshowOptions() 
		{
			$myDBaseObj = $this->myDBaseObj;
			
			$myDBaseObj->setPayPalCredentials(STAGESHOW_PAYPAL_IPN_NOTIFY_URL);
			
			if (isset($myDBaseObj->adminOptions['Dev_RunDevCode']) && ($myDBaseObj->adminOptions['Dev_RunDevCode']))
			{
				if (!defined('STAGESHOW_RUNDEVCODE'))
					define('STAGESHOW_RUNDEVCODE', 1);
			}
						
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
			
			if ($myDBaseObj->adminOptions['ActivationCount'] == 1)
			{
	  			// FUNCTIONALITY: FIRST Activate - Apply STAGESHOW_ACTIVATE_******** defaults if defined
				
				// Add Sample PayPal shopping cart Images and URLs
				if (defined('STAGESHOW_SAMPLE_PAYPALLOGOIMAGE_FILE'))
					$myDBaseObj->adminOptions['PayPalLogoImageFile'] = STAGESHOW_SAMPLE_PAYPALLOGOIMAGE_FILE;
				if (defined('STAGESHOW_SAMPLE_PAYPALHEADERIMAGE_FILE'))
					$myDBaseObj->adminOptions['PayPalHeaderImageFile'] = STAGESHOW_SAMPLE_PAYPALHEADERIMAGE_FILE;
				
				if (defined('STAGESHOW_ACTIVATE_ORGANISATION_ID'))
					$myDBaseObj->adminOptions['OrganisationID'] = STAGESHOW_ACTIVATE_ORGANISATION_ID;

				if (defined('STAGESHOW_ACTIVATE_ADMIN_EMAIL')) 
				{
					$myDBaseObj->adminOptions['AdminEMail'] = STAGESHOW_ACTIVATE_ADMIN_EMAIL;
					$myDBaseObj->adminOptions['AuthTxnEMail'] = STAGESHOW_ACTIVATE_ADMIN_EMAIL;
				}
	    	}
			 
			$LogsFolder = ABSPATH . '/' . $myDBaseObj->adminOptions['LogsFolderPath'];
			if (!is_dir($LogsFolder))
				mkdir($LogsFolder, 0644, TRUE);

	  		// FUNCTIONALITY: Activate - Set EMail template to file name ONLY
			// EMail Template defaults to templates folder - remove folders from path
			$myDBaseObj->CheckEmailTemplatePath('EMailTemplatePath');
			
      		$this->saveStageshowOptions();
      
			$setupUserRole = $myDBaseObj->adminOptions['SetupUserRole'];

	  		// FUNCTIONALITY: Activate - Add Capabilities
			// Add capability to submit events to all default users
			$adminRole = get_role($setupUserRole);
			if ( !empty($adminRole) ) 
			{
				// Adding Manage StageShow Capabilities to Administrator					
				if (!$adminRole->has_cap(STAGESHOW_CAPABILITY_VALIDATEUSER))
					$adminRole->add_cap(STAGESHOW_CAPABILITY_VALIDATEUSER);
				if (!$adminRole->has_cap(STAGESHOW_CAPABILITY_SALESUSER))
					$adminRole->add_cap(STAGESHOW_CAPABILITY_SALESUSER);
				if (!$adminRole->has_cap(STAGESHOW_CAPABILITY_ADMINUSER))
					$adminRole->add_cap(STAGESHOW_CAPABILITY_ADMINUSER);
				if (!$adminRole->has_cap(STAGESHOW_CAPABILITY_SETUPUSER))
					$adminRole->add_cap(STAGESHOW_CAPABILITY_SETUPUSER);
			}				
			
      		$myDBaseObj->upgradeDB();
			
			if (!$myDBaseObj->adminOptions['PayPalInvChecked'])
			{
				// Check that all PayPal buttons have the SOLDOUTURL set			
				$results = $myDBaseObj->GetAllPerformancesList();
				foreach ($results as $result)
					$myDBaseObj->payPalAPIObj->AdjustInventory($result->perfPayPalButtonID, 0);
				
				$myDBaseObj->adminOptions['PayPalInvChecked'] = true;
			}
			
		}

	    function deactivate()
	    {
	    }

		function init()
		{
			$myDBaseObj = $this->myDBaseObj;
			$myDBaseObj->init($this->env['caller']);
			
	  		// FUNCTIONALITY: Runtime - Load language files
			$langRelPath = STAGESHOW_LANG_RELPATH;
			load_plugin_textdomain('stageshow', false, $langRelPath);
		}
		
		function OutputMetaTag()
		{
			$myDBaseObj = $this->myDBaseObj;
			
	  		// FUNCTIONALITY: Runtime - Output StageShow Meta Tag
			// Get Version Number
			$pluginID = $myDBaseObj->get_name();
			$pluginVer = $myDBaseObj->get_version();
			
			echo "\n<meta name='$pluginID' content='$pluginID for WordPress by Malcolm Shergold - Ver:$pluginVer' />\n";						
		}
		
		function CreateSample()
		{
			$myDBaseObj = $this->myDBaseObj;
			$this->saveStageshowOptions();
			$myDBaseObj->CreateSample();
		}
		
		function OutputContent_BoxOffice( $atts )
		{
	  		// FUNCTIONALITY: Runtime - Output Box Office
			$myDBaseObj = $this->myDBaseObj;
			$pluginID = $myDBaseObj->get_name();
			$pluginVer = $myDBaseObj->get_version();
			$pluginAuthor = $myDBaseObj->get_author();
			$pluginURI = $myDBaseObj->get_pluginURI();
			echo "\n<!-- $pluginID Plugin $pluginVer for Wordpress by $pluginAuthor - $pluginURI -->\n";			
			
			$atts = shortcode_atts(array(
				'id'    => '',
				'style' => 'normal' 
			), $atts );
        
			ob_start();
			
			$showID = $atts['id'];
			if ( $showID !== '' )
		    {
				$this->OutputContent_ShowBoxOffice($showID);
		    }
		    else
			{
				// Get the ID of the show(s)
				$shows = $myDBaseObj->GetAllShowsList();
	      
				foreach ( $shows as $show )
				{
					$this->OutputContent_ShowBoxOffice($show->showID);
				}
			}
			
			$boxOfficeOutput = ob_get_contents();
			ob_end_clean();
			
			return $boxOfficeOutput;						
		}
		
		function OutputContent_ShowBoxOffice( $showID )
		{
			$myDBaseObj = $this->myDBaseObj;
			
			$payPalAPIObj = $myDBaseObj->payPalAPIObj;
			
			// Get all database entries for this show ... ordered by date/time then ticket type
			$myDBaseObj->prepareBoxOffice($showID);			
			$results = $myDBaseObj->GetPricesListByShowID($showID, true);
			$perfCount = 0;
			
			if (count($results) == 0)
			{
				echo "<!-- StageShow BoxOffice - No Output for ShowID=$showID -->\n";
				return;
			}
      
			$hiddenTags  = "\n";
			$hiddenTags .= '<input type="hidden" name="cmd" value="_s-xclick"/>'."\n";
			if (strlen($myDBaseObj->adminOptions['PayPalLogoImageFile']) > 0)
			{
				$hiddenTags .= '<input type="hidden" name="image_url" value="'.$myDBaseObj->getImageURL('PayPalLogoImageFile').'"/>'."\n";
			}
			if (strlen($myDBaseObj->adminOptions['PayPalHeaderImageFile']) > 0)
			{
				$hiddenTags .= '<input type="hidden" name="cpp_header_image" value="'.$myDBaseObj->getImageURL('PayPalHeaderImageFile').'"/>'."\n";
			}

			$hiddenTags .= '<input type="hidden" name="on0" value="TicketType"/>'."\n";      
			$hiddenTags .= '<input type="hidden" name="SiteURL" value="'.get_site_url().'"/>'."\n";
      
			if (strlen($payPalAPIObj->PayPalNotifyURL) > 0)
				$notifyTag  = '<input type="hidden" name="notify_url" value="'.$payPalAPIObj->PayPalNotifyURL.'"/>'."\n";
			else
				$notifyTag = '';
				
			$altTag = $myDBaseObj->adminOptions['OrganisationID'].' '.__('Tickets', $this->myDomain);
?>
			<div class="stageshow-boxoffice">
			<div id="icon-stageshow" class="icon32"></div>
			<h2>
			<?php echo $results[0]->showName; ?>
			</h2>
<?php      
			if (isset($results[0]->showNote) && ($results[0]->showNote !== ''))
			{
				echo '<div class="stageshow-boxoffice-shownote">'.$results[0]->showNote . "</div><br>\n";
			}
			
			$widthCol1 = '25%';
			$widthCol2 = '25%';
			$widthCol3 = '15%';
			$widthCol4 = '15%';
			$widthCol5 = '20%';
			
			$lastPerfDateTime = '';
			
			$currencySymbol = '';
			if ($myDBaseObj->adminOptions['UseCurrencySymbol'])
				$currencySymbol = $myDBaseObj->adminOptions['CurrencySymbol'];
				
			$oddPage = true;
			for ($perfIndex = 0; $perfIndex<count($results); $perfIndex++)
			{
				$result = $results[$perfIndex];
				
				if ($myDBaseObj->IsPerfEnabled($result))
				{
					$perfCount++;
					if ($perfCount == 1) echo '
			 <table width="100%" border="0">
			 <tr>
				 <td>
					<table width="100%" cellspacing="0">
						<tr class="stageshow-boxoffice-header">
							<td width="'.$widthCol1.'" class="stageshow-boxoffice-datetime">Date/Time</td>
							<td width="'.$widthCol2.'" class="stageshow-boxoffice-type">Ticket Type</td>
							<td width="'.$widthCol3.'" class="stageshow-boxoffice-price">Price</td>
							<td width="'.$widthCol4.'" class="stageshow-boxoffice-qty">Qty</td>
							<td width="'.$widthCol5.'" class="stageshow-boxoffice-add">&nbsp;</td>
						</tr>
					</table>
				 </td>
			 </tr>
					';
					
					$perfPayPalButtonID = $result->perfPayPalButtonID;
					
					// Line below is test code to use different Notify URLs for each button
					//$notifyTag = '<input type="hidden" name="notify_url" value="'.get_site_url().'/wp-content/plugins/stageshow/stageshow_NotifyURL_x'.$result->perfID.'.php"/>'."\n";
					
					if (($lastPerfDateTime !== $result->perfDateTime) || defined('STAGESHOW_BOXOFFICE_ALLDATES'))
					{
						$formattedPerfDateTime = $myDBaseObj->FormatDateForDisplay($result->perfDateTime);
						echo '<tr><td>&nbsp;</td></tr>';
					}
					else
						$formattedPerfDateTime = '&nbsp;';
						
					if (($result->perfNote !== '') && ($result->perfNotePosn === 'above'))
					{
						if ($lastPerfDateTime !== $result->perfDateTime)
							echo '<tr><td class="stageshow-boxoffice-perfnote">'.$result->perfNote . "<td><tr>\n"; 
					}
					
					$rowClass = $oddPage ? "stageshow-boxoffice-oddrow" : "stageshow-boxoffice-evenrow";
					$oddPage = !$oddPage;
					
					echo '
			 <tr class="stageshow-boxoffice-row .'.$rowClass.'">
				 <td class="stageshow-boxoffice-data">
					<form target="paypal" action="'.$payPalAPIObj->PayPalURL.'" method="post">
					<input type="hidden" name="os0" value="'.$result->priceType.'"/>
					<input type="hidden" name="hosted_button_id" value="'.$perfPayPalButtonID.'"/>
					<table width="100%" cellspacing="0">
						<tr>
						'.$hiddenTags.'
						'.$notifyTag.'
						<td width="'.$widthCol1.'" class="stageshow-boxoffice-datetime">'.$formattedPerfDateTime.'</td>
						<td width="'.$widthCol2.'" class="stageshow-boxoffice-type">'.$result->priceType.'</td>
						<td width="'.$widthCol3.'" class="stageshow-boxoffice-price">'.$currencySymbol.$result->priceValue.'</td>
						<td width="'.$widthCol4.'" class="stageshow-boxoffice-qty">
							<select name="quantity">
								<option value="1" selected="">1</option>
					';
					for ($no=2; $no<=$myDBaseObj->adminOptions['MaxTicketQty']; $no++)
						echo '<option value="'.$no.'">'.$no.'</option>'."\n";
					echo '
							</select>
						</td>
						<td width="'.$widthCol5.'" class="stageshow-boxoffice-add">
							';											
					if (!$myDBaseObj->IsPerfEnabled($result)) echo '&nbsp;';
					else if ($result->perfSeats == 0) echo '
						'.__('Sold Out', $this->myDomain);
					else echo '
						<input type="submit" value="Add"  alt="'.$altTag.'"/>';
						echo '
						</td>
					</tr>
					</table>
					</form>
				 </td>
			 </tr>
					';
					
					if (($result->perfNote !== '') && ($result->perfNotePosn === 'below'))
					{
						if (($perfIndex == count($results)-1) || ($results[$perfIndex+1]->perfID != $result->perfID))
							echo '<tr><td class="stageshow-boxoffice-perfnote">'.$result->perfNote . "<td><tr>\n"; 
					}
					
					$lastPerfDateTime = $result->perfDateTime;
				}
			}
			if ($perfCount == 0) 
				echo __('Bookings Not Currently Available', $this->myDomain)."<br>\n";
			else echo '
			  </table>';
				
?>
			<br></br>
</div>

<?php
			// Stage Show BoxOffice HTML Output - End 
		}				

		function printAdminPage() {
			//Outputs an admin page
      			
			$myDBaseObj = $this->myDBaseObj;					
			$payPalAPIObj = $myDBaseObj->payPalAPIObj;
			
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
					include 'admin/stageshow_manage_prices.php';      
					new StageShowPricesAdminClass($this->env);
					break;
					
				case STAGESHOW_MENUPAGE_PRICEPLANS :
					include 'admin/stageshowplus_manage_priceplans.php';      
					new StageShowPlusPricePlansAdminClass($this->env);
					break;
					
				case STAGESHOW_MENUPAGE_SALES :
					include 'admin/'.$this->adminClassFilePrefix.'_manage_sales.php';
					$classId = $this->adminClassPrefix.'SalesAdminClass';
					new $classId($this->env);
					break;
					
				case STAGESHOW_MENUPAGE_BUTTONS :
					global $salesManDBaseObj;
					$salesManDBaseObj = $this->myDBaseObj;
					include STAGESHOW_TEST_PATH.'paypal_manage_buttons.php';      
					new PayPalButtonsAdminClass($this->env, $salesManDBaseObj->GetOurButtonsList());
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
					include STAGESHOW_TEST_PATH.'stageshow_test.php';   
					new StageShowTestSettingsAdminClass($this->env);
					break;		
					
				case STAGESHOW_MENUPAGE_TEST:
					include STAGESHOW_TEST_PATH.'stageshow_test.php';   
					new StageShowTestAdminClass($this->env);
					break;
							
				case STAGESHOW_MENUPAGE_DEBUG:
		      		include STAGESHOW_ADMIN_PATH.'stageshow_debug.php';    
					new StageShowDebugAdminClass($this->env);
					break;							
			}
		}//End function printAdminPage()	
		
		function load_admin_styles()
		{
			//echo "<!-- load_admin_styles called! ".plugins_url( 'admin/css/stageshow-admin.css', __FILE__ )." -->\n";
			
			// Add our own style sheet
			wp_enqueue_style( 'stageshow', plugins_url( 'admin/css/stageshow-admin.css', __FILE__ ));
		}

		function StageShow_ap() 
		{
			$myDBaseObj = $this->myDBaseObj;		
			
			if (!isset($this)) {
				return;
			}

			// Array of capabilities in decreasing order of functionality
			$stageShow_caps = array(
				STAGESHOW_CAPABILITY_DEVUSER,
				STAGESHOW_CAPABILITY_SETUPUSER,
				STAGESHOW_CAPABILITY_ADMINUSER,
				STAGESHOW_CAPABILITY_SALESUSER,
				STAGESHOW_CAPABILITY_VALIDATEUSER,
			);
			
			foreach ($stageShow_caps as $stageShow_cap)
			{
				if (current_user_can($stageShow_cap))
				{
					$adminCap = $stageShow_cap;
					break;
				}
			}
			
			if (isset($adminCap) && function_exists('add_menu_page')) 
			{
				$ourPluginName = $myDBaseObj->get_name();
				
				$icon_url = STAGESHOW_ADMIN_IMAGES_URL.'stageshow16grey.png';
				add_menu_page($ourPluginName, $ourPluginName, $adminCap, STAGESHOW_MENUPAGE_ADMINMENU, array(&$this, 'printAdminPage'), $icon_url);
				add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('StageShow Overview', $this->myDomain),__('Overview', $this->myDomain),    $adminCap,                        STAGESHOW_MENUPAGE_ADMINMENU,    array(&$this, 'printAdminPage'));
				add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('Show Editor', $this->myDomain),       __('Shows', $this->myDomain),        STAGESHOW_CAPABILITY_ADMINUSER,   STAGESHOW_MENUPAGE_SHOWS,        array(&$this, 'printAdminPage'));
				if ( file_exists(STAGESHOW_ADMIN_PATH.'stageshowplus_manage_priceplans.php') ) 
					add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('Price Plan Editor', $this->myDomain),  __('Price Plans', $this->myDomain),STAGESHOW_CAPABILITY_ADMINUSER, STAGESHOW_MENUPAGE_PRICEPLANS,   array(&$this, 'printAdminPage'));
				add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('Performance Editor', $this->myDomain),__('Performances', $this->myDomain), STAGESHOW_CAPABILITY_ADMINUSER,   STAGESHOW_MENUPAGE_PERFORMANCES, array(&$this, 'printAdminPage'));
				add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('Price Edit', $this->myDomain),        __('Prices', $this->myDomain),       STAGESHOW_CAPABILITY_ADMINUSER,   STAGESHOW_MENUPAGE_PRICES,       array(&$this, 'printAdminPage'));

				if ( current_user_can(STAGESHOW_CAPABILITY_VALIDATEUSER)
				  || current_user_can(STAGESHOW_CAPABILITY_SALESUSER))
					add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('Sales Admin', $this->myDomain),       __('Sales', $this->myDomain),     $adminCap,                        STAGESHOW_MENUPAGE_SALES,        array(&$this, 'printAdminPage'));
				
				if ( current_user_can(STAGESHOW_CAPABILITY_VALIDATEUSER)
				  || current_user_can(STAGESHOW_CAPABILITY_ADMINUSER))
					add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('Admin Tools', $this->myDomain),       __('Tools', $this->myDomain),     $adminCap,                        STAGESHOW_MENUPAGE_TOOLS,        array(&$this, 'printAdminPage'));

				add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('Edit Settings', $this->myDomain),     __('Settings', $this->myDomain),    STAGESHOW_CAPABILITY_SETUPUSER,   STAGESHOW_MENUPAGE_SETTINGS,     array(&$this, 'printAdminPage'));

				// Show test menu if stageshow_test.php is present
				if ( $this->testModeEnabled )
				{
					add_submenu_page( 'options-general.php', 'StageShow Test', 'StageShow Test', STAGESHOW_CAPABILITY_DEVUSER, STAGESHOW_MENUPAGE_TESTSETTINGS, array(&$this, 'printAdminPage'));

					if (!$myDBaseObj->getOption('Dev_DisableTestMenus'))
					{
						
						if ( file_exists(STAGESHOW_TEST_PATH.'paypal_manage_buttons.php') ) 
							add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('Manage Buttons', $this->myDomain),    __('Buttons', $this->myDomain),   STAGESHOW_CAPABILITY_DEVUSER, STAGESHOW_MENUPAGE_BUTTONS,      array(&$this, 'printAdminPage'));
						
						if ( isset($this->testModeEnabled) )
							add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('TEST', $this->myDomain), __('TEST', $this->myDomain), STAGESHOW_CAPABILITY_DEVUSER, STAGESHOW_MENUPAGE_TEST, array(&$this, 'printAdminPage'));
					}
				}
				
				if ( file_exists(STAGESHOW_ADMIN_PATH.'stageshow_debug.php') )
					add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('DEBUG', $this->myDomain), __('DEBUG', $this->myDomain), STAGESHOW_CAPABILITY_DEVUSER, STAGESHOW_MENUPAGE_DEBUG, array(&$this, 'printAdminPage'));
			}	
			
		}
		
	}
} //End Class StageShowPluginClass

?>