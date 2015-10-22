<?php
/* 
Description: StageShow Plugin Sample Database functions
 
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

include 'stageshowlib_sample_dbase.php';

if (!class_exists('StageShowWPOrgSampleDBaseClass')) 
{
	define('STAGESHOW_PRICE_S1_P1_ALL', '12.50');
	define('STAGESHOW_PRICE_S1_P2_ADULT', '5.50');
	define('STAGESHOW_PRICE_S1_P3_ADULT', '4.00');
	define('STAGESHOW_PRICE_S1_P4_ALL', '6.00');
	define('STAGESHOW_PRICE_S1_P2_CHILD', '3.00');
	define('STAGESHOW_PRICE_S1_P3_CHILD', '2.00');
	
	class StageShowWPOrgSampleDBaseClass extends StageShowLibSampleDBaseClass // Define class
  	{
		function CreateSample($sampleDepth = 0)
		{
			// FUNCTIONALITY: DBase - StageShow - Implement "Create Sample"
			$showName1 = "The Wordpress Show";

			// Sample dates to reflect current date/time
			$showTime1 = date(StageShowWPOrgDBaseClass::STAGESHOW_DATE_FORMAT, $this->Sample_strtotime("+28 days"))." 20:00";
			$showTime2 = date(StageShowWPOrgDBaseClass::STAGESHOW_DATE_FORMAT, $this->Sample_strtotime("+29 days"))." 20:00";
			$showTime3 = date(StageShowWPOrgDBaseClass::STAGESHOW_DATE_FORMAT, $this->Sample_strtotime("+30 days"))." 14:30";
			$showTime4 = date(StageShowWPOrgDBaseClass::STAGESHOW_DATE_FORMAT, $this->Sample_strtotime("+30 days"))." 20:00";
			// Populate table
			$this->sample_showID1 = $this->AddSampleShow($showName1);
			$statusMsg = '';
			// Populate performances table	  
			$perfCount = 4;
			if (defined('STAGESHOW_SAMPLE_PERFORMANCES_COUNT'))
				$perfCount = STAGESHOW_SAMPLE_PERFORMANCES_COUNT;
			$perfID1 = $perfCount >= 1 ? $this->AddSamplePerformance($statusMsg, $this->sample_showID1, $showTime1, "Day1Eve", 80) : -1;
			$perfID2 = $perfCount >= 2 ? $this->AddSamplePerformance($statusMsg, $this->sample_showID1, $showTime2, "Day2Eve", 60) : -1;
			$perfID3 = $perfCount >= 3 ? $this->AddSamplePerformance($statusMsg, $this->sample_showID1, $showTime3, "Day3Mat", 80) : -1;
			$perfID4 = $perfCount >= 4 ? $this->AddSamplePerformance($statusMsg, $this->sample_showID1, $showTime4, "Day3Eve", 60) : -1;
			if (($perfID1 == 0) ||($perfID2 == 0) || ($perfID3 == 0) || ($perfID4 == 0))
			{
				echo '<div id="message" class="error"><p>'.__('Cannot Add Performances', $this->get_domain()).' - '.$statusMsg.'</p></div>';
				return;
			}
			
			if ($sampleDepth < 2)
			{
				// Populate prices table
				$this->priceID_S1_P1_ALL   = $this->AddSamplePrice('Day1Eve', 'All',   STAGESHOW_PRICE_S1_P1_ALL);
				$this->priceID_S1_P2_ADULT = $this->AddSamplePrice('Day2Eve', 'Adult', STAGESHOW_PRICE_S1_P2_ADULT);
				$this->priceID_S1_P3_ADULT = $this->AddSamplePrice('Day3Mat', 'Adult', STAGESHOW_PRICE_S1_P3_ADULT);
				$this->priceID_S1_P4_ALL   = $this->AddSamplePrice('Day3Eve', 'All',   STAGESHOW_PRICE_S1_P4_ALL);
				$this->priceID_S1_P2_CHILD = $this->AddSamplePrice('Day2Eve', 'Child', STAGESHOW_PRICE_S1_P2_CHILD);
				$this->priceID_S1_P3_CHILD = $this->AddSamplePrice('Day3Mat', 'Child', STAGESHOW_PRICE_S1_P3_CHILD);
			}
			
			if (!$this->isDbgOptionSet('Dev_NoSampleSales') && ($sampleDepth < 1))
			{
				// Add some ticket sales
				$saleTime1 = date(StageShowWPOrgDBaseClass::STAGESHOW_DATE_FORMAT, $this->Sample_strtotime("-4 days"))." 17:32:47";
				$saleTime2 = date(StageShowWPOrgDBaseClass::STAGESHOW_DATE_FORMAT, $this->Sample_strtotime("-3 days"))." 10:14:51";
				$saleEMail = 'other@someemail.co.zz';
				if (defined('STAGESHOW_SAMPLE_EMAIL'))
					$saleEMail = STAGESHOW_SAMPLE_EMAIL;
				$saleID = $this->AddSampleSale($saleTime1, 'A.N.', 'Other', $saleEMail, 12.00, 'SQP4KMTNIEXGS5ZBU', PAYMENT_API_SALESTATUS_COMPLETED,
					'1 The Street', 'Somewhere', 'Bigshire', 'BG1 5AT', 'UK');
				$this->AddSampleSaleItem($saleID, $this->priceID_S1_P3_CHILD, 4, STAGESHOW_PRICE_S1_P3_CHILD);
				$this->AddSampleSaleItem($saleID, $this->priceID_S1_P3_ADULT, 1, STAGESHOW_PRICE_S1_P3_ADULT);
				
				$saleEMail = 'mybrother@someemail.co.zz';
				if (defined('STAGESHOW_SAMPLE_EMAIL'))
					$saleEMail = STAGESHOW_SAMPLE_EMAIL;
				$total2 = (4 * STAGESHOW_PRICE_S1_P1_ALL);
				$saleID = $this->AddSampleSale($saleTime2, 'M.Y.', 'Brother', $saleEMail, $total2, '1S34QJHTK9AAQGGVG', PAYMENT_API_SALESTATUS_COMPLETED,
					'The Bungalow', 'Otherplace', 'Littleshire', 'LI1 9ZZ', 'UK');
				$this->AddSampleSaleItem($saleID, $this->priceID_S1_P1_ALL, 4, STAGESHOW_PRICE_S1_P1_ALL);
				
				$timeStamp = current_time('timestamp');
				if (defined('STAGESHOW_EXTRA_SAMPLE_SALES'))
				{
					// Add a lot of ticket sales
					for ($sampleSaleNo = 1; $sampleSaleNo<=STAGESHOW_EXTRA_SAMPLE_SALES; $sampleSaleNo++)
					{
						$saleDate = date(self::MYSQL_DATETIME_FORMAT, $timeStamp);
						$saleFirstName = 'Sample'.$sampleSaleNo;
						$saleLastName = 'Buyer'.$sampleSaleNo;
						$saleEMail = 'extrasale'.$sampleSaleNo.'@sample.org.uk';
						$saleID = $this->AddSampleSale($saleDate, $saleFirstName, $saleLastName, $saleEMail, 12.50, 'TXNID_'.$sampleSaleNo, PAYMENT_API_SALESTATUS_COMPLETED,
						'Almost', 'Anywhere', 'Very Rural', 'Tinyshire', 'TN55 8XX', 'UK');
						$this->AddSampleSaleItem($saleID, $this->priceID_S1_P3_ADULT, 3, STAGESHOW_PRICE_S1_P3_ADULT);
						$timeStamp = $this->Sample_strtotime("+1 hour +7 seconds", $timeStamp);
					}
				}
			}
		}
		
		function isDbgOptionSet($optionID)
		{
			return $this->myDBaseObj->isDbgOptionSet($optionID);
		}
       
		function saveOptions()
		{
			return $this->myDBaseObj->saveOptions();
		}
		
		function Sample_strtotime($time)
		{ 
			if (defined('STAGESHOW_SAMPLE_BASETIME'))
			{
				$now = strtotime(STAGESHOW_SAMPLE_BASETIME);				
			}
			else
			{
				$now = time();				
			}
			return strtotime($time, $now);
		}
		
		function AddSampleShow($showName, $showState = STAGESHOW_STATE_ACTIVE)
		{
			return $this->myDBaseObj->AddShow($showName, $showState);
		}
		
		function AddSamplePerformance(&$rtnMsg, $showID, $perfDateTime, $perfRef = '', $perfSeats = -1)
		{
			$perfID = $this->myDBaseObj->CreateNewPerformance($rtnMsg, $showID, $perfDateTime, $perfRef, $perfSeats);
			
			$this->perfIDs[$perfRef] = $perfID;
			
			return $perfID;
		}
		
		function AddSamplePrice($perfRef, $priceType, $priceValue = STAGESHOW_PRICE_UNKNOWN, $visibility = STAGESHOW_VISIBILITY_PUBLIC)
		{
			if (defined('STAGESHOW_SAMPLEPRICE_DIVIDER'))
			{
				$priceValue = $priceValue/STAGESHOW_SAMPLEPRICE_DIVIDER;
				$priceValue = number_format($priceValue, 2);
			}
			$perfID = $this->perfIDs[$perfRef];
			$priceID = $this->myDBaseObj->AddPrice($perfID, $priceType, $priceValue, $visibility);
			
			return $priceID;
		}
		
		// Add Sale - Address details are optional
		function AddSampleSale($saleDateTime, $saleFirstName, $saleLastName, $saleEMail, $salePaid, $saleTxnId, $saleStatus, $salePPStreet, $salePPCity, $salePPState, $salePPZip, $salePPCountry, $salePPPhone = '')
		{
			$salePaid += $this->myDBaseObj->GetTransactionFee();
			if (defined('STAGESHOW_SAMPLEPRICE_DIVIDER'))
			{
				$salePaid = $salePaid/STAGESHOW_SAMPLEPRICE_DIVIDER;
			}
			$salePaid = number_format($salePaid, 2);
			
			return parent::AddSampleSale($saleDateTime, $saleFirstName, $saleLastName, $saleEMail, $salePaid, $saleTxnId, $saleStatus, $salePPStreet, $salePPCity, $salePPState, $salePPZip, $salePPCountry, $salePPPhone);
		}
				
		function AddSampleSaleItem($saleID, $stockID, $qty, $paid, $saleExtras = array())
		{
			if (defined('STAGESHOW_SAMPLEPRICE_DIVIDER'))
			{
				$paid = $paid/STAGESHOW_SAMPLEPRICE_DIVIDER;
				$paid = number_format($paid, 2);
			}
			
			return $this->myDBaseObj->AddSaleItem($saleID, $stockID, $qty, $paid, $saleExtras);
		}
		
	}
}

?>