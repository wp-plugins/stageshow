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

if (!class_exists('StageShowLibSalesPluginBaseClass')) 
{
	define('STAGESHOWLIB_STATE_DOWNLOAD',  'Download');
	define('STAGESHOWLIB_STATE_POST',      'Post');
	define('STAGESHOWLIB_STATE_DELETED',   'deleted');
	define('STAGESHOWLIB_STATE_DISCARDED', 'discarded');
			
	if (!defined('STAGESHOWLIB_MAXSALECOUNT'))
	{
		define('STAGESHOWLIB_MAXSALECOUNT', 4);
	}
	
	class StageShowLibSalesPluginBaseClass
	{
		const PAGEMODE_NORMAL = 'normal';
		const PAGEMODE_DEMOSALE = 'demosale';
		
		var $lastItemID = '';
		var $pageMode = self::PAGEMODE_NORMAL;
		var $adminPageActive = false;
		
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
					$this->trolleyid = STAGESHOWLIB_TROLLEYID.'_cart_obj';
				else
					$this->trolleyid = $this->myDomain.'_cart_obj';
					
				if (defined('RUNSTAGESHOWDEMO'))
				{
					$this->trolleyid = $this->myDBaseObj->get_name().'_cart_obj';
					$this->trolleyid .= '_'.$this->myDBaseObj->loginID;
				}
			}
			if (!isset($this->shortcode)) $this->shortcode = $this->myDomain.'-store';
			
			// Add an action to check for PayPal redirect
			add_action('wp_loaded', array(&$this, 'OnlineStore_ProcessCheckout'));

			// FUNCTIONALITY: Main - Add ShortCode for client "front end"
			add_shortcode($this->shortcode, array(&$this, 'OutputContent_OnlineStore'));
		}
		
		function CheckAdminReferer($referer = '')
		{
		}
		
		function OutputContent_OnlineStoreMain($reqRecordId)
		{
      		// Get all database entries for this item ... ordered by date/time then ticket type
	      	$results = $this->GetOnlineStoreProducts($reqRecordId);
			$this->OutputContent_OnlineStoreSection($results);
			$this->OutputContent_OnlineStoreFooter();
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
			
			if ($reqRecordId === 0)
			{
				$reqRecordId = '';
			}
			
			return ($reqRecordId == '') ? $myDBaseObj->GetPricesList(null) :  $this->GetOnlineStoreProductDetails($reqRecordId);
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
			
		function IsOnlineStoreItemSoldOut($result, $salesSummary)
		{
			return false;
		}
			
		function IsOnlineStoreItemAvailable($saleItems)
		{
			return true;
		}
			
		function GetOnlineStoreItemNote($result, $posn)
		{
			return '';
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
		}		
				
		function OutputContent_OnlineStoreRow($result)
		{
			$myDBaseObj = $this->myDBaseObj;
			
			$altTag = $myDBaseObj->adminOptions['OrganisationID'].' '.__('Sales', $this->myDomain);
			$buttonURL = $myDBaseObj->getImageURL('AddCartButtonURL');

			$itemPrice = $myDBaseObj->FormatCurrency($this->GetOnlineStoreItemPrice($result));
			
			$stockDetails = isset($result->stockDetails) ? $result->stockDetails : '';
			$addColSpan = ($stockDetails != '') ? ' rowspan="2" ' : '';
			
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
				<td '.$addColSpan.'class="'.$this->cssBaseID.'-add">
					<input type="submit" value="'.__('Add', $this->myDomain).'" alt="'.$altTag.'" '.$buttonTag.' id="AddTicketSale" name="AddTicketSale"/>
				</td>
				</tr>				
				';
				
			if ($stockDetails != '') echo '
					<tr>
						<td colspan="4" class="'.$this->cssBaseID.'-details">'.$result->stockDetails.'</td>
					</tr>				
				';
				
			echo "</table>\n";
		}
		
		function OutputContent_OnlineStoreFooter()
		{
		}
		
		function OutputContent_OnlineStore( $atts )
		{
	  		// FUNCTIONALITY: Runtime - Output Shop Front
			$myDBaseObj = $this->myDBaseObj;
			
			$pluginID = $myDBaseObj->get_name();
			$pluginVer = $myDBaseObj->get_version();
			$pluginAuthor = $myDBaseObj->get_author();
			$pluginURI = $myDBaseObj->get_pluginURI();
		
			// Remove any incomplete Checkouts
			$myDBaseObj->PurgePendingSales();
					
			ob_start();			
			$hasActiveTrolley = $this->OnlineStore_HandleTrolley();
			$trolleyContent = ob_get_contents();
			ob_end_clean();
			
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
			
			if ($myDBaseObj->getOption('ProductsAfterTrolley'))
			{
				$outputContent = $trolleyContent.$outputContent;
			}
			else
			{
				$outputContent .= $trolleyContent;
			}
			
			if (!$hasActiveTrolley)
			{
				$boxofficeURL = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
				if ($myDBaseObj->getOption('boxofficeURL') != $boxofficeURL)
				{
					$myDBaseObj->adminOptions['boxofficeURL'] = $boxofficeURL;
					$myDBaseObj->saveOptions();
				}
			}
			
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
				
			$oddPage = true;
			
			for ($recordIndex = 0; $recordIndex<count($results); $recordIndex++)
			{		
				$result = $results[$recordIndex];
				
				if (!$this->IsOnlineStoreItemEnabled($result))
					continue;
					
				$storeRowHTML = $this->OutputContent_OnlineStoreRow($result);
				if ($storeRowHTML == '')
					continue;
				
				$rowCount++;
				if ($rowCount == 1) $this->OutputContent_OnlineStoreHeader($result);				
					
				$stockID = $this->GetOnlineStoreStockID($result);
				if ($this->lastItemID !== $stockID)
				{
					$this->GetOnlineStoreItemNote($result, 'above');
				}
											
				$rowClass = $this->cssBaseID . '-row ' . $this->cssBaseID . ($oddPage ? "-oddrow" : "-evenrow");
				$oddPage = !$oddPage;
					
				$addSaleItemParams = '';
/*				
				{
					$addSaleItemURL = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
					$addSaleItemParams = ' action="'.$addSaleItemURL.'"';
				}
*/
				echo '
					<tr class="'.$rowClass.'">
					<td class="'.$this->cssBaseID.'-data">
					<form '.$addSaleItemParams.' method="post">
					';
				echo $this->GetOnlineStoreRowHiddenTags($result);
				echo $hiddenTags;
				echo $notifyTag;
				echo $storeRowHTML;
				
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
				
		function OutputContent_OnlineTrolleyRow($priceEntry, $cartEntry)
		{
			$qty = $cartEntry->qty;			
			$priceValue = $cartEntry->price;
			$total = $priceValue * $qty;
								
			echo '<td class="'.$this->cssTrolleyBaseID.'-name">'.$priceEntry->stockName.'</td>'."\n";
			echo '<td class="'.$this->cssTrolleyBaseID.'-ref">'.$priceEntry->stockRef.'</td>'."\n";
			echo '<td class="'.$this->cssTrolleyBaseID.'-price">'.$this->myDBaseObj->FormatCurrency($priceValue).'</td>'."\n";
			echo '<td class="'.$this->cssTrolleyBaseID.'-qty">'.$qty.'</td>'."\n";
			
			return $total;
		}
		
		function OutputContent_OnlineTrolleyFee($cartContents)
		{
			return 0;
		}
				
		function GetButtonID($buttonID)
		{
			if (defined('RUNSTAGESHOWDEMO'))
			{
				$pluginID = $this->myDBaseObj->get_name();
				$buttonID .= '_'.$pluginID;
			}
			
			return $buttonID;
		}
				
		function OutputContent_OnlineCheckoutButton($cartContents)
		{
			$buttonID = $this->GetButtonID('checkout');
			echo '<input class="button-primary" type="submit" name="'.$buttonID.'" id="'.$buttonID.'" value="'.__('Checkout', $this->myDomain).'"/>'."\n";
		}
		
		function GetTrolleyContents()
		{
			if (isset($_SESSION[$this->trolleyid]))
			{
				$cartContents = unserialize($_SESSION[$this->trolleyid]);
			}
			else
			{
				$cartContents = new stdClass;
				$cartContents->nextIndex = 1;
			}
			
			if ($this->myDBaseObj->isDbgOptionSet('Dev_ShowTrolley'))
			{
				StageShowLibUtilsClass::print_r($cartContents, 'Get cartContents ('.$this->trolleyid.')');
			}
			
			return $cartContents;
		}
		
		function CompareTrolleyEntries($cartEntry1, $cartEntry2)
		{
			return ($cartEntry1->sortBy == $cartEntry2->sortBy);
		}
		
		function AddToTrolleyContents(&$cartContents, $newCartEntry)
		{
			if (isset($cartContents->rows))
			{
				foreach ($cartContents->rows as $index => $cartEntry)
				{
					if ($this->CompareTrolleyEntries($newCartEntry, $cartEntry))
					{
						$cartContents->rows[$index]->qty += $newCartEntry->qty;
						return;
					}
					
					if ($newCartEntry->sortBy > $cartEntry->sortBy)
						continue;
						
					$tmpCartEntry = $cartEntry;
					$cartContents->rows[$index] = $newCartEntry;
					$newCartEntry = $tmpCartEntry;
				}				
			}
			
			$cartContents->fee = $this->myDBaseObj->GetTransactionFee();
			
			$index = $cartContents->nextIndex;
			$cartContents->nextIndex++;
			
			$cartContents->rows[$index] = $newCartEntry;
		}
		
		function SaveTrolleyContents($cartContents)
		{
			if ($this->myDBaseObj->isDbgOptionSet('Dev_ShowTrolley'))
			{
				StageShowLibUtilsClass::print_r($cartContents, 'Save cartContents ('.$this->trolleyid.')');
			}
			
			$_SESSION[$this->trolleyid] = serialize($cartContents);
		}
		
					
		function SetTrolleyID($id = '')
		{
			if (defined('STAGESHOWLIB_TROLLEYID'))
				$this->trolleyid = STAGESHOWLIB_TROLLEYID.'_saleedit_';
			else
				$this->trolleyid = $this->myDomain.'_saleedit_';
					
			if (defined('RUNSTAGESHOWDEMO'))
			{
				$this->trolleyid = $this->myDBaseObj->get_name().'_saleedit_';
				$this->trolleyid .= '_'.$this->myDBaseObj->loginID;
			}
			
			$this->trolleyid .= ($id != '') ? $id : 'new';
		}
		
		function ClearTrolleyContents()
		{
			if ($this->myDBaseObj->isDbgOptionSet('Dev_ShowTrolley'))
			{
				echo 'CLEAR cartContents ('.$this->trolleyid.") <br>\n";
			}
			
			unset($_SESSION[$this->trolleyid]);
		}
				
		function OnlineStore_HandleTrolley()
		{
			// Only Allow One Shopping Trolley (If there are multiple StageShow shortcodes on one page)
			if (isset($this->DoneSalesTrolley))
				return;
			$this->DoneSalesTrolley = true;
				
			$myDBaseObj = $this->myDBaseObj;
			
			if (isset($this->checkoutMsg))
			{
				if (!isset($this->checkoutMsgClass))
				{
					$this->checkoutMsgClass = $this->cssDomain.'-error';
				}
				echo '<div id="message" class="'.$this->checkoutMsgClass.'">'.$this->checkoutMsg.'</div>';					
			}
				
			$cartContents = $this->GetTrolleyContents();
			
			return $this->OnlineStore_HandleTrolleyButtons($cartContents);
		}
		
		function OnlineStore_GetSortField($result)
		{
			return 0;
		}
		
		function OnlineStore_AddTrolleyExtras(&$cartEntry, $result)
		{
		}
		
		function OnlineStore_HandleTrolleyButtons($cartContents)
		{
			$myDBaseObj = $this->myDBaseObj;
			
			if (isset($_GET['action']) && isset($_GET['editpage']))
			{
				if ($_GET['action'] == 'editsale')
				{
					$buttonID = $this->GetButtonID('editbuyer');
					if (isset($_POST[$buttonID])) $this->cart_ReadOnly = true;
					
					$buttonID = $this->GetButtonID('savesaleedit');
					if (isset($_POST[$buttonID])) return true;
				}
			}
			
			if (isset($_POST['AddTicketSale']))
			{
				// Get the product ID from posted data
				//$ticketType = $_POST['os0'];
				$reqQty = $_POST['quantity'];
					
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
					if ($reqQty > 0)
					{
						$cartEntry = new stdClass;
						$cartEntry->itemID = $itemID;
						$cartEntry->qty = $reqQty;
						$cartEntry->price = $this->GetOnlineStoreItemPrice($priceEntries[0]);
						
						$this->OnlineStore_AddTrolleyExtras($cartEntry, $priceEntries[0]);
						$cartEntry->sortBy = $this->OnlineStore_GetSortField($priceEntries[0]);
						
						$this->AddToTrolleyContents($cartContents, $cartEntry);
					}

					$this->SaveTrolleyContents($cartContents);
				}
			}
				
			if (!isset($cartContents->rows))
				return false;
				
			if (count($cartContents->rows) == 0)
				return false;
				
			$hiddenTags  = "\n";
		
			$doneHeader = false;

			if (isset($_GET['remove']))
			{
				$itemID = $_GET['remove'];
				unset($cartContents->rows[$itemID]);
				if (count($cartContents->rows) == 0)
				{
					$cartContents->fee = 0;
				}
				$this->SaveTrolleyContents($cartContents);
			}
				
			$runningTotal = 0;			
			foreach ($cartContents->rows as $cartIndex => $cartEntry)
			{				
				$itemID = $cartEntry->itemID;
				$qty = $cartEntry->qty;
				
				$priceEntries = $this->GetOnlineStoreProductDetails($itemID);				
				if (count($priceEntries) == 0)
				{
					echo '<div id="message" class="'.$this->cssDomain.'-error">'.__("Shopping Trolley Cleared", $this->myDomain).'</div>';					
					if (current_user_can(STAGESHOWLIB_CAPABILITY_SYSADMIN))
					{
						echo "<br><strong></strong></br>Dumping Trolley (only for SysAdmin User)</strong><br>";
						echo "No entry for ItemID:$itemID<br>";
						StageShowLibUtilsClass::print_r($cartContents, 'cartContents');
					}
					$this->ClearTrolleyContents();
					return false;
				}
					
				$priceEntry = $priceEntries[0];
				if (!$doneHeader)
				{
					$trolleyHeading = $this->adminPageActive ? __('Selected Seats', $this->myDomain) : __('Your Shopping Trolley', $this->myDomain);
					echo '<div class="'.$this->cssTrolleyBaseID.'-header"><h2>'."$trolleyHeading</h2></div>\n";
					if ( ($myDBaseObj->getOption('CheckoutNotePosn') == 'header') && ($myDBaseObj->getOption('CheckoutNote') != '') )
					{
						echo $myDBaseObj->getOption('CheckoutNote');
					}
					
					$actionURL = get_permalink();
					$actionURL = remove_query_arg('remove', $actionURL);
					$actionURL = remove_query_arg('editpage', $actionURL);
					$actionURL = add_query_arg('editpage', 'seats', $actionURL);
					
					echo '<div class="'.$this->cssTrolleyBaseID.'">'."\n";
					echo '<form method="post" action="'.$actionURL.'">'."\n";
					echo '<table class="'.$this->cssTrolleyBaseID.'-table">'."\n";
					if ( ($myDBaseObj->getOption('CheckoutNotePosn') == 'titles') && ($myDBaseObj->getOption('CheckoutNote') != '') )
					{
						echo $myDBaseObj->getOption('CheckoutNote');
					}
					$this->OutputContent_OnlineTrolleyHeader($priceEntry);
						
					$doneHeader = true;
				}
					
				echo '<tr class="'.$this->cssTrolleyBaseID.'-row">'."\n";
					
				$runningTotal += $this->OutputContent_OnlineTrolleyRow($priceEntry, $cartEntry);
					
				if (!isset($this->cart_ReadOnly))
				{
					$removeLineURL = get_permalink();
					$removeLineURL  = add_query_arg('editpage', 'tickets', $removeLineURL);
					$removeLineURL  = add_query_arg('remove', $cartIndex, $removeLineURL);
					echo '<td class="'.$this->cssTrolleyBaseID.'-remove"><a href=' . $removeLineURL . '>'.__('Remove', $this->myDomain).'</a></td>'."\n";
				}
				else
				{
					echo '<td class="'.$this->cssTrolleyBaseID.'-remove">&nbsp;</td>'."\n";
				}	
				
				echo "</tr>\n";
					
				$hiddenTags .= '<input type="hidden" name="id'.$cartIndex.'" value="'.$itemID.'"/>'."\n";
				$hiddenTags .= '<input type="hidden" name="qty'.$cartIndex.'" value="'.$qty.'"/>'."\n";
			}
			
			$runningTotal += $this->OutputContent_OnlineTrolleyFee($cartContents);
				
			if ($doneHeader)
			{	
				// Add totals row and checkout button
				$trolleyTotal = $myDBaseObj->FormatCurrency($runningTotal);
			
				echo '<tr class="'.$this->cssTrolleyBaseID.'-totalrow">'."\n";
				echo '<td colspan="'.($this->trolleyHeaderCols-4).'">&nbsp;</td>'."\n";
				echo '<td>'.__('Total', $this->myDomain).'</td>'."\n";
				echo '<td>&nbsp;</td>'."\n";
				echo '<td class="'.$this->cssTrolleyBaseID.'-total">'.$trolleyTotal.'</td>'."\n";
				echo '<td>&nbsp;</td>'."\n";
				echo "</tr>\n";
			
				if ( ($myDBaseObj->getOption('CheckoutNotePosn') == 'above') && ($myDBaseObj->getOption('CheckoutNote') != '') )
				{
					echo '<tr><td colspan="'.$this->trolleyHeaderCols.'">'.$myDBaseObj->getOption('CheckoutNote')."</td></tr>\n";
				}
					
				if (!isset($this->cart_ReadOnly))
				{
					echo '<tr>'."\n";
					echo '<td align="center" colspan="'.$this->trolleyHeaderCols.'" class="'.$this->cssTrolleyBaseID.'-checkout">'."\n";
					
					$this->OutputContent_OnlineCheckoutButton($cartContents);
						
					echo '</td>'."\n";
					echo "</tr>\n";
				}
				
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
			
			return $doneHeader;			
		}

		function GetOnlineStoreTrolleyDetails($cartIndex, $cartEntry)
		{
			$saleDetails['itemID' . $cartIndex] = $cartEntry->itemID;
			$saleDetails['qty' . $cartIndex] = $cartEntry->qty;;
			
			return $saleDetails;
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
			if ($myDBaseObj->isDbgOptionSet('Dev_ShowTrolley'))
			{
				StageShowLibUtilsClass::print_r($cartContents, 'cartContents');
			}
				
			if (!isset($cartContents->rows))
			{
				$rslt->checkoutMsg  = __('Cannot Checkout', $this->myDomain).' - ';
				$rslt->checkoutMsg .= __('Shopping Trolley Empty', $this->myDomain);
				return $rslt;
			}
			
			// Build request parameters for redirect to PayPal checkout
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
				
				$ParamsOK &= $this->CheckPayPalParam($passedParams, "id" , $cartEntry->itemID, $cartIndex);
				$ParamsOK &= $this->CheckPayPalParam($passedParams, "qty" , $cartEntry->qty, $cartIndex);
				if (!$ParamsOK)
				{
					$rslt->checkoutMsg  = __('Cannot Checkout', $this->myDomain).' - ';
					$rslt->checkoutMsg .= __('Shopping Trolley Contents have changed', $this->myDomain);
					return $rslt;
				}
					
				$itemPrice = $this->GetOnlineStoreItemPrice($priceEntry);
				$shipping = 0.0;						
				
				$rslt->paypalParams['item_name_'.$paramCount] = $this->GetOnlineStoreItemName($priceEntry);
				$rslt->paypalParams['amount_'.$paramCount] = $itemPrice;
				$rslt->paypalParams['quantity_'.$paramCount] = $qty;
				$rslt->paypalParams['shipping_'.$paramCount] = $shipping;
					
				$rslt->saleDetails = array_merge($rslt->saleDetails, $this->GetOnlineStoreTrolleyDetails($paramCount, $cartEntry));
				$rslt->saleDetails['itemPaid' . $paramCount] = $itemPrice;
				
				$rslt->totalDue += ($itemPrice * $qty);
			}
			
			$rslt->fee = $cartContents->fee;
			$this->OnlineStore_AddTransactionFee($rslt, $paramCount);
											
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

		function OnlineStore_AddTransactionFee(&$rslt, &$paramCount)
		{
		}
		
		function OnlineStore_ProcessCheckout()
		{
			// Process checkout request for Integrated Trolley
			// This function must be called before any output as it redirects to PayPal if successful
			$buttonID  = $this->GetButtonID('checkout');
			if (isset($_POST[$buttonID]))
			{
				$myDBaseObj = $this->myDBaseObj;
				
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
				
				$checkoutRslt->paypalParams['image_url'] = $myDBaseObj->getImageURL('PayPalLogoImageFile');
				$checkoutRslt->paypalParams['cpp_header_image'] = $myDBaseObj->getImageURL('PayPalHeaderImageFile');
				$checkoutRslt->paypalParams['no_shipping'] = '2';
				$checkoutRslt->paypalParams['business'] = $myDBaseObj->adminOptions['PayPalMerchantID'];	// Can use adminOptions['PayPalAPIEMail']
				$checkoutRslt->paypalParams['currency_code'] = $myDBaseObj->adminOptions['PayPalCurrency'];
				$checkoutRslt->paypalParams['cmd'] = '_cart';
				$checkoutRslt->paypalParams['upload'] = '1';
				
				if ($myDBaseObj->adminOptions['CheckoutCompleteURL'] != '')
				{
					$checkoutRslt->paypalParams['rm'] = '2';
					$checkoutRslt->paypalParams['return'] = $myDBaseObj->adminOptions['CheckoutCompleteURL'];
				}
				
				if ($myDBaseObj->adminOptions['CheckoutCancelledURL'] != '')
				{
					$checkoutRslt->paypalParams['cancel_return'] = $myDBaseObj->adminOptions['CheckoutCancelledURL'];
				}
					
				$checkoutRslt->paypalParams['notify_url'] = $myDBaseObj->PayPalNotifyURL;
			
				$paypalURL = PayPalAPIClass::GetPayPalURL(false);
				//$paypalURL = 'http://www.paypal.com/cgi-bin/webscr';
				
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
				$ParamsOK = $this->IsOnlineStoreItemAvailable($checkoutRslt);
					
				if ($ParamsOK)
  				{
					// Update quantities ...
					$saleId = $this->myDBaseObj->LogSale($checkoutRslt->saleDetails, StageShowLibSalesDBaseClass::STAGESHOWLIB_LOGSALEMODE_CHECKOUT);
					$paypalURL = add_query_arg('custom', $saleId, $paypalURL);		
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
				
					if (defined('RUNSTAGESHOWDEMO'))
					{
						$this->demosale = $saleId;
						$this->pageMode = self::PAGEMODE_DEMOSALE;
					}
					elseif ($this->myDBaseObj->isDbgOptionSet('Dev_IPNLocalServer'))
					{
						$this->checkoutMsg .= __('Using Local IPN Server - PayPal Checkout call skipped', $this->myDomain);
					}
					else 
					{
						header( 'Location: '.$paypalURL ) ;
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
		
		function GetParamAsHiddenTag($paramId)
		{
			if (isset($_GET[$paramId]))	
			{
				$paramValue = $_GET[$paramId];
			}
			else if (isset($_POST[$paramId]))	
			{
				$paramValue = $_POST[$paramId];
			}
			else
			{
				return "<!-- GetParamAsHiddenTag($paramId) returned NULL -->\n";
			}
			
			return '<input type="hidden" name="'.$paramId.'" id="'.$paramId.'" value="'.$paramValue.'"/>'."\n";
		}
	}
}
?>