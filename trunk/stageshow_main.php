<?php
/* 
Description: StageShow Plugin Top Level Code
 
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

include 'stageshow_defs.php';

if (!defined('STAGESHOW_PAYPAL_IPN_NOTIFY_URL'))
	define('STAGESHOW_PAYPAL_IPN_NOTIFY_URL', get_site_url().'/wp-content/plugins/stageshow/stageshow_NotifyURL.php');

include 'admin/stageshow_paypal_api.php';      
include 'admin/stageshow_dbase_api.php';      
      
if (!defined('STAGESHOW_ACTIVATE_EMAIL_TEMPLATE_PATH'))
	define('STAGESHOW_ACTIVATE_EMAIL_TEMPLATE_PATH', 'templates/stageshow_EMail.php');

if (!defined('STAGESHOW_SALES_PER_PAGE'))
	define('STAGESHOW_SALES_PER_PAGE', 20);

if (!defined('STAGESHOW_MAXTICKETCOUNT'))
	define('STAGESHOW_MAXTICKETCOUNT', 4);

if (!class_exists('StageShowPluginClass')) {
	class StageShowPluginClass {
		var $pluginName;
		
		function StageShowPluginClass() { //constructor	
			add_action('admin_print_styles', array(&$this, 'load_styles') );
			
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
			global $stageShowDBaseObj;
			global $myPayPalAPILiveObj;
			global $myPayPalAPITestObj;
			
			$this->setPayPalCredentials();
			
			return $stageShowDBaseObj->adminOptions;
		}
    
		// Saves the admin options to the PayPal object(s)
		function setPayPalCredentials() 
		{
			global $stageShowDBaseObj;
			global $myPayPalAPILiveObj;
			global $myPayPalAPITestObj;
			
			$myPayPalAPITestObj->SetLoginParams(
				$stageShowDBaseObj->adminOptions['PayPalAPITestUser'], 
				$stageShowDBaseObj->adminOptions['PayPalAPITestPwd'], 
				$stageShowDBaseObj->adminOptions['PayPalAPITestSig'], 
				$stageShowDBaseObj->adminOptions['PayPalCurrency'], 
				$stageShowDBaseObj->adminOptions['PayPalAPITestEMail']);
				
			$myPayPalAPILiveObj->SetLoginParams(
				$stageShowDBaseObj->adminOptions['PayPalAPILiveUser'], 
				$stageShowDBaseObj->adminOptions['PayPalAPILivePwd'], 
				$stageShowDBaseObj->adminOptions['PayPalAPILiveSig'], 
				$stageShowDBaseObj->adminOptions['PayPalCurrency'], 
				$stageShowDBaseObj->adminOptions['PayPalAPILiveEMail']);
				
			if ($stageShowDBaseObj->adminOptions['Dev_ShowPayPalIO'] == 1)
			{
				$myPayPalAPITestObj->EnableDebug();
				$myPayPalAPILiveObj->EnableDebug();
			}
		}
    
		// Saves the admin options to the options data table
		function saveStageshowOptions() {
			global $stageShowDBaseObj;
			
			$this->setPayPalCredentials();
			
			$stageShowDBaseObj->saveOptions();
		}
    
    // ----------------------------------------------------------------------
    // Activation / Deactivation Functions
    // ----------------------------------------------------------------------
    
    function activate() {
			global $stageShowDBaseObj;
          
      // Pre-configured PayPal Sandbox settings - can be defined in wp-config.php
      if (defined('STAGESHOW_ACTIVATE_PAYPALAPI_TESTUSER'))
				$stageShowDBaseObj->adminOptions['PayPalAPITestUser'] = STAGESHOW_ACTIVATE_PAYPALAPI_TESTUSER;
      if (defined('STAGESHOW_ACTIVATE_PAYPALAPI_TESTPWD'))
	      $stageShowDBaseObj->adminOptions['PayPalAPITestPwd']  = STAGESHOW_ACTIVATE_PAYPALAPI_TESTPWD;
      if (defined('STAGESHOW_ACTIVATE_PAYPALAPI_TESTSIG'))
	      $stageShowDBaseObj->adminOptions['PayPalAPITestSig']  = STAGESHOW_ACTIVATE_PAYPALAPI_TESTSIG;
      if (defined('STAGESHOW_ACTIVATE_PAYPALAPI_TESTEMAIL'))
	      $stageShowDBaseObj->adminOptions['PayPalAPITestEMail']  = STAGESHOW_ACTIVATE_PAYPALAPI_TESTEMAIL;
            
      // Pre-configured PayPal "Live" settings - can be defined in wp-config.php
      if (defined('STAGESHOW_ACTIVATE_PAYPALAPI_LIVEUSER'))
				$stageShowDBaseObj->adminOptions['PayPalAPILiveUser'] = STAGESHOW_ACTIVATE_PAYPALAPI_LIVEUSER;
      if (defined('STAGESHOW_ACTIVATE_PAYPALAPI_LIVEPWD'))
	      $stageShowDBaseObj->adminOptions['PayPalAPILivePwd']  = STAGESHOW_ACTIVATE_PAYPALAPI_LIVEPWD;
      if (defined('STAGESHOW_ACTIVATE_PAYPALAPI_LIVESIG'))
	      $stageShowDBaseObj->adminOptions['PayPalAPILiveSig']  = STAGESHOW_ACTIVATE_PAYPALAPI_LIVESIG;
      if (defined('STAGESHOW_ACTIVATE_PAYPALAPI_LIVEEMAIL'))
	      $stageShowDBaseObj->adminOptions['PayPalAPILiveEMail']  = STAGESHOW_ACTIVATE_PAYPALAPI_LIVEEMAIL;
      
      // Initialise PayPal target ....
      if ( (strlen($stageShowDBaseObj->adminOptions['PayPalAPILiveUser']) > 0) && 
			     (strlen($stageShowDBaseObj->adminOptions['PayPalAPILivePwd']) > 0) && 
			     (strlen($stageShowDBaseObj->adminOptions['PayPalAPILiveSig']) > 0) )
				$stageShowDBaseObj->adminOptions['PayPalEnv']  = 'live';
			else
				$stageShowDBaseObj->adminOptions['PayPalEnv']  = 'sandbox';
				
      if (defined('STAGESHOW_ACTIVATE_ORGANISATION_ID'))
				$stageShowDBaseObj->adminOptions['OrganisationID'] = STAGESHOW_ACTIVATE_ORGANISATION_ID;
      if (defined('STAGESHOW_ACTIVATE_ADMIN_ID'))
				$stageShowDBaseObj->adminOptions['AdminID'] = STAGESHOW_ACTIVATE_ADMIN_ID;
      if (defined('STAGESHOW_ACTIVATE_ADMIN_EMAIL')) {
				$stageShowDBaseObj->adminOptions['AdminEMail'] = STAGESHOW_ACTIVATE_ADMIN_EMAIL;
				$stageShowDBaseObj->adminOptions['BookingsEMail'] = STAGESHOW_ACTIVATE_ADMIN_EMAIL;
				$stageShowDBaseObj->adminOptions['SentCopyEMail'] = STAGESHOW_ACTIVATE_ADMIN_EMAIL;
      }
      
      $stageShowDBaseObj->adminOptions['EMailTemplatePath'] = STAGESHOW_ACTIVATE_EMAIL_TEMPLATE_PATH;
      
			$LogsFolder = ABSPATH . '/' . $stageShowDBaseObj->adminOptions['LogsFolderPath'];
			if (!is_dir($LogsFolder))
				mkdir($LogsFolder, 0644, TRUE);
						
      $this->saveStageshowOptions();
      
      $stageShowDBaseObj->activate();
		}

    function deactivate()
    {
    }

		function dm_prevent_update_check( $r, $url ) 
		{
			if ( 0 === strpos( $url, 'http://api.wordpress.org/plugins/update-check/' ) ) 
			{
					$my_plugin = plugin_basename( __FILE__ );
					$plugins = unserialize( $r['body']['plugins'] );
					unset( $plugins->plugins[$my_plugin] );
					unset( $plugins->active[array_search( $my_plugin, $plugins->active )] );
					$r['body']['plugins'] = serialize( $plugins );
			}
			return $r;
		}

		function CreateSample()
		{
      global $stageShowDBaseObj;
      
      // Add Sample PayPal shopping cart Images and URLs
      if (defined('STAGESHOW_SAMPLE_PAYPALLOGOIMAGE_URL'))
				$stageShowDBaseObj->adminOptions['PayPalLogoImageURL'] = STAGESHOW_SAMPLE_PAYPALLOGOIMAGE_URL;
      if (defined('STAGESHOW_SAMPLE_PAYPALHEADERIMAGE_URL'))
	      $stageShowDBaseObj->adminOptions['PayPalHeaderImageURL'] = STAGESHOW_SAMPLE_PAYPALHEADERIMAGE_URL;

      $this->saveStageshowOptions();
      
      $stageShowDBaseObj->CreateSample();
		}
		
		function IsOptionChanged($optionID1, $optionID2 = '', $optionID3 = '', $optionID4 = '')
		{
      global $stageShowDBaseObj;
      
			if (isset($_POST[$optionID1]) && ($this->GetArrayElement($stageShowDBaseObj->adminOptions, $optionID1) !== trim($_POST[$optionID1])))
				return true;
			
			if ($optionID2 === '') return false;			
			if (isset($_POST[$optionID2]) && ($this->GetArrayElement($stageShowDBaseObj->adminOptions, $optionID2) !== trim($_POST[$optionID2])))
				return true;
			
			if ($optionID3 === '') return false;			
			if (isset($_POST[$optionID3]) && ($this->GetArrayElement($stageShowDBaseObj->adminOptions, $optionID3) !== trim($_POST[$optionID3])))
				return true;
			
			return false;
		}
		
		function ValidateEmail($ourEMail)
		{
			return true;
		}
		
		function OutputContent_BoxOffice( $atts )
		{
			echo "\n<!-- BoxOffice implemented by StageShow-Plus Wordpress Plugin - http://wordpress.org/extend/plugins/stageshow/ -->\n";
			
      global $stageShowDBaseObj;
			
			$atts = shortcode_atts(array(
				'id'    => '',
				'style' => 'normal' 
			), $atts );
        
      $showID = $atts['id'];
      
      if ( $showID !== '' )
      {
				$this->OutputContent_ShowBoxOffice($showID);
				return;
      }
      
      // Get the ID of the show(s)
      $shows = $stageShowDBaseObj->GetAllShowsList();
      
      foreach ( $shows as $show )
      {
				$this->OutputContent_ShowBoxOffice($show->showID);
      }
    }
     
		function OutputContent_ShowBoxOffice( $showID )
		{
      global $stageShowDBaseObj;
      global $myPayPalAPILiveObj;
      global $myPayPalAPITestObj;
			
			// Choose PayPal target environment
			if ($stageShowDBaseObj->adminOptions['PayPalEnv'] === 'live')
				$myPayPalAPIObj = $myPayPalAPILiveObj;
			else
				$myPayPalAPIObj = $myPayPalAPITestObj;
			
      // Get all database entries for this show ... ordered by date/time then ticket type
      $results = $stageShowDBaseObj->GetPricesListByShowID($showID);
			$perfCount = 0;
			
      if (count($results) == 0) 
			{
				echo "<!-- StageShow BoxOffice - No Output for ShowID=$showID -->\n";
				return;
			}
      
      $hiddenTags  = "\n";
      $hiddenTags .= '<input type="hidden" name="cmd" value="_s-xclick"/>'."\n";
      if (strlen($stageShowDBaseObj->adminOptions['PayPalLogoImageURL']) > 0) {
        $hiddenTags .= '<input type="hidden" name="image_url" value="'.$stageShowDBaseObj->GetURL($stageShowDBaseObj->adminOptions['PayPalLogoImageURL']).'"/>'."\n";
      }
      if (strlen($stageShowDBaseObj->adminOptions['PayPalHeaderImageURL']) > 0) {
        $hiddenTags .= '<input type="hidden" name="cpp_header_image" value="'.$stageShowDBaseObj->GetURL($stageShowDBaseObj->adminOptions['PayPalHeaderImageURL']).'"/>'."\n";
      }

      $hiddenTags .= '<input type="hidden" name="on0" value="TicketType"/>'."\n";      
      $hiddenTags .= '<input type="hidden" name="SiteURL" value="'.get_site_url().'"/>'."\n";
      
      if (strlen($myPayPalAPIObj->PayPalNotifyURL) > 0)
	      $notifyTag  = '<input type="hidden" name="notify_url" value="'.$myPayPalAPIObj->PayPalNotifyURL.'"/>'."\n";
      else
				$notifyTag = '';
				
			$altTag = $stageShowDBaseObj->adminOptions['OrganisationID'].' '.__('Tickets', STAGESHOW_DOMAIN_NAME);
?>
			<div class="boxoffice">
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
			
      foreach($results as $result)
			{
				if ($stageShowDBaseObj->IsPerfEnabled($result))
				{
					$perfCount++;
					if ($perfCount == 1) echo '
		 <table width="100%" border="0">
			 <tr>
				 <td>
					<table width="100%" cellspacing="0">
						<tr>
							<td width="'.$widthCol1.'">Date/Time</td>
							<td width="'.$widthCol2.'">Ticket Type</td>
							<td width="'.$widthCol3.'">Price</td>
							<td width="'.$widthCol4.'">Qty</td>
							<td width="'.$widthCol5.'">&nbsp;</td>
						</tr>
					</table>
				 </td>
			 </tr>
					';
					
					$perfPayPalButtonID = ($stageShowDBaseObj->adminOptions['PayPalEnv'] === 'live' ? $result->perfPayPalLIVEButtonID : $result->perfPayPalTESTButtonID);
					//echo "perfPayPalButtonID = $perfPayPalButtonID<br>\n";
					
					// Line below is test code to use different Notify URLs for each button
					//$notifyTag = '<input type="hidden" name="notify_url" value="'.get_site_url().'/wp-content/plugins/stageshow/stageshow_NotifyURL_x'.$result->perfID.'.php"/>'."\n";
					
					if ($lastPerfDateTime !== $result->perfDateTime)
					{
						$formattedPerfDateTime = $stageShowDBaseObj->FormatDateForDisplay($result->perfDateTime);
						echo '<tr><td>&nbsp;</td></tr>';
					}
					else
						$formattedPerfDateTime = '&nbsp;';
						
					echo '
			 <tr id="boxoffice-row">
				 <td id="boxoffice-data">
					<form target="paypal" action="'.$myPayPalAPIObj->PayPalURL.'" method="post">
					<input type="hidden" name="os0" value="'.$result->priceType.'"/>
					<input type="hidden" name="hosted_button_id" value="'.$perfPayPalButtonID.'"/>
					<table cellspacing="0">
						<tr>
						'.$hiddenTags.'
						'.$notifyTag.'
						<td width="'.$widthCol1.'">'.$formattedPerfDateTime.'</td>
						<td width="'.$widthCol2.'">'.$result->priceType.'</td>
						<td width="'.$widthCol3.'">'.$result->priceValue.'</td>
						<td width="'.$widthCol4.'">
							<select name="quantity">
								<option value="1" selected="">1</option>
					';
					for ($no=2; $no<=STAGESHOW_MAXTICKETCOUNT; $no++)
						echo '<option value="'.$no.'">'.$no.'</option>'."\n";
					echo '
							</select>
						</td>
						<td width="'.$widthCol5.'">
							';											
					if (!$stageShowDBaseObj->IsPerfEnabled($result)) echo '&nbsp;';
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
    
function ShowPageNavigation( $which, $current_item, $total_items, $total_pages ) 
{
	// $which is 'top' ot 'bottom'

	$output = '<span class="displaying-num">' . sprintf( _n( '1 item', '%s items', $total_items ), number_format_i18n( $total_items ) ) . '</span>';

	$current_url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

	$current_url = remove_query_arg( array( 'hotkeys_highlight_last', 'hotkeys_highlight_first' ), $current_url );

	$page_links = array();

	$disable_first = $disable_last = '';
	if ( $current_item == 1 )
		$disable_first = ' disabled';
	if ( $current_item == $total_pages )
		$disable_last = ' disabled';

	$page_links[] = sprintf( "<a class='%s' title='%s' %s>%s</a>",
		'first-page' . $disable_first,
		$disable_first === '' ? esc_attr__('Go to the first page') : '',
		$disable_first === '' ? 'href='.esc_url( remove_query_arg( 'paged', $current_url ) ) : '',
		'&laquo;'
	);

	$page_links[] = sprintf( "<a class='%s' title='%s' %s>%s</a>",
		'prev-page' . $disable_first,
		$disable_first === '' ? esc_attr__('Go to the previous page') : '',
		$disable_first === '' ? 'href='.esc_url( add_query_arg( 'paged', max( 1, $current_item-1 ), $current_url ) ) : '',
		'&lsaquo;'
	);

	if ( 'bottom' == $which )
		$html_current_page = $current_item;
	else
		$html_current_page = sprintf( "<input class='current-page' title='%s' type='text' name='%s' value='%s' size='%d' />",
			esc_attr__( 'Current page' ),
			esc_attr( 'paged' ),
			$current_item,
			strlen( $total_pages )
		);

	$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
	$page_links[] = '<span class="paging-input">' . sprintf( _x( '%1$s of %2$s', 'paging' ), $html_current_page, $html_total_pages ) . '</span>';

	$page_links[] = sprintf( "<a class='%s' title='%s' %s>%s</a>",
		'next-page' . $disable_last,
		$disable_last === '' ? esc_attr__('Go to the next page') : '',
		$disable_last === '' ? 'href='.esc_url( add_query_arg( 'paged', min( $total_pages, $current_item+1 ), $current_url ) ) : '',
		'&rsaquo;'
	);

	$page_links[] = sprintf( "<a class='%s' title='%s' %s>%s</a>",
		'last-page' . $disable_last,
		$disable_last === '' ? esc_attr__('Go to the last page') : '',
		$disable_last === '' ? 'href='.esc_url( add_query_arg( 'paged', $total_pages, $current_url ) ) : '',
		'&raquo;'
	);

	$output .= "\n" . join( "\n", $page_links );

	$page_class = $total_pages < 2 ? ' one-page' : '';

	echo "<div class='tablenav-pages{$page_class}'>$output</div>";
}

		function Output_confirmDeleteScript()
		{
      echo '
<script>
function confirmDelete(userMsg) 
{
//  return (confirm("Are you sure you want to delete"));
  return (confirm("Delete "+userMsg+"?"));
}
</script>
';      		
		}
		
		function printAdminPage() {
			global $stageShowDBaseObj;		
			//Prints out an admin page
      			
			$this->Output_confirmDeleteScript();
			
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
		
		function load_styles()
		{
			//echo "<!-- load_styles called! -->\n";
			
			// Add our own style sheet
			wp_enqueue_style( 'stageshow', plugins_url( 'admin/css/admin.css', __FILE__ ));
		}

	}
} //End Class StageShowPluginClass

if (!isset($stageShowObj) && class_exists("StageShowPluginClass")) 
{
	global $stageShowObj;
	
	$stageShowObj = new StageShowPluginClass();
}

if ( file_exists(STAGESHOW_ADMIN_PATH.'/stageshow_extns.php') )
{
	include(STAGESHOW_ADMIN_PATH.'/stageshow_extns.php');
}
			
//Initialize the admin panel
if (!function_exists("StageShow_ap")) {
	function StageShow_ap() {
		global $stageShowObj;		
		if (!isset($stageShowObj)) {
			return;
		}

		if (function_exists('add_menu_page')) 
		{
			$icon_url = STAGESHOW_ADMIN_IMAGES_URL.'stageshow16grey.png';
			add_menu_page(STAGESHOW_PLUGINNAME, STAGESHOW_PLUGINNAME, 'manage_options', STAGESHOW_CODE_PREFIX.'_adminmenu', array(&$stageShowObj, 'printAdminPage'), $icon_url);
			add_submenu_page( STAGESHOW_CODE_PREFIX.'_adminmenu', __('StageShow Overview', STAGESHOW_DOMAIN_NAME),__('Overview', STAGESHOW_DOMAIN_NAME),    'manage_options', STAGESHOW_CODE_PREFIX.'_adminmenu',    array(&$stageShowObj, 'printAdminPage'));
			add_submenu_page( STAGESHOW_CODE_PREFIX.'_adminmenu', __('Show Editor', STAGESHOW_DOMAIN_NAME),       __('Show', STAGESHOW_DOMAIN_NAME),        'manage_options', STAGESHOW_CODE_PREFIX.'_shows',        array(&$stageShowObj, 'printAdminPage'));
			add_submenu_page( STAGESHOW_CODE_PREFIX.'_adminmenu', __('Performance Editor', STAGESHOW_DOMAIN_NAME),__('Performance', STAGESHOW_DOMAIN_NAME), 'manage_options', STAGESHOW_CODE_PREFIX.'_performances', array(&$stageShowObj, 'printAdminPage'));
			add_submenu_page( STAGESHOW_CODE_PREFIX.'_adminmenu', __('Price Edit', STAGESHOW_DOMAIN_NAME),        __('Price', STAGESHOW_DOMAIN_NAME),       'manage_options', STAGESHOW_CODE_PREFIX.'_prices',       array(&$stageShowObj, 'printAdminPage'));
			add_submenu_page( STAGESHOW_CODE_PREFIX.'_adminmenu', __('Sales Admin', STAGESHOW_DOMAIN_NAME),       __('Sales', STAGESHOW_DOMAIN_NAME),       'manage_options', STAGESHOW_CODE_PREFIX.'_sales',        array(&$stageShowObj, 'printAdminPage'));
			add_submenu_page( STAGESHOW_CODE_PREFIX.'_adminmenu', __('Admin Tools', STAGESHOW_DOMAIN_NAME),       __('Tools', STAGESHOW_DOMAIN_NAME),       'manage_options', STAGESHOW_CODE_PREFIX.'_tools',        array(&$stageShowObj, 'printAdminPage'));
			add_submenu_page( STAGESHOW_CODE_PREFIX.'_adminmenu', __('Edit Settings', STAGESHOW_DOMAIN_NAME),     __('Settings', STAGESHOW_DOMAIN_NAME),    'manage_options', STAGESHOW_CODE_PREFIX.'_settings',     array(&$stageShowObj, 'printAdminPage'));

      // Show test menu if stageshow_test.php is present
			if ( file_exists(STAGESHOW_ADMIN_PATH.'/stageshow_test.php') )
				add_submenu_page( STAGESHOW_CODE_PREFIX.'_adminmenu', __('TEST', STAGESHOW_DOMAIN_NAME), __('TEST', STAGESHOW_DOMAIN_NAME), 'manage_options', STAGESHOW_CODE_PREFIX.'_test', array(&$stageShowObj, 'printAdminPage'));
      
      // Show debug menu if stageshow_debug.php is present
			if ( file_exists(STAGESHOW_ADMIN_PATH.'/stageshow_debug.php') )
				add_submenu_page( STAGESHOW_CODE_PREFIX.'_adminmenu', __('DEBUG', STAGESHOW_DOMAIN_NAME), __('DEBUG', STAGESHOW_DOMAIN_NAME), 'manage_options', STAGESHOW_CODE_PREFIX.'_debug', array(&$stageShowObj, 'printAdminPage'));
		}	
	}
}

//if (is_admin()) 

//Actions and Filters	
if (isset($stageShowObj)) {
	//Actions
	add_action('admin_menu', 'StageShow_ap');
  
	//Filters
  //Add ShortCode for "front end listing"
  add_shortcode(STAGESHOW_CODE_PREFIX."-boxoffice", array(&$stageShowObj, 'OutputContent_BoxOffice'));
	
  //Add Style Sheet
	wp_enqueue_style('stageshow', STAGESHOW_URL.'css/stageshow.css'); // StageShow core style
}

?>