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

if (!class_exists('StageShowWPOrgSalesCartPluginClass')) 
	include STAGESHOW_INCLUDE_PATH.'stageshow_trolley_sales.php';
	
include 'stageshowlib_salesplugin.php';
	
if (!class_exists('StageShowWPOrgSalesPluginClass')) 
{
	class StageShowWPOrgSalesPluginClass extends StageShowWPOrgSalesCartPluginClass // Define class
	{
		function __construct()
		{
			$this->cssBaseID = "stageshow-boxoffice";
		
			if (defined('STAGESHOW_SHORTCODE'))
			{
				$this->shortcode = STAGESHOW_SHORTCODE;
			}
			elseif (defined('CORONDECK_RUNASDEMO'))
			{
				$this->shortcode = str_replace('stage', 's', STAGESHOW_DIR_NAME).'-boxoffice';
			}
			else
			{
				$this->shortcode = STAGESHOW_SHORTCODE_PREFIX."-boxoffice";
			}
			
			parent::__construct();
		}
	
		function OutputContent_GetAtts( $atts )
		{
			$atts = shortcode_atts(array(
				'id'    => '',
				'perf'  => '',
				'count' => '',
				'anchor' => '',
				'style' => 'normal' 
			), $atts );
        
        	return $atts;
		}
		
		function OutputContent_DoShortcode($atts)
		{
			if ($this->pageMode == self::PAGEMODE_DEMOSALE)
			{
				include STAGESHOW_INCLUDE_PATH.'stageshow_gatewaysimulator.php';
				
				ob_start();
				new StageShowGatewaySimulator(STAGESHOWLIB_DBASE_CLASS, $this->demosale);
				$simulatorOutput = ob_get_contents();
				ob_end_clean();

				return $simulatorOutput;
			}
			
			if (isset($_POST['SUBMIT_simulateGateway']))
			{
				// Save Form values for next time
				$paramIDs = $_POST['paramIDs'];	// TODO: Check for SQLi
				$paramsList = explode(',', $paramIDs);
				foreach ($paramsList as $tagName)
				{
					$paramVal = $_POST[$tagName];						// TODO: Check for SQLi
					$sessionVar = 'StageShowSim_'.$tagName;
					$_SESSION[$sessionVar] = $paramVal;
				}
				$this->myDBaseObj->saveOptions();
				
				ob_start();		// "Soak up" any output
				$gatewayType = $this->myDBaseObj->gatewayObj->GetType();
				$callbackFile = STAGESHOW_INCLUDE_PATH.'stageshowlib_'.$gatewayType.'_callback.php';
				include $callbackFile;
				$simulatorOutput = ob_get_contents();
				//echo $simulatorOutput;
				ob_end_clean();
				$saleStatus = 'DEMO MODE: Sale Completed';
				echo '<div id="message" class="stageshow-ok ok">'.$saleStatus.'</div>';
			}
			
			return parent::OutputContent_DoShortcode($atts);
		}
	
		function OutputContent_OnlineStoreFooter()
		{
			if ($this->adminPageActive)
				return;
				
			$url = $this->myDBaseObj ->get_pluginURI();
			$name = $this->myDBaseObj ->get_pluginName();
			$weblink = __('Driven by').' <a target="_blank" href="'.$url.'">'.$name.'</a>';
			echo '<div class="stageshow-boxoffice-weblink">'.$weblink.'</div>'."\n";
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
		
		
		
		function OnlineStore_AddExtraPayment(&$rslt, $amount, $name, $detailID)
		{
			if (($rslt->totalDue > 0) && ($amount > 0))
			{
				$rslt->totalDue += $amount;
				
				$this->myDBaseObj->gatewayObj->AddItem($name, $amount, 1, 0);
				
				$rslt->saleDetails[$detailID] = $amount;			
			}
			else
			{
				$rslt->saleDetails[$detailID] = 0;				
			}	
		}
		
	}
}

?>
