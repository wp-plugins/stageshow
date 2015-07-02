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

if (!defined('STAGESHOWLIB_DATABASE_FULL'))
{
	if (!class_exists('StageShowLibSalesCartPluginBaseClass')) 
		include STAGESHOW_INCLUDE_PATH.'stageshowlib_salesplugin_trolley.php';
	
	class StageShowWPOrgSalesCartPluginClass_Parent extends StageShowLibSalesCartPluginBaseClass {}
}
else
{
	if (!class_exists('StageShowLibSalesPluginBaseClass')) 
	include STAGESHOW_INCLUDE_PATH.'stageshowlib_salesplugin.php';
	
	class StageShowWPOrgSalesCartPluginClass_Parent extends StageShowLibSalesPluginBaseClass {}
}

if (!class_exists('StageShowWPOrgSalesCartPluginClass')) 
{
	define ('STAGESHOWLIB_UPDATETROLLEY_TARGET', 'stageshow_jquery_trolley.php');
	
	class StageShowWPOrgSalesCartPluginClass extends StageShowWPOrgSalesCartPluginClass_Parent // Define class
	{
		function __construct()
		{
			$this->cssBaseID = "stageshow-boxoffice";
		
			parent::__construct();
			
			$this->myJSRoot = $this->myDomain;
			
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
	
		function Cart_OutputContent_OnlineStoreMain($atts)
		{
			if (($atts['id'] != '') || ($atts['perf'] != ''))
			{			
				parent::Cart_OutputContent_OnlineStoreMain($atts);
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
					parent::Cart_OutputContent_OnlineStoreMain($atts);
					if (--$count == 0)
						break;

					if ($this->adminPageActive)
					{
						$buttonID = $this->GetButtonID('editbuyer');
						if (isset($_POST[$buttonID]))	// 'editbuyer' editing sale - get buyer details
						{
							break;
						}
					}						
						
				}
			}
			
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

			$showID = htmlspecialchars_decode($atts['id']);
								
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
		
		function GetOnlineStoreStockID($result)
		{
			return $result->perfID;
		}
			
		function GetOnlineStoreItemID($result)
		{
			return $result->priceID;
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
				
		function OutputContent_OnlineStoreTitle($result)
		{
			$showNameAnchor = '';
			$nameLen = strlen($result->showName);
			for ($i=0; $i<$nameLen; $i++)
			{
				$nxtChar = $result->showName[$i];
				if ($nxtChar == ' ')
					$nxtChar = '_';
				elseif (!ctype_alnum ($nxtChar))
					continue;
					
				$showNameAnchor .= $nxtChar;				
			}
			$this->Cart_OutputContent_Anchor($showNameAnchor);
			
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
			$submitId     = $this->GetOnlineStoreElemTagId('AddItemButton', $result);
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
					
				$maxQty = $myDBaseObj->getOption('MaxTicketQty');
				if (!$this->myDBaseObj->isOptionSet('QtySelectTextInput'))
				{
					$qtySelectHTML = '
						<select class="stageshow-trolley-ui" name="'.$quantityTagId.'" id="'.$quantityTagId.'">
						<option value="1" selected="">1</option>
						';
					if (($seatsAvailable > 0) && ($seatsAvailable <= $maxQty))
					{
						// TODO - Deduct number of seats in shopping trolley from $seatsAvailable
						$maxQty = $seatsAvailable;
					}
					for ($no=2; $no<=$maxQty; $no++)
						$qtySelectHTML .= '<option value="'.$no.'">'.$no.'</option>'."\n";					
					$qtySelectHTML .= '
						</select>';
				}	
				else
				{
					$onKeypressHandler = ' onkeypress="StageShowLib_OnKeypressNumericOnly(this, event, '.$maxQty.', 0, false);" ';
					$onChangeHandler = ' onchange="StageShowLib_OnChangeNumericOnly(this, event, '.$maxQty.', 0, false);" ';
					$qtySelectHTML = '<input type="text" autocomplete="off" maxlength="2" size="3" class="stageshow-trolley-ui" name="'.$quantityTagId.'" id="'.$quantityTagId.'" '.$onKeypressHandler.$onChangeHandler.'value="1" />';
				}			
					
				$storeRowHTML .= '<td class="stageshow-boxoffice-qty">'.$qtySelectHTML.'</td>';
				
				$altTag = $myDBaseObj->getOption('OrganisationID').' '.__('Tickets', $this->myDomain);
				$buttonClasses = '';						
				if ($this->adminPageActive) $buttonClasses .= ' button-secondary';
				
				$buttonClassdef = $this->GetButtonTypeDef('add', $submitId, '', $buttonClasses);
				
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
						$seatsAvailableText = ($seatsAvailable > 1) ? __('Seats Available', $this->myDomain) : __('Seat Available', $this->myDomain);
						$storeRowHTML .= '
							<tr>
							<td colspan="4" class="stageshow-boxoffice-available">'.$seatsAvailable.' <span>'.$seatsAvailableText.'</span></td>
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
			if (!$this->saleConfirmationMode)
				echo '<td class="'.$this->cssTrolleyBaseID.'-remove">&nbsp;</td>'."\n";
			echo "</tr>\n";
		}
				
		function GetButtonPostID($buttonID)
		{
			$postID = parent::GetButtonPostID($buttonID);
			
			$buttonImageID = $buttonID;
			switch ($buttonID)
			{
				case 'checkoutdetails':
					$buttonImageID = 'checkout';
					// Fall into next case ...
					
				case 'checkout':
					if ($this->myDBaseObj->IfButtonHasURL($buttonImageID))
					{
						$postID .='_x';
					}
					break;
			}
			
			return $postID;
		}
				
		function GetButtonTypeDef($buttonID, $buttonName = '', $buttonType = 'submit', $buttonClasses = '')
		{
			$buttonSrc = '';
			
			$buttonImageID = $buttonID;
					
			switch ($buttonImageID)
			{
				case 'checkoutdetails':
					$buttonImageID = 'checkout';
					// Fall into next case ...
					
				case 'add':
				case 'remove':
				case 'checkout':
					if ($this->myDBaseObj->ButtonHasURL($buttonImageID, $buttonURL))
					{
						$buttonType = 'image';
						$buttonSrc = ' src="'.$buttonURL.'"';
					}
					break;
					
				case 'confirm':
					if (defined('STAGESHOW_CONFIRMBUTTON_URL') && (STAGESHOW_CONFIRMBUTTON_URL != ''))
					{
						$buttonType = 'image';
						$buttonSrc = ' src="'.STAGESHOW_CONFIRMBUTTON_URL.'"';							
					}
					break;
				
				default:
					break;					
			}

			$buttonTypeDef = parent::GetButtonTypeDef($buttonID, $buttonName, $buttonType, $buttonClasses);				
			$buttonTypeDef .= $buttonSrc;

			return $buttonTypeDef;
		}
				
		function OutputContent_OnlineCheckoutButton($cartContents)
		{
			$checkoutSelector = '';
			if (!$this->myDBaseObj->SettingsConfigured())
			{
				return '';
			}
			
			$checkoutSelector = parent::OutputContent_OnlineCheckoutButton($cartContents);				
			return $checkoutSelector; 
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
		
		function OutputContent_OnlineTrolleyExtras($cartContents)
		{
			return 0;
		}
					
		function OnlineStore_GetPriceType($result)
		{
			return $result->priceType;
		}
		
		function OnlineStore_GetSortField($result)
		{
			// Includes perfID is case perfDateTime and priceType are the same ....
			return $result->perfDateTime.'-'.$result->priceType.'-'.$result->perfID;
		}
		
		function OnlineStore_AddTrolleyExtras(&$cartEntry, $result)
		{
			$cartEntry->perfID = $result->perfID;
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
						if ($this->seatsAvail[$perfID] < 0) continue;
						
						// Deduct the total number of seats sold for this performance	
						$salesSummary = $myDBaseObj->GetPerformanceSummaryByPerfID($perfID);
						$this->seatsAvail[$perfID] -= $salesSummary->totalQty;				
					}
					
					if ($this->seatsAvail[$perfID] >= 0)
					{
						// Add the number of seats for this performance for this sale entry
						// (i.e. assume that these seats have been deleted)
						$qty = isset($saleEntry->priceNoOfSeats) ? $saleEntry->ticketQty * $saleEntry->priceNoOfSeats : $saleEntry->ticketQty;						
						$this->seatsAvail[$perfID] += $qty;						
					}
				}
			}

			$qty = isset($cartEntry->priceNoOfSeats) ? $cartEntry->qty * $cartEntry->priceNoOfSeats : $cartEntry->qty;						
			$perfID = $cartEntry->perfID;
			
			if (!isset($this->seatsAvail[$perfID]))
			{
				// This performance has been added to the sale
				$salesSummary = $myDBaseObj->GetPerformanceSummaryByPerfID($perfID);
					
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
}

?>