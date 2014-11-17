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
	
	if (!defined('STAGESHOWLIB_NOTETOSELLER_ROWS'))
	{
		define('STAGESHOWLIB_NOTETOSELLER_ROWS', 2);
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
			$myDBaseObj = $this->myDBaseObj;
			
			$this->myDomain = $myDBaseObj->get_domain();
							
			if (!isset($this->cssDomain)) $this->cssDomain = $this->myDomain;
			if (!isset($this->cssBaseID)) $this->cssBaseID = $this->cssDomain.'-shop';
			if (!isset($this->cssTrolleyBaseID)) $this->cssTrolleyBaseID = $this->cssDomain.'-trolley';
			
			if (!isset($this->colID)) 
			{
				$this->colID['name'] = __('Name', $this->myDomain);
				$this->cssColID['name'] = "name";			
				$this->colID['datetime'] = __('Name', $this->myDomain);
				$this->cssColID['datetime'] = "name";			
				$this->colID['ref'] = __('Ref', $this->myDomain);
				$this->cssColID['ref'] = "ref";

				$this->colID['price'] = __('Price', $this->myDomain);
				$this->cssColID['price'] = "price";
				$this->colID['qty'] = __('Quantity', $this->myDomain);
				$this->cssColID['qty'] = "qty";
			}
				
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
			
			if ($myDBaseObj->getDbgOption('Dev_ShowGET'))
			{
				echo "<br>".'$_GET'."<br>\n";
				print_r($_GET);
			}
			if ($myDBaseObj->getDbgOption('Dev_ShowPOST'))
			{
				echo "<br>".'$_POST'."<br>\n";
				print_r($_POST);
			}		
		}
		
		function GetOurURL()
		{			
			$actionURL = remove_query_arg('_wpnonce');
			$actionURL = remove_query_arg('remove', $actionURL);
			$actionURL = remove_query_arg('editpage', $actionURL);
			
			return $actionURL;
		}
		
		function CheckAdminReferer($referer = '')
		{
		}
		
		function OutputContent_OnlineStoreMain($atts)
		{
      		// Get all database entries for this item ... ordered by date/time then ticket type
	      	$results = $this->GetOnlineStoreProducts($atts);
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
		
		function GetOnlineStoreProducts($atts)
		{
			$myDBaseObj = $this->myDBaseObj;
			
			$reqRecordId = $atts['id'];
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
			
		function GetOnlineStoreItemName($result, $cartEntry = null)
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
			
		function GetOnlineStoreItemsAvailable($result)
		{
			return -1;
		}
			
		function GetOnlineStoreItemNote($result, $posn)
		{
			return '';
		}
			
		function IsOnlineStoreItemAvailable($saleItems)
		{
			return true;
		}

		function GetOnlineStoreElemTagId($id, $result)
		{
			$itemID = $this->GetOnlineStoreItemID($result);	
			$id .= '_' . $itemID;
			return $id;
		}

		function OutputContent_OnlineStoreTitle($result)
		{
		}
			
		function OutputContent_OnlineStoreHeader($result)
		{
			echo '
				<table class="'.$this->cssBaseID.'-table" width="100%" border="0">
					<tr>
						<td class="'.$this->cssBaseID.'-header">
							<table width="100%" cellspacing="0">
								<tr>
									<td class="'.$this->cssBaseID.'-'.$this->cssColID['datetime'].'">'.$this->colID['datetime'].'</td>
									<td class="'.$this->cssBaseID.'-'.$this->cssColID['ref'].'">'.$this->colID['ref'].'</td>
									<td class="'.$this->cssBaseID.'-'.$this->cssColID['price'].'">'.$this->colID['price'].'</td>
									<td class="'.$this->cssBaseID.'-'.$this->cssColID['qty'].'">'.$this->colID['qty'].'</td>
									<td class="'.$this->cssBaseID.'-add">&nbsp;</td>
								</tr>
							</table>
						</td>
					</tr>
				';
		}		
				
		function OutputContent_OnlineStoreRow($result)
		{
			$storeRowHTML = '';
			$myDBaseObj = $this->myDBaseObj;
			
			$altTag = $myDBaseObj->adminOptions['OrganisationID'].' '.__('Sales', $this->myDomain);
			$buttonURL = $myDBaseObj->getImageURL('AddCartButtonURL');

			$itemPrice = $myDBaseObj->FormatCurrency($this->GetOnlineStoreItemPrice($result));
			
			$stockDetails = isset($result->stockDetails) ? $result->stockDetails : '';
			$addColSpan = ($stockDetails != '') ? ' rowspan="2" ' : '';
			
			$storeRowHTML .= '
				<table cellspacing="0">
					<tr>
						<td class="'.$this->cssBaseID.'-'.$this->cssColID['datetime'].'">'.$result->stockName.'</td>
						<td class="'.$this->cssBaseID.'-'.$this->cssColID['type'].'">'.$result->stockRef.'</td>
						<td class="'.$this->cssBaseID.'-price">'.$itemPrice.'</td>
						<td class="'.$this->cssBaseID.'-qty">
				';
				
				switch ($result->stockType)
				{
					case STAGESHOWLIB_STATE_POST:
						$quantityTagId = $this->GetOnlineStoreElemTagId('quantity', $result); 
						$storeRowHTML .= '
								<select name="'.$quantityTagId.'">
									<option value="1" selected="">1</option>
						';
						for ($no=2; $no<=STAGESHOWLIB_MAXSALECOUNT; $no++)
							$storeRowHTML .= '<option value="'.$no.'">'.$no.'</option>'."\n";
						$storeRowHTML .= '
								</select>
							</td>
						';
						break;
						
					case STAGESHOWLIB_STATE_DOWNLOAD:
					default:
						$storeRowHTML .= '<input type="hidden" name="quantity" value="1"/>1'."\n";
						break;
				}
				
			$buttonTag = ($buttonURL != '') ? ' src="'.$buttonURL.'"' : '';
			
			$buttonId = $this->GetOnlineStoreElemTagId('AddTicketSale', $result);
						
			$storeRowHTML .= '
				<td '.$addColSpan.'class="'.$this->cssBaseID.'-add">
							<input type="submit" value="'.__('Add', $this->myDomain).'" alt="'.$altTag.'" '.$buttonTag.' id="'.$buttonId.'" name="'.$buttonId.'"/>
				</td>
				</tr>				
				';
				
			if ($stockDetails != '') $storeRowHTML .= '
					<tr>
						<td colspan="4" class="'.$this->cssBaseID.'-details">'.$result->stockDetails.'</td>
					</tr>				
				';
				
			$storeRowHTML .= "</table>\n";			
			return $storeRowHTML;
		}
		
		function OutputContent_OnlineStoreFooter()
		{
		}
		
		function OutputContent_GetAtts( $atts )
		{
			$atts = shortcode_atts(array(
				'id'    => '',
				'count' => '',
				'style' => 'normal' 
			), $atts );
        
        	return $atts;
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
			
			$outputContent  = "\n<!-- $pluginID Plugin Code - Starts Here -->\n";
			$outputContent .= '<form></form>'."\n";		// Insulate StageShow from unterminated form tags
			
			$actionURL = $this->GetOurURL();
			$outputContent .= '<form id=trolley method="post" action="'.$actionURL.'">'."\n";				
			$outputContent .= $myDBaseObj->GetWPNonceField();
				 
			if (isset($this->editpage))
			{
				$outputContent .= '<input type="hidden" name="editpage" value="'.$this->editpage.'"/>'."\n";				
			}
		
			
			ob_start();			
			$hasActiveTrolley = $this->OnlineStore_HandleTrolley();
			$trolleyContent = ob_get_contents();
			ob_end_clean();
			
			$atts = $this->OutputContent_GetAtts($atts);
        
			ob_start();
			
			$this->OutputContent_OnlineStoreMain($atts);
			$this->OutputContent_OnlineStoreFooter();
			
			$boxofficeContent = ob_get_contents();
			ob_end_clean();
			
			if ($myDBaseObj->getOption('ProductsAfterTrolley'))
			{
				$outputContent .= $trolleyContent.$boxofficeContent;
			}
			else
			{
				$outputContent .= $boxofficeContent.$trolleyContent;
			}
			
			$outputContent .= '</form>'."\n";	
			
			$outputContent .= "\n<!-- $pluginID Plugin Code - Ends Here -->\n";
			
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
					';

				echo $storeRowHTML;
				
				echo '
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
			echo '<td class="'.$this->cssTrolleyBaseID.'-'.$this->cssColID['name'].'">'.$this->colID['name'].'</td>'."\n";
			echo '<td class="'.$this->cssTrolleyBaseID.'-'.$this->cssColID['ref'].'">'.$this->colID['ref'].'</td>'."\n";
			echo '<td class="'.$this->cssTrolleyBaseID.'-'.$this->cssColID['price'].'">'.$this->colID['price'].'</td>'."\n";
			echo '<td class="'.$this->cssTrolleyBaseID.'-'.$this->cssColID['qty'].'">'.$this->colID['qty'].'</td>'."\n";
			echo '<td class="'.$this->cssTrolleyBaseID.'-remove">&nbsp;</td>'."\n";
			echo "</tr>\n";
			
			$this->trolleyHeaderCols = 5;	// Count of the number of columns in the header
		}
				
		function OutputContent_OnlineTrolleyRow($priceEntry, $cartEntry)
		{
			$qty = $cartEntry->qty;			
			$priceValue = $cartEntry->price;
			$total = $priceValue * $qty;
								
			echo '<td class="'.$this->cssTrolleyBaseID.'-'.$this->cssColID['name'].'">'.$priceEntry->stockName.'</td>'."\n";
			echo '<td class="'.$this->cssTrolleyBaseID.'-'.$this->cssColID['ref'].'">'.$priceEntry->stockRef.'</td>'."\n";
			echo '<td class="'.$this->cssTrolleyBaseID.'-'.$this->cssColID['price'].'">'.$this->myDBaseObj->FormatCurrency($priceValue).'</td>'."\n";
			echo '<td class="'.$this->cssTrolleyBaseID.'-'.$this->cssColID['qty'].'">'.$qty.'</td>'."\n";
			
			return $total;
		}
		
		function OutputContent_OnlineTrolleyFee($cartContents)
		{
			return 0;
		}
				
		function OutputContent_OnlineTrolleyDonation($cartContents)
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
				
		function GetButtonTypeDef($buttonID, $buttonName = '', $buttonType = 'submit')
		{
			$buttonTypeDef = 'type="'.$buttonType.'"';
			
			if ($buttonName == '')
			{
				$buttonName = $this->GetButtonID($buttonID);
			}
			$buttonTypeDef .= ' id="'.$buttonName.'" name="'.$buttonName.'"';
			
			return $buttonTypeDef;
		}
				
		function OutputContent_OnlineRemoveButton($cartIndex, $removeLinkContent='')
		{
			if (isset($_REQUEST['_wpnonce']))
			{
				$wpNonce = $_REQUEST['_wpnonce'];
			}
			else
			{
				$myDBaseObj = $this->myDBaseObj;
				$wpNonce = $myDBaseObj->GetWPNonce();
			}
			
			if ($removeLinkContent == '')
			{
				$removeLinkContent = __('Remove', $this->myDomain);
			}
			$removeLineURL = $this->GetOurURL();
			$removeLineURL  = add_query_arg('editpage', 'tickets', $removeLineURL);
			$removeLineURL  = add_query_arg('remove', $cartIndex, $removeLineURL);
			$removeLineURL  = add_query_arg('_wpnonce', $wpNonce, $removeLineURL);
			$removeButton = '<a href="' . $removeLineURL . '">'.$removeLinkContent.'</a>';
			return $removeButton;
		}
		
		function OutputContent_OnlineCheckoutButton($cartContents)
		{
			$buttonType = $this->GetButtonTypeDef('checkout');
			echo '<input '.$buttonType.' value="'.__('Checkout', $this->myDomain).'"/>'."\n";
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
				$cartContents->saleDonation = '';
				$cartContents->saleNoteToSeller = '';
			}
			
			if ($this->myDBaseObj->isDbgOptionSet('Dev_ShowTrolley'))
			{
				if ($this->myDBaseObj->getDbgOption('Dev_ShowCallStack'))
				{
					StageShowLibUtilsClass::ShowCallStack();
				}
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
			
			$cartContents->saleTransactionFee = $this->myDBaseObj->GetTransactionFee();
			
			$index = $cartContents->nextIndex;
			$cartContents->nextIndex++;
			
			$cartContents->rows[$index] = $newCartEntry;
		}
		
		function SaveTrolleyContents($cartContents)
		{
			if ($this->myDBaseObj->isDbgOptionSet('Dev_ShowTrolley'))
			{
				if ($this->myDBaseObj->getDbgOption('Dev_ShowCallStack'))
				{
					StageShowLibUtilsClass::ShowCallStack();
				}
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
					$this->checkoutMsgClass = $this->cssDomain.'-error error';
				}
				echo '<div id="message" class="'.$this->checkoutMsgClass.'">'.$this->checkoutMsg.'</div>';					
			}
				
			$cartContents = $this->GetTrolleyContents();
			
			return $this->OnlineStore_HandleTrolleyButtons($cartContents);
		}
		
		function OnlineStore_GetSortField($result)
		{
			return $result->stockName.'_'.$result->stockID;
		}
		
		function OnlineStore_AddTrolleyExtras(&$cartEntry, $result)
		{
		}
		
		function OnlineStore_HandleTrolleyButtons($cartContents)
		{
			$myDBaseObj = $this->myDBaseObj;
			
			if (isset($_GET['action']) && isset($_REQUEST['editpage']))
			{
				if ($_GET['action'] == 'editsale')
				{
					$buttonID = $this->GetButtonID('editbuyer');
					if (isset($_POST[$buttonID])) $this->cart_ReadOnly = true;
					$buttonID = $this->GetButtonID('savesaleedit');
					if (isset($_POST[$buttonID])) 
					{
						$cartContents = $this->GetTrolleyContents();
						
						$cartContents->saleEMail     = $_POST['saleEMail'];
						$cartContents->saleFirstName = $_POST['saleFirstName'];
						$cartContents->saleLastName  = $_POST['saleLastName'];
						$cartContents->salePPStreet  = $_POST['salePPStreet'];
						$cartContents->salePPCity    = $_POST['salePPCity'];								
						$cartContents->salePPState   = $_POST['salePPState'];
						$cartContents->salePPZip     = $_POST['salePPZip'];
						$cartContents->salePPCountry = $_POST['salePPCountry'];
						$cartContents->salePPPhone   = $_POST['salePPPhone'];	
						$cartContents->saleStatus    = $_POST['saleStatus'];	
																
						$cartContents->saleNoteToSeller = isset($_POST['saleNoteToSeller']) ? $_POST['saleNoteToSeller'] : '';	
																
						$this->SaveTrolleyContents($cartContents);
					
						$saleID = $this->OnlineStoreSaveEdit();
						
						if ($saleID > 0)
						{
							echo '
							<div id="message" class="updated">
							<p>'.__('Sale Details have been saved', $this->myDomain);
							$myDBaseObj->OutputViewTicketButton($saleID);
							echo '
							</div>';
							
							return true;	// Supress output of shopping trolley
						}
						
						// $cartContents is updated when OnlineStoreSaveEdit() fails - Reload it!
						$cartContents = $this->GetTrolleyContents();
						unset($_POST[$buttonID]);
					}				
				}
			}
			
			$itemID = 0;
			foreach ($_POST as $postId => $postVal)
			{
				$postIdElems = explode("_", $postId);
				if (count($postIdElems) < 2) continue;
				if ($postIdElems[0] == 'AddTicketSale') 
				{
					$itemID = $postIdElems[1];
				}
				if ($postIdElems[0] == 'RemoveTicketSale') 
				{
					$_GET['remove'] = $postIdElems[1];
				}
			}
			
			if ($itemID > 0)
			{
				// Get the product ID from posted data
				//$ticketType = $_POST['os0'];
				$reqQty = $_POST['quantity_'.$itemID];

				// Interogate the database to confirm that the item exists
				$priceEntries = $this->GetOnlineStoreProductDetails($itemID);

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
				
			if (isset($_POST['saleDonation']))
			{
				$cartContents->saleDonation = $myDBaseObj->FormatCurrency($_POST['saleDonation']);
				$this->SaveTrolleyContents($cartContents);
			}
			
			$hiddenTags  = "\n";
		
			$doneHeader = false;

			if (isset($_GET['remove']))
			{
				$itemID = $_GET['remove'];
				unset($cartContents->rows[$itemID]);
				if (count($cartContents->rows) == 0)
				{
					$cartContents->saleTransactionFee = $myDBaseObj->FormatCurrency(0);
					$cartContents->saleDonation = '';
				}
				$this->SaveTrolleyContents($cartContents);
			}
				
			$runningTotal = 0;		
			
			if (isset($this->editpage))
			{
				$checkoutNote = '';
				$checkoutNotePosn = '';
			}			
			else
			{
				$checkoutNote = $myDBaseObj->getOption('CheckoutNote');
				$checkoutNotePosn = $myDBaseObj->getOption('CheckoutNotePosn');
			}
				
			foreach ($cartContents->rows as $cartIndex => $cartEntry)
			{				
				$itemID = $cartEntry->itemID;
				$qty = $cartEntry->qty;
				
				$priceEntries = $this->GetOnlineStoreProductDetails($itemID);				
				if (count($priceEntries) == 0)
				{
					echo '<div id="message" class="'.$this->cssDomain.'-error error">'.__("Shopping Trolley Cleared", $this->myDomain).'</div>';					
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
					if ( ($checkoutNotePosn == 'header') && ($checkoutNote != '') )
					{
						echo $checkoutNote;
					}
					
					echo '<div class="'.$this->cssTrolleyBaseID.'">'."\n";
					echo '<table class="'.$this->cssTrolleyBaseID.'-table">'."\n";
					if ( ($checkoutNotePosn == 'titles') && ($checkoutNote != '') )
					{
						echo $checkoutNote;
					}
					$this->OutputContent_OnlineTrolleyHeader($priceEntry);
						
					$doneHeader = true;
				}
					
				echo '<tr class="'.$this->cssTrolleyBaseID.'-row">'."\n";
					
				$runningTotal += $this->OutputContent_OnlineTrolleyRow($priceEntry, $cartEntry);
					
				if (!isset($this->cart_ReadOnly))
				{
					$removeLinkContent = $this->OutputContent_OnlineRemoveButton($cartIndex);
				}
				else
				{
					$removeLinkContent = '&nbsp;';
				}	
				echo '<td class="'.$this->cssTrolleyBaseID.'-remove">'.$removeLinkContent.'</td>'."\n";
				
				echo "</tr>\n";
					
				$hiddenTags .= '<input type="hidden" name="id'.$cartIndex.'" value="'.$itemID.'"/>'."\n";
				$hiddenTags .= '<input type="hidden" name="qty'.$cartIndex.'" value="'.$qty.'"/>'."\n";
			}
			
			if ($doneHeader)
			{	
				$runningTotal += $this->OutputContent_OnlineTrolleyFee($cartContents);
				$trolleyTotal = $runningTotal + $this->OutputContent_OnlineTrolleyDonation($cartContents);
				
				// Add totals row and checkout button
				$runningTotal = $myDBaseObj->FormatCurrency($runningTotal);				
				$trolleyTotal = $myDBaseObj->FormatCurrency($trolleyTotal);
				
				echo '<tr class="'.$this->cssTrolleyBaseID.'-totalrow">'."\n";
				echo '<td colspan="'.($this->trolleyHeaderCols-4).'">&nbsp;</td>'."\n";
				echo '<td>'.__('Total', $this->myDomain)."\n";
				echo '<input type="hidden" id="saleTrolleyTotal" name="saleTrolleyTotal" value="'.$runningTotal.'"/>'."\n";
				echo '</td>'."\n";
				echo '<td>&nbsp;</td>'."\n";
				echo '<td class="'.$this->cssTrolleyBaseID.'-total" id="'.$this->cssTrolleyBaseID.'-totalval" name="'.$this->cssTrolleyBaseID.'-totalval">'.$trolleyTotal.'</td>'."\n";
				echo '<td>&nbsp;</td>'."\n";
				echo "</tr>\n";
				
				if ( ($checkoutNotePosn == 'above') && ($checkoutNote != '') )
				{
					echo '<tr><td colspan="'.$this->trolleyHeaderCols.'">'.$checkoutNote."</td></tr>\n";
				}
					
				if (!isset($this->cart_ReadOnly))
				{
					if (!isset($this->editpage) && $myDBaseObj->isOptionSet('UseNoteToSeller'))
					{
						if (isset($_POST['saleNoteToSeller']))
							$noteToSeller = $_POST['saleNoteToSeller'];
						else if (isset($cartContents->saleNoteToSeller))
							$noteToSeller = $cartContents->saleNoteToSeller;
						else
							$noteToSeller = '';

						$noteCols = $this->trolleyHeaderCols-1;
						$rowsDef = defined('STAGESHOWLIB_NOTETOSELLER_ROWS') ? "rows=".STAGESHOWLIB_NOTETOSELLER_ROWS." " : "";
						
						echo '
							<tr class="stageshow-trolley-notetoseller">
							<td>'.__('Message To Seller', $this->myDomain).'</td>
							<td colspan="'.$noteCols.'">
							<textarea name="saleNoteToSeller" id="saleNoteToSeller" '.$rowsDef.'>'.$noteToSeller.'</textarea>
							</td>
							</tr>
							';
					}
					
					echo '<tr>'."\n";
					echo '<td align="center" colspan="'.$this->trolleyHeaderCols.'" class="'.$this->cssTrolleyBaseID.'-checkout">'."\n";
					
					$this->OutputContent_OnlineCheckoutButton($cartContents);
					
					echo '<input type="hidden" id="saleCustomValues" name="saleCustomValues" value=""/>'."\n";
					
					echo '</td>'."\n";
					echo "</tr>\n";
				}
				
				if ( ($checkoutNotePosn == 'below') && ($checkoutNote != '') )
				{
					echo '<tr><td colspan="'.$this->trolleyHeaderCols.'">'.$checkoutNote."</td></tr>\n";
				}
					
				if ($myDBaseObj->getOption('CheckoutNoteToSeller'))
				{
					echo '<tr><td colspan="'.$this->trolleyHeaderCols.'">'.$checkoutNote."</td></tr>\n";
				}
					
				echo "</table>\n";
				echo $hiddenTags;						
				echo '</div>'."\n";
				
				if ( ($checkoutNotePosn == 'bottom') && ($checkoutNote != '') )
				{
					echo $checkoutNote;
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
			
			$rslt->saleDetails['saleNoteToSeller'] = isset($_POST['saleNoteToSeller']) ? $myDBaseObj->_real_escape($_POST['saleNoteToSeller']) : '';
			
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
				
				$rslt->paypalParams['item_name_'.$paramCount] = $this->GetOnlineStoreItemName($priceEntry, $cartEntry);
				$rslt->paypalParams['amount_'.$paramCount] = $itemPrice;
				$rslt->paypalParams['quantity_'.$paramCount] = $qty;
				$rslt->paypalParams['shipping_'.$paramCount] = $shipping;
					
				$rslt->saleDetails = array_merge($rslt->saleDetails, $this->GetOnlineStoreTrolleyDetails($paramCount, $cartEntry));
				$rslt->saleDetails['itemPaid' . $paramCount] = $itemPrice;
				
				$rslt->totalDue += ($itemPrice * $qty);
			}
			
			$this->OnlineStore_AddExtraPayment($rslt, $paramCount, $cartContents->saleTransactionFee, __('Booking Fee', $this->myDomain), 'saleTransactionfee');

			if (isset($_POST['saleDonation']))
			{
				$newSaleDonation = $_POST['saleDonation'];
				if (!is_numeric($newSaleDonation) || ($newSaleDonation < 0))
				{
					$newSaleDonation = 0;
				}
				$cartContents->saleDonation = $myDBaseObj->FormatCurrency($newSaleDonation);					
			}	
			if ($cartContents->saleDonation > 0)
			{
				$this->OnlineStore_AddExtraPayment($rslt, $paramCount, $cartContents->saleDonation, __('Donation', $this->myDomain), 'saleDonation');				
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

		function OnlineStore_AddExtraPayment(&$rslt, &$paramCount, $amount, $name, $detailID)
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
				
				// Use Merchant ID if it is defined
				if ($myDBaseObj->isOptionSet('PayPalMerchantID'))
				{
					$checkoutRslt->paypalParams['business'] = $myDBaseObj->adminOptions['PayPalMerchantID'];	// Can use adminOptions['PayPalAPIEMail']
				}
				else
				{
					$checkoutRslt->paypalParams['business'] = $myDBaseObj->adminOptions['PayPalAPIEMail'];	// Can use adminOptions['PayPalAPIEMail']
				}
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
						if ($this->myDBaseObj->isDbgOptionSet('Dev_IPNDisplay'))
						{
							$paypalURLParams = str_replace('&', '<br>', $paypalURL);
							$this->checkoutMsg .= "<br>PayPal URL:$paypalURLParams<br>\n";
						}					
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