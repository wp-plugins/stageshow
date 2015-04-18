<?php
/*
Description: Core Library Generic Base Class for Sales Plugins

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

if (!class_exists('StageShowLibSalesCartPluginBaseClass')) 
	include STAGESHOWLIB_INCLUDE_PATH.'stageshowlib_salesplugin_trolley.php';
	
if (!class_exists('StageShowLibSalesPluginBaseClass')) 
{
	include 'stageshowlib_nonce.php';
	
	class StageShowLibSalesPluginBaseClass extends StageShowLibSalesCartPluginBaseClass // Define class
	{
		function __construct()
		{
			$myDBaseObj = $this->myDBaseObj;
			
			parent::__construct();
							
			// Add an action to check for Payment Gateway redirect
			add_action('wp_loaded', array(&$this, 'OnlineStore_ProcessCheckout'));

			// FUNCTIONALITY: Main - Add ShortCode for client "front end"
			add_shortcode($this->shortcode, array(&$this, 'OutputContent_DoShortcode'));
			
			if (defined('STAGESHOWLIB_BLOCK_HTTPS'))
			{
				add_filter('http_api_transports', array($this, 'StageShowLibBlockSSLHttp'), 10, 3);				
			}

		}
		
		function StageShowLibBlockSSLHttp($transports, $args, $url)
		{
			$argsCount = count($args);
			if (($argsCount == 1) && isset($args['ssl']))
			{
//echo "<br> ***************** HTTP SSL Transport Disabled ***************** <br>\n";
				return array();
			}

			return $transports;
		}

		function GetOurURL()
		{			
			$actionURL = remove_query_arg('_wpnonce');
			$actionURL = remove_query_arg('remove', $actionURL);
			$actionURL = remove_query_arg('editpage', $actionURL);
			
			return $actionURL;
		}
		
		
		function load_user_scripts()
		{
			$myDBaseObj = $this->myDBaseObj;			

			$myDBaseObj->gatewayObj->Gateway_LoadUserScripts();
		}	
		
		function load_admin_styles()
		{
			$myDBaseObj = $this->myDBaseObj;			

			$myDBaseObj->gatewayObj->Gateway_LoadAdminStyles();
		}
		
		function GetOnlineStoreItemName($result, $cartEntry = null)
		{
			return $result->stockName;
		}
			
		function GetOnlineStoreMaxSales($result)
		{
			return -1;
		}
			
		function IsOnlineStoreItemAvailable($saleItems)
		{
			return true;
		}

		function OutputContent_OnlineStoreFooter()
		{
		}

		function OutputContent_OnlineStoreMessages()
		{
			if (isset($this->checkoutMsg))
			{
				if (!isset($this->checkoutMsgClass))
				{
					$this->checkoutMsgClass = $this->cssDomain.'-error error';
				}
				echo '<div id="message" class="'.$this->checkoutMsgClass.'">'.$this->checkoutMsg.'</div>';					
			}				
		}
		
		function OutputContent_GetAtts( $atts )
		{
			$atts = shortcode_atts(array(
				'id'    => '',
				'count' => '',
				'anchor' => '',
				'style' => 'normal' 
			), $atts );
        
        	return $atts;
		}
		
		function DefineTranslatedText($text, $domain, $delim = '')
		{
			if ($delim == '')
			{
				$delimStart = '>';
				$delimEnd = '<';				
			}
			else
			{
				$delimStart = $delimEnd = $delim;
			}

			$translation = __($text, $domain);
			if ($text != $translation)
			{
	        	echo "tl8_srch[tl8_srch.length] = '".$delimStart.$text.$delimEnd."';\n";
	        	echo "tl8_repl[tl8_repl.length] = '".$delimStart.$translation.$delimEnd."';\n";
			}
		}
		
		function OutputContent_TrolleyButtonJQuery($atts)
		{
			if (!defined('STAGESHOWLIB_UPDATETROLLEY_TARGET')) return 0;
			
			if (defined('STAGESHOWLIB_DISABLE_JQUERY_BOXOFFICE')) return 0;
			
			static	$shortcodeCount = 0;
			
			$shortcodeCount++;
			
			if ($shortcodeCount == 1)
			{
				echo "\n<script>\n";
				echo "var tl8_srch = [];\n";		
				echo "var tl8_repl = [];\n";
					
				$this->DefineTranslatedText('Show', $this->myDomain);
				$this->DefineTranslatedText('Date & Time', $this->myDomain);
				$this->DefineTranslatedText('Ticket Type', $this->myDomain);
				$this->DefineTranslatedText('Quantity', $this->myDomain);
				$this->DefineTranslatedText('Seat', $this->myDomain);
				$this->DefineTranslatedText('Price', $this->myDomain);

				$this->DefineTranslatedText('Add', $this->myDomain, '"');
				$this->DefineTranslatedText('Remove', $this->myDomain, '"');
				$this->DefineTranslatedText('Reserve', $this->myDomain, '"');
				$this->DefineTranslatedText('Checkout', $this->myDomain, '"');
				$this->DefineTranslatedText('Select Seats', $this->myDomain, '"');

				$this->DefineTranslatedText('Donation', $this->myDomain);
				$this->DefineTranslatedText('Message To Seller', $this->myDomain);
				$this->DefineTranslatedText('Send tickets by post', $this->myDomain);

				$this->DefineTranslatedText('Seat Available', $this->myDomain);
				$this->DefineTranslatedText('Seats Available', $this->myDomain);
								
				echo "</script>\n";
			}

			$jQueryURL = STAGESHOWLIB_URL.'include/'.STAGESHOWLIB_UPDATETROLLEY_TARGET;
			
			echo '<script>
				jQuery(document).ready(
					function()
					{
					}
				);
				
			function stageshowJQuery_OnClickTrolleyButton(obj)
			{
				jQuery("body").css("cursor", "progress");
				
				/* Disable All UI Buttons */
				stageshow_enable_interface("stageshow-trolley-ui", false);
						
				var postvars = {
					jquery: "true"
				};
				';
					
			foreach ($atts as $attKey => $attVal)
			{
        		$attKey = 'scatt_'.$attKey;
				echo '
				postvars.'.$attKey.' = "'.$attVal.'";';
			}
			
			$_wpnonce = StageShowLibNonce::GetStageShowLibNonce(STAGESHOWLIB_UPDATETROLLEY_TARGET);
			if ($_wpnonce != '')
			{
				echo '
				postvars._wpnonce = "'.$_wpnonce.'";';
			}				
			
			if (isset($_REQUEST['action']))
			{
				echo '
				postvars.action = "'.$_REQUEST['action'].'";';
			}				
			
			if ($this->myDBaseObj->isOptionSet('AllowDonation'))
			{
				echo '
				var saleDonationElem = document.getElementById("saleDonation");
				if (saleDonationElem)
				{
					postvars.saleDonation = saleDonationElem.value;
				}';
			}
				
			if ($this->myDBaseObj->isOptionSet('UseNoteToSeller'))
			{
				echo '
				var saleNoteToSellerElem = document.getElementById("saleNoteToSeller");
				if (saleNoteToSellerElem)
				{
					postvars.saleNoteToSeller = saleNoteToSellerElem.value;
				}';
			}
				
			if ($this->myDBaseObj->isOptionSet('PostTicketsEnabled'))
			{
				echo '
				var salePostTicketsElem = document.getElementById("salePostTickets");
				if (salePostTicketsElem)
				{
					if (salePostTicketsElem.checked)
					{
						postvars.salePostTickets = salePostTicketsElem.value;
					}
				}';
			}
				
			echo '
				var buttonId = obj.id;	
				postvars[buttonId] = "submit";
				var qty = 0;
				var nameParts = buttonId.split("_");
				if (nameParts[0] == "AddTicketSale")
				{
					var qtyId = "quantity_" + nameParts[1];
					var qty = document.getElementById(qtyId).value;
					postvars[qtyId] = qty;
				}
				
				/* Get New HTML from Server */
				var url = "'.$jQueryURL.'";
			    jQuery.post(url, postvars,
				    function(data, status)
				    {
						/* Apply translations to any message */
						for (var index=0; index<tl8_srch.length; index++)
						{
							var srchFor = tl8_srch[index];
							var repWith = tl8_repl[index];
							data = StageShowLib_replaceAll(srchFor, repWith, data);
						}
									
						divElem = jQuery("#stageshow-trolley-container'.$shortcodeCount.'");
						divElem.html(data);
						
						jQuery("body").css("cursor", "default");
				    }
			    );
			    
			    return false;
			}

</script>
';
			return $shortcodeCount;
		}
				
		function OutputContent_DoShortcode( $atts )
		{
	  		// FUNCTIONALITY: Runtime - Output Shop Front
			$myDBaseObj = $this->myDBaseObj;
			
			$pluginID = $myDBaseObj->get_pluginName();
			$pluginVer = $myDBaseObj->get_version();
			$pluginAuthor = $myDBaseObj->get_author();
			$pluginURI = $myDBaseObj->get_pluginURI();
		
			$myDBaseObj->AllUserCapsToServervar();
		
			// Remove any incomplete Checkouts
			$myDBaseObj->PurgePendingSales();
			
			$outputContent  = "\n<!-- \n";
			$outputContent .= "$pluginID Plugin Code - Starts Here\n";
			if (is_array($atts))
			{
				foreach ($atts as $attID => $att)
				{
					$outputContent .= "$attID=$att \n";			
				}
			}
			$outputContent .= "--> \n";

			$outputContent .= '<form></form>'."\n";		// Insulate StageShow from unterminated form tags

			$actionURL = $this->GetOurURL();
			$actionURL = remove_query_arg('ppexp', $actionURL);

			$atts = $this->OutputContent_GetAtts($atts);
 			$shortcodeCount = $this->OutputContent_TrolleyButtonJQuery($atts);
		      
        	$ourAnchor = $atts['anchor'];
			if ($ourAnchor != '')
			{
				$pageAnchor = '#'.self::ANCHOR_PREFIX.$ourAnchor;	// i.e. trolley
				if (strpos($actionURL, '?'))
				{
					//$actionURL = str_replace('?', $pageAnchor.'?', $actionURL);
					$actionURL .= $pageAnchor;
				}
				else
				{
					$actionURL .= $pageAnchor;
				}				
			}
			
			$outputContent .= '<form id=trolley method="post" action="'.$actionURL.'">'."\n";				
			
			$divId = $this->cssTrolleyBaseID.'-container'.$shortcodeCount;			
			$outputContent .= "<div id=$divId name=$divId>\n";	
						
			$outputContent .= $myDBaseObj->GetWPNonceField();
				 
			if (isset($this->editpage))
			{
				$outputContent .= '<input type="hidden" name="editpage" value="'.$this->editpage.'"/>'."\n";				
			}				
			
			ob_start();
			$trolleyContent = $this->Cart_OnlineStore_GetCheckoutDetails();	
			if ($trolleyContent == '')	
			{
				$showBoxOffice = true;
				$hasActiveTrolley = $this->Cart_OnlineStore_HandleTrolley();
			}
			else
			{
				// Just output checkout details dialogue
				$showBoxOffice = false;	
				$hasActiveTrolley = false;			
			}
			$trolleyContent = ob_get_contents();
			ob_end_clean();
			
			if ($showBoxOffice)
			{
				ob_start();
				
				$this->Cart_OutputContent_Anchor("boxoffice");
			
				if (!$this->OutputContent_ProcessGatewayCallbacks($atts))
				{
					$this->Cart_OutputContent_OnlineStoreMain($atts);
				}
				
				$this->OutputContent_OnlineStoreFooter();
				
				$boxofficeContent = ob_get_contents();
				ob_end_clean();				
			}
			else
			{
				$boxofficeContent = '';
			}
			
			$this->OutputContent_OnlineStoreMessages();
			
			if ($myDBaseObj->getOption('ProductsAfterTrolley'))
			{
				$outputContent .= $trolleyContent.$boxofficeContent;
			}
			else
			{
				$outputContent .= $boxofficeContent.$trolleyContent;
			}
			
			$outputContent .= '</div>'."\n";	
			$outputContent .= '</form>'."\n";	
			
			$outputContent .= "\n<!-- $pluginID Plugin Code - Ends Here -->\n";
			
			if (!$hasActiveTrolley)
			{
				$boxofficeURL = StageShowLibUtilsClass::GetPageURL();
				if ($myDBaseObj->getOption('boxofficeURL') != $boxofficeURL)
				{
					$myDBaseObj->adminOptions['boxofficeURL'] = $boxofficeURL;
					$myDBaseObj->saveOptions();
				}
			}
			
			return $outputContent;						
		}
		
		
		function GetOnlineStoreTrolleyDetails($cartIndex, $cartEntry)
		{
			$saleDetails['itemID' . $cartIndex] = $cartEntry->itemID;
			$saleDetails['qty' . $cartIndex] = $cartEntry->qty;;
			
			return $saleDetails;
		}

		function OutputContent_ProcessGatewayCallbacks()
		{
			return false;
		}

		function OnlineStore_ScanCheckoutSales()
		{
			$myDBaseObj = $this->myDBaseObj;
				
			// Check that request matches contents of cart
			$passedParams = array();	// Dummy array used when checking passed params
			
			$rslt = new stdClass();
			$rslt->saleDetails = array();
			$rslt->paypalParams = array();
			$ParamsOK = true;
			
			$rslt->totalDue = 0;
								
			$cartContents = $this->GetTrolleyContents();
			if ($this->myDBaseObj->dev_ShowTrolley())
			{
				StageShowLibUtilsClass::print_r($cartContents, 'cartContents');
			}
			
			$rslt->saleDetails['saleNoteToSeller'] = StageShowLibDBaseClass::GetSafeString('saleNoteToSeller', '');
			
			if (!isset($cartContents->rows))
			{
				$rslt->checkoutMsg  = __('Cannot Checkout', $this->myDomain).' - ';
				$rslt->checkoutMsg .= __('Shopping Trolley Empty', $this->myDomain);
				return $rslt;
			}
			
			// Build request parameters for redirect to Payment Gateway checkout
			$paramCount = 0;
			foreach ($cartContents->rows as $cartIndex => $cartEntry)
			{				
				$paramCount++;
				$itemID = $cartEntry->itemID;
				$qty = $cartEntry->qty;
					
				$priceEntries = $this->GetOnlineStoreProductDetails($itemID);
				if (count($priceEntries) == 0)
					return $rslt;
					
				// Get sales quantities for each item
				$priceEntry = $priceEntries[0];
				$stockID = $this->GetOnlineStoreStockID($priceEntry);
				isset($rslt->totalSales[$stockID]) ? $rslt->totalSales[$stockID] += $qty : $rslt->totalSales[$stockID] = $qty;
						
				// Save the maximum number of sales for this stock item to a class variable
				$rslt->maxSales[$stockID] = $this->GetOnlineStoreMaxSales($priceEntry);

				$ParamsOK &= $this->CheckGatewayParam($passedParams, "id" , $cartEntry->itemID, $cartIndex);
				$ParamsOK &= $this->CheckGatewayParam($passedParams, "qty" , $cartEntry->qty, $cartIndex);
				if (!$ParamsOK)
				{
					$rslt->checkoutMsg  = __('Cannot Checkout', $this->myDomain).' - ';
					$rslt->checkoutMsg .= __('Shopping Trolley Contents have changed', $this->myDomain);
					return $rslt;
				}
					
				$itemPrice = $this->GetOnlineStoreItemPrice($priceEntry);
				$shipping = 0.0;						
				
				$itemName = $this->GetOnlineStoreItemName($priceEntry, $cartEntry);
				
				$myDBaseObj->gatewayObj->AddItem($itemName, $itemPrice, $qty, $shipping);
				
				$rslt->saleDetails = array_merge($rslt->saleDetails, $this->GetOnlineStoreTrolleyDetails($paramCount, $cartEntry));
				$rslt->saleDetails['itemPaid' . $paramCount] = $itemPrice;
				
				$rslt->totalDue += ($itemPrice * $qty);
			}
			
			$this->OnlineStore_AddExtraPayment($rslt, $cartContents->saleTransactionFee, __('Booking Fee', $this->myDomain), 'saleTransactionfee');

			if (isset($_POST['saleDonation']))
			{
				$currencySymbol = $this->myDBaseObj->getOption('CurrencySymbol');
				$newSaleDonation = StageShowLibHTTPIO::GetRequestedCurrency('saleDonation', false);
				$cartContents->saleDonation = $myDBaseObj->FormatCurrency($newSaleDonation);					
			}	
			
			if ($cartContents->saleDonation > 0)
			{
				$this->OnlineStore_AddExtraPayment($rslt, $cartContents->saleDonation, __('Donation', $this->myDomain), 'saleDonation');				
			}	
			
			// Shopping Trolley contents have changed if there are "extra" passed parameters 
			$cartIndex++;
			$ParamsOK &= !isset($_POST['id'.$cartIndex]);
			$ParamsOK &= !isset($_POST['qty'.$cartIndex]);
			if (!$ParamsOK)
			{
				$rslt->checkoutMsg = __('Cannot Checkout', $this->myDomain).' - ';
				$rslt->checkoutMsg .= __('Item(s) removed from Shopping Trolley', $this->myDomain);
				return $rslt;
			}
			
			return $rslt;
		}		

		function OnlineStore_AddExtraPayment(&$rslt, $amount, $name, $detailID)
		{
		}
		
		function OnlineStore_ProcessCheckout()
		{
			// Process checkout request for Integrated Trolley
			// This function must be called before any output as it redirects to Payment Gateway if successful
			$myDBaseObj = $this->myDBaseObj;				
			
			if (isset($_SESSION['REDIRECTED_GET']))
			{
				// Get Arguments from page redirect in SESSION Variable
				$redirectedArgs = unserialize($_SESSION['REDIRECTED_GET']);
				foreach ($redirectedArgs as $argID => $argVal)
				{
					$_GET[$argID] = $argVal;
					$_REQUEST[$argID] = $argVal;
				}
				unset($_SESSION['REDIRECTED_GET']);
			}
			if ($myDBaseObj->isDbgOptionSet('Dev_ShowGET'))
			{
				StageShowLibUtilsClass::print_r($_GET, '$_GET');
			}
			if ($myDBaseObj->isDbgOptionSet('Dev_ShowPOST'))
			{
				StageShowLibUtilsClass::print_r($_POST, '$_POST');
			}		
			if ($myDBaseObj->isDbgOptionSet('Dev_ShowSESSION'))
			{
				StageShowLibUtilsClass::print_r($_SESSION, '$_SESSION');
			}		
			
			$checkout = $myDBaseObj->gatewayObj->IsCheckout();

			if ($checkout != '')
			{
				$checkoutRslt = $this->OnlineStore_ScanCheckoutSales();
				if (isset($checkoutRslt->checkoutMsg)) 
				{
					$this->checkoutMsg = $checkoutRslt->checkoutMsg;
					return;
				}
							
				if ($checkoutRslt->totalDue == 0)
				{
					$this->checkoutMsg = __('Cannot Checkout', $this->myDomain).' - ';
					$this->checkoutMsg .= __('Total sale is zero', $this->myDomain);
					return;						
				}
								
				// Lock tables so we can commit the pending sale
				$this->myDBaseObj->LockSalesTable();
					
				// Check quantities before we commit 
				$ParamsOK = $this->IsOnlineStoreItemAvailable($checkoutRslt);
					
				if ($ParamsOK)
  				{
					$userFieldsList = $myDBaseObj->gatewayObj->Gateway_ClientFields();
					foreach ($userFieldsList as $userField => $userLabel)
					{
						$elemId = 'checkoutdetails-'.$userField;
						$checkoutRslt->saleDetails[$userField] = stripslashes($_POST[$elemId]);
					}

					$systemFieldsList = $myDBaseObj->gatewayObj->Gateway_SystemFields();				
					foreach ($systemFieldsList as $systemField => $systemValue)
					{
						$checkoutRslt->saleDetails[$systemField] = $systemValue;
					}
					
					// Update quantities ...
					$saleId = $this->myDBaseObj->LogSale($checkoutRslt->saleDetails, StageShowLibSalesDBaseClass::STAGESHOWLIB_LOGSALEMODE_CHECKOUT);					
					$gatewayURL = $myDBaseObj->gatewayObj->GetGatewayRedirectURL($saleId, $checkoutRslt->saleDetails);					
				}
				else
				{
					$this->checkoutMsg = __('Cannot Checkout', $this->myDomain).' - '.$this->checkoutMsg;
				}	
				
				// Release Tables
				$this->myDBaseObj->UnLockTables();
					
				if ($ParamsOK)
  				{
					$this->ClearTrolleyContents();
				
					if (defined('CORONDECK_RUNASDEMO'))
					{
						$this->demosale = $saleId;
						$this->pageMode = self::PAGEMODE_DEMOSALE;
					}
					elseif ($this->myDBaseObj->isDbgOptionSet('Dev_IPNLocalServer'))
					{
						$this->checkoutMsg .= __('Using Local IPN Server - Gateway Checkout call skipped', $this->myDomain);
						if ($this->myDBaseObj->isDbgOptionSet('Dev_IPNDisplay'))
						{
							$gatewayURLParams = str_replace('&', '<br>', $gatewayURL);
							$this->checkoutMsg .= "<br>Gateway URL:$gatewayURLParams<br>\n";
						}					
					}
					else 
					{
						if (isset($_SESSION['stageshowlib_debug_blockgateway']))
						{
							$gatewayURLParams = explode('&', $gatewayURL);
							StageShowLibUtilsClass::print_r($gatewayURLParams, 'gatewayURLParams');
							exit;
						}
						$myDBaseObj->gatewayObj->RedirectToGateway($gatewayURL);
						exit;
					}
				}
				else
				{
					$this->checkoutMsg = __('Cannot Checkout', $this->myDomain).' - ';
					$this->checkoutMsg .= __('Sold out for one or more items', $this->myDomain);
					return;						
				}
				
			}			
		}
		
		function CheckGatewayParam(&$paramsArray, $paramId, $paramValue, $paramIndex = 0)		
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
		
	}
}
?>
