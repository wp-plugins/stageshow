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
{
	include 'stageshowlib_httpio.php';
	
	if (!defined('STAGESHOWLIB_STATE_DOWNLOAD'))
	{
		define('STAGESHOWLIB_STATE_DOWNLOAD',  'Download');
		define('STAGESHOWLIB_STATE_POST',      'Post');
		define('STAGESHOWLIB_STATE_DELETED',   'deleted');
		define('STAGESHOWLIB_STATE_DISCARDED', 'discarded');
	}
			
	if (!defined('STAGESHOWLIB_MAXSALECOUNT'))
	{
		define('STAGESHOWLIB_MAXSALECOUNT', 4);
	}
	
	if (!defined('STAGESHOWLIB_NOTETOSELLER_ROWS'))
	{
		define('STAGESHOWLIB_NOTETOSELLER_ROWS', 2);
	}
	
	if (!defined('STAGESHOWLIB_TROLLEYTIMEOUT'))
	{
		define('STAGESHOWLIB_TROLLEYTIMEOUT', 30*60);
	}
	
	if (!defined('STAGESHOWLIB_PAYMENT_METHODS'))
	{
		define('STAGESHOWLIB_PAYMENT_METHODS', __('/Cash/Cheque/Credit Card/Debit Card/Voucher'));
	}
	
	if (!defined('STAGESHOWLIB_SENDEMAIL_TARGET'))
	{
		define('STAGESHOWLIB_SENDEMAIL_TARGET', 'stageshowlib_jquery_email.php');
	}
	
	class StageShowLibSalesCartPluginBaseClass
	{
		const PAGEMODE_NORMAL = 'normal';
		const PAGEMODE_DEMOSALE = 'demosale';
		
		const ANCHOR_PREFIX = 'Anchor_';
		
		var $lastItemID = '';
		var $pageMode = self::PAGEMODE_NORMAL;
		var $adminPageActive = false;
		
		var $myDomain;
		var $cssDomain;
		var $cssBaseID;
		var $colID;
		var $cssColID;
		var $cssTrolleyBaseID;
		var $trolleyid;
		var $shortcode;

		var $myJSRoot = 'StageShowLib_JQuery';
		
		var $trolleyHeaderCols;
		
		var $checkoutMsg;
		var $checkoutMsgClass;
		
		var $editpage;
		var $cart_ReadOnly;
		var $saleConfirmationMode;
		
		var $TL8Strings = array();
		
		var $boxofficeContent = '';
			
		function __construct()
		{
			$myDBaseObj = $this->myDBaseObj;
			
			$this->myDomain = $myDBaseObj->get_domain();
			
			if (isset($_REQUEST['pageURI']))
			{
				$this->pageURI = $_REQUEST['pageURI'];
			}
			else
			{
				$this->pageURI = $_SERVER['REQUEST_URI'];				
			}
			
			if (!isset($this->cssDomain)) $this->cssDomain = $this->myDomain;
			if (!isset($this->cssBaseID)) $this->cssBaseID = $this->cssDomain.'-shop';
			if (!isset($this->cssTrolleyBaseID)) $this->cssTrolleyBaseID = $this->cssDomain.'-trolley';
			
			if (!isset($this->colID)) 
			{
				$this->colID['name'] = __('Name', $this->myDomain);
				$this->cssColID['name'] = "name";			
				$this->colID['datetime'] = __('Date & Time', $this->myDomain);
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
					
				if (defined('CORONDECK_RUNASDEMO'))
				{
					$this->trolleyid = $this->myDBaseObj->get_name().'_cart_obj';
					$this->trolleyid .= '_'.$this->myDBaseObj->loginID;
				}
			}
			if (!isset($this->shortcode)) $this->shortcode = $this->myDomain.'-store';
		}
		
		function CheckAdminReferer($referer = '')
		{
		}
		
		function TranslatedText($text, $unused)
		{
			if (isset($this->TL8Strings[$text]))
			{
				$TL8text = $this->TL8Strings[$text];
			}
			else
			{
				$TL8text = __($text, $this->myDomain);
			}
			$id = 'label_'.str_replace(' ', '_', $text);
			
			return $TL8text.'<input type="hidden" name="'.$id.'" id="'.$id.'" value="'.$TL8text.'" />';
		}
		
		function Cart_OutputContent_OnlineStoreMain($atts)
		{
			// Deal with sale editor pages
			if ($this->adminPageActive)
			{
				$buttonID = $this->GetButtonID('editbuyer');
				if (isset($_POST[$buttonID]))	// 'editbuyer' editing sale - get buyer details
				{
					// Output Buyer Details Form
					if (!current_user_can(STAGESHOWLIB_CAPABILITY_ADMINUSER))
						return;
				
					$cartContents = $this->GetTrolleyContents();
			
					// Get $cartContents->salePostTickets = **** etc.
					$this->OnlineStore_AddTrolleySuplementaryInputs($cartContents);

					$saleId = StageShowLibHTTPIO::GetRequestedInt('id', 0);
					echo '<input type="hidden" name="id" value="'.$saleId.'"/>'."\n";
						
					$this->OutputContent_OnlinePurchaserDetails($cartContents);
					return;
				}
				
				$buttonID = $this->GetButtonID('savesaleedit');
				if (isset($_POST[$buttonID]))
					return;

			}
			
      		// Get all database entries for this item ... ordered by date/time then ticket type
	      	$results = $this->GetOnlineStoreProducts($atts);
			$this->OutputContent_OnlineStoreSection($results);
		}

		function OutputContent_OnlinePurchaserDetails($cartContents, $extraHTML = '')
		{
			$paramIDs = array(
				'saleEMail'     => __('EMail', $this->myDomain),
				'saleFirstName' => __('First Name', $this->myDomain),
				'saleLastName'  => __('Last Name', $this->myDomain),
				'salePPStreet'  => __('Street', $this->myDomain),
				'salePPCity'    => __('City', $this->myDomain),
				'salePPState'   => __('County', $this->myDomain),
				'salePPZip'     => __('Postcode', $this->myDomain),
				'salePPCountry' => __('Country', $this->myDomain),
				'salePPPhone'   => __('Phone', $this->myDomain),
				);
			
			$formHTML  = ''; 
			
			$formHTML .= '<div class="'.$this->cssBaseID.'-purchaserdetails">'."\n";			
			$formHTML .= "<h2>Purchaser Details:</h2>\n"; 
			$formHTML .= '<form method="post">'."\n";						
			$formHTML .= $this->GetParamAsHiddenTag('id');
			$formHTML .= "<table>\n";			

			// Output all Payment Gateway tags as edit boxes
			foreach ($paramIDs as $paramID => $paramLabel)
			{
				$paramValue = isset($cartContents->$paramID) ? $cartContents->$paramID : '';
				$formHTML .=  '
				<tr class="'.$this->cssBaseID.'-formRow">
					<td class="'.$this->cssBaseID.'-formFieldID">'.$paramLabel.':&nbsp;</td>
					<td class="'.$this->cssBaseID.'-formFieldValue" colspan="2">
						<input name="'.$paramID.'" id="'.$paramID.'" type="text" maxlength="50" size="50" value="'.$paramValue.'" />
					</td>
				</tr>
			';
			}
			
			$methodsSeparator = substr(STAGESHOWLIB_PAYMENT_METHODS, 0, 1);
			$methodsList = explode($methodsSeparator, STAGESHOWLIB_PAYMENT_METHODS);
			/* First Entry will be blank - Overwrite with the Payment Gateway name */
			$methodsList[0] = $this->myDBaseObj->gatewayObj->GetName();;
			$methodsList[] = '';
			
			$formHTML .=  '
				<tr class="'.$this->cssBaseID.'-formRow">
					<td class="'.$this->cssBaseID.'-formFieldID">'.__('Payment Method', $this->myDomain).':&nbsp;</td>
					<td class="'.$this->cssBaseID.'-formFieldValue" colspan="2">
				<select id="saleMethod" name="saleMethod">';
				
			$selectedMethod = isset($cartContents->saleMethod) ? $cartContents->saleMethod : '';
			foreach ($methodsList as $methodId)
			{
				$isSelected = ($selectedMethod == $methodId) ? 'selected=true ' : '';
				$formHTML .=  '
					<option value="'.$methodId.'" '.$isSelected.'>'.$methodId.'&nbsp;</option>';
			}
					
			$formHTML .=  '
				</select>
					</td>
				</tr>
				';
				
			if ($extraHTML == '')
			{
				$formHTML .= '
				<input type="hidden" id="saleStatus" name="saleStatus" value="'.PAYMENT_API_SALESTATUS_COMPLETED.'"/>
				';
			}
			else
			{
				$formHTML .= $extraHTML;				
			}
			
			$saveCaption = __('Save', $this->myDomain);
			$buttonID = $this->GetButtonID('savesaleedit');
			
			$buttonClassdef = ($this->adminPageActive) ? 'class="button-secondary " ' : 'class="xx" ';
			
			$formHTML .=  '
				<tr class="'.$this->cssBaseID.'-formRow">
					<td colspan="2" class="'.$this->cssBaseID.'-savesale">
						<input name="'.$buttonID.'" '.$buttonClassdef.'id="'.$buttonID.'" type="submit" value="'.$saveCaption.'" />
					</td>
				</tr>
			';
			
			$formHTML .= "</table>\n";			
			$formHTML .= "</form>\n";			
			$formHTML .= "<div>\n";			
			
			echo $formHTML;
			return $formHTML;
		}
					
		function OnlineStoreSaveEdit()
		{
			$myDBaseObj = $this->myDBaseObj;
			
			if (isset($_POST['id']))
			{
				// Get Current DB Entry
				$saleID = StageShowLibHTTPIO::GetRequestedInt('id');
				$saleEntries = $myDBaseObj->GetSale($saleID);				
			}
			else
			{
				$saleID = 0;
				$saleEntries = array();
			}
//echo "<br> -- saleID=$saleID --<br><br>";
			
			// Scan Trolley Contents
			$cartContents = $this->GetTrolleyContents();
			
			$itemsOK = true;
			foreach ($cartContents->rows as $cartIndex => $cartEntry)
			{
				$itemValid = $this->IsOnlineStoreItemValid($cartContents->rows[$cartIndex], $saleEntries);
				$itemsOK &= $itemValid;
//echo "<br>itemsOK=$itemsOK<br><br>";
			}
			
			if (!$itemsOK)
			{
				$this->SaveTrolleyContents($cartContents);
			}

			if ($itemsOK)
			{
				$runningTotal = 0;		
							
				foreach ($cartContents->rows as $cartEntry)
				{
					$runningTotal += ($cartEntry->price * $cartEntry->qty);
				}
					
				if (isset($cartContents->salePostTickets) && $cartContents->salePostTickets)
				{
					$cartContents->salePostage = $myDBaseObj->getOption('PostageFee');
					$runningTotal += $cartContents->salePostage;
				}
				else
					$cartContents->salePostage = 0;
				
				if (isset($cartContents->saleDonation))
				{
					$runningTotal += $cartContents->saleDonation;
				}
				
				if (isset($cartContents->saleTransactionFee))
				{
					$runningTotal += $cartContents->saleTransactionFee;
				}
				
				$cartContents->salePaid = $runningTotal;				
					
				if ($saleID == 0)
				{
					// Add a new Sale
					$saleDateTime = current_time('mysql'); 
									
					$cartContents->saleTxnId = 'MAN-'.time();				
					$cartContents->salePPName = $cartContents->saleFirstName.''.$cartContents->saleLastName;
					$cartContents->saleFee = 0.0;
					
					global $current_user;
					if (is_user_logged_in())
					{
						wp_get_current_user();
						$cartContents->user_login = $current_user->user_login;
					}		
					
					$saleID = $myDBaseObj->Ex_AddSale($saleDateTime, $cartContents);
				}
				else
				{
					// Calculate new sale total
					// Update Sale
					$saleID = $myDBaseObj->UpdateSale($cartContents, StageShowLibSalesCartDBaseClass::STAGESHOWLIB_FROMTROLLEY);
					$saleID = abs($saleID);		// Returned value will be negative if nothing is changed
				}
				$this->ClearTrolleyContents();
				
				// Delete Existing Tickets and Add New Ones
				$myDBaseObj->DeleteOrders($saleID);
				
				foreach ($cartContents->rows as $cartEntry)
				{
					$myDBaseObj->AddSaleFromTrolley($saleID, $cartEntry);					
				}
				//DELETE_AND_REPLACE_TICKETS = UNDEFINED_AS_YET;
			}
			else if (isset($this->checkoutMsg))
			{
				if (!isset($this->checkoutMsgClass))
				{
					$this->checkoutMsgClass = $this->cssDomain.'-error error';
				}
				echo '<div id="message" class="'.$this->checkoutMsgClass.'">'.$this->checkoutMsg.'</div>';					
				$saleID = 0;
			}
				
			return $saleID;
		}
		
		function IsOnlineStoreItemEnabled($result)
		{
			return true;
		}
		
		function IsOnlineStoreItemValid(&$cartEntry, $saleEntries)
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
			
			$reqRecordId = htmlspecialchars_decode($atts['id']);
			return ($reqRecordId == '') ? $myDBaseObj->GetPricesList(null) :  $this->GetOnlineStoreProductDetails($reqRecordId);
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
			
			$altTag = $myDBaseObj->getOption('OrganisationID').' '.__('Sales', $this->myDomain);
			$buttonURL = $myDBaseObj->getImageURL('AddCartButtonURL');

			$itemPrice = $myDBaseObj->FormatCurrency($this->GetOnlineStoreItemPrice($result));
			
			$stockDetails = isset($result->stockDetails) ? $result->stockDetails : '';
			$addColSpan = ($stockDetails != '') ? ' rowspan="2" ' : '';
			
			$storeRowHTML .= '
				<table width="100%" cellspacing="0">
					<tr>
						<td class="'.$this->cssBaseID.'-'.$this->cssColID['datetime'].'">'.$result->stockName.'</td>
						<td class="'.$this->cssBaseID.'-'.$this->cssColID['ref'].'">'.$result->stockRef.'</td>
						<td class="'.$this->cssBaseID.'-price">'.$itemPrice.'</td>
						<td class="'.$this->cssBaseID.'-qty">
				';
				
				switch ($result->stockType)
				{
					case STAGESHOWLIB_STATE_POST:
						$quantityTagId = $this->GetOnlineStoreElemTagId('quantity', $result); 
						$storeRowHTML .= '
								<select class="'.$this->cssTrolleyBaseID.'-ui" name="'.$quantityTagId.'" id="'.$quantityTagId.'">
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
						$quantityTagId = $this->GetOnlineStoreElemTagId('quantity', $result); 
						$storeRowHTML .= '<input type="hidden" name="'.$quantityTagId.'" id="'.$quantityTagId.'" value="1"/>1'."\n";
						break;
				}
				
			$buttonTag = ($buttonURL != '') ? ' src="'.$buttonURL.'"' : '';
			
			$submitButton = __('Add', $this->myDomain);
			$submitId = $this->GetOnlineStoreElemTagId('AddItemButton', $result);

			$buttonClasses = '';						
			if ($this->adminPageActive) $buttonClasses .= ' button-secondary';
				
			$buttonClassdef = $this->GetButtonTypeDef('add', $submitId, '', $buttonClasses);
			
			$storeRowHTML .= '
				<td '.$addColSpan.'class="'.$this->cssBaseID.'-add">
					<input '.$buttonClassdef.' value="'.$submitButton.'" alt="'.$altTag.'" '.$buttonTag.'/>
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
		
		function Cart_OutputContent_GetAtts( $atts )
		{
			$atts = shortcode_atts(array(
				'id'    => '',
				'count' => '',
				'anchor' => '',
				'style' => 'normal' 
			), $atts );
        
        	return $atts;
		}
		
		function Cart_OutputContent_Anchor( $anchor )
		{
			$anchor = self::ANCHOR_PREFIX.$anchor;
			echo '<a name="'.$anchor.'" id="'.$anchor.'"></a>';	
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
			if (!$this->saleConfirmationMode)
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
				
		function OutputContent_OnlineTrolleyExtras($cartContents)
		{
			return 0;
		}
				
		function OutputContent_OnlineTrolleyFooterRows($cartContents)
		{
			return;
		}
		
		function GetButtonPostID($buttonID)
		{
			return $this->GetButtonID($buttonID);
		}
				
		function GetButtonID($buttonID)
		{
			if (defined('CORONDECK_RUNASDEMO'))
			{
				$pluginID = $this->myDBaseObj->get_name();
				$buttonID .= '_'.$pluginID;
			}
			
			return $buttonID;
		}
				
		function GetButtonTypeDef($buttonID, $buttonName = '', $buttonType = '', $buttonClasses = 'button-primary')
		{
			$buttonTypeDef = '';
			
			if ($buttonType == '')
			{
				$buttonType = 'submit';
			}
			
			// Try for a payment gateway defined button ...
			$buttonImage = $this->myDBaseObj->gatewayObj->GetButtonImage($buttonID);
			if ($buttonImage != '')
			{
				$buttonType = 'image';
				$buttonTypeDef .= 'src="'.$buttonImage.'" ';
			}
			
			$buttonTypeDef .= 'type="'.$buttonType.'"';
				
			if ($buttonName == '')
			{
				$buttonName = $this->GetButtonID($buttonID);
			}

			if ($buttonType == 'image')
			{
				$buttonClasses .= ' '.$this->myDomain.'-button-image';				
			}

			$buttonClasses .= ' '.$this->cssTrolleyBaseID.'-ui';
			$buttonClasses .= ' '.$this->cssTrolleyBaseID.'-button';

			$buttonTypeDef .= ' id="'.$buttonName.'" name="'.$buttonName.'"';					
			$buttonTypeDef .= ' class="'.$buttonClasses.'"';					

			if (!$this->adminPageActive)
			{
				$onClickHandler = $this->myJSRoot.'_OnClick'.ucwords($buttonID);
				$buttonTypeDef .= ' onClick="return '.$onClickHandler.'(this, '.$this->shortcodeCount.')"';				
			}
			
			return $buttonTypeDef;
		}
				
		function OutputContent_OnlineRemoveButton($cartIndex, $removeLinkContent='')
		{
			$buttonName = 'RemoveItemButton'.'_'.$cartIndex;
			$buttonType = $this->GetButtonTypeDef('remove', $buttonName, '', 'button-secondary');

			echo '<input '."$buttonType $removeLinkContent".' value="'.__('Remove', $this->myDomain).'"/>'."\n";
		}
		
		function OutputContent_OnlineCheckoutButton($cartContents)
		{
			if ($this->adminPageActive)
			{
				echo '<input class="'.$this->cssBaseID.'-button button-primary" type="submit" name="'.$this->GetButtonID('editbuyer').'" value="'.__('Next', $this->myDomain).'"/>'."\n";
				return '';
			}
			
			$checkoutSelector = $this->myDBaseObj->gatewayObj->GetCheckoutType();
			$showCheckoutButton = $checkoutSelector == StageShowLibGatewayBaseClass::STAGESHOWLIB_CHECKOUTSTYLE_STANDARD;
			
			$gatewayParent = $this->myDBaseObj->gatewayObj->GetParent();
			if ($gatewayParent == 'paypal')
			{
				$secure_connection = !empty($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] != 'off');
				
				if ( (!$secure_connection) 
				  && ($checkoutSelector != StageShowLibGatewayBaseClass::STAGESHOWLIB_CHECKOUTSTYLE_STANDARD)
				  && (!current_user_can(STAGESHOWLIB_CAPABILITY_ADMINUSER)) )
				{
					// PayPal Express is only allowed on secure connections
					$checkoutSelector = StageShowLibGatewayBaseClass::STAGESHOWLIB_CHECKOUTSTYLE_STANDARD;
					echo "\n<!-- ******* PayPal Express Disabled: Not on secure connection ******* -->\n";
				}
				$showCheckoutButton = ($checkoutSelector != StageShowLib_paypal_exp_GatewayClass::STAGESHOWLIB_CHECKOUTSTYLE_EXPRESS);				
			}
			
			if ($showCheckoutButton)
			{
				if (count($this->myDBaseObj->gatewayObj->Gateway_ClientFields()) > 0)
					$buttonType = $this->GetButtonTypeDef('checkoutdetails');
				else
					$buttonType = $this->GetButtonTypeDef('checkout');
				echo '<input '.$buttonType.' value="'.__('Checkout', $this->myDomain).'"/>'."\n";
			}
			
			return $checkoutSelector;
		}
		
		function GetTrolleyDefaults()
		{
			$cartDefaults = new stdClass;
			$cartDefaults->nextIndex = 1;
			$cartDefaults->saleDonation = '';
			$cartDefaults->saleNoteToSeller = '';
			$cartDefaults->salePostTickets = false;
			$cartDefaults->timestamp = 0;
			
			return $cartDefaults;
		}
		
		function GetTrolleyContents()
		{
			$clearTrolley = true;
			$timestampNow = time();
			if (isset($_SESSION[$this->trolleyid]))
			{
				$cartContents = unserialize($_SESSION[$this->trolleyid]);
				if ($timestampNow - $cartContents->timestamp <= STAGESHOWLIB_TROLLEYTIMEOUT)
				{
					$clearTrolley = false;
				}
			}
			
			if ($clearTrolley)
			{
				$cartContents = $this->GetTrolleyDefaults();
			}
			
			if ($this->myDBaseObj->dev_ShowTrolley())
			{
				if ($clearTrolley)
				{
					echo "Trolley Cleared - (Timeout or Missing)!<br>";
				}
				StageShowLibUtilsClass::print_r($cartContents, 'Get cartContents ('.$this->trolleyid.')');
			}
			
			$cartContents->timestamp = $timestampNow;

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
			
			$cartContents->timestamp = time();
		}
		
		function SaveTrolleyContents($cartContents)
		{
			$cartContents->timestamp = time();
			if ($this->myDBaseObj->dev_ShowTrolley())
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
					
			if (defined('CORONDECK_RUNASDEMO'))
			{
				$this->trolleyid = $this->myDBaseObj->get_name().'_saleedit_';
				$this->trolleyid .= '_'.$this->myDBaseObj->loginID;
			}
			
			$this->trolleyid .= ($id != '') ? $id : 'new';
		}
		
		function ClearTrolleyContents()
		{
			if ($this->myDBaseObj->dev_ShowTrolley())
			{
				echo 'CLEAR cartContents ('.$this->trolleyid.") <br>\n";
			}
			
			unset($_SESSION[$this->trolleyid]);
		}
		
		function Cart_OnlineStore_GetCheckoutDetails()
		{
			$myDBaseObj = $this->myDBaseObj;
			
			$buttonID = $this->GetButtonPostID('checkoutdetails');
			if (!isset($_POST[$buttonID]))	// Get checkout details from user
				return '';
			
			// Get the list of fields for user to add
			$userFieldsList = $myDBaseObj->gatewayObj->Gateway_ClientFields();				
			if (count($userFieldsList) == 0)
				return '';
				
			if (isset($_POST['checkout-submit']))
			{
				return '';
			}
			
			$html = '';
/*			
			// If Fields Defined 
			$classId = $myPluginObj->adminClassPrefix.'SalesAdminListClass';
			$salesList = new $classId(null, null);
			
			$rowsDefs = $myDBaseObj->gatewayObj->Gateway_SettingsRowsDefinition();
StageShowLibUtilsClass::print_r($rowsDefs, '$rowsDefs');
*/				
			// TODO: Pass trolley details as parameters
			$cartContents = $this->GetTrolleyContents();
			$paramCount = 0;
			if (isset($cartContents->rows))
			{
				foreach ($cartContents->rows as $cartIndex => $cartEntry)
				{				
					$paramCount++;
					$itemID = $cartEntry->itemID;
					$qty = $cartEntry->qty;
					
					$html .= '
						<input type="hidden" id="id'.$cartIndex.'" name="id'.$cartIndex.'" value="'.$itemID.'" />
						<input type="hidden" id="qty'.$cartIndex.'" name="qty'.$cartIndex.'" value="'.$qty.'" />';
				}				
			}
					
			$this->Cart_OutputContent_Anchor("checkoutdetails");
					
			$detailsCSSBase = $this->cssDomain.'-checkoutdetails';
			
			echo '<div class="'.$this->cssTrolleyBaseID.'-header"><h2>'.__('Your Contact Details', $this->myDomain)."</h2></div>\n";

			$missingMessage = __('must be entered', $this->myDomain);
			
			$html .= '
<script>
function StageShowLib_JS_OnClickSubmitDetails(obj)
{
	var divElem = document.getElementById("'.$this->cssDomain.'-checkoutdetails");
	var inputElems = divElem.getElementsByTagName("input"); 
	for (var i = 0; i < inputElems.length; i++) 
	{ 
		var tagValue = inputElems[i].value;	
		if (tagValue.length == 0)
		{
			/* Set focus to this Element */
			var inputElem = inputElems[i];
			inputElem.focus();
			inputElem.scrollIntoView(false);
			
			/* Get Label for this input field */
			var inputElem = inputElems[i];
			var inputName = inputElem.name;
			var labelElem = document.getElementById(inputName + "-label");
			
			/* Create Error Message */
			var ErrMsg = labelElem.innerHTML + " '.$missingMessage.'";
			alert(ErrMsg);
			
			/* Block Checkout */
			return false;
		}
	}
	
	return true;
}
</script>
			';
			
			$html .= '
				<div id="'.$detailsCSSBase.'" class="'.$detailsCSSBase.'">
				<table class="'.$detailsCSSBase.'-table">';
				
			foreach ($userFieldsList as $userField => $userLabel)
			{
				$elemId = 'checkoutdetails-'.$userField;
				
				$html .= '
					<tr class="'.$detailsCSSBase.'-row '.$detailsCSSBase.'-row-'.$userField.'">
					<td class="'.$detailsCSSBase.'-label" id="'.$elemId.'-label">'.$userLabel.'</td>
					<td class="'.$detailsCSSBase.'-value"><input type=text id="'.$elemId.'" name = "'.$elemId.'" /></td>
					</tr>';							
				
			}
			$html .= '
				</table>
				</div>';
			
			$buttonType = $this->GetButtonTypeDef('checkout');
			$buttonType = str_replace('_OnClickCheckout', '_OnClickSubmitDetails', $buttonType);
			$html .= '<input '.$buttonType.' value="'.__('Checkout', $this->myDomain).'"/>'."\n";
echo $html;
			return $html;	
		}
				
		function Cart_OnlineStore_HandleTrolley()
		{
			// Only Allow One Shopping Trolley (If there are multiple shortcodes on one page)
			if (isset($this->DoneSalesTrolley))
				return;
			$this->DoneSalesTrolley = true;
				
			$myDBaseObj = $this->myDBaseObj;

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
		
		function OnlineStore_EMailSaleButton($saleDetails)
		{
			$targetFile = STAGESHOWLIB_SENDEMAIL_TARGET;
			$ourNOnce = StageShowLibNonce::GetStageShowLibNonce($targetFile);

			$emailSaleButtonClick = 'stageshowlib_manualsale_email_click'.$saleDetails->saleID;
			
			$jQueryURL = STAGESHOWLIB_URL."include/".$targetFile;
			$jQueryParams = "this, '".$ourNOnce."','".$jQueryURL."'";	
			echo '
				&nbsp;&nbsp;<input type="button" class="'.$this->cssDomain.'-trolley-ui button-secondary" name="EMailSale" id="EMailSale" value="'.__('EMail Sale', $this->myDBaseObj->get_domain()).'" onclick="'.$emailSaleButtonClick.'()" />
				&nbsp;&nbsp;<span class="'.$this->cssDomain.'-sendemail-status" name="'.$this->cssDomain.'-sendemail-status" id="'.$this->cssDomain.'-sendemail-status"></span>
<script>
function '.$emailSaleButtonClick.'()
{
	/* Set Cursor to Busy and Disable All UI Buttons */
	StageShowLib_SetBusy(true, "'.$this->cssDomain.'-trolley-ui");
	
	/* Implement Manual Sale EMail */
	var postvars = {
		saleTxnId: "'.$saleDetails->saleTxnId.'",	
		saleID: "'.$saleDetails->saleID.'",
		saleEMail: "'.$saleDetails->saleEMail.'",
		nonce: "'.$ourNOnce.'",
		jquery: "true"
	};
	
	/* Get New HTML from Server */
	var url = "'.$jQueryURL.'";
    jQuery.post(url, postvars,
	    function(data, status)
	    {
	    	if (status != "success") data = "JQuery Error: " + status;
	    	
			divElem = jQuery("#'.$this->cssDomain.'-sendemail-status");
			divElem.html(data);
			
			/* Set Cursor to Normal and Enable All UI Buttons */
			StageShowLib_SetBusy(false, "'.$this->cssDomain.'-trolley-ui");
	    }
    );
    
    return false;
}

</script>    
			';
		}
		
		function OnlineStore_AddTrolleySuplementaryInputs(&$cartContents)
		{
			if (isset($_POST['saleDonation']))
			{
				$cartContents->saleDonation = StageShowLibHTTPIO::GetRequestedCurrency('saleDonation', false);
			}	

			if (isset($_POST['saleNoteToSeller']))
			{
				$cartContents->saleNoteToSeller = StageShowLibDBaseClass::GetSafeString('saleNoteToSeller', '');
			}
			
			$cartContents->salePostTickets = isset($_POST['salePostTickets']);
			$this->SaveTrolleyContents($cartContents);
		}
		
		function OnlineStore_HandleTrolleyButtons($cartContents)
		{
			$myDBaseObj = $this->myDBaseObj;
			
			if (isset($_GET['action']) && isset($_REQUEST['editpage']))
			{
				if ($_GET['action'] == 'editsale')
				{
					$buttonID = $this->GetButtonID('editbuyer');
					if (isset($_POST[$buttonID])) 
					{
						$this->cart_ReadOnly = true;
						$this->OnlineStore_AddTrolleySuplementaryInputs($cartContents);	
					}
					
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
						$cartContents->saleMethod    = $_POST['saleMethod'];	
																
						$cartContents->saleNoteToSeller = isset($_POST['saleNoteToSeller']) ? stripslashes($_POST['saleNoteToSeller']) : '';	
						
						$saleEMail = $cartContents->saleEMail;
																
						$this->SaveTrolleyContents($cartContents);
						
						$saleID = $this->OnlineStoreSaveEdit();
						
						if ($saleID > 0)
						{
							echo '
							<div id="message" class="updated">
							<p>'.__('Sale Details have been saved', $this->myDomain);
							$salesList = $myDBaseObj->GetSale($saleID);
							if (count($salesList) > 0)
							{
								$saleDetails = $salesList[0];							
								$myDBaseObj->OutputViewTicketButton($saleID, $saleEMail);
								$this->OnlineStore_EMailSaleButton($saleDetails);
							}
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
				if (count($postIdElems) < 2) 
					continue;
					
				$reqId = $postIdElems[1];
				if (!is_numeric($reqId)) 
					continue;
					
				if ($postIdElems[0] == 'AddItemButton') 
				{
					$itemID = $reqId;
				}
				if ($postIdElems[0] == 'RemoveItemButton') 
				{
					$_GET['remove'] = $reqId;
				}
			}

			if ($itemID > 0)
			{
				// Get the product ID from posted data
				$reqQty = StageShowLibHTTPIO::GetRequestedInt('quantity_'.$itemID);

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

					$this->OnlineStore_AddTrolleySuplementaryInputs($cartContents);
					$this->SaveTrolleyContents($cartContents);
				}
			}
				
			if (!isset($cartContents->rows))
				return false;
				
			if (count($cartContents->rows) == 0)
				return false;
				
			if (isset($_POST['saleDonation']))
			{
				$cartContents->saleDonation = StageShowLibHTTPIO::GetRequestedCurrency('saleDonation', false);
				$this->SaveTrolleyContents($cartContents);
			}
			
			if (isset($_GET['remove']))
			{
				$itemID = $_GET['remove'];
				unset($cartContents->rows[$itemID]);
				if (count($cartContents->rows) == 0)
				{
					$cartContents->saleTransactionFee = $myDBaseObj->FormatCurrency(0);
					$cartContents->saleDonation = '';
				}
				
				$this->OnlineStore_AddTrolleySuplementaryInputs($cartContents);
			}
				
			$doneHeader = $this->OnlineStore_OutputTrolley($cartContents);
			return $doneHeader;			
		}
		
		function OnlineStore_OutputTrolley($cartContents)
		{
			$myDBaseObj = $this->myDBaseObj;
	
			$doneHeader = false;
			$runningTotal = 0;		
			$hiddenTags  = "\n";
					
			$this->saleConfirmationMode = isset($cartContents->confirmSaleMode);				
			
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
					if ($myDBaseObj->isSysAdmin())
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
					if ($this->saleConfirmationMode)
						$trolleyHeading = __('Your Order Details', $this->myDomain);
					else if ($this->adminPageActive)
						$trolleyHeading = __('Sale Items', $this->myDomain);
					else
						$trolleyHeading = __('Your Shopping Trolley', $this->myDomain);
						
					$this->Cart_OutputContent_Anchor("trolley");
					
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
					
				if (!$this->saleConfirmationMode)
				{
					echo '<td class="'.$this->cssTrolleyBaseID.'-remove">'."\n";
					if (!isset($this->cart_ReadOnly))
					{
						$removeLinkContent = $this->OutputContent_OnlineRemoveButton($cartIndex);
					}
					else
					{
						$removeLinkContent = '&nbsp;';
					}	
					echo $removeLinkContent.'</td>'."\n";
					
					echo "</tr>\n";					
				}
					
				$hiddenTags .= '<input type="hidden" name="id'.$cartIndex.'"  id="id'.$cartIndex.'" value="'.$itemID.'"/>'."\n";
				$hiddenTags .= '<input type="hidden" name="qty'.$cartIndex.'" id="qty'.$cartIndex.'" value="'.$qty.'"/>'."\n";
			}
			
			if ($doneHeader)
			{	
				$runningTotal += $this->OutputContent_OnlineTrolleyFee($cartContents);
				$trolleyTotal = $runningTotal + $this->OutputContent_OnlineTrolleyExtras($cartContents);

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
				if (!$this->saleConfirmationMode)
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
							$noteToSeller = stripslashes($_POST['saleNoteToSeller']);
						else if (isset($cartContents->saleNoteToSeller))
							$noteToSeller = $cartContents->saleNoteToSeller;
						else
							$noteToSeller = '';

						$noteCols = $this->trolleyHeaderCols-1;
						$rowsDef = defined('STAGESHOWLIB_NOTETOSELLER_ROWS') ? "rows=".STAGESHOWLIB_NOTETOSELLER_ROWS." " : "";
						
						echo '
							<tr class="'.$this->cssDomain.'-trolley-notetoseller">
							<td>'.__('Message To Seller', $this->myDomain).'</td>
							<td colspan="'.$noteCols.'">
							<textarea class="'.$this->cssDomain.'-trolley-ui" name="saleNoteToSeller" id="saleNoteToSeller" '.$rowsDef.'>'.$noteToSeller.'</textarea>
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
				
					$this->OutputContent_OnlineTrolleyFooterRows($cartContents);
				}
				
				if ( ($checkoutNotePosn == 'below') && ($checkoutNote != '') )
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

		function GetParamAsHiddenTag($paramId)
		{
			if (isset($_GET[$paramId]))	
			{
				$paramValue = $_GET[$paramId];
			}
			else if (isset($_POST[$paramId]))	
			{
				$paramValue = $_POST[$paramId];	// TODO: Check for SQLi
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