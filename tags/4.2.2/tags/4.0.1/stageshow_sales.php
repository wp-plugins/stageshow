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
      
include 'include/stageshowlib_salesplugin.php';
	
if (!class_exists('StageShowSalesPluginClass')) 
{
	class StageShowSalesPluginClass extends StageShowLibSalesPluginBaseClass 
	{
		function __construct()
		{
			$this->cssBaseID = "stageshow-boxoffice";
		
			if (defined('STAGESHOW_SHORTCODE'))
			{
				$this->shortcode = STAGESHOW_SHORTCODE;
			}
			elseif (defined('RUNSTAGESHOWDEMO'))
			{
				$this->shortcode = str_replace('stage', 's', STAGESHOW_DIR_NAME).'-boxoffice';
			}
			else
			{
				$this->shortcode = STAGESHOW_SHORTCODE_PREFIX."-boxoffice";
			}
			
			parent::__construct();
			
			// colID and cssColID are re-defined here 
			$this->colID['name'] = defined('STAGESHOW_BOXOFFICECOL_NAME') ? STAGESHOW_BOXOFFICECOL_NAME : __('Show', $this->myDomain);
			$this->cssColID['name'] = "show";					
			$this->colID['datetime'] = defined('STAGESHOW_BOXOFFICECOL_DATETIME') ? STAGESHOW_BOXOFFICECOL_DATETIME : __('Date & Time', $this->myDomain);
			$this->cssColID['datetime'] = "datetime";		
			$this->colID['ref'] = defined('STAGESHOW_BOXOFFICECOL_TICKET') ? STAGESHOW_BOXOFFICECOL_TICKET : __('Ticket Type', $this->myDomain);
			$this->cssColID['ref'] = "type";
			$this->colID['price'] = defined('STAGESHOW_BOXOFFICECOL_PRICE') ? STAGESHOW_BOXOFFICECOL_PRICE : __('Price', $this->myDomain);
			$this->cssColID['price'] = "price";
			$this->colID['qty'] = defined('STAGESHOW_BOXOFFICECOL_QTY') ? STAGESHOW_BOXOFFICECOL_QTY : __('Quantity', $this->myDomain);
			$this->cssColID['qty'] = "qty";
				
			$this->colID['cartqty'] = defined('STAGESHOW_BOXOFFICECOL_CARTQTY') ? STAGESHOW_BOXOFFICECOL_CARTQTY : __('Quantity', $this->myDomain);
			$this->cssColID['cartqty'] = "qty";	
		}
	
		function OutputContent_GetAtts( $atts )
		{
			$atts = shortcode_atts(array(
				'id'    => '',
				'perf'  => '',
				'count' => '',
				'style' => 'normal' 
			), $atts );
        
        	return $atts;
		}
		
		function OutputContent_OnlineStore($atts)
		{
			if ($this->pageMode == self::PAGEMODE_DEMOSALE)
			{
				include 'include/stageshow_paypalsimulator.php';
				
				ob_start();
				new StageShowPayPalSimulator(STAGESHOW_DBASE_CLASS, $this->demosale);
				$simulatorOutput = ob_get_contents();
				ob_end_clean();

				return $simulatorOutput;
			}
			
			if (isset($_POST['SUBMIT_simulatePayPal']))
			{
				// Save Form values for next time
				$paramIDs = $_POST['paramIDs'];
				$paramsList = explode(',', $paramIDs);
				foreach ($paramsList as $tagName)
				{
					$paramVal = $_POST[$tagName];					
					$sessionVar = 'StageShowSim_'.$tagName;
					$_SESSION[$sessionVar] = $paramVal;
				}
				$this->myDBaseObj->saveOptions();
				
				ob_start();		// "Soak up" any output
				include 'stageshow_ipn_callback.php';
				$simulatorOutput = ob_get_contents();
				ob_end_clean();
				$saleStatus = 'DEMO MODE: Sale Completed';
				echo '<div id="message" class="stageshow-ok ok">'.$saleStatus.'</div>';
			}
			
			return parent::OutputContent_OnlineStore($atts);
		}
	
		function OutputContent_OnlineStoreMain($atts)
		{
			if (($atts['id'] != '') || ($atts['perf'] != ''))
			{			
				parent::OutputContent_OnlineStoreMain($atts);
			}			
		    else
			{
				$myDBaseObj = $this->myDBaseObj;
				
				// Get ID of "active" Shows in order of first performance
				$shows = $myDBaseObj->GetActiveShowsList();
	      
		  		// Count can be used to limit the number of Shows displayed
				if ($atts['count'] > 0)
					$count = $atts['count'];
				else
					$count = count($shows);
					
				foreach ( $shows as $show )
				{
					if (!$myDBaseObj->IsShowEnabled($show))
						continue;
					
					$atts['id'] = $show->showID;
					parent::OutputContent_OnlineStoreMain($atts);
					if (--$count == 0)
						break;
				}
			}
			
		}
		
		function OutputContent_OnlineStoreFooter()
		{
			if ($this->adminPageActive)
				return;
				
			$url = $this->myDBaseObj ->get_pluginURI();
			$name = $this->myDBaseObj ->get_name();
			$weblink = __('Driven by').' <a target="_blank" href="'.$url.'">'.$name.'</a>';
			echo '<div class="stageshow-boxoffice-weblink">'.$weblink.'</div>'."\n";
		}
		
		function IsOnlineStoreItemEnabled($result)
		{
			$myDBaseObj = $this->myDBaseObj;

			return $myDBaseObj->IsPerfEnabled($result);
		}
				
		function GetOnlineStoreProductDetails($priceID)
		{
			return $this->myDBaseObj->GetPricesListByPriceID($priceID);
		}
		
		function GetOnlineStoreProducts($atts)
		{
			$myDBaseObj = $this->myDBaseObj;

			$showID = $atts['id'];
								
			if ($showID !== '')
			{
				// Get the prices list for a single show
				$results = $myDBaseObj->GetPricesListByShowID($showID, true);
				$myDBaseObj->prepareBoxOffice($showID);			
				if (count($results) == 0)
				{
					echo "<!-- StageShow BoxOffice - No Output for ShowID=$showID -->\n";
				}
	      
				return $results;
			}
			else if ($atts['count'] == '')
			{
				return $myDBaseObj->GetPricesList(null, true);
			}
			
			return null;
		}
		
		function GetOnlineStorePriceID($result)
		{
			return $result->priceID;
		}
			
		function GetOnlineStoreStockID($result)
		{
			return $result->perfID;
		}
			
		function GetOnlineStoreItemID($result)
		{
			return $result->priceID;
		}
			
		function GetOnlineStoreItemName($result, $cartEntry = null)
		{
			$showName = $result->showName;
			$perfDateTime = $result->perfDateTime;
			$priceType = $result->priceType;
						
			$fullName = $showName.'-'.$perfDateTime.'-'.$priceType;
			
			return $fullName;
		}

		function GetOnlineStoreMaxSales($result)
		{
			return $result->perfSeats;
		}
			
		function GetOnlineStoreItemPrice($result)
		{
			return $result->priceValue;
		}
			
		function GetOnlineStoreItemsAvailable($result)
		{
			static $lastPerfID = 0;
			static $itemsAvailable = 0;
			
			if ($lastPerfID != $result->perfID)
			{
				$salesSummary = $this->myDBaseObj->GetPerformanceSummaryByPerfID($result->perfID);
				if ($result->perfSeats >=0) 
				{
					$itemsAvailable = $result->perfSeats - $salesSummary->totalQty;
					if ($itemsAvailable < 0) $itemsAvailable = 0;
				}
				else
				{
					$itemsAvailable = -1;	// i.e. No limit
				}
				
				$lastPerfID = $result->perfID;
			}
			
			
			return $itemsAvailable;
		}
			
		function IsOnlineStoreItemAvailable($saleItems)
		{
			$ParamsOK = true;
			$this->checkoutMsg = '';
			
			// Check quantities before we commit 
			foreach ($saleItems->totalSales as $perfID => $qty)
			{						
				$perfSaleQty  = $this->myDBaseObj->GetSalesQtyByPerfID($perfID);
				$perfSaleQty += $qty;
				$seatsAvailable = $saleItems->maxSales[$perfID];
				if ( ($seatsAvailable > 0) && ($seatsAvailable < $perfSaleQty) ) 
				{
					$this->checkoutMsg = __('Sold out for one or more performances', $this->myDomain);
					$ParamsOK = false;
					break;
				}
			}
			
			return $ParamsOK;
		}
		
		function GetOnlineStoreElemTagId($id, $result)
		{
			$id  = parent::GetOnlineStoreElemTagId($id, $result);
//			$id .= '_' . $result->perfID;
			return $id;
		}

		function OutputContent_OnlineStoreTitle($result)
		{
			echo '<h2>'.$result->showName."</h2>\n";					

			if (isset($result->showNote) && ($result->showNote !== ''))
			{
				echo '<div class="stageshow-boxoffice-shownote">'.$result->showNote . "</div><br>\n";
			}
		}
			
		function OutputContent_OnlineStoreHeader($result)
		{
			parent::OutputContent_OnlineStoreHeader($result);
		}
				
		function OutputContent_OnlineStoreRow($result)
		{
			static $salesSummary;
			
			$storeRowHTML = '';
			
			$submitButton = __('Add', $this->myDomain);
			$submitId     = $this->GetOnlineStoreElemTagId('AddTicketSale', $result);
			$showAllDates = defined('STAGESHOW_BOXOFFICE_ALLDATES');
				
			$myDBaseObj = $this->myDBaseObj;

			// Sales Summary from PerfID
			if (!isset($this->lastPerfDateTime))
			{
				$this->lastPerfDateTime = '';
			}
				
			$seatsAvailable = $this->GetOnlineStoreItemsAvailable($result);
			$soldOut = ($seatsAvailable == 0);
			
			$separator = '';
			if (($this->lastPerfDateTime !== $result->perfDateTime) || $showAllDates)
			{
				$formattedPerfDateTime = $myDBaseObj->FormatDateForDisplay($result->perfDateTime);
				if ($this->lastPerfDateTime != '') $separator = "\n".'<tr><td class="stageshow-boxoffice-separator">&nbsp;</td></tr>';
				$this->lastPerfDateTime = $result->perfDateTime;
			}
			else
			{
				$formattedPerfDateTime = '&nbsp;';
			}
			
			$storeRowHTML .= '
				<table width="100%" cellspacing="0">'.$separator.'
				<tr>
				<td class="stageshow-boxoffice-datetime">'.$formattedPerfDateTime.'</td>
				';
				
			$storeRowHTML .= '
			<td class="stageshow-boxoffice-type">'.$this->OnlineStore_GetPriceType($result).'</td>
			<td class="stageshow-boxoffice-price">'.$myDBaseObj->FormatCurrency($result->priceValue).'</td>
			';
															
			if (!$soldOut)
			{
				$quantityTagId = $this->GetOnlineStoreElemTagId('quantity', $result); 
				$storeRowHTML .= '
					<td class="stageshow-boxoffice-qty">
					<select name="'.$quantityTagId.'">
					<option value="1" selected="">1</option>
					';
				$maxQty = $myDBaseObj->adminOptions['MaxTicketQty'];
				if (($seatsAvailable > 0) && ($seatsAvailable <= $myDBaseObj->adminOptions['MaxTicketQty']))
				{
					// TODO - Deduct number of seats in shopping trolley from $seatsAvailable
					$maxQty = $seatsAvailable;
				}
				for ($no=2; $no<=$maxQty; $no++)
					$storeRowHTML .= '<option value="'.$no.'">'.$no.'</option>'."\n";
				$storeRowHTML .= '
					</select>
					</td>
				';
				
				$altTag = $myDBaseObj->adminOptions['OrganisationID'].' '.__('Tickets', $this->myDomain);						
				$buttonClassdef = ($this->adminPageActive) ? 'class="button-secondary " ' : '';
			
				$buttonClassdef .= $this->GetButtonTypeDef('add', $submitId);
				
				$storeRowHTML .= '
					<td class="stageshow-boxoffice-add">
					<input '.$buttonClassdef.' value="'.$submitButton.'" alt="'.$altTag.'"/>
					</td>
				';
			}
			else
			{
				$storeRowHTML .= '
					<td class="stageshow-boxoffice-soldout" colspan=2>'.__('Sold Out', $this->myDomain).'</td>
					';
			}
				
			$storeRowHTML .= '
				</tr>
				';

			if ($myDBaseObj->getOption('ShowSeatsAvailable'))
			{
				if (isset($result->showAvailable))
				{
					// TODO - SSG Allocated Seating - Check Seats Available Count ....
					if ($seatsAvailable > 0)
					{
						$storeRowHTML .= '
							<tr>
							<td colspan="4" class="stageshow-boxoffice-available">'.$seatsAvailable.' '._n('Seat Available', 'Seats Available', $seatsAvailable, $this->myDomain).'</td>
							</tr>
							';
					}				
				}				
			}
		
			$storeRowHTML .= '
				</table>
				';
				
			return $storeRowHTML;
		}
		
		function OutputContent_OnlineStoreSection( $results )
		{
			if (count($results) > 0)
			{
		  		$lastIndex = count($results)-1;
		  		for ($index=0; $index<$lastIndex;$index++)
				{
					if ($results[$index]->perfID != $results[$index+1]->perfID)
					{
						$results[$index]->showAvailable = true;
					}
				}
				$results[$lastIndex]->showAvailable = true;
			}
			
			return parent::OutputContent_OnlineStoreSection( $results );
		}
		
		function OutputContent_OnlineTrolleyDetailsHeaders()
		{
			echo '<td class="'.$this->cssTrolleyBaseID.'-'.$this->cssColID['cartqty'].'">'.$this->colID['cartqty'].'</td>'."\n";
			return 1;
		}
		
		function OutputContent_OnlineTrolleyHeader($result)
		{
			$this->trolleyHeaderCols = 5;	// Count of the number of columns in the header
			
			echo '<tr class="'.$this->cssTrolleyBaseID.'-titles">'."\n";
			echo '<td class="'.$this->cssTrolleyBaseID.'-'.$this->cssColID['name'].'">'.$this->colID['name'].'</td>'."\n";
			echo '<td class="'.$this->cssTrolleyBaseID.'-'.$this->cssColID['datetime'].'">'.$this->colID['datetime'].'</td>'."\n";
			echo '<td class="'.$this->cssTrolleyBaseID.'-'.$this->cssColID['ref'].'">'.$this->colID['ref'].'</td>'."\n";
			$this->trolleyHeaderCols += $this->OutputContent_OnlineTrolleyDetailsHeaders();
			echo '<td class="'.$this->cssTrolleyBaseID.'-'.$this->cssColID['price'].'">'.$this->colID['price'].'</td>'."\n";
			echo '<td class="'.$this->cssTrolleyBaseID.'-remove">&nbsp;</td>'."\n";
			echo "</tr>\n";
		}
				
		function GetButtonTypeDef($buttonID, $buttonName = '', $buttonType = 'submit')
		{
			$buttonTypeDef = '';
			
			switch ($buttonID)
			{
				case 'add':
					if (defined('STAGESHOW_ADDBUTTON_URL'))
					{
						$buttonTypeDef = parent::GetButtonTypeDef($buttonID, $buttonName, 'image');
						$buttonTypeDef .= ' src="'.STAGESHOW_ADDBUTTON_URL.'"';
					}
					break;
					
				case 'checkout':
					if (defined('STAGESHOW_CHECKOUTBUTTON_URL'))
					{
						$buttonTypeDef = parent::GetButtonTypeDef($buttonID, $buttonName, 'image');
						$buttonTypeDef .= ' src="'.STAGESHOW_CHECKOUTBUTTON_URL.'"';
					}
					break;
					
				default:
					break;					
			}
			
			if ($buttonTypeDef == '')
			{
				$buttonTypeDef = parent::GetButtonTypeDef($buttonID, $buttonName);
			}

			$onClickHandler = 'stageshow_OnClick'.ucwords($buttonID);
			$buttonTypeDef .= ' onClick="'.$onClickHandler.'(this)"';
			
			return $buttonTypeDef;
		}
				
		function OutputContent_OnlineRemoveButton($cartIndex, $removeLinkContent='')
		{
			$removeLinkText = __('Remove', $this->myDomain);	
			if (!defined('STAGESHOW_REMOVEBUTTON_URL'))
			{
				$removeLinkContent = parent::OutputContent_OnlineRemoveButton($cartIndex, $removeLinkContent);
			}
			else if (STAGESHOW_REMOVEBUTTON_URL != '')
			{
				$removeLinkContent = '<img alt='.$removeLinkText.' src="'.STAGESHOW_REMOVEBUTTON_URL.'">';
				$removeLinkContent = parent::OutputContent_OnlineRemoveButton($cartIndex, $removeLinkContent);
			}
			else
			{
				$buttonName = 'RemoveTicketSale_'.$cartIndex;
				$buttonType = $this->GetButtonTypeDef('remove', $buttonName);
				$removeLinkContent =  '<input class="stageshow-boxoffice-button button-primary" '.$buttonType.' value="'.$removeLinkText.'"/>';
			}

			return $removeLinkContent;
		}
		
		function OutputContent_OnlineCheckoutButton($cartContents)
		{
			if ($this->adminPageActive)
			{
				echo '<input class="stageshow-boxoffice-button button-primary" type="submit" name="'.$this->GetButtonID('editbuyer').'" value="'.__('Next', $this->myDomain).'"/>'."\n";
				return;
			}
			
			if ($this->myDBaseObj->PayPalConfigured())
			{
				parent::OutputContent_OnlineCheckoutButton($cartContents);
			}
		}
		
		function OutputContent_OnlineTrolleyDetailsCols($priceEntry, $cartEntry)
		{
			$qty = $cartEntry->qty;
			echo '<td class="'.$this->cssTrolleyBaseID.'-'.$this->cssColID['cartqty'].'">'.$qty.'</td>'."\n";
		}
		
		function OutputContent_OnlineTrolleyRow($priceEntry, $cartEntry)
		{
			$showName = $priceEntry->showName;
			$perfDateTime = $this->myDBaseObj->FormatDateForDisplay($priceEntry->perfDateTime);
			$priceType = $this->OnlineStore_GetPriceType($priceEntry);
			$priceValue = $cartEntry->price;
			$qty = $cartEntry->qty;
			$total = $priceValue * $qty;
			$formattedTotal = $this->myDBaseObj->FormatCurrency($total);
			$shipping = 0.0;
						
			if ($showName != '')
			{
				echo '<td class="'.$this->cssTrolleyBaseID.'-'.$this->cssColID['name'].'">'.$showName.'</td>'."\n";
				echo '<td class="'.$this->cssTrolleyBaseID.'-'.$this->cssColID['datetime'].'">'.$perfDateTime.'</td>'."\n";
			}
			else
			{
				echo '<td colspan="2">&nbsp;</td>'."\n";
			}
			echo '<td class="'.$this->cssTrolleyBaseID.'-'.$this->cssColID['ref'].'">'.$priceType.'</td>'."\n";
			$this->OutputContent_OnlineTrolleyDetailsCols($priceEntry, $cartEntry);
			echo '<td class="'.$this->cssTrolleyBaseID.'-'.$this->cssColID['price'].'">'.$formattedTotal.'</td>'."\n";

			return $total;
		}
		
		function OutputContent_OnlineTrolleyFee($cartContents)
		{
			if ($cartContents->saleTransactionFee > 0)
			{				
				$priceEntry = new stdClass;
				$cartEntry = new stdClass;
				
				$priceEntry->showName = '';						
				$priceEntry->perfDateTime = '';						
				$priceEntry->priceType = __('Booking Fee', $this->myDomain);
				
				$cartEntry->qty = 1;
				$cartEntry->price = $cartContents->saleTransactionFee;
				
				echo '<tr class="'.$this->cssTrolleyBaseID.'-row">'."\n";					
				$this->OutputContent_OnlineTrolleyRow($priceEntry, $cartEntry);
				echo '<td>&nbsp;</td>'."\n";					
				echo "</tr>\n";
			}
			
			return $cartContents->saleTransactionFee;				
		}
		
		function OutputContent_OnlineTrolleyDonation($cartContents)
		{
			return 0;
		}
				
		function GetUserInfo($user_metaInfo, $fieldId, $fieldSep = '')
		{
			if (isset($this->myDBaseObj->adminOptions[$fieldId]))
			{
				$metaField = $this->myDBaseObj->adminOptions[$fieldId];
			}
			else
			{
				$metaField = $fieldId;
			}
			
			if ($metaField == '')
				return '';
				
			if (!isset($user_metaInfo[$metaField][0]))
				return $fieldSep == '' ? __('Unknown', $this->myDomain) : '';
			
			$userInfoVal = 	$user_metaInfo[$metaField][0];
			return $fieldSep.$userInfoVal;
		}
		
		function OnlineStore_GetPriceType($result)
		{
			return $result->priceType;
		}
		
		function OnlineStore_GetSortField($result)
		{
			return $result->perfDateTime.'-'.$result->priceType;
		}
		
		function OnlineStore_AddTrolleyExtras(&$cartEntry, $result)
		{
			$cartEntry->perfID = $result->perfID;
		}
		
		function OnlineStore_AddExtraPayment(&$rslt, &$paramCount, $amount, $name, $detailID)
		{
			if (($rslt->totalDue > 0) && ($amount > 0))
			{
				$rslt->totalDue += $amount;
				
				$paramCount++;
				
				$rslt->paypalParams['item_name_'.$paramCount] = $name;
				$rslt->paypalParams['amount_'.$paramCount] = $amount;
				$rslt->paypalParams['quantity_'.$paramCount] = 1;
				$rslt->paypalParams['shipping_'.$paramCount] = 0;	
				
				$rslt->saleDetails[$detailID] = $amount;			
			}
			else
			{
				$rslt->saleDetails[$detailID] = 0;				
			}	
		}
		
		function OnlineStore_ProcessCheckout()
		{
			$myDBaseObj = $this->myDBaseObj;
				
			parent::OnlineStore_ProcessCheckout();
		}
		
		function IsOnlineStoreItemValid(&$cartEntry, $saleEntries)
		{
			// Test if this item is valid (i.e. Available))
			static $firstPass = true;
			$myDBaseObj = $this->myDBaseObj;
			
			if ($firstPass)
			{			
				// Just do this on the first call
				$firstPass = false;
				
				foreach ($saleEntries as $saleEntry)
				{
					$perfID = $saleEntry->perfID;
					
					if (!isset($this->seatsAvail[$perfID]))
					{
						// Get the maximum number of seats 
						$this->seatsAvail[$perfID] = $saleEntry->perfSeats;	
						if ($this->seatsAvail[$perfID] <= 0) continue;
						
						// Deduct the total number of seats sold for this performance	
						$salesSummary = $myDBaseObj->GetPerformanceSummaryByPerfID($perfID);
						$this->seatsAvail[$perfID] -= $salesSummary->totalQty;				
//StageShowLibUtilsClass::print_r($salesSummary, 'salesSummary-'.__LINE__);
					}
					
					// Add the number of seats for this performance for this sale entry
					$qty = isset($saleEntry->priceNoOfSeats) ? $saleEntry->ticketQty * $saleEntry->priceNoOfSeats : $saleEntry->ticketQty;						
					$this->seatsAvail[$perfID] += $qty;	
					
					
				}
			}

			$qty = isset($cartEntry->priceNoOfSeats) ? $cartEntry->qty * $cartEntry->priceNoOfSeats : $cartEntry->qty;						
			$perfID = $cartEntry->perfID;
			
			if (!isset($this->seatsAvail[$perfID]))
			{
				// This performance has been added to the sale
				$salesSummary = $myDBaseObj->GetPerformanceSummaryByPerfID($perfID);
//StageShowLibUtilsClass::print_r($salesSummary, 'salesSummary-'.__LINE__);
					
				// Get the maximum number of seats 
				$this->seatsAvail[$perfID] = $salesSummary->perfSeats;	
				if ($this->seatsAvail[$perfID] > 0)
				{
					// Deduct the total number of seats sold for this performance	
					$this->seatsAvail[$perfID] -= $salesSummary->totalQty;				
				}
			}
			
			if ($this->seatsAvail[$perfID] < 0)
				return true;
				
			if ($this->seatsAvail[$perfID] < $qty)
			{
				$this->seatsAvail[$perfID] = 0;
				$salesSummary = $myDBaseObj->GetPerformanceSummaryByPerfID($perfID);
				$perfDateTime = $this->myDBaseObj->FormatDateForDisplay($salesSummary->perfDateTime);
				$this->checkoutMsg = __('Insufficient seats', $this->myDomain).' - ('.$salesSummary->showName.' '.$perfDateTime.')';
				return false;
			}
				
			$this->seatsAvail[$perfID] -= $qty;
			
			return true;
		}
			
		
	}
} //End Class StageShowSalesPluginClass

?>