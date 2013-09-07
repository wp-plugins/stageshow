<?php
/*
Description: Core Library Generic Base Class for Sales Plugins

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

if (!class_exists('SalesPluginBaseClass')) 
{
	define('STAGESHOWLIB_STATE_DOWNLOAD',  'Download');
	define('STAGESHOWLIB_STATE_POST',      'Post');
	define('STAGESHOWLIB_STATE_DELETED',   'deleted');
	define('STAGESHOWLIB_STATE_DISCARDED', 'discarded');
			
	class SalesPluginBaseClass
	{
		var $lastItemID = '';
		
		function __construct()
		{
			$this->myDomain = $this->myDBaseObj->get_domain();
							
			if (!isset($this->cssDomain)) $this->cssDomain = $this->myDomain;
			if (!isset($this->cssBaseID)) $this->cssBaseID = $this->cssDomain.'-shop';
			if (!isset($this->cssTrolleyBaseID)) $this->cssTrolleyBaseID = $this->cssDomain.'-trolley';
			
			// nameColID and refColID are defined here
			// Note: The same text strings must be used on admin pages for translations to work
			if (!isset($this->nameColID)) $this->nameColID = 'Name';
			if (!isset($this->cssNameColID)) $this->cssNameColID = "name";			
			
			if (!isset($this->refColID)) $this->refColID = 'Ref';
			if (!isset($this->cssRefColID)) $this->cssRefColID = "ref";
			
			if (!isset($this->trolleyid)) 
			{
				if (defined('STAGESHOWLIB_TROLLEYID'))
					$this->trolleyid = STAGESHOWLIB_TROLLEYID.'_cart_contents';
				else
					$this->trolleyid = $this->myDomain.'_cart_contents';
			}
			if (!isset($this->shortcode)) $this->shortcode = $this->myDomain.'-store';
			
			// Add an action to check for PayPal redirect
			add_action('wp_loaded', array(&$this, 'OnlineStore_ProcessCheckout'));

			// FUNCTIONALITY: Main - Add ShortCode for client "front end"
			add_shortcode($this->shortcode, array(&$this, 'OutputContent_OnlineStore'));
		}
		
		function OutputContent_OnlineStoreMain($reqRecordId)
		{
      		// Get all database entries for this item ... ordered by date/time then ticket type
	      	$results = $this->GetOnlineStoreProducts($reqRecordId);
			$this->OutputContent_OnlineStoreSection($results);
		}
			
		function IsOnlineStoreItemEnabled($result)
		{
			return true;
		}
		
		function GetOnlineStoreProductDetails($reqRecordId)
		{
			return $this->myDBaseObj->GetStockItem($reqRecordId);
		}
		
		function GetOnlineStoreProducts($reqRecordId = 0)
		{
			$myDBaseObj = $this->myDBaseObj;
			
			return (is_int($reqRecordId) && ($reqRecordId == 0)) ? $myDBaseObj->GetPricesList(null) :  $this->GetOnlineStoreProductDetails($reqRecordId);
		}
		
		function GetOnlineStorePriceID($result)
		{
				return $result->stockID;
		}
			
		function GetOnlineStoreStockID($result)
		{
			return $this->GetOnlineStoreItemID($result);
		}
			
		function GetOnlineStoreItemID($result)
		{
			if (!isset($result->stockID)) return 0;
			
			return $result->stockID;
		}
			
		function GetOnlineStoreItemName($result)
		{
			return $result->stockName;
		}
			
		function GetOnlineStoreMaxSales($result)
		{
			return -1;
		}
			
		function GetOnlineStoreItemPrice($result)
		{
			return $result->stockPrice + $result->stockPostage;
		}
			
		function IsOnlineStoreItemSoldOut($result)
		{
			return false;
		}
			
		function IsOnlineStoreItemAvailable($totalSales, $maxSales)
		{
			return true;
		}
			
		function GetOnlineStoreItemNote($result, $posn)
		{
		}
			
		function GetOnlineStoreHiddenTags()
		{
			$hiddenTags  = "\n";

			return $hiddenTags;
		}
		
		function GetOnlineStoreRowHiddenTags($result)
		{
			$itemID = $this->GetOnlineStoreItemID($result);
								
			return '<input type="hidden" name="PriceId" value="'.$itemID.'"/>'."\n";
		}
		
		function OutputContent_OnlineStoreTitle($result)
		{
		}
			
		function OutputContent_OnlineStoreHeader($result)
		{
			$nameColLabel = __($this->nameColID, $this->myDomain);
			$refColLabel = __($this->refColID, $this->myDomain);
				
			echo '
				<table width="100%" border="0">
					<tr>
						<td class="'.$this->cssBaseID.'-header">
							<table width="100%" cellspacing="0">
								<tr>
									<td class="'.$this->cssBaseID.'-'.$this->cssNameColID.'">'.$nameColLabel.'</td>
									<td class="'.$this->cssBaseID.'-'.$this->cssRefColID.'">'.$refColLabel.'</td>
									<td class="'.$this->cssBaseID.'-price">'.__('Price', $this->myDomain).'</td>
									<td class="'.$this->cssBaseID.'-qty">'.__('Quantity', $this->myDomain).'</td>
									<td class="'.$this->cssBaseID.'-add">&nbsp;</td>
								</tr>
							</table>
						</td>
					</tr>
				';
			//echo '<tr><td class="'.$this->cssBaseID.'-headerspace">&nbsp;</td></tr>'."\n";
		}		
				
		function OutputContent_OnlineStoreRow($result)
		{
			$myDBaseObj = $this->myDBaseObj;
			
			$altTag = $myDBaseObj->adminOptions['OrganisationID'].' '.__('Sales', $this->myDomain);
			$buttonURL = $myDBaseObj->getImageURL('AddCartButtonURL');

			$itemPrice = $myDBaseObj->FormatCurrency($this->GetOnlineStoreItemPrice($result));
			
			echo '
				<table cellspacing="0">
					<tr>
						<td class="'.$this->cssBaseID.'-'.$this->cssNameColID.'">'.$result->stockName.'</td>
						<td class="'.$this->cssBaseID.'-'.$this->cssRefColID.'">'.$result->stockRef.'</td>
						<td class="'.$this->cssBaseID.'-price">'.$itemPrice.'</td>
						<td class="'.$this->cssBaseID.'-qty">
				';
				
				switch ($result->stockType)
				{
					case STAGESHOWLIB_STATE_POST:
						echo '
								<select name="quantity">
									<option value="1" selected="">1</option>
						';
						for ($no=2; $no<=STAGESHOWLIB_MAXSALECOUNT; $no++)
							echo '<option value="'.$no.'">'.$no.'</option>'."\n";
						echo '
								</select>
							</td>
						';
						break;
						
					case STAGESHOWLIB_STATE_DOWNLOAD:
					default:
						echo '<input type="hidden" name="quantity" value="1"/>1'."\n";
						break;
				}
				
			$buttonTag = ($buttonURL != '') ? ' src="'.$buttonURL.'"' : '';
			
			echo '
						<td class="'.$this->cssBaseID.'-addcol">
							<input type="submit" value="'.__('Add', $this->myDomain).'" alt="'.$altTag.'" '.$buttonTag.' id="AddTicketSale" name="AddTicketSale"/>
						</td>
					</tr>
				</table>
				';
		}
		
		function OutputContent_OnlineStore( $atts )
		{
	  		// FUNCTIONALITY: Runtime - Output Shop Front
			$myDBaseObj = $this->myDBaseObj;
			
			$pluginID = $myDBaseObj->get_name();
			$pluginVer = $myDBaseObj->get_version();
			$pluginAuthor = $myDBaseObj->get_author();
			$pluginURI = $myDBaseObj->get_pluginURI();
			echo "\n<!-- $pluginID Plugin $pluginVer for Wordpress by $pluginAuthor - $pluginURI -->\n";			
			
			$this->OnlineStore_HandleTrolley();
			
			$atts = shortcode_atts(array(
				'id'    => '',
				'count' => '',
				'style' => 'normal' 
			), $atts );
        
			ob_start();
			
			$reqRecordId = $atts['id'];
			$this->OutputContent_OnlineStoreMain($reqRecordId);
			
			$outputContent = ob_get_contents();
			ob_end_clean();
			
			return $outputContent;						
		}
		
		function OutputContent_OnlineStoreSection( $results )
		{
			$myDBaseObj = $this->myDBaseObj;
			
			$rowCount = 0;
			
			if (count($results) == 0)
			{
				echo "<!-- OnlineStore - No Output -->\n";
				return;
			}
      
			echo '<div class="'.$this->cssBaseID.'">'."\n";
			$this->OutputContent_OnlineStoreTitle($results[0]);
			
			$hiddenTags  = "\n";
 			$hiddenTags .= $this->GetOnlineStoreHiddenTags();
   
			if (strlen($myDBaseObj->PayPalNotifyURL) > 0)
				$notifyTag  = '<input type="hidden" name="notify_url" value="'.$myDBaseObj->PayPalNotifyURL.'"/>'."\n";
			else
				$notifyTag = '';
				
			$soldOut = false;
			$oddPage = true;
			
			for ($recordIndex = 0; $recordIndex<count($results); $recordIndex++)
			{		
				$result = $results[$recordIndex];
				
				if (!$this->IsOnlineStoreItemEnabled($result))
					continue;
					
				$rowCount++;
				if ($rowCount == 1) $this->OutputContent_OnlineStoreHeader($result);				
					
				$stockID = $this->GetOnlineStoreStockID($result);
				if ($this->lastItemID !== $stockID)
				{
					$soldOut = $this->IsOnlineStoreItemSoldOut($result);
					$this->GetOnlineStoreItemNote($result, 'above');
				}
											
				$rowClass = $this->cssBaseID . '-row ' . $this->cssBaseID . ($oddPage ? "-oddrow" : "-evenrow");
				$oddPage = !$oddPage;
					
				{
					$addSaleItemURL = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
					$addSaleItemParams = ' action="'.$addSaleItemURL.'"';
				}
					
				echo '
					<tr class="'.$rowClass.'">
					<td class="'.$this->cssBaseID.'-data">
					<form '.$addSaleItemParams.' method="post">
					';
				echo $this->GetOnlineStoreRowHiddenTags($result);
				echo $hiddenTags;
				echo $notifyTag;
					
				$this->OutputContent_OnlineStoreRow($result, $soldOut);

				echo '
					</form>
					</td>
					</tr>
				';
				$this->lastItemID = $stockID;
								
				$nextItemID = $recordIndex+1<count($results) ? $this->GetOnlineStoreStockID($results[$recordIndex+1]) : -1;
				if ($nextItemID !== $stockID)
				{
					$this->GetOnlineStoreItemNote($result, 'below');
				}

			}

			if ($rowCount == 0) 
				echo __('Sales Not Available Currently', $this->myDomain)."<br>\n";
			else
			{
				echo '
					</table>
					';
			}	
			
			echo '
				</div>
				';

			// OnlineStore BoxOffice HTML Output - End 
		}
						
		function OutputContent_OnlineTrolleyHeader($result)
		{
			echo '<tr class="'.$this->cssTrolleyBaseID.'-titles">'."\n";
			echo '<td class="'.$this->cssTrolleyBaseID.'-name">'.__('Name', $this->myDomain).'</td>'."\n";
			echo '<td class="'.$this->cssTrolleyBaseID.'-ref">'.__('Ref', $this->myDomain).'</td>'."\n";
			echo '<td class="'.$this->cssTrolleyBaseID.'-price">'.__('Price', $this->myDomain).'</td>'."\n";
			echo '<td class="'.$this->cssTrolleyBaseID.'-qty">'.__('Quantity', $this->myDomain).'</td>'."\n";
			echo '<td class="'.$this->cssTrolleyBaseID.'-remove">&nbsp;</td>'."\n";
			echo "</tr>\n";
			
			$this->trolleyHeaderCols = 5;	// Count of the number of columns in the header
		}
				
		function OutputContent_OnlineTrolleyRow($priceEntry, $qty)
		{
			$itemName = $priceEntry->stockName;
			$priceRef = $priceEntry->stockRef;
						
			$priceValue = $this->GetOnlineStoreItemPrice($priceEntry);
			$total = $priceValue * $qty;
								
			echo '<td class="'.$this->cssTrolleyBaseID.'-name">'.$priceEntry->stockName.'</td>'."\n";
			echo '<td class="'.$this->cssTrolleyBaseID.'-ref">'.$priceEntry->stockRef.'</td>'."\n";
			echo '<td class="'.$this->cssTrolleyBaseID.'-price">'.$this->myDBaseObj->FormatCurrency($priceValue).'</td>'."\n";
			echo '<td class="'.$this->cssTrolleyBaseID.'-qty">'.$qty.'</td>'."\n";
			
			return $total;
		}
		
		function OutputContent_OnlineCheckoutButton()
		{
			echo '<input class="button-primary" type="submit" name="checkout" value="'.__('Checkout', $this->myDomain).'"/>'."\n";
		}
		
		function OnlineStore_HandleTrolley()
		{
			$myDBaseObj = $this->myDBaseObj;
			
			{
				if (isset($this->checkoutError))
				{
					echo '<div id="message" class="'.$this->cssDomain.'-error">'.$this->checkoutError.'</div>';					
				}
				
				$cartContents = isset($_SESSION['$this->trolleyid']) ? $_SESSION['$this->trolleyid'] : array();
			
				$this->OnlineStore_HandleTrolleyButtons($cartContents);
			}				
				
		}
		
		function OnlineStore_HandleTrolleyButtons($cartContents)
		{
			$myDBaseObj = $this->myDBaseObj;
			
			if (isset($_POST['AddTicketSale']))
			{
				// Get the product ID from posted data
				//$ticketType = $_POST['os0'];
				$ticketQty = $_POST['quantity'];
					
				$itemID = $_POST['PriceId'];
					
				// Interogate the database to confirm that the item exists
				$priceEntries = $this->GetOnlineStoreProductDetails($itemID);
	/*									
				$priceEntry = $priceEntries[0];
					
				$itemID = $this->GetOnlineStoreItemID($priceEntry);	
	*/
				// Add the item to the shopping trolley
				if (count($priceEntries) > 0)
				{
					if (isset($cartContents[$itemID]))
					{
						$cartContents[$itemID] += $ticketQty;
					}
					else
					{
						$cartContents[$itemID] = $ticketQty;
					}
					$_SESSION['$this->trolleyid'] = $cartContents;
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
							$itemID = $_GET['id'];
							unset($cartContents[$itemID]);
							$_SESSION['$this->trolleyid'] = $cartContents;
							break;
					}
				}
					
				$cartIndex = 0;		
				$runningTotal = 0;			
				foreach ($cartContents as $itemID => $qty)
				{
					$cartIndex++;
						
					$priceEntries = $this->GetOnlineStoreProductDetails($itemID);				
					if (count($priceEntries) == 0)
					{
						echo '<div id="message" class="'.$this->cssDomain.'-error">'.__("Shopping Trolley Cleared", $this->myDomain).'</div>';					
						if (current_user_can('manage_options'))
						{
							echo "No entry for ItemID:$itemID<br>";
							StageShowLibUtilsClass::print_r($cartContents, 'cartContents');
						}
						$_SESSION['$this->trolleyid'] = array();
						return;
					}
						
					$priceEntry = $priceEntries[0];
					if (!$doneHeader)
					{
						echo '<div class="'.$this->cssTrolleyBaseID.'-header"><h2>'.__('Your Shopping Trolley', $this->myDomain).'</h2></div>'."\n";
						if ( ($myDBaseObj->getOption('CheckoutNotePosn') == 'header') && ($myDBaseObj->getOption('CheckoutNote') != '') )
						{
							echo $myDBaseObj->getOption('CheckoutNote');
						}
						echo '<div class="'.$this->cssTrolleyBaseID.'">'."\n";
						echo '<form method="post">'."\n";
						echo "<table>\n";
						if ( ($myDBaseObj->getOption('CheckoutNotePosn') == 'titles') && ($myDBaseObj->getOption('CheckoutNote') != '') )
						{
							echo $myDBaseObj->getOption('CheckoutNote');
						}
						$this->OutputContent_OnlineTrolleyHeader($priceEntry);
							
						$doneHeader = true;
					}
						
					echo '<tr class="'.$this->cssTrolleyBaseID.'-row">'."\n";
						
					$runningTotal += $this->OutputContent_OnlineTrolleyRow($priceEntry, $qty);
						
					$removeLineURL = get_permalink();
					$removeLineURL  = add_query_arg('action', 'remove', $removeLineURL);
					$removeLineURL  = add_query_arg('id', $itemID, $removeLineURL);
					echo '<td class="'.$this->cssTrolleyBaseID.'-remove"><a href=' . $removeLineURL . '>'.__('Remove', $this->myDomain).'</a></td>'."\n";
						
					echo "</tr>\n";
						
					$hiddenTags .= '<input type="hidden" name="id'.$cartIndex.'" value="'.$itemID.'"/>'."\n";
					$hiddenTags .= '<input type="hidden" name="qty'.$cartIndex.'" value="'.$qty.'"/>'."\n";
				}
					
				if ($doneHeader)
				{	
					// Add totals row and checkout button
					$runningTotal = $myDBaseObj->FormatCurrency($runningTotal);
				
					echo '<tr class="'.$this->cssTrolleyBaseID.'-totalrow">'."\n";
					echo '<td>&nbsp;</td>'."\n";
					echo '<td>'.__('Total', $this->myDomain).'</td>'."\n";
					echo '<td class="'.$this->cssTrolleyBaseID.'-total">'.$runningTotal.'</td>'."\n";
					//echo '<td colspan="'.'">&nbsp;</td>'."\n"; // .$this->trolleyHeaderCols-3.'';
					echo '<td colspan="'.($this->trolleyHeaderCols-3).'">&nbsp;</td>'."\n";
					echo "</tr>\n";
				
					if ( ($myDBaseObj->getOption('CheckoutNotePosn') == 'above') && ($myDBaseObj->getOption('CheckoutNote') != '') )
					{
						echo '<tr><td colspan="'.$this->trolleyHeaderCols.'">'.$myDBaseObj->getOption('CheckoutNote')."</td></tr>\n";
					}
						
					echo '<tr>'."\n";
					echo '<td align="right" colspan="'.$this->trolleyHeaderCols.'" class="'.$this->cssTrolleyBaseID.'-checkout">'."\n";
					
					$this->OutputContent_OnlineCheckoutButton();
						
					echo '</td>'."\n";
					echo "</tr>\n";
					
					if ( ($myDBaseObj->getOption('CheckoutNotePosn') == 'below') && ($myDBaseObj->getOption('CheckoutNote') != '') )
					{
						echo '<tr><td colspan="'.$this->trolleyHeaderCols.'">'.$myDBaseObj->getOption('CheckoutNote')."</td></tr>\n";
					}
						
					echo "</table>\n";
					echo $hiddenTags;						
					echo '</form>'."\n";					
					echo '</div>'."\n";
					
					if ( ($myDBaseObj->getOption('CheckoutNotePosn') == 'bottom') && ($myDBaseObj->getOption('CheckoutNote') != '') )
					{
						echo $myDBaseObj->getOption('CheckoutNote');
					}
					
				}					
			}
		}

		function OnlineStore_ScanCheckoutSales()
		{
			$myDBaseObj = $this->myDBaseObj;
				
			// Remove any incomplete Checkouts
			$myDBaseObj->PurgePendingSales();
					
			// Check that request matches contents of cart
			$passedParams = array();	// Dummy array used when checking passed params
			
			$rslt = new stdClass();
			$rslt->paypalParams = array();
			$ParamsOK = true;
			
			$rslt->totalDue = 0;
								
			$cartContents = isset($_SESSION['$this->trolleyid']) ? $_SESSION['$this->trolleyid'] : array();
			if ($myDBaseObj->isOptionSet('Dev_ShowTrolley'))
			{
				StageShowLibUtilsClass::print_r($cartContents, 'cartContents');
			}
				
			// Build request parameters for redirect to PayPal checkout
			$cartIndex = 0;					
			foreach ($cartContents as $itemID => $qty)
			{
				$cartIndex++;
					
				$priceEntries = $this->GetOnlineStoreProductDetails($itemID);
				if (count($priceEntries) == 0)
					return $rslt;
					
				// Get sales quantities for each item
				$priceEntry = $priceEntries[0];
				$stockID = $this->GetOnlineStoreStockID($priceEntry);
				isset($rslt->totalSales[$stockID]) ? $rslt->totalSales[$stockID] += $qty : $rslt->totalSales[$stockID] = $qty;
						
				// Save the maximum number of sales for this stock item to a class variable
				$rslt->maxSales[$stockID] = $this->GetOnlineStoreMaxSales($priceEntry);
				
				$ParamsOK &= $this->CheckPayPalParam($passedParams, "id" , $itemID, $cartIndex);
				$ParamsOK &= $this->CheckPayPalParam($passedParams, "qty" , $qty, $cartIndex);
				if (!$ParamsOK)
				{
					$rslt->checkoutError  = __('Cannot Checkout', $this->myDomain).' - ';
					$rslt->checkoutError .= __('Shopping Cart Contents have changed', $this->myDomain);
					return $rslt;
				}
					
				$itemPrice = $this->GetOnlineStoreItemPrice($priceEntry);
				$shipping = 0.0;
						
				$rslt->paypalParams['item_name_'.$cartIndex] = $this->GetOnlineStoreItemName($priceEntry);
				$rslt->paypalParams['amount_'.$cartIndex] = $itemPrice;
				$rslt->paypalParams['quantity_'.$cartIndex] = $qty;
				$rslt->paypalParams['shipping_'.$cartIndex] = $shipping;
					
				$rslt->saleDetails['itemID' . $cartIndex] = $itemID;
				$rslt->saleDetails['qty' . $cartIndex] = $qty;
				$rslt->saleDetails['itemPaid' . $cartIndex] = $itemPrice;
				
				$rslt->totalDue += ($itemPrice * $qty);
			}
				
			// Shopping Cart contents have changed if there are "extra" passed parameters 
			$cartIndex++;
			$ParamsOK &= !isset($_POST['id'.$cartIndex]);
			$ParamsOK &= !isset($_POST['qty'.$cartIndex]);
			if (!$ParamsOK)
			{
				$rslt->checkoutError = __('Cannot Checkout', $this->myDomain).' - ';
				$rslt->checkoutError .= __('Item(s) removed from Shopping Cart', $this->myDomain);
				return $rslt;
			}
			
			return $rslt;
		}		

		function OnlineStore_ProcessCheckout()
		{
			// Process checkout request for Integrated Trolley
			// This function must be called before any output as it redirects to PayPal if successful
			if (isset($_POST['checkout']))
			{
				$myDBaseObj = $this->myDBaseObj;
				
				$checkoutRslt = $this->OnlineStore_ScanCheckoutSales();
				if (isset($checkoutRslt->checkoutError)) return;
				
				if ($checkoutRslt->totalDue == 0)
				{
					$this->checkoutError = __('Cannot Checkout', $this->myDomain).' - ';
					$this->checkoutError .= __('Total sale is zero', $this->myDomain);
					return;						
				}
				
				$checkoutRslt->paypalParams['image_url'] = $myDBaseObj->getImageURL('PayPalLogoImageFile');
				$checkoutRslt->paypalParams['cpp_header_image'] = $myDBaseObj->getImageURL('PayPalHeaderImageFile');
				$checkoutRslt->paypalParams['no_shipping'] = '2';
				$checkoutRslt->paypalParams['business'] = $myDBaseObj->adminOptions['PayPalMerchantID'];	// Can use adminOptions['PayPalAPIEMail']
				$checkoutRslt->paypalParams['currency_code'] = $myDBaseObj->adminOptions['PayPalCurrency'];
				$checkoutRslt->paypalParams['cmd'] = '_cart';
				$checkoutRslt->paypalParams['upload'] = '1';
				//$checkoutRslt->paypalParams['rm'] = '2';
				//$checkoutRslt->paypalParams['return'] = 'http://tigs/TestBed';
				$checkoutRslt->paypalParams['notify_url'] = $myDBaseObj->PayPalNotifyURL;
			
				$paypalURL = 'http://www.paypal.com/cgi-bin/webscr';
				
				$paypalMethod = 'GET';				
				if ($paypalMethod == 'GET')
				{
					foreach ($checkoutRslt->paypalParams as $paypalArg => $paypalParam)
						$paypalURL = add_query_arg($paypalArg, urlencode($paypalParam), $paypalURL);
					$checkoutRslt->paypalParams = array();					
				}
				
				// Lock tables so we can commit the pending sale
				$this->myDBaseObj->LockSalesTable();
					
				// Check quantities before we commit 
				$ParamsOK = $this->IsOnlineStoreItemAvailable($checkoutRslt->totalSales, $checkoutRslt->maxSales);
					
				if ($ParamsOK)
  				{
					// Update quantities ...
					$saleId = $this->myDBaseObj->LogPendingSale($checkoutRslt->saleDetails);
					$paypalURL = add_query_arg('custom', $saleId, $paypalURL);		
				}
						
				// Release Tables
				$this->myDBaseObj->UnLockTables();
					
				if ($ParamsOK)
  				{
					$_SESSION['$this->trolleyid'] = array();	// Clear the shopping cart
				
					if ($this->myDBaseObj->isOptionSet('Dev_IPNLocalServer'))
					{
						$this->checkoutError .= __('Using Local IPN Server - PayPal Checkout call skipped', $this->myDomain);
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
		
	}
}
?>