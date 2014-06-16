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
			add_action('admin_enqueue_scripts', array(&$this, 'load_admin_styles') );
			
			// Add a reference to the header
			add_action('wp_head', array(&$this, 'OutputMetaTag'));
			
			// Add a action to check for PayPal redirect
			add_action('wp_loaded', array(&$this, 'ProcessTrolley'));

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
			add_shortcode(STAGESHOW_SHORTCODE_PREFIX."-checkout", array(&$this, 'OutputContent_Trolley'));
			
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
			
			$LogsFolder = ABSPATH . '/' . $myDBaseObj->adminOptions['LogsFolderPath'];
			if (!is_dir($LogsFolder))
				mkdir($LogsFolder, 0644, TRUE);

	  		// FUNCTIONALITY: Activate - Set EMail template to file name ONLY
			// EMail Template defaults to templates folder - remove folders from path
			$myDBaseObj->CheckEmailTemplatePath('EMailTemplatePath');
			
			if (!isset($myDBaseObj->adminOptions['TrolleyType']))
			{
				// Set TrolleyType default ... detect if this is a new install
				if ( ($myDBaseObj->adminOptions['PayPalAPIUser'] == '')
				  && ($myDBaseObj->adminOptions['PayPalAPIPwd'] == '')
				  && ($myDBaseObj->adminOptions['PayPalAPISig'] == '') )
				 {
					$myDBaseObj->adminOptions['TrolleyType'] = 'Integrated';
				 }
				else
					$myDBaseObj->adminOptions['TrolleyType'] = 'PayPal';
					
				$myDBaseObj->adminOptions['CheckoutTimeout'] = PAYPAL_APILIB_CHECKOUT_TIMEOUT_DEFAULT;
			}
			
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
			
			if (!$myDBaseObj->UseIntegratedTrolley())
			{
				if (!$myDBaseObj->adminOptions['PayPalInvChecked'])
				{
					// Check that all PayPal buttons have the SOLDOUTURL set			
					$results = $myDBaseObj->GetAllPerformancesList();
					foreach ($results as $result)
						$myDBaseObj->payPalAPIObj->AdjustInventory($result->perfPayPalButtonID, 0);
					
					$myDBaseObj->adminOptions['PayPalInvChecked'] = true;
				}				
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

		function CheckPayPalParam(&$paramsArray, $paramId, $paramValue, $paramIndex = 0)		
		{
			if ($paramIndex > 0)
				$paramId .= $paramIndex;
				
			
			$paramsArray[$paramId] = $paramValue;
			if (isset($_POST[$paramId]))
			{
				if ($_POST[$paramId] != $paramValue)
				{
					return false;
				}
			}
			else
			{
				return false;
			}
				
			return true;
		}
		
		function ProcessTrolley()
		{
			if (isset($_POST['checkout']))
			{
				$myDBaseObj = $this->myDBaseObj;
				
				// Remove any incomplete Checkouts
				$myDBaseObj->PurgePendingSales();
					
				// Check that request matches contents of cart
				$paypalURL = 'http://www.paypal.com/cgi-bin/webscr';
				$passedParams = array();	// Dummy array used when checking passed params
				$paypalParams = array();
				$ParamsOK = true;
								
				$cartContents = isset($_SESSION['sshow_cart_contents']) ? $_SESSION['sshow_cart_contents'] : array();
				if (isset($myDBaseObj->adminOptions['Dev_ShowTrolley']) && ($myDBaseObj->adminOptions['Dev_ShowTrolley']))
				{
					StageShowLibUtilsClass::print_r($cartContents, 'cartContents');
				}
				$cartIndex = 0;					
				foreach ($cartContents as $priceID => $qty)
				{
					$cartIndex++;
					
					$priceEntry = $myDBaseObj->GetPricesListByPriceID($priceID);
					if (count($priceEntry) == 0)
						return;
					
					// Get performance ticket quantities for each performance	
					$perfID = $priceEntry[0]->perfID;
					isset($totalSales[$perfID]) ? $totalSales[$perfID] += $qty : $totalSales[$perfID] = $qty;	
					$perfSeats[$perfID] = $priceEntry[0]->perfSeats;
					
					$ParamsOK &= $this->CheckPayPalParam($passedParams, "id" , $priceID, $cartIndex);
					$ParamsOK &= $this->CheckPayPalParam($passedParams, "qty" , $qty, $cartIndex);
					if (!$ParamsOK)
					{
						$this->checkoutError  = __('Cannot Checkout', $this->myDomain).' - ';
						$this->checkoutError .= __('Shopping Cart Contents have changed', $this->myDomain);
						return;
					}
					
					$showName = $priceEntry[0]->showName;
					$perfDateTime = $priceEntry[0]->perfDateTime;
					$priceType = $priceEntry[0]->priceType;
					$priceValue = $priceEntry[0]->priceValue;
					$shipping = 0.0;
						
					$fullName = $showName.'-'.$perfDateTime.'-'.$priceType;
						
					$paypalParams['item_name_'.$cartIndex] = $fullName;
					$paypalParams['amount_'.$cartIndex] = $priceValue;
					$paypalParams['quantity_'.$cartIndex] = $qty;
					$paypalParams['shipping_'.$cartIndex] = $shipping;
					
					$saleDetails['itemID' . $cartIndex] = $priceID;
					$saleDetails['qty' . $cartIndex] = $qty;
				}
				
				// Shopping Cart contents have changed if there are "extra" passed parameters 
				$cartIndex++;
				$ParamsOK &= !isset($_POST['id'.$cartIndex]);
				$ParamsOK &= !isset($_POST['qty'.$cartIndex]);
				if (!$ParamsOK)
				{
					$this->checkoutError = __('Cannot Checkout', $this->myDomain).' - ';
					$this->checkoutError .= __('Item(s) removed from Shopping Cart', $this->myDomain);
					return;
				}

				$paypalParams['image_url'] = $myDBaseObj->getImageURL('PayPalLogoImageFile');
				$paypalParams['cpp_header_image'] = $myDBaseObj->getImageURL('PayPalHeaderImageFile');
				$paypalParams['no_shipping'] = '2';
				$paypalParams['business'] = $myDBaseObj->adminOptions['PayPalMerchantID'];	// Can use adminOptions['PayPalAPIEMail']
				$paypalParams['currency_code'] = $myDBaseObj->adminOptions['PayPalCurrency'];
				$paypalParams['cmd'] = '_cart';
				$paypalParams['upload'] = '1';
				//$paypalParams['rm'] = '2';
				//$paypalParams['return'] = 'http://tigs/TestBed';
				$paypalParams['notify_url'] = $myDBaseObj->PayPalNotifyURL;
			
				$paypalMethod = 'GET';				
				if ($paypalMethod == 'GET')
				{
					foreach ($paypalParams as $paypalArg => $paypalParam)
						$paypalURL = add_query_arg($paypalArg, $paypalParam, $paypalURL);
					$paypalParams = array();					
				}
				
				if ($ParamsOK)
  				{
					// Lock tables so we can commit the pending sale
					$this->myDBaseObj->LockSalesTable();
					
					// Check qunatities before we commit 
					foreach ($totalSales as $perfID => $qty)
					{						
						$perfSaleQty = $this->myDBaseObj->GetSalesQtyByPerfID($perfID);	
						$perfSaleQty += $qty;
						$seatsAvailable = $perfSeats[$perfID];
						if ( ($seatsAvailable > 0) && ($seatsAvailable < $perfSaleQty) ) 
						{
							$ParamsOK = false;
						}
					}
					
					if ($ParamsOK)
	  				{
						// Update quantities ...
						$saleId = $this->myDBaseObj->LogPendingSale($saleDetails);
						$paypalURL = add_query_arg('custom', $saleId, $paypalURL);		
					}
						
					// Release Tables
					$this->myDBaseObj->UnLockTables();
					
					if ($ParamsOK)
	  				{
						$_SESSION['sshow_cart_contents'] = array();	// Clear the shopping cart
					
						if ($this->myDBaseObj->adminOptions['Dev_IPNLocalServer'])
						{
							$this->checkoutError .= __('Using Local IPN Server - PayPal Checkout call blocked', $this->myDomain);
						}
						else 
						{
							header( 'Location: '.$paypalURL ) ;
							exit;
						}
					}
					else
					{
						$this->checkoutError = __('Cannot Checkout', $this->myDomain).' - ';
						$this->checkoutError .= __('Sold out for one or more performances', $this->myDomain);
						return;						
					}
				}
/*				
				// This was how I wanted to do it ... but PayPal didn't much care for it...
				$paypalMethod = 'POST';				
				
				for ($redirectCount = 5; $redirectCount>0; $redirectCount--)
				{
					$response = $myDBaseObj->HTTPAction($paypalURL, $paypalParams, $paypalMethod, false);
					//StageShowLibUtilsClass::print_r($response);
					switch ($response['APIStatus'])
					{
						case 301:
						case 302:
							$paypalURL = $response['APIHeaders']['location'];
							$paypalParams = array();
							$paypalParams['cookies'] = $response['APICookies'];
							$paypalMethod = 'GET';
							break;
							
						case 200:
							echo $response['APIResponseText'];
							exit;
								
						default:
							break 2;
							
					}
				}
*/				
				
			}			
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
		
		function HandleTrolley()
		{
			$myDBaseObj = $this->myDBaseObj;
			if ($myDBaseObj->UseIntegratedTrolley())
			{
				if (isset($this->checkoutError))
				{
					echo '<div id="message" class="stageshow-error">'.$this->checkoutError.'</div>';					
				}
				
				$cartContents = isset($_SESSION['sshow_cart_contents']) ? $_SESSION['sshow_cart_contents'] : array();
				
				$myDBaseObj = $this->myDBaseObj;
				
				if (isset($_POST['hosted_button_id']))
				{
					// Get the product ID from posted data
					$ticketType = $_POST['os0'];
					$ticketQty = $_POST['quantity'];
					
					$priceID = $_POST['hosted_button_id'];
					$priceEntry = $myDBaseObj->GetPricesListByPriceID($priceID);				
					
					if (isset($priceEntry[0]->priceID))
					{
						$priceID = $priceEntry[0]->priceID;					
						
						if (isset($cartContents[$priceID]))
						{
							$cartContents[$priceID] += $ticketQty;
						}
						else
						{
							$cartContents[$priceID] = $ticketQty;
						}
						$_SESSION['sshow_cart_contents'] = $cartContents;
					}
				}
				
				if (count($cartContents) > 0)
				{
					$hiddenTags  = "\n";
			
					$doneHeader = false;

					if (isset($_GET['action']))
					{
						switch($_GET['action'])
						{
							case 'remove':
								if (!isset($_GET['id']))
									break;
								$priceID = $_GET['id'];
								unset($cartContents[$priceID]);
								$_SESSION['sshow_cart_contents'] = $cartContents;
								break;
						}
					}
					
					$cartIndex = 0;		
					$runningTotal = 0;			
					foreach ($cartContents as $priceID => $qty)
					{
						$cartIndex++;
						
						$priceEntry = $myDBaseObj->GetPricesListByPriceID($priceID);					
						if (count($priceEntry) == 0)
						{
							$_SESSION['sshow_cart_contents'] = array();
							echo "Shopping Cart Cleared<br>";
							return;
						}
						
						if (!$doneHeader)
						{
							echo '<div class="stageshow-trolley-header"><h2>'.__('Your Shopping Trolley', $this->myDomain).'</h2></div>'."\n";
							echo '<div class="stageshow-trolley">'."\n";
							echo '<form method="post">'."\n";
							echo "<table>\n";
							echo '<tr class="stageshow-trolley-titles">'."\n";
							echo '<td class="stageshow-trolley-show">Show</td>'."\n";
							echo '<td class="stageshow-trolley-datetime">Performance</td>'."\n";
							echo '<td class="stageshow-trolley-type">Type</td>'."\n";
							echo '<td class="stageshow-trolley-qty">Quantity</td>'."\n";
							echo '<td class="stageshow-trolley-price">Price</td>'."\n";
							echo '<td class="stageshow-trolley-remove">&nbsp;</td>'."\n";
							echo "</tr>\n";
							
							$doneHeader = true;
						}
						
						$showName = $priceEntry[0]->showName;
						$perfDateTime = $myDBaseObj->FormatDateForDisplay($priceEntry[0]->perfDateTime);
						$priceType = $priceEntry[0]->priceType;
						$priceValue = $priceEntry[0]->priceValue;
						$total = $myDBaseObj->FormatCurrency($priceValue * $qty);
						$shipping = 0.0;
						
						$runningTotal += $total;
						
						$fullName = $showName.'-'.$perfDateTime.'-'.$priceType;
						
						$removeLineURL = get_permalink();
						$removeLineURL  = add_query_arg('action', 'remove', $removeLineURL);
						$removeLineURL  = add_query_arg('id', $priceEntry[0]->priceID, $removeLineURL);

						echo '<tr class="stageshow-trolley-row">'."\n";
						echo '<td class="stageshow-trolley-show">'.$showName.'</td>'."\n";
						echo '<td class="stageshow-trolley-datetime">'.$perfDateTime.'</td>'."\n";
						echo '<td class="stageshow-trolley-type">'.$priceType.'</td>'."\n";
						echo '<td class="stageshow-trolley-qty">'.$qty.'</td>'."\n";
						echo '<td class="stageshow-trolley-price">'.$total.'</td>'."\n";
						echo '<td class="stageshow-trolley-remove"><a href=' . $removeLineURL . '>'.__('Remove', $this->myDomain).'</a></td>'."\n";
						
						echo "</tr>\n";
						
						$hiddenTags .= '<input type="hidden" name="id'.$cartIndex.'" value="'.$priceID.'"/>'."\n";
						$hiddenTags .= '<input type="hidden" name="qty'.$cartIndex.'" value="'.$qty.'"/>'."\n";
				}
					
					if ($doneHeader)
					{	
						$runningTotal = $myDBaseObj->FormatCurrency($runningTotal);
						
						echo '<tr class="stageshow-trolley-totalrow">'."\n";
						echo '<td>&nbsp;</td>'."\n";
						echo '<td>'.__('Total', $this->myDomain).'</td>'."\n";
						echo '<td>&nbsp;</td>'."\n";
						echo '<td>&nbsp;</td>'."\n";
						echo '<td class="stageshow-trolley-total">'.$runningTotal.'</td>'."\n";
						echo '<td>&nbsp;</td>'."\n";
						echo "</tr>\n";
						
						echo '<tr>'."\n";
						echo '<td align="right" colspan="6" class="stageshow-trolley-checkout">'."\n";
						echo '<input class="button-primary" type="submit" name="checkout" value="'.__('Checkout', $this->myDomain).'"/>'."\n";
						echo '</td>'."\n";
						echo "</tr>\n";
						
						echo "</table>\n";
						echo $hiddenTags;						
						echo '</form>'."\n";					
						echo '</div>'."\n";
					}					
				}
			}
			else if (isset($_POST['hosted_button_id']))
			{
				// Gets here if an attempt is made to add tickets when IPN Local Server debug option is selected
				echo '<div id="message" class="stageshow-error">'.__('PayPal checkout inaccessible - Using Local IPN Server', $this->myDomain).'</div>';							
			}

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
			
			$this->HandleTrolley();
			
			$atts = shortcode_atts(array(
				'id'    => '',
				'count' => '',
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
	      
		  		// Count can be used to limit the number of Shows displayed
				if (isset($atts['count']))
					$count = $atts['count'];
				else
					$count = count($shows);
					
				foreach ( $shows as $show )
				{
					$this->OutputContent_ShowBoxOffice($show->showID);
					if (--$count == 0)
						break;
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
			
			// Get sales quantities and max seats for all performances of this show
			$salesSummary = $myDBaseObj->GetPerformancesListByShowID($showID);
			
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

			if (!$myDBaseObj->UseIntegratedTrolley())
			{
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
			}
    
			if (strlen($myDBaseObj->PayPalNotifyURL) > 0)
				$notifyTag  = '<input type="hidden" name="notify_url" value="'.$myDBaseObj->PayPalNotifyURL.'"/>'."\n";
			else
				$notifyTag = '';
				
			$altTag = $myDBaseObj->adminOptions['OrganisationID'].' '.__('Tickets', $this->myDomain);
?>
			<div class="stageshow-boxoffice">
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
			for ($perfIndex = 0, $priceIndex = 0; $priceIndex<count($results); $priceIndex++)
			{
				$result = $results[$priceIndex];
				
										
				while (($salesSummary[$perfIndex]->perfID != $result->perfID) && ($perfIndex < Count($salesSummary)))
				{
					$perfIndex++;					
				}

				if ($salesSummary[$perfIndex]->perfID != $result->perfID)
				{					
					// Database error ....	
					echo "*********** Database Error! *************";
					return;
				}
				
				$endEntry = (($priceIndex == count($results)-1) || ($results[$priceIndex+1]->perfID != $result->perfID));
				
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
					
					if ($myDBaseObj->UseIntegratedTrolley())
						$perfPayPalButtonID = $result->priceID;
					else
						$perfPayPalButtonID = $result->perfPayPalButtonID;
					
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
					
					if ($myDBaseObj->UseIntegratedTrolley())
					{
						$addTicketParams = '';
					}
					else
					{
						$addTicketURL = $myDBaseObj->PayPalURL;
						$addTicketParams = ' action="'.$addTicketURL.'" target="paypal" ';
					}
					
					echo '
			<tr class="stageshow-boxoffice-row .'.$rowClass.'">
				<td class="stageshow-boxoffice-data">
					<form '.$addTicketParams.' method="post">
					<input type="hidden" name="os0" value="'.$result->priceType.'"/>
					<input type="hidden" name="hosted_button_id" value="'.$perfPayPalButtonID.'"/>
					<table width="100%" cellspacing="0">
						<tr>
						'.$hiddenTags.'
						'.$notifyTag.'
						<td width="'.$widthCol1.'" class="stageshow-boxoffice-datetime">'.$formattedPerfDateTime.'</td>
						<td width="'.$widthCol2.'" class="stageshow-boxoffice-type">'.$result->priceType.'</td>
						<td width="'.$widthCol3.'" class="stageshow-boxoffice-price">'.$myDBaseObj->FormatCurrency($result->priceValue).'</td>
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
																
					if ( ($result->perfSeats >=0) && ($salesSummary[$perfIndex]->totalQty >= $result->perfSeats) )
						echo __('Sold Out', $this->myDomain);
					else 
						echo '<input type="submit" value="Add"  alt="'.$altTag.'"/>';
					
					echo '
						</td>
						</tr>
					</table>
					</form>
				</td>
			</tr>
					';
					
					if ($endEntry)
					{
						if (($result->perfNote !== '') && ($result->perfNotePosn === 'below'))
						{
								echo '<tr><td class="stageshow-boxoffice-perfnote">'.$result->perfNote . "<td><tr>\n"; 
						}
					}
					
					$lastPerfDateTime = $result->perfDateTime;
				}	// End of ... if ($myDBaseObj->IsPerfEnabled($result))
				
				if ($endEntry)
				{
					$perfIndex++;					
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
		
		function OutputContent_Trolley( $atts )
		{
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
			
			// Add our own Javascript
			wp_enqueue_script( 'stageshow', plugins_url( 'admin/js/stageshow-admin.js', __FILE__ ));
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
						if (!$myDBaseObj->UseIntegratedTrolley())
						{
							if ( file_exists(STAGESHOW_TEST_PATH.'paypal_manage_buttons.php') ) 
								add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('Manage Buttons', $this->myDomain),    __('Buttons', $this->myDomain),   STAGESHOW_CAPABILITY_DEVUSER, STAGESHOW_MENUPAGE_BUTTONS,      array(&$this, 'printAdminPage'));							
						}
						
						add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('TEST', $this->myDomain), __('TEST', $this->myDomain), STAGESHOW_CAPABILITY_DEVUSER, STAGESHOW_MENUPAGE_TEST, array(&$this, 'printAdminPage'));
					}
				
					if (!$myDBaseObj->getOption('Dev_DisableTestMenus'))
					{
						if ( file_exists(STAGESHOW_ADMIN_PATH.'stageshow_debug.php') )
							add_submenu_page( STAGESHOW_MENUPAGE_ADMINMENU, __('DEBUG', $this->myDomain), __('DEBUG', $this->myDomain), STAGESHOW_CAPABILITY_DEVUSER, STAGESHOW_MENUPAGE_DEBUG, array(&$this, 'printAdminPage'));
					}
				}
			}	
			
		}
		
	}
} //End Class StageShowPluginClass

?>