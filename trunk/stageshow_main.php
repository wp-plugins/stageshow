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

if (!defined('STAGESHOW_PAYPAL_IPN_NOTIFY_URL'))
	define('STAGESHOW_PAYPAL_IPN_NOTIFY_URL', get_site_url().'/wp-content/plugins/stageshow/stageshow_NotifyURL.php');

include 'include/stageshow_dbase_api.php';      
      
if (!defined('STAGESHOW_ACTIVATE_EMAIL_TEMPLATE_PATH'))
	define('STAGESHOW_ACTIVATE_EMAIL_TEMPLATE_PATH', 'templates/stageshow_EMail.php');

if (!defined('STAGESHOW_ITEMS_PER_PAGE'))
	define('STAGESHOW_ITEMS_PER_PAGE', 12);

if (!defined('STAGESHOW_MAXTICKETCOUNT'))
	define('STAGESHOW_MAXTICKETCOUNT', 4);

if (!class_exists('StageShowPluginClass')) 
{
	class StageShowPluginClass // Define class
	{
		var $pluginName;
		var $myDBaseObj;
		var	$env;
		var $dbaseObj;
		
		function __construct($dbaseObj) 
		{
			add_action('wp_enqueue_scripts', array(&$this, 'load_user_styles') );
			add_action('admin_print_styles', array(&$this, 'load_admin_styles') );
			
			// Add a reference to the header
			add_action('wp_head', array(&$this, 'OutputMetaTag'));

			$this->myDBaseObj = $dbaseObj;
			
			$this->env = array(
		    'caller' => __FILE__,
		    'PluginObj' => $this,
		    'DBaseObj' => $this->myDBaseObj,
			);

			$this->getStageshowOptions();
			
			$myDBaseObj = $this->myDBaseObj;
			$this->pluginName = str_replace('-', ' ', $myDBaseObj->get_name());
			
			//Actions
			add_action('admin_menu', array(&$this, 'StageShow_ap'));
		  
			//Filters
			//Add ShortCode for "front end listing"
			add_shortcode(STAGESHOW_SHORTCODE_PREFIX."-boxoffice", array(&$this, 'OutputContent_BoxOffice'));
		}
		
		function load_user_styles() {
			$this->getStageshowOptions();
			
			//Add Style Sheet
			wp_enqueue_style(STAGESHOW_CODE_PREFIX, STAGESHOW_STYLESHEET_URL); // StageShow core style
		}
		
		//Returns an array of admin options
		function getStageshowOptions() {
			$myDBaseObj = $this->myDBaseObj;
			
			$myDBaseObj->setPayPalCredentials(STAGESHOW_PAYPAL_IPN_NOTIFY_URL);
			
			return $myDBaseObj->adminOptions;
		}
    
		// Saves the admin options to the options data table
		function saveStageshowOptions() {
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
      
			$defaultOptions = $myDBaseObj->GetDefaultOptions();
			foreach ($defaultOptions as $optionKey => $optionValue)
			{
				// Add default values to settings that are not already set
				if (!isset($myDBaseObj->adminOptions[$optionKey]) || ($myDBaseObj->adminOptions[$optionKey] == ''))
					$myDBaseObj->adminOptions[$optionKey] = $optionValue;
			}
			
      // Initialise PayPal target ....
			if (defined('PAYPAL_APILIB_ACTIVATE_TESTMODE'))
			{
				if (defined('PAYPAL_APILIB_ACTIVATE_TESTMODE'))
					$myDBaseObj->adminOptions['PayPalEnv']  = 'sandbox';
				else
					$myDBaseObj->adminOptions['PayPalEnv']  = 'live';
			}
			
			if ($myDBaseObj->adminOptions['PayPalEnv']  == 'sandbox')
			{
				// Pre-configured PayPal Sandbox settings - can be defined in wp-config.php
				if (defined('PAYPAL_APILIB_ACTIVATE_TESTUSER'))
					$myDBaseObj->adminOptions['PayPalAPIUser'] = PAYPAL_APILIB_ACTIVATE_TESTUSER;
				if (defined('PAYPAL_APILIB_ACTIVATE_TESTPWD'))
					$myDBaseObj->adminOptions['PayPalAPIPwd']  = PAYPAL_APILIB_ACTIVATE_TESTPWD;
				if (defined('PAYPAL_APILIB_ACTIVATE_TESTSIG'))
					$myDBaseObj->adminOptions['PayPalAPISig']  = PAYPAL_APILIB_ACTIVATE_TESTSIG;
				if (defined('PAYPAL_APILIB_ACTIVATE_TESTEMAIL'))
					$myDBaseObj->adminOptions['PayPalAPIEMail']  = PAYPAL_APILIB_ACTIVATE_TESTEMAIL;
	    }
			else
			{
				// Pre-configured PayPal "Live" settings - can be defined in wp-config.php
				if (defined('PAYPAL_APILIB_ACTIVATE_LIVEUSER'))
					$myDBaseObj->adminOptions['PayPalAPIUser'] = PAYPAL_APILIB_ACTIVATE_LIVEUSER;
				if (defined('PAYPAL_APILIB_ACTIVATE_LIVEPWD'))
					$myDBaseObj->adminOptions['PayPalAPIPwd']  = PAYPAL_APILIB_ACTIVATE_LIVEPWD;
				if (defined('PAYPAL_APILIB_ACTIVATE_LIVESIG'))
					$myDBaseObj->adminOptions['PayPalAPISig']  = PAYPAL_APILIB_ACTIVATE_LIVESIG;
				if (defined('PAYPAL_APILIB_ACTIVATE_LIVEEMAIL'))
					$myDBaseObj->adminOptions['PayPalAPIEMail']  = PAYPAL_APILIB_ACTIVATE_LIVEEMAIL;				
			}      
				
      if (defined('STAGESHOW_ACTIVATE_ORGANISATION_ID'))
				$myDBaseObj->adminOptions['OrganisationID'] = STAGESHOW_ACTIVATE_ORGANISATION_ID;

			if (defined('STAGESHOW_ACTIVATE_ADMIN_EMAIL')) 
			{
				$myDBaseObj->adminOptions['AdminEMail'] = STAGESHOW_ACTIVATE_ADMIN_EMAIL;
				$myDBaseObj->adminOptions['AuthTxnEMail'] = STAGESHOW_ACTIVATE_ADMIN_EMAIL;
      }
      
			$LogsFolder = ABSPATH . '/' . $myDBaseObj->adminOptions['LogsFolderPath'];
			if (!is_dir($LogsFolder))
				mkdir($LogsFolder, 0644, TRUE);

      $this->saveStageshowOptions();
      
			$setupUserRole = $myDBaseObj->adminOptions['SetupUserRole'];

			// Add capability to submit events to all default users
			// TODO-Improvement Should only do this on first install ....
			$adminRole = get_role($setupUserRole);
			if ( !empty($adminRole) ) 
			{
				// Adding Manage StageShow Capabilities to Administrator					
				if (!$adminRole->has_cap(STAGESHOW_VALIDATEUSER_ROLE))
					$adminRole->add_cap(STAGESHOW_VALIDATEUSER_ROLE);
				if (!$adminRole->has_cap(STAGESHOW_SALESUSER_ROLE))
					$adminRole->add_cap(STAGESHOW_SALESUSER_ROLE);
				if (!$adminRole->has_cap(STAGESHOW_ADMINUSER_ROLE))
					$adminRole->add_cap(STAGESHOW_ADMINUSER_ROLE);
				if (!$adminRole->has_cap(STAGESHOW_SETUPUSER_ROLE))
					$adminRole->add_cap(STAGESHOW_SETUPUSER_ROLE);
			}				
			
			MJSLibUtilsClass::DeleteFile(STAGESHOW_ADMIN_PATH.'stageshow_dbase_api.php');
			MJSLibUtilsClass::DeleteFile(STAGESHOW_ADMIN_PATH.'stageshow_paypal_api.php');
			
      $myDBaseObj->activate();
		}

    function deactivate()
    {
    }

		function OutputMetaTag()
		{
			$myDBaseObj = $this->myDBaseObj;
			
			// Get Version Number
			$pluginID = $myDBaseObj->get_name();
			$pluginVer = $myDBaseObj->get_version();
			
			echo "\n<meta name='$pluginID' content='$pluginID Ver:$pluginVer for WordPress by Malcolm Shergold' />\n";			
		}
		
		function CreateSample()
		{
      $myDBaseObj = $this->myDBaseObj;
      
      // Add Sample PayPal shopping cart Images and URLs
      if (defined('STAGESHOW_SAMPLE_PAYPALLOGOIMAGE_URL'))
				$myDBaseObj->adminOptions['PayPalLogoImageURL'] = STAGESHOW_SAMPLE_PAYPALLOGOIMAGE_URL;
      if (defined('STAGESHOW_SAMPLE_PAYPALHEADERIMAGE_URL'))
	      $myDBaseObj->adminOptions['PayPalHeaderImageURL'] = STAGESHOW_SAMPLE_PAYPALHEADERIMAGE_URL;

      $this->saveStageshowOptions();
      
      $myDBaseObj->CreateSample();
		}
		
		function OutputContent_BoxOffice( $atts )
		{
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
      $results = $myDBaseObj->GetPricesListByShowID($showID);
			$perfCount = 0;
			
      if (count($results) == 0) 
			{
				echo "<!-- StageShow BoxOffice - No Output for ShowID=$showID -->\n";
				return;
			}
      
      $hiddenTags  = "\n";
      $hiddenTags .= '<input type="hidden" name="cmd" value="_s-xclick"/>'."\n";
      if (strlen($myDBaseObj->adminOptions['PayPalLogoImageURL']) > 0) {
        $hiddenTags .= '<input type="hidden" name="image_url" value="'.$payPalAPIObj->GetURL($myDBaseObj->adminOptions['PayPalLogoImageURL']).'"/>'."\n";
      }
      if (strlen($myDBaseObj->adminOptions['PayPalHeaderImageURL']) > 0) {
        $hiddenTags .= '<input type="hidden" name="cpp_header_image" value="'.$payPalAPIObj->GetURL($myDBaseObj->adminOptions['PayPalHeaderImageURL']).'"/>'."\n";
      }

      $hiddenTags .= '<input type="hidden" name="on0" value="TicketType"/>'."\n";      
      $hiddenTags .= '<input type="hidden" name="SiteURL" value="'.get_site_url().'"/>'."\n";
      
      if (strlen($payPalAPIObj->PayPalNotifyURL) > 0)
	      $notifyTag  = '<input type="hidden" name="notify_url" value="'.$payPalAPIObj->PayPalNotifyURL.'"/>'."\n";
      else
				$notifyTag = '';
				
			$altTag = $myDBaseObj->adminOptions['OrganisationID'].' '.__('Tickets', STAGESHOW_DOMAIN_NAME);
?>
			<div class="stageshow-boxoffice">
				<div id="icon-stageshow" class="icon32"></div>
				<h2>
					<?php echo $results[0]->showName; ?>
				</h2>
					<?php      
			
			$widthCol1 = '25%';
			$widthCol2 = '25%';
			$widthCol3 = '15%';
			$widthCol4 = '15%';
			$widthCol5 = '20%';
			
			$lastPerfDateTime = '';
			
			$oddPage = true;
      foreach($results as $result)
			{
				if ($myDBaseObj->IsPerfEnabled($result))
				{
					$perfCount++;
					if ($perfCount == 1) echo '
		 <table width="100%" border="0">
			 <tr>
				 <td>
					<table width="100%" cellspacing="0">
						<tr class="boxoffice-header">
							<td width="'.$widthCol1.'" class="boxoffice-datetime">Date/Time</td>
							<td width="'.$widthCol2.'" class="boxoffice-type">Ticket Type</td>
							<td width="'.$widthCol3.'" class="boxoffice-price">Price</td>
							<td width="'.$widthCol4.'" class="boxoffice-qty">Qty</td>
							<td width="'.$widthCol5.'" class="boxoffice-add">&nbsp;</td>
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
						
					$rowClass = $oddPage ? "boxoffice-oddrow" : "boxoffice-evenrow";
					$oddPage = !$oddPage;
					
					echo '
			 <tr class="boxoffice-row .'.$rowClass.'">
				 <td class="boxoffice-data">
					<form target="paypal" action="'.$payPalAPIObj->PayPalURL.'" method="post">
					<input type="hidden" name="os0" value="'.$result->priceType.'"/>
					<input type="hidden" name="hosted_button_id" value="'.$perfPayPalButtonID.'"/>
					<table width="100%" cellspacing="0">
						<tr>
						'.$hiddenTags.'
						'.$notifyTag.'
						<td width="'.$widthCol1.'" class="boxoffice-datetime">'.$formattedPerfDateTime.'</td>
						<td width="'.$widthCol2.'" class="boxoffice-type">'.$result->priceType.'</td>
						<td width="'.$widthCol3.'" class="boxoffice-price">'.$result->priceValue.'</td>
						<td width="'.$widthCol4.'" class="boxoffice-qty">
							<select name="quantity">
								<option value="1" selected="">1</option>
					';
					for ($no=2; $no<=$myDBaseObj->adminOptions['MaxTicketQty']; $no++)
						echo '<option value="'.$no.'">'.$no.'</option>'."\n";
					echo '
							</select>
						</td>
						<td width="'.$widthCol5.'">
							';											
					if (!$myDBaseObj->IsPerfEnabled($result)) echo '&nbsp;';
					else if ($result->perfSeats == 0) echo '
						'.__('Sold Out', STAGESHOW_DOMAIN_NAME);
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
					
					$lastPerfDateTime = $result->perfDateTime;
				}
			}
			if ($perfCount == 0) 
				echo __('Bookings closed', STAGESHOW_DOMAIN_NAME)."<br>\n";
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
					include 'admin/stageshow_manage_overview.php';
					new StageShowOverviewAdminClass($this->env);						
					break;
					
        case STAGESHOW_MENUPAGE_SHOWS:
					include 'admin/stageshow_manage_shows.php';      
					new StageShowShowsAdminClass($this->env);
          break;
          
        case STAGESHOW_MENUPAGE_PERFORMANCES :
					include 'admin/stageshow_manage_performances.php';      
					new StageShowPerformancesAdminClass($this->env);
					break;
					
				case STAGESHOW_MENUPAGE_PRICES :
					include 'admin/stageshow_manage_prices.php';      
					new StageShowPricesAdminClass($this->env);
					break;
					
				case STAGESHOW_MENUPAGE_PRESETS :
					include 'admin/stageshow_manage_presets.php';      
					new StageShowPresetsAdminClass($this->env);
					break;
					
				case STAGESHOW_MENUPAGE_SALES :
					include 'admin/stageshow_manage_sales.php';      
					new StageShowAdminSalesClass($this->env);
					break;
					
				case STAGESHOW_MENUPAGE_BUTTONS :
					global $salesManDBaseObj;
					$salesManDBaseObj = $this->myDBaseObj;
					
					if (!defined('SALESMAN_INCLUDE_PATH'))
						define ('SALESMAN_INCLUDE_PATH', STAGESHOW_INCLUDE_PATH);
					if (!defined('SALESMAN_DOMAIN_NAME'))
						define ('SALESMAN_DOMAIN_NAME', STAGESHOW_DOMAIN_NAME);
						
					include 'admin/salesman_manage_buttons.php';      
					new ButtonsManAdminButtonsClass($this->env);
					break;
					
				case STAGESHOW_MENUPAGE_SETTINGS :
					include 'admin/stageshow_manage_settings.php';      
					new StageShowManageSettingsClass($this->env);
					break;
          
				case STAGESHOW_MENUPAGE_TOOLS:
					include 'admin/stageshow_manage_tools.php';      
					new StageShowToolsAdminClass($this->env);
					break;
							
				case STAGESHOW_MENUPAGE_TEST:
		      include 'admin/stageshow_test.php';   
					new StageShowTestAdminClass($this->env);
					break;
							
				case STAGESHOW_MENUPAGE_DEBUG:
		      include 'admin/stageshow_debug.php';    
					new StageShowDebugAdminClass($this->env);
					break;							
			}
		}//End function printAdminPage()	
		
		function load_admin_styles()
		{
			//echo "<!-- load_admin_styles called! ".plugins_url( 'admin/css/admin.css', __FILE__ )." -->\n";
			
			// Add our own style sheet
			wp_enqueue_style( 'stageshow', plugins_url( 'admin/css/admin.css', __FILE__ ));
		}

		function StageShow_ap() 
		{
			$myDBaseObj = $this->myDBaseObj;		
			
			if (!isset($this)) {
				return;
			}

			if (function_exists('add_menu_page')) 
			{
				$pluginName = $myDBaseObj->get_name();
				
				$icon_url = STAGESHOW_ADMIN_IMAGES_URL.'stageshow16grey.png';
				add_menu_page($pluginName, $pluginName, STAGESHOW_SALESUSER_ROLE, STAGESHOW_MENUPAGE_ADMINMENU, array(&$this, 'printAdminPage'), $icon_url);
				add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('StageShow Overview', STAGESHOW_DOMAIN_NAME),__('Overview', STAGESHOW_DOMAIN_NAME),    STAGESHOW_SALESUSER_ROLE, STAGESHOW_MENUPAGE_ADMINMENU,    array(&$this, 'printAdminPage'));
				add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('Show Editor', STAGESHOW_DOMAIN_NAME),       __('Show', STAGESHOW_DOMAIN_NAME),        STAGESHOW_ADMINUSER_ROLE, STAGESHOW_MENUPAGE_SHOWS,        array(&$this, 'printAdminPage'));
				add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('Performance Editor', STAGESHOW_DOMAIN_NAME),__('Performance', STAGESHOW_DOMAIN_NAME), STAGESHOW_ADMINUSER_ROLE, STAGESHOW_MENUPAGE_PERFORMANCES, array(&$this, 'printAdminPage'));
				if ( file_exists(STAGESHOW_ADMIN_PATH.'stageshow_manage_presets.php') ) 
					add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('Presets Editor', STAGESHOW_DOMAIN_NAME),  __('Presets', STAGESHOW_DOMAIN_NAME),     STAGESHOW_ADMINUSER_ROLE, STAGESHOW_MENUPAGE_PRESETS,      array(&$this, 'printAdminPage'));
				add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('Price Edit', STAGESHOW_DOMAIN_NAME),        __('Price', STAGESHOW_DOMAIN_NAME),       STAGESHOW_ADMINUSER_ROLE, STAGESHOW_MENUPAGE_PRICES,       array(&$this, 'printAdminPage'));
				add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('Sales Admin', STAGESHOW_DOMAIN_NAME),       __('Sales', STAGESHOW_DOMAIN_NAME),       STAGESHOW_SALESUSER_ROLE, STAGESHOW_MENUPAGE_SALES,        array(&$this, 'printAdminPage'));
				add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('Admin Tools', STAGESHOW_DOMAIN_NAME),       __('Tools', STAGESHOW_DOMAIN_NAME),       STAGESHOW_ADMINUSER_ROLE, STAGESHOW_MENUPAGE_TOOLS,        array(&$this, 'printAdminPage'));
				add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('Edit Settings', STAGESHOW_DOMAIN_NAME),     __('Settings', STAGESHOW_DOMAIN_NAME),    STAGESHOW_SETUPUSER_ROLE, STAGESHOW_MENUPAGE_SETTINGS,     array(&$this, 'printAdminPage'));

				if (defined('STAGESHOW_ENABLE_TEST') && (STAGESHOW_ENABLE_TEST === true))
				{
					if ( file_exists(STAGESHOW_ADMIN_PATH.'salesman_manage_buttons.php') ) 
						add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('Manage Buttons', STAGESHOW_DOMAIN_NAME),    __('Buttons', STAGESHOW_DOMAIN_NAME),   STAGESHOW_ADMINUSER_ROLE, STAGESHOW_MENUPAGE_BUTTONS,      array(&$this, 'printAdminPage'));
						
					// Show test menu if stageshow_test.php is present
					if ( file_exists(STAGESHOW_ADMIN_PATH.'stageshow_test.php') )
						add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('TEST', STAGESHOW_DOMAIN_NAME), __('TEST', STAGESHOW_DOMAIN_NAME), STAGESHOW_DEVUSER_ROLE, STAGESHOW_MENUPAGE_TEST, array(&$this, 'printAdminPage'));
		      
					// Show debug menu if stageshow_debug.php is present
					if ( file_exists(STAGESHOW_ADMIN_PATH.'stageshow_debug.php') )
						add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('DEBUG', STAGESHOW_DOMAIN_NAME), __('DEBUG', STAGESHOW_DOMAIN_NAME), STAGESHOW_DEVUSER_ROLE, STAGESHOW_MENUPAGE_DEBUG, array(&$this, 'printAdminPage'));
				}
			}	
		}
		
	}
} //End Class StageShowPluginClass

?>