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
		
			// nameColID and refColID are defined here
			// Note: The same text strings must be used on admin pages for translations to work
			$this->nameColID = 'Date & Time';
			$this->cssNameColID = "datetime";
			
			$this->refColID = 'Ticket Type';
			$this->cssRefColID = "type";

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
				echo '<div id="message" class="stageshow-ok">'.$saleStatus.'</div>';
			}
			
			return parent::OutputContent_OnlineStore($atts);
		}
	
		function OutputContent_OnlineStoreMain($reqRecordId = '')
		{
			if ($reqRecordId != '')
			{			
				parent::OutputContent_OnlineStoreMain($reqRecordId);
			}			
		    else
			{
				$myDBaseObj = $this->myDBaseObj;
				
				// Get ID of "active" Shows in order of first performance
				$shows = $myDBaseObj->GetActiveShowsList();
	      
		  		// Count can be used to limit the number of Shows displayed
				if (isset($atts['count']))
					$count = $atts['count'];
				else
					$count = count($shows);
					
				foreach ( $shows as $show )
				{
					if (!$myDBaseObj->IsShowEnabled($show))
						continue;
						
					parent::OutputContent_OnlineStoreMain($show->showID);
					if (--$count == 0)
						break;
				}
			}
			
		}
		
		function OutputContent_OnlineStoreFooter()
		{
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
		
		function GetOnlineStoreProducts($showID = 0)
		{
			$myDBaseObj = $this->myDBaseObj;

			if ($showID === 0)
			{
				return $myDBaseObj->GetPricesList(null, true);
			}
			
			// Get the prices list for a single show
			$results = $myDBaseObj->GetPricesListByShowID($showID, true);
			$myDBaseObj->prepareBoxOffice($showID);			
			if (count($results) == 0)
			{
				echo "<!-- StageShow BoxOffice - No Output for ShowID=$showID -->\n";
			}
      
			return $results;
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
			
		function GetOnlineStoreItemName($result)
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
			
		function IsOnlineStoreItemSoldOut($result, $salesSummary)
		{
			$soldOut = ( ($result->perfSeats >=0) && ($salesSummary->totalQty >= $result->perfSeats) );
			return $soldOut;
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
			
		function GetOnlineStoreHiddenTags()
		{
			$myDBaseObj = $this->myDBaseObj;
			$hiddenTags  = parent::GetOnlineStoreHiddenTags();

			return $hiddenTags;
		}
		
		function GetOnlineStoreRowHiddenTags($result)
		{
			$hiddenTags  = '<input type="hidden" name="PerfId" value="'.$result->perfID.'"/>'."\n";
			$hiddenTags .= parent::GetOnlineStoreRowHiddenTags($result);
			
			return $hiddenTags;
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
			$submitId     = 'AddTicketSale';
			$showAllDates = defined('STAGESHOW_BOXOFFICE_ALLDATES');
				
			$myDBaseObj = $this->myDBaseObj;

			// Sales Summary from PerfID
			if (!isset($salesSummary))
			{
				$lastShowID = 0;
				$lastPerfID = 0;
				$this->lastPerfDateTime = '';
			}
			else
			{
				$lastShowID = $salesSummary->showID;
				$lastPerfID = $salesSummary->perfID;
			}
				
			if ($result->perfID != $lastPerfID)
			{
				// New performance - Get sales figures
				$salesSummary = $myDBaseObj->GetPerformanceSummaryByPerfID($result->perfID);
			}			
			
			$soldOut = $this->IsOnlineStoreItemSoldOut($result, $salesSummary);
			
			$altTag = $myDBaseObj->adminOptions['OrganisationID'].' '.__('Tickets', $this->myDomain);
						
			$separator = '';
			if (($this->lastPerfDateTime !== $result->perfDateTime) || $showAllDates)
			{
				$formattedPerfDateTime = $myDBaseObj->FormatDateForDisplay($result->perfDateTime);
				if ($lastPerfID != 0) $separator = "\n".'<tr><td class="stageshow-boxoffice-separator">&nbsp;</td></tr>';
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
				$storeRowHTML .= '
					<td class="stageshow-boxoffice-qty">
					<select name="quantity">
					<option value="1" selected="">1</option>
					';
				for ($no=2; $no<=$myDBaseObj->adminOptions['MaxTicketQty']; $no++)
					$storeRowHTML .= '<option value="'.$no.'">'.$no.'</option>'."\n";
				$storeRowHTML .= '
					</select>
					</td>
				';
			}
															
			if (!$soldOut)
			{
				$storeRowHTML .= '
					<td class="stageshow-boxoffice-add">
					<input type="submit" id="'.$submitId.'" name="'.$submitId.'" value="'.$submitButton.'" alt="'.$altTag.'"/>
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
				if (!isset($result->joinNext))
				{
					// TODO - SSG Allocated Seating - Check Seats Available Count ....
					if (!$soldOut && ($result->perfSeats >=0))
					{
						$seatsAvailable = $result->perfSeats - $salesSummary->totalQty;
						$storeRowHTML .= '
							<tr>
							<td colspan="4" class="stageshow-boxoffice-available">'.$seatsAvailable.' '.__('Seats Available', $this->myDomain).'</td>
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
		  		$lastIndex = count($results);
		  		for ($index=0; $index<count($results)-1;$index++)
				{
					if ($results[$index]->perfID == $results[$index+1]->perfID)
					{
						$results[$index]->joinNext = true;
					}
				}
			}
			
			return parent::OutputContent_OnlineStoreSection( $results );
		}
		
		function OutputContent_OnlineTrolleyDetailsHeaders()
		{
			echo '<td class="'.$this->cssTrolleyBaseID.'-qty">'.__('Quantity', $this->myDomain).'</td>'."\n";
			return 1;
		}
		
		function OutputContent_OnlineTrolleyHeader($result)
		{
			$this->trolleyHeaderCols = 5;	// Count of the number of columns in the header
			
			echo '<tr class="'.$this->cssTrolleyBaseID.'-titles">'."\n";
			echo '<td class="'.$this->cssTrolleyBaseID.'-show">'.__('Show', $this->myDomain).'</td>'."\n";
			echo '<td class="'.$this->cssTrolleyBaseID.'-datetime">'.__('Date & Time', $this->myDomain).'</td>'."\n";
			echo '<td class="'.$this->cssTrolleyBaseID.'-type">'.__('Ticket Type', $this->myDomain).'</td>'."\n";
			$this->trolleyHeaderCols += $this->OutputContent_OnlineTrolleyDetailsHeaders();
			echo '<td class="'.$this->cssTrolleyBaseID.'-price">'.__('Price', $this->myDomain).'</td>'."\n";
			echo '<td class="'.$this->cssTrolleyBaseID.'-remove">&nbsp;</td>'."\n";
			echo "</tr>\n";
			
		}
				
		function OutputContent_OnlineCheckoutButton($cartContents)
		{
			if ($this->adminPageActive)
			{
				echo '<input class="button-primary" type="submit" name="'.$this->GetButtonID('editbuyer').'" value="'.__('Next', $this->myDomain).'"/>'."\n";
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
			echo '<td class="'.$this->cssTrolleyBaseID.'-qty">'.$qty.'</td>'."\n";
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
				echo '<td class="'.$this->cssTrolleyBaseID.'-show">'.$showName.'</td>'."\n";
				echo '<td class="'.$this->cssTrolleyBaseID.'-datetime">'.$perfDateTime.'</td>'."\n";
			}
			else
			{
				echo '<td colspan="2">&nbsp;</td>'."\n";
			}
			echo '<td class="'.$this->cssTrolleyBaseID.'-type">'.$priceType.'</td>'."\n";
			$this->OutputContent_OnlineTrolleyDetailsCols($priceEntry, $cartEntry);
			echo '<td class="'.$this->cssTrolleyBaseID.'-price">'.$formattedTotal.'</td>'."\n";

			return $total;
		}
		
		function OutputContent_OnlineTrolleyFee($cartContents)
		{
			if ($cartContents->fee > 0)
			{				
				$priceEntry = new stdClass;
				$cartEntry = new stdClass;
				
				$priceEntry->showName = '';						
				$priceEntry->perfDateTime = '';						
				$priceEntry->priceType = __('Booking Fee', $this->myDomain);
				
				$cartEntry->qty = 1;
				$cartEntry->price = $cartContents->fee;
				
				echo '<tr class="'.$this->cssTrolleyBaseID.'-row">'."\n";					
				$this->OutputContent_OnlineTrolleyRow($priceEntry, $cartEntry);
				echo '<td>&nbsp;</td>'."\n";					
				echo "</tr>\n";
			}
			
			return $cartContents->fee;				
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
		
		function OnlineStore_AddTransactionFee(&$rslt, &$paramCount)
		{
			if (($rslt->totalDue > 0) && ($rslt->fee > 0))
			{
				$fee = $rslt->fee;
				$rslt->totalDue += $fee;
				
				$paramCount++;
				
				$rslt->paypalParams['item_name_'.$paramCount] = __('Booking Fee', $this->myDomain);
				$rslt->paypalParams['amount_'.$paramCount] = $fee;
				$rslt->paypalParams['quantity_'.$paramCount] = 1;
				$rslt->paypalParams['shipping_'.$paramCount] = 0;	
				
				$rslt->saleDetails['transactionfee'] = $fee;			
			}
			else
			{
				$rslt->saleDetails['transactionfee'] = 0;				
			}	
		}
		
		function OnlineStore_ProcessCheckout()
		{
			$myDBaseObj = $this->myDBaseObj;
				
			parent::OnlineStore_ProcessCheckout();
		}
		
		
	}
} //End Class StageShowSalesPluginClass

?>