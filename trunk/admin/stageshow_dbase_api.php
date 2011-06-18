<?php
/* 
Description: StageShow Plugin Database Access functions
 
Copyright 2011 Malcolm Shergold

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

if (!class_exists('StageShowDBaseClass')) {
	// Set the DB tables names
	global $wpdb;

	define('STAGESHOW_TABLE_PREFIX', $wpdb->prefix.STAGESHOW_CODE_PREFIX.'_');
	define('STAGESHOW_PERFORMANCES_TABLE', STAGESHOW_TABLE_PREFIX.'perfs');
	define('STAGESHOW_PRICES_TABLE', STAGESHOW_TABLE_PREFIX.'prices');
	define('STAGESHOW_SALES_TABLE', STAGESHOW_TABLE_PREFIX.'sales');
	define('STAGESHOW_TICKETS_TABLE', STAGESHOW_TABLE_PREFIX.'tickets');

	define('STAGESHOW_DEFAULT_PAYPALLOGOIMAGE_URL', STAGESHOW_URL.'/images/StageShowLogo.jpg');
	define('STAGESHOW_DEFAULT_PAYPALHEADERIMAGE_URL', STAGESHOW_URL.'/images/StageShowHeader.gif');
	
	define('STAGESHOW_DATE_FORMAT', 'Y-m-d');
	define('STAGESHOW_DATETIME_MYSQL_FORMAT', 'Y-m-d H:i:s');
	define('STAGESHOW_DATETIME_TEXTLEN', 19);
	
	define('STAGESHOW_SHOWNAME_TEXTLEN', 80);
	define('STAGESHOW_PERFREF_TEXTLEN', 16);
	define('STAGESHOW_PRICETYPE_TEXTLEN', 32);
	define('STAGESHOW_PRICEREF_TEXTLEN', 16);
	define('STAGESHOW_TICKETNAME_TEXTLEN', 110);
	define('STAGESHOW_TICKETTYPE_TEXTLEN', 32);
	define('STAGESHOW_TICKETSEAT_TEXTLEN', 10);
		
	define('STAGESHOW_PPLOGIN_USER_TEXTLEN', 127);
	define('STAGESHOW_PPLOGIN_PWD_TEXTLEN', 65);
	define('STAGESHOW_PPLOGIN_SIG_TEXTLEN', 65);
	define('STAGESHOW_PPLOGIN_EMAIL_TEXTLEN', 65);
	
	define('STAGESHOW_PPLOGIN_EDITLEN', 70);
	
	define('STAGESHOW_PPSALENAME_TEXTLEN',128);
	define('STAGESHOW_PPSALEEMAIL_TEXTLEN',127);
	define('STAGESHOW_PPSALEPPNAME_TEXTLEN',128);
	define('STAGESHOW_PPSALEPPSTREET_TEXTLEN',200);
	define('STAGESHOW_PPSALEPPCITY_TEXTLEN',40);
	define('STAGESHOW_PPSALEPPSTATE_TEXTLEN',40);
	define('STAGESHOW_PPSALEPPZIP_TEXTLEN',20);
	define('STAGESHOW_PPSALEPPCOUNTRY_TEXTLEN',64);
	define('STAGESHOW_PPSALETXNID_TEXTLEN',20);
	define('STAGESHOW_PPSALESTATUS_TEXTLEN',20);

	define('STAGESHOW_PPBUTTINID_TEXTLEN',16);
	define('STAGESHOW_ACTIVESTATE_TEXTLEN',10);

	define('STAGESHOW_TICKETNAME_DIVIDER', ' - ');
	
  class StageShowDBaseClass
  {
		var $ForReading = 1;
		var $ForWriting = 2;
		var $ForAppending = 8;

		var $adminOptions;
    
		function StageShowDBaseClass() { //constructor		
			$this->getOptions();
		}

    function activate() {
      global $wpdb;
      
			$table_name = STAGESHOW_PERFORMANCES_TABLE;
			//if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name)
			{
				$sql = "CREATE TABLE ".$table_name.' ( 
					perfID INT UNSIGNED NOT NULL AUTO_INCREMENT,
					showID INT UNSIGNED NOT NULL,
					perfState VARCHAR('.STAGESHOW_ACTIVESTATE_TEXTLEN.'),
					perfDateTime DATETIME NOT NULL,
					perfRef VARCHAR('.STAGESHOW_PERFREF_TEXTLEN.') NOT NULL,
					perfSeats INT NOT NULL,
					perfPayPalTESTButtonID VARCHAR('.STAGESHOW_PPBUTTINID_TEXTLEN.'), 
					perfPayPalLIVEButtonID VARCHAR('.STAGESHOW_PPBUTTINID_TEXTLEN.'),
					perfOpens DATETIME,
					perfExpires DATETIME,
					UNIQUE KEY perfID (perfID)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;';

				//excecute the query
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($sql);
			}

			$table_name = STAGESHOW_PRICES_TABLE;
			//if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name)
			{
				$sql = "CREATE TABLE ".$table_name.' ( 
					priceID INT UNSIGNED NOT NULL AUTO_INCREMENT,
					perfID INT UNSIGNED NOT NULL,
					priceType VARCHAR('.STAGESHOW_PRICETYPE_TEXTLEN.') NOT NULL,
					priceRef VARCHAR('.STAGESHOW_PRICEREF_TEXTLEN.'),
					priceValue DECIMAL(9,2) NOT NULL,
					UNIQUE KEY priceID (priceID)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;';

				//excecute the query
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($sql);
			}

			$table_name = STAGESHOW_SALES_TABLE;
			//if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name)
			{
				$sql = "CREATE TABLE ".$table_name.' ( 
					saleID INT UNSIGNED NOT NULL AUTO_INCREMENT,
					saleDateTime DATETIME NOT NULL,
					saleName VARCHAR('.STAGESHOW_PPSALENAME_TEXTLEN.') NOT NULL,
					saleEMail VARCHAR('.STAGESHOW_PPSALEEMAIL_TEXTLEN.') NOT NULL,
					salePPName VARCHAR('.STAGESHOW_PPSALEPPNAME_TEXTLEN.'),
					salePPStreet VARCHAR('.STAGESHOW_PPSALEPPSTREET_TEXTLEN.'),
					salePPCity VARCHAR('.STAGESHOW_PPSALEPPCITY_TEXTLEN.'),
					salePPState VARCHAR('.STAGESHOW_PPSALEPPSTATE_TEXTLEN.'),
					salePPZip VARCHAR('.STAGESHOW_PPSALEPPZIP_TEXTLEN.'),
					salePPCountry VARCHAR('.STAGESHOW_PPSALEPPCOUNTRY_TEXTLEN.'),
					salePaid DECIMAL(9,2) NOT NULL,
					saleTxnId VARCHAR('.STAGESHOW_PPSALETXNID_TEXTLEN.') NOT NULL,
					saleStatus VARCHAR('.STAGESHOW_PPSALESTATUS_TEXTLEN.'),
					UNIQUE KEY saleID (saleID)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;';

				//excecute the query
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($sql);
			}
      
      $ticketNameLen = STAGESHOW_SHOWNAME_TEXTLEN + strlen(STAGESHOW_TICKETNAME_DIVIDER) + STAGESHOW_DATETIME_TEXTLEN;

			$table_name = STAGESHOW_TICKETS_TABLE;
			//if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name)
			{
				$sql = "CREATE TABLE ".$table_name.' ( 
					ticketID INT UNSIGNED NOT NULL AUTO_INCREMENT,
					saleID INT UNSIGNED NOT NULL,
					priceID INT UNSIGNED NOT NULL,
					ticketName VARCHAR('.$ticketNameLen.') NOT NULL,
					ticketType VARCHAR('.STAGESHOW_PRICETYPE_TEXTLEN.') NOT NULL,
					ticketQty INT NOT NULL,
					ticketSeat VARCHAR('.STAGESHOW_TICKETSEAT_TEXTLEN.'),
					UNIQUE KEY ticketID (ticketID)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;';

				//excecute the query
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($sql);
			}
		}

    function uninstall()
    {
      global $wpdb;
      
      $wpdb->query('DROP TABLE IF EXISTS '.STAGESHOW_PERFORMANCES_TABLE);      
      $wpdb->query('DROP TABLE IF EXISTS '.STAGESHOW_PRICES_TABLE);      
      $wpdb->query('DROP TABLE IF EXISTS '.STAGESHOW_SALES_TABLE);      
      $wpdb->query('DROP TABLE IF EXISTS '.STAGESHOW_TICKETS_TABLE);  
    }

		//Returns an array of admin options
		function getOptions() {
			// Initialise settings array with default values
			$this->adminOptions = array(        
        'ShowName' => '',
        
        'PayPalEnv' => 'sandbox',
        'PayPalCurrency' => STAGESHOW_PAYPAL_DEFAULT_CURRENCY,
        
        'PayPalAPITestUser' => '',
        'PayPalAPITestSig' => '',
        'PayPalAPITestPwd' => '',
        'PayPalAPITestEMail' => '',
        
        'PayPalAPILiveUser' => '',
        'PayPalAPILiveSig' => '',
        'PayPalAPILivePwd' => '',
        'PayPalAPILiveEMail' => '',
        
        'PayPalLogoImageURL' => STAGESHOW_DEFAULT_PAYPALLOGOIMAGE_URL,
        'PayPalHeaderImageURL' => STAGESHOW_DEFAULT_PAYPALHEADERIMAGE_URL,
        
        'OrganisationID' => '',
        'AdminID' => '',
        'AdminEMail' => '',        
        'BookingsEMail' => '',       
        'SentCopyEMail' => '',                
        'EMailTemplatePath' => '',
        
				'DeleteOrphans' => false,
				
        'SLen' => 0,                
        'PLen' => 4,
        
        'Dev_EnableDebug' => '',
        'Dev_ShowSQL' => '',
        'Dev_ShowPayPalIO' => '',
        'Dev_ShowEMailMsgs' => '',
        'Dev_ShowDBIds' => '',        
        
        'LogsFolderPath' => '../logs');
				
			// Get current values from MySQL
			$currOptions = get_option(STAGESHOW_OPTIONS_NAME);
			
			// Now update defaults with values from DB
			if (!empty($currOptions)) {
				foreach ($currOptions as $key => $option)
					$this->adminOptions[$key] = $option;
			}				
			
			if ($this->adminOptions['PayPalCurrency'] === '')
				$this->adminOptions['PayPalCurrency'] = STAGESHOW_PAYPAL_DEFAULT_CURRENCY;
/*			
			echo "Options Loaded:";
			print_r($this->adminOptions);
			echo "<br>\n";
*/			
			$this->saveOptions();
			return $this->adminOptions;
		}
    
		// Saves the admin options to the options data table
		function saveOptions() 
		{			
			update_option(STAGESHOW_OPTIONS_NAME, $this->adminOptions);
		}
    
		function GetNewSettings()
		{
			$this->adminOptions['OrganisationID'] = $_POST['OrganisationID'];
			$this->adminOptions['AdminID'] = $this->adminOptions['OrganisationID'].' '.__('Bookings',STAGESHOW_DOMAIN_NAME);
			$this->adminOptions['AdminEMail'] = $_POST['AdminEMail'];
			$this->adminOptions['BookingsEMail'] = $this->adminOptions['AdminEMail'];					
			$this->adminOptions['SentCopyEMail'] = $this->adminOptions['AdminEMail'];					
		}
    
    function ShowSettings($isUpdated)
    {
			if ($isUpdated)
			{
				$OrganisationID = $this->adminOptions['OrganisationID'];				
				$AdminEMail = $this->adminOptions['AdminEMail'];				
			}
			else
			{				
				$OrganisationID = stripslashes($_POST['OrganisationID']);
				$AdminEMail = stripslashes($_POST['AdminEMail']);
			}
?>    
		<tr valign="top">
      <td width="220"><?php _e('Organisation', STAGESHOW_DOMAIN_NAME); ?>:</td>
			<td width="780">
				<input type="text" maxlength="<?php echo STAGESHOW_ORGANISATIONID_TEXTLEN; ?>" size="<?php echo STAGESHOW_ORGANISATIONID_TEXTLEN; ?>" name="OrganisationID" value="<?php echo $OrganisationID; ?>" />
      </td>
		</tr>
		<tr valign="top">
      <td><?php _e('EMail from Admin Address', STAGESHOW_DOMAIN_NAME); ?>:</td>
			<td>
				<input type="text" maxlength="<?php echo STAGESHOW_ADMINMAIL_TEXTLEN; ?>" size="<?php echo STAGESHOW_ADMINMAIL_EDITLEN; ?>" name="AdminEMail" value="<?php echo $AdminEMail; ?>" />
      </td>
		</tr>
<?php			
    }
    
		function SaveSettings()
		{
			$this->GetNewSettings();
			$this->saveOptions();			
		}
		
		function CreateSample()
		{
      $showName1 = "The Wordpress Show";
      
      // Sample dates to reflect current date/time
      $showTime1 = date(STAGESHOW_DATE_FORMAT, strtotime("-1 days"))." 20:00:00";
      $showTime2 = date(STAGESHOW_DATE_FORMAT, strtotime("-0 days"))." 20:00:00";
      $showTime3 = date(STAGESHOW_DATE_FORMAT, strtotime("+1 days"))." 14:30:00";
      $showTime4 = date(STAGESHOW_DATE_FORMAT, strtotime("+1 days"))." 20:00:00";
      
	    // Populate table
      $showID1 = $this->AddShow($showName1);
          
      $statusMsg = '';
			
	    // Populate performances table	  
			$perfCount = 4;
			if (defined('STAGESHOW_SAMPLE_PERFORMANCES_COUNT'))
				$perfCount = STAGESHOW_SAMPLE_PERFORMANCES_COUNT;
				
      $perfID1 = $perfCount >= 1 ? $this->CreateNewPerformance($statusMsg, $showID1, $showTime1, "Day1Eve", 80) : -1;
      $perfID2 = $perfCount >= 2 ? $this->CreateNewPerformance($statusMsg, $showID1, $showTime2, "Day2Eve", 60) : -1;
      $perfID3 = $perfCount >= 3 ? $this->CreateNewPerformance($statusMsg, $showID1, $showTime3, "Day3Mat", 80) : -1;
      $perfID4 = $perfCount >= 4 ? $this->CreateNewPerformance($statusMsg, $showID1, $showTime4, "Day3Eve", 60) : -1;
      if (($perfID1 == 0) ||($perfID2 == 0) || ($perfID3 == 0) || ($perfID4 == 0))
			{
				echo '<div id="message" class="updated"><p>'.__('Cannot Add Performances', STAGESHOW_DOMAIN_NAME).' - '.$statusMsg.'.</p></div>';
				return;
			}
			
	    // Populate prices table
	    $priceID1_A1 = $this->AddPrice($perfID1, 'Adult', '5.50');
	    $priceID1_A2 = $this->AddPrice($perfID2, 'Adult', '5.50');
	    $priceID1_A3 = $this->AddPrice($perfID3, 'Adult', '4.00');
	    $priceID1_A4 = $this->AddPrice($perfID4, 'Adult', '12.00');
	    
	    $priceID1_C1 = $this->AddPrice($perfID1, 'Child', '3.00');
	    $priceID1_C2 = $this->AddPrice($perfID2, 'Child', '3.00');
	    $priceID1_C3 = $this->AddPrice($perfID3, 'Child', '2.00');
                 
			$perfsList = $this->GetPerformancesListByShowID($showID1);
			{
				$this->UpdateCartButtons($perfsList);
			}
			
			// Add some ticket sales
			$saleEMail = 'other@someemail.co.uk';
			if (defined('STAGESHOW_SAMPLE_EMAIL'))
				$saleEMail = STAGESHOW_SAMPLE_EMAIL;
			$saleID = $this->AddSale(date(STAGESHOW_DATE_FORMAT, strtotime("-4 days"))." 17:32:47", 'A.N.Other', $saleEMail, 12.00, 'ABCD1234XX', 'Completed',
								'Andrew Other', '1 The Street', 'Somewhere', 'Bigshire', 'BG1 5AT', 'UK');
			$this->AddTicket($saleID, $priceID1_C3, $showName1.STAGESHOW_TICKETNAME_DIVIDER.$showTime3, 'Child', 4);
			$this->AddTicket($saleID, $priceID1_A3, $showName1.STAGESHOW_TICKETNAME_DIVIDER.$showTime3, 'Adult', 1);
			
			$saleEMail = 'mybrother@someemail.co.uk';
			if (defined('STAGESHOW_SAMPLE_EMAIL'))
				$saleEMail = STAGESHOW_SAMPLE_EMAIL;
			$saleID = $this->AddSale(date(STAGESHOW_DATE_FORMAT, strtotime("-2 days"))." 10:14:51", 'M.Y.Brother', $saleEMail, 48.00, '87654321qa', 'Pending',
								'Matt Brother', 'The Bungalow', 'Otherplace', 'Littleshire', 'LI1 9ZZ', 'UK');
			$this->AddTicket($saleID, $priceID1_A4, $showName1.STAGESHOW_TICKETNAME_DIVIDER.$showTime4, 'Adult', 4);
		}
		
		function CreateNewPerformance(&$rtnMsg, $showID, $perfDateTime, $perfRef = '', $perfSeats = 0)
		{
			global $myPayPalAPILiveObj;
			global $myPayPalAPITestObj;
			global $stageShowDBaseObj;
			
			if ($showID <= 0) return 0;
			
			$perfState = '';
			$perfID = 0;
			
			// Get the show name
			$shows = $this->GetShowsList($showID);
			$showName = $shows[0]->showName;
			
			// Create PayPal buttons ....
			$TestButtonStatus = $myPayPalAPITestObj->CreateButton($hostedTESTButtonID, $showName);
			$LiveButtonStatus = $myPayPalAPILiveObj->CreateButton($hostedLIVEButtonID, $showName);
				
			if (($TestButtonStatus === STAGESHOW_PAYPAL_CREATEBUTTON_ERROR) || ($LiveButtonStatus === STAGESHOW_PAYPAL_CREATEBUTTON_ERROR))
			{
				// Error creating at least one button ... tidy up and report error
				if ($TestButtonStatus === STAGESHOW_PAYPAL_CREATEBUTTON_OK)
					$myPayPalAPITestObj->DeleteButton($hostedTESTButtonID);
						
				if ($LiveButtonStatus === STAGESHOW_PAYPAL_CREATEBUTTON_OK)
					$myPayPalAPILiveObj->DeleteButton($hostedLIVEButtonID);
						
				$rtnMsg = __('Error Creating PayPal Button(s)', STAGESHOW_DOMAIN_NAME);
			}
			else if (($TestButtonStatus === STAGESHOW_PAYPAL_CREATEBUTTON_NOLOGIN) && ($LiveButtonStatus === STAGESHOW_PAYPAL_CREATEBUTTON_NOLOGIN))
			{
				//echo "TestButtonStatus = $TestButtonStatus<br>\n";
				//echo "LiveButtonStatus = $LiveButtonStatus<br>\n";
				$rtnMsg = __('PayPal Login Settings Invalid', STAGESHOW_DOMAIN_NAME);
			}
			else
			{
				// PayPal button(s) created - Add performance to database					
				// Give performance unique Ref - Check what default reference IDs already exist in database
				$perfID = $this->AddPerformance($showID, $perfState, $perfDateTime, $perfRef, $perfSeats, $hostedTESTButtonID, $hostedLIVEButtonID);
				if ($perfID == 0)
					$rtnMsg = __('Performance Reference is not unique', STAGESHOW_DOMAIN_NAME);
				else
					$rtnMsg = __('Settings have been saved', STAGESHOW_DOMAIN_NAME);
			}
			
			return $perfID;
		}
		
    function FormatDateForDisplay($dateInDB)
    {	
			// Convert time string to UNIX timestamp
			$timestamp = strtotime( $dateInDB );
			
			if (defined('STAGESHOW_DATETIME_BOXOFFICE_FORMAT'))
				$dateFormat = STAGESHOW_DATETIME_BOXOFFICE_FORMAT;
			else
				// Use Wordpress Date and Time Format
				$dateFormat = get_option( 'date_format' ).' '.get_option( 'time_format' );
			
			// Return Time & Date formatted for display to user
			return date($dateFormat, $timestamp);				 
		}

/*
		function FormatDateForDisplay($dateInDB)
    {	
			// Extract the date from MYSQL format
			list($date, $time) = explode(' ', $dateInDB);
			list($year, $month, $day) = explode('-', $date);
			list($hour, $minute, $second) = explode(':', $time);

			$timestamp = mktime($hour, $minute, $second, $month, $day, $year);

			$rtnVal  = '';
			$rtnVal .= substr($dateInDB, 8, 2);
			$rtnVal .= '/';
			$rtnVal .= substr($dateInDB, 5, 2);
			$rtnVal .= '/';
			$rtnVal .= substr($dateInDB, 0, 4);
			
			$rtnVal .= ' ';
			$rtnVal .= substr($dateInDB, 11, 5);
			
			return $rtnVal;
    }
*/    
		function UpdateCartButtons($perfsList)
		{
			global $myPayPalAPILiveObj;
			global $myPayPalAPITestObj;
			
			foreach($perfsList as $perfEntry)
			{
				$description = $perfEntry->showName.' - '.$this->FormatDateForDisplay($perfEntry->perfDateTime);
				$reference = $perfEntry->showID.'-'.$perfEntry->perfID;
				
				$perfSales = $this->GetSalesQtyByPerfID($perfEntry->perfID);								
				$quantity = $perfEntry->perfSeats - $perfSales;								
				$pricesList = $this->GetPricesListByPerfID($perfEntry->perfID);
				
				$priceIDs = (array)null;
				$ticketPrices = (array)null;
					
				foreach($pricesList as $pricesEntry)
				{
					// Add Ticket IDs and Prices for this performance to an array 
					array_push($priceIDs, $pricesEntry->priceType);
					array_push($ticketPrices, $pricesEntry->priceValue);
				}
								
				$myPayPalAPITestObj->UpdateButton($perfEntry->perfPayPalTESTButtonID, $description, $reference, $priceIDs, $ticketPrices);
				$myPayPalAPITestObj->UpdateInventory($perfEntry->perfPayPalTESTButtonID, $quantity);
								
				$myPayPalAPILiveObj->UpdateButton($perfEntry->perfPayPalLIVEButtonID, $description, $reference, $priceIDs, $ticketPrices);
				$myPayPalAPILiveObj->UpdateInventory($perfEntry->perfPayPalLIVEButtonID, $quantity);
			}
		}
		
		function GetEmail($ourOptions)
		{
			$ourEmail = '';
			
			// Get from email address from settings
			if (strlen($ourOptions['AdminID']) > 0)
				$ourEmail .= ' '.$ourOptions['AdminID'];
				
			if (strlen($ourOptions['AdminEMail']) > 0)
				$ourEmail .= ' <'.$ourOptions['AdminEMail'].'>';

			return $ourEmail;
		}
		
		function IsStateActive($state)
		{
			return ($state === '') || ($state === 'activate') || ($state === 'Active');
		}
		
		function GetAllShowsList()
		{
			return $this->GetShowsList(1);
		}
		
		function GetShowsList($showID = 0)
		{
			// Get ShowName (if configured) from Options
			$ourOptions = get_option(STAGESHOW_OPTIONS_NAME);
			
			if ( ($showID <= 1) && (isset($ourOptions['showName'])) && (strlen($ourOptions['showName']) > 0) )
			{
				$showEntry = new stdClass();
				
				$showEntry->showID = 1;
				$showEntry->showName = $ourOptions['showName'];
				$showEntry->showState = 'activate';
				
				$results = array($showEntry);
			}
			else
			{					
				$results = array();
			}
			
			return $results;
		}
		
		function IsShowNameUnique($showName)
		{
			return true;
		}
		
		function IsShowActivated($showID)
		{
			return true;
		}
		
		function SetShowActivated($showID, $showState = 'activate')
		{
		}
		
		function CanAddShow()
		{		
			$ourOptions = get_option(STAGESHOW_OPTIONS_NAME);
      
			// Check if a show is already configured
			if ( (isset($ourOptions['showName'])) && (strlen($ourOptions['showName']) > 0) )
				return false;
						
			return true;
		}
		 
		function AddShow($showName = '', $showState = 'activate')
		{
			global $wpdb;
			
			$ourOptions = get_option(STAGESHOW_OPTIONS_NAME);
      
			// Check if a show is already configured
			if ( (isset($ourOptions['showName'])) && (strlen($ourOptions['showName']) > 0) )
				return 0;
				
			$ourOptions['showName'] = $showName;
			update_option(STAGESHOW_OPTIONS_NAME, $ourOptions);
			
     	return 1;
		}
				
		function UpdateShowName($showID, $showName)
		{
			if (!$this->IsShowNameUnique($showName))
				return "ERROR";
				
			if ($showID != 1)	// Only one show supported
				return "ERROR";
				
			// Save Show Name()
			$ourOptions = get_option(STAGESHOW_OPTIONS_NAME);
			$ourOptions['showName'] = $showName;
			update_option(STAGESHOW_OPTIONS_NAME, $ourOptions);
			
			// Show Name Changed ... Updated Any Hosted Buttons
			$perfsList = $this->GetPerformancesListByShowID($showID);
			$this->UpdateCartButtons($perfsList);
													
			return "OK";
		}
				
		function CanDeleteShow(&$showSales, $showID)
		{
			$lastPerfDate = $this->GetLastPerfDateTime($showID);

			if (($perfDate = strtotime($lastPerfDate)) === false)
				$canDelete = false;
			else
			{
				$dateDiff = strtotime("now")-$perfDate;
				$canDelete = ($dateDiff > 60*60*24);
			}

			$showSales = $this->GetSalesQtyByShowID($showID);
			$canDelete |= ($showSales == 0);		
			
			return $canDelete;		
		}
		
		function ShowIDSQLJoin($joinType = '')
		{
			return '';
		}
		
		function ShowIDSQLFilter($showID, $sqlCmd = 'WHERE')
		{
			return '';
		}
		
		function PerfIDSQLFilter($perfID, $sqlCmd = 'WHERE')
		{
      if (is_numeric($perfID))
      {
				if ($perfID > 0)
				{
					return ' '.$sqlCmd.' '.STAGESHOW_PERFORMANCES_TABLE.'.perfID='.$perfID;
				}
			}
			else
			{
				return ' '.$sqlCmd.' '.STAGESHOW_PERFORMANCES_TABLE.'.perfDateTime="'.$perfID.'"';
			}

			return '';
		}
				
		function SaleIDSQLFilter($saleID, $sqlCmd = 'WHERE')
		{
      if (is_numeric($saleID))
      {
				if ($saleID > 0)
				{
					return ' '.$sqlCmd.' '.STAGESHOW_SALES_TABLE.'.saleID='.$saleID;
				}
			}
			else
			{
				//return ' '.$sqlCmd.' '.STAGESHOW_SALES_TABLE.'.perfDateTime="'.$perfID.'"';
			}

			return '';
		}
				
		function get_results($sql)
		{
			global $wpdb;
      
			$results = $wpdb->get_results($sql);
			
			// Add Show Name
			$ourOptions = get_option(STAGESHOW_OPTIONS_NAME);
			if (isset($ourOptions['showName']))
			{
				for ($i=0; $i<count($results); $i++)
				{
					$results[$i]->showName = $ourOptions['showName'];
					$results[$i]->showState = 'activate';
				}
			}
			
			return $results;
		}
		
		function GetAllPerformancesList()
		{
			return $this->GetPerformancesList();
		}
				
		function GetPerformancesListByShowID($showID)
		{
			return $this->GetPerformancesList($showID);
		}
				
		function GetPerformancesListByPerfID($perfID)
		{
			return $this->GetPerformancesList(0, $perfID);
		}
				
		private function GetPerformancesList($showID = 0, $perfID = 0)
		{
      global $wpdb;
      
			$sql = 'SELECT * FROM '.STAGESHOW_PERFORMANCES_TABLE;
			$sql .= $this->ShowIDSQLJoin('LEFT');

			$sqlWhere  = $this->ShowIDSQLFilter($showID);
			$sqlWhere .= $this->PerfIDSQLFilter($perfID, (strlen($sqlWhere)>0?'AND':'WHERE'));
			$sql .= $sqlWhere;
			
			$sql .= ' ORDER BY '.STAGESHOW_PERFORMANCES_TABLE.'.perfDateTime';
			
			$this->ShowSQL($sql); 
			
			$perfsListArray = $this->get_results($sql);

			return $perfsListArray;
		}
		
		function CanDeletePerformance(&$perfSales, $perfID, $perfDateTime = '')
		{
			if ($perfDateTime === '')
			{
				$perfsList = $this->GetPerformancesListByPerfID($perfID);
				$perfDateTime = $perfsList[0]->perfDateTime;
			}
				
			// Performances can be deleted if there are no tickets sold or 24 hours after start date/time
			if (($perfDate = strtotime($perfDateTime)) === false)
				$canDelete = false;
			else
			{
				$dateDiff = strtotime("now")-$perfDate;
				$canDelete = ($dateDiff > 60*60*24);
			}
			
			$perfSales = $this->GetSalesQtyByPerfID($perfID);
			$canDelete |= ($perfSales == 0);
			
			return $canDelete;
		}
		
		function IsPerfActivated($perfID)
		{
			$sql = 'SELECT perfState FROM '.STAGESHOW_PERFORMANCES_TABLE;

			$sqlWhere = $this->PerfIDSQLFilter($perfID);
			$sql .= $sqlWhere;			
			$this->ShowSQL($sql); 
			
			$results = $this->get_results($sql);
			$perfState = $results[0]->perfState;
			
			return $this->IsStateActive($perfState);
		}
		
		function SetPerfActivated($perfID, $perfState = 'activate')
		{
      global $wpdb;
      
			$sql  = 'UPDATE '.STAGESHOW_PERFORMANCES_TABLE;
			$sql .= ' SET perfState="'.$perfState.'"';
			$sql .= $this->PerfIDSQLFilter($perfID);
			$this->ShowSQL($sql); 

			$wpdb->query($sql);	
			return "OK";							
		}
		
		private function GetLastPerfDateTime($showID = 0)
		{
      global $wpdb;
      
			$sql  = 'SELECT MAX(perfDateTime) AS LastPerf FROM '.STAGESHOW_PERFORMANCES_TABLE;
			$sql .= ' WHERE '.STAGESHOW_PERFORMANCES_TABLE.'.showID='.$showID;
			
			$this->ShowSQL($sql); 
				
			$results = $this->get_results($sql);
			
			if (count($results) == 0) return 0;
			return $results[0]->LastPerf;
		}
		
		function IsPerfEnabled($result)
		{
			//echo "Show:$result->showID $result->showState Perf:$result->perfID $result->perfState<BR>\n";
			return $this->IsStateActive($result->showState) && $this->IsStateActive($result->perfState);
		}
		
		function IsPerfRefUnique($perfRef)
		{
      global $wpdb;
      
			$sql  = 'SELECT COUNT(*) AS MatchCount FROM '.STAGESHOW_PERFORMANCES_TABLE;
			$sql .= ' WHERE '.STAGESHOW_PERFORMANCES_TABLE.'.perfRef="'.$perfRef.'"';
			$this->ShowSQL($sql); 
			
			$perfsCount = $this->get_results($sql);
			return ($perfsCount[0]->MatchCount > 0) ? false : true;
		}
		
		function CanAddPerformance()
		{
			$sql  = 'SELECT COUNT(*) AS perfLen FROM '.STAGESHOW_PERFORMANCES_TABLE;
			$this->ShowSQL($sql); 
			
			$results = $this->get_results($sql);
			return (($this->adminOptions['PLen']==0) || ($results[0]->perfLen<$this->adminOptions['PLen']));
		}

		function AddPerformance($showID, $perfState, $perfDateTime, $perfRef, $perfSeats, $perfPayPalTESTButtonID, $perfPayPalLIVEButtonID)
		{
      global $wpdb;
      
      if ($perfRef === '')
      {
				$perfRefNo = 1;
				while (true)
				{
					// Query Database for proposed Performance Ref until we find one that doesn't already exist
					$perfRef = 'PERF'.$perfRefNo;
					if ($this->IsPerfRefUnique($perfRef))
						break;
					$perfRefNo++;
				}
			}
			else
			{
				if (!$this->IsPerfRefUnique($perfRef))
					return 0;	// Error - Performance Reference is not unique
			}
			
			$sql  = 'INSERT INTO '.STAGESHOW_PERFORMANCES_TABLE.'(showID, perfState, perfDateTime, perfRef, perfSeats, perfPayPalTESTButtonID, perfPayPalLIVEButtonID)';
			$sql .= ' VALUES('.$showID.', "'.$perfState.'", "'.$perfDateTime.'", "'.$perfRef.'", "'.$perfSeats.'", "'.$perfPayPalTESTButtonID.'", "'.$perfPayPalLIVEButtonID.'")';
			$this->ShowSQL($sql); 
			
			$wpdb->query($sql);
			
     	return mysql_insert_id();
		}
				
		function UpdatePerformanceTime($perfID, $newPerfDateTime)
		{
			$sqlSET = 'perfDateTime="'.$newPerfDateTime.'"';
			return $this->UpdatePerformanceEntry($perfID, $sqlSET);
		}
				
		function UpdatePerformanceRef($perfID, $newPerfRef)
		{
			if (!$this->IsPerfRefUnique($newPerfRef))
				return "ERROR";
				
			$sqlSET = 'perfRef="'.$newPerfRef.'"';
			return $this->UpdatePerformanceEntry($perfID, $sqlSET);
		}
				
		function UpdatePerformanceSeats($perfID, $newPerfSeats)
		{
			$sqlSET = 'perfSeats="'.$newPerfSeats.'"';
			return $this->UpdatePerformanceEntry($perfID, $sqlSET);
		}								
				
		function UpdatePerformanceEntry($perfID, $sqlSET)
		{
			global $wpdb;
      
			$sql  = 'UPDATE '.STAGESHOW_PERFORMANCES_TABLE;
			$sql .= ' SET '.$sqlSET;
			$sql .= $this->PerfIDSQLFilter($perfID);
			$this->ShowSQL($sql); 

			$wpdb->query($sql);	
			return "OK";							
		}
		
		function GetSettings()
		{
			return $this->GetPricesList();
		}
				
		function GetPricesListByShowID($showID)
		{
			return $this->GetPricesList($showID);
		}
				
		function GetPricesListByPerfID($perfID)
		{
			return $this->GetPricesList(0, $perfID);
		}
				
		function JoinedFields()
		{
			return ', '.STAGESHOW_PERFORMANCES_TABLE.'.perfID';
		}
		
		private function GetPricesList($showID = 0, $perfID = 0, $moreSQL = '')
		{
			global $wpdb;
			
			$selectFields  = '*';
			if (($showID == 0) && ($perfID == 0))
			{
				// Explicitly add joined fields from "base" tables (otherwise values will be NULL if there is no matching JOIN)
				$selectFields .= $this->JoinedFields();

				$joinCmd = 'LEFT';
			}
			else
				$joinCmd = '';

			$sql  = 'SELECT '.$selectFields.' FROM '.STAGESHOW_PRICES_TABLE;
      $sql .= ' '.$joinCmd.' JOIN '.STAGESHOW_PERFORMANCES_TABLE.' ON '.STAGESHOW_PERFORMANCES_TABLE.'.perfID='.STAGESHOW_PRICES_TABLE.'.perfID';
			$sql .= $this->ShowIDSQLJoin($joinCmd);

			$sqlWhere  = $this->ShowIDSQLFilter($showID);
			$sqlWhere .= $this->PerfIDSQLFilter($perfID, (strlen($sqlWhere)>0?'AND':'WHERE'));
			$sql .= $sqlWhere;
			
			if (strlen($moreSQL) > 0)
				$sql .= ' AND '.$moreSQL;
			
			$sql .= ' ORDER BY '.STAGESHOW_PERFORMANCES_TABLE.'.perfDateTime';
			$sql .= ' , '.STAGESHOW_PRICES_TABLE.'.priceType';
			
			$this->ShowSQL($sql); 
			
			return $this->get_results($sql);
		}

		function GetPriceEntry($priceID)
		{
			$sql  = 'SELECT * FROM '.STAGESHOW_PRICES_TABLE;
			$sql .= ' JOIN '.STAGESHOW_PERFORMANCES_TABLE.' ON '.STAGESHOW_PERFORMANCES_TABLE.'.perfID='.STAGESHOW_PRICES_TABLE.'.perfID';
			$sql .= $this->ShowIDSQLJoin();
			
			$sql .= ' WHERE '.STAGESHOW_PRICES_TABLE.'.priceID="'.$priceID.'"';
			
			$this->ShowSQL($sql); 
			
			return $this->get_results($sql);
		}
				

		function IsPriceTypeUnique($perfID, $priceType)
		{
      global $wpdb;
      
			$sql  = 'SELECT COUNT(*) AS MatchCount FROM '.STAGESHOW_PRICES_TABLE;
			$sql .= ' WHERE '.STAGESHOW_PRICES_TABLE.'.priceType="'.$priceType.'"';
			$sql .= ' AND '.STAGESHOW_PRICES_TABLE.'.perfID="'.$perfID.'"';
			$this->ShowSQL($sql); 

			$pricesEntries = $this->get_results($sql);
			return ($pricesEntries[0]->MatchCount > 0) ? false : true;
		}
		
		function AddPrice($perfID, $priceType, $priceValue, $priceRef = '')
		{
      global $wpdb;
      
      if ($perfID <= 0) return 0;
      
      if ($priceType === '')
      {
				$priceTypeNo = 1;
				while (true)
				{
					// Query Database for proposed Performance Ref until we find one that doesn't already exist
					$priceType = 'TYPE'.$priceTypeNo;
					if ($this->IsPriceTypeUnique($perfID, $priceType))
						break;
					$priceTypeNo++;
				}
			}
			else
			{
				if (!$this->IsPriceTypeUnique($perfID, $priceType))
					return 0;	// Error - Performance Reference is not unique
			}
			
      $sql  = 'INSERT INTO '.STAGESHOW_PRICES_TABLE.' (perfID, priceType, priceRef, priceValue)';
      $sql .= ' VALUES('.$perfID.', "'.$priceType.'", "'.$priceRef.'", "'.$priceValue.'")';
			$this->ShowSQL($sql); 
			
			$wpdb->query($sql);
			
     	return mysql_insert_id();
		}
		
		function UpdatePricePerfID($priceID, $newPerfID)
		{
			$sqlSET = 'perfID="'.$newPerfID.'"';
			return $this->UpdatePriceEntry($priceID, $sqlSET);
		}								
				
		function UpdatePriceType($priceID, $newPriceType)
		{
			$sqlSET = 'priceType="'.$newPriceType.'"';
			return $this->UpdatePriceEntry($priceID, $sqlSET);
		}								
				
		function UpdatePriceValue($priceID, $newPriceValue)
		{
			$sqlSET = 'priceValue="'.$newPriceValue.'"';
			return $this->UpdatePriceEntry($priceID, $sqlSET);
		}								
				
		function UpdatePriceEntry($priceID, $sqlSET)
		{
			global $wpdb;
      
			$sql  = 'UPDATE '.STAGESHOW_PRICES_TABLE;
			$sql .= ' SET '.$sqlSET;			
			$sql .= ' WHERE priceID='.$priceID;			
			$this->ShowSQL($sql); 

			$wpdb->query($sql);	
			return "OK";							
		}
		
		function GetSalesQtyByShowID($showID)
		{
			return $this->GetSalesQty($showID);
		}
				
		function GetSalesQtyByPerfID($perfID)
		{
			return $this->GetSalesQty(0, $perfID);
		}
				
		private function GetSalesQty($showID = 0, $perfID = 0)
		{
			global $wpdb;
			
			$rtnVal = 0;
			
			if (($showID != 0) || ($perfID != 0))
			{
				$sql  = 'SELECT SUM(ticketQty) AS totalQty FROM '.STAGESHOW_TICKETS_TABLE;
				$sql .= ' JOIN '.STAGESHOW_PRICES_TABLE.' ON '.STAGESHOW_PRICES_TABLE.'.priceID='.STAGESHOW_TICKETS_TABLE.'.priceID';
				$sql .= ' JOIN '.STAGESHOW_PERFORMANCES_TABLE.' ON '.STAGESHOW_PERFORMANCES_TABLE.'.perfID='.STAGESHOW_PRICES_TABLE.'.perfID';

				if ($perfID != 0)
					$sql .= ' WHERE '.STAGESHOW_PERFORMANCES_TABLE.'.perfID="'.$perfID.'"';
				if ($showID != 0)
					$sql .= ' WHERE '.STAGESHOW_PERFORMANCES_TABLE.'.showID="'.$showID.'"';
					
				$this->ShowSQL($sql); 
			
				$salesResults = $this->get_results($sql);

				if (count($salesResults) > 0)
				{
					$rtnVal =$salesResults[0]->totalQty;					
					if (empty($rtnVal)) $rtnVal = 0;
				}
			}

			return $rtnVal;
		}
		
		function GetAllSalesList()
		{
			return $this->GetSalesList();
		}
				
		function GetSalesListByShowID($showID)
		{
			// Note: This call is unused and therefore untested
			return $this->GetSalesList($showID);
		}
				
		function GetSalesListByPerfID($perfID)
		{
			// Note: This call is unused and therefore untested
			return $this->GetSalesList(0, $perfID);
		}
				
		function GetSalesListBySaleID($saleID)
		{
			return $this->GetSalesList(0, 0, $saleID);
		}
				
		private function GetSalesList($showID = 0, $perfID = 0, $saleID = 0)
		{
			global $wpdb;
			
			// Add JOINS and WHERE SQL to filter results
			if (($showID != 0) || ($perfID != 0))
			{
				$sql  = 'SELECT * FROM '.STAGESHOW_SALES_TABLE;
				$sql .= ' JOIN '.STAGESHOW_TICKETS_TABLE.' ON '.STAGESHOW_TICKETS_TABLE.'.saleID='.STAGESHOW_SALES_TABLE.'.saleID';      
				$sql .= ' JOIN '.STAGESHOW_PRICES_TABLE.' ON '.STAGESHOW_PRICES_TABLE.'.priceID='.STAGESHOW_TICKETS_TABLE.'.priceID';
				$sql .= ' JOIN '.STAGESHOW_PERFORMANCES_TABLE.' ON '.STAGESHOW_PERFORMANCES_TABLE.'.perfID='.STAGESHOW_PRICES_TABLE.'.perfID';
				$sql .= $this->ShowIDSQLJoin();
				
				// Add SQL filter(s)
				$sqlWhere  = $this->ShowIDSQLFilter($showID);
				$sqlWhere .= $this->PerfIDSQLFilter($perfID, (strlen($sqlWhere)>0?'AND':'WHERE'));
				$sql .= $sqlWhere;
				
				$sql .= ' ORDER BY '.STAGESHOW_SALES_TABLE.'.saleID';
			}
			else
			{
				$sql  = 'SELECT * FROM '.STAGESHOW_SALES_TABLE;
			}		
			
			if ($saleID != 0)
				$sql .= ' WHERE '.STAGESHOW_SALES_TABLE.'.saleID="'.$saleID.'"';
			
			$this->ShowSQL($sql); 
			
			$salesListArray = $this->get_results($sql);
			
			return $salesListArray;
		}			
		
		function GetAllTicketsList()
		{
			return $this->GetTicketsList();
		}
				
		function GetTicketsListBySaleID($saleID)
		{
			return $this->GetTicketsList($saleID);
		}
				
		function GetTicketsListByPriceID($priceID)
		{
			return $this->GetTicketsList(0, $priceID);
		}
				
		private function GetTicketsList($saleID = 0, $priceID = 0)
		{
			global $wpdb;
			
			$selectFields  = '*';
			if (($saleID == 0) && ($priceID == 0))
			{
				// Explicitly add joined fields from "base" tables (otherwise values will be NULL if there is no matching JOIN)
				$selectFields .= ', '.STAGESHOW_SALES_TABLE.'.saleID';

				$joinCmd = ' LEFT JOIN ';
			}
			else
				$joinCmd = ' JOIN ';
				
			$sql  = 'SELECT '.$selectFields.' FROM '.STAGESHOW_SALES_TABLE;
			$sql .= $joinCmd.STAGESHOW_TICKETS_TABLE.' ON '.STAGESHOW_SALES_TABLE.'.saleID='.STAGESHOW_TICKETS_TABLE.'.saleID';
			
			$sqlWhere = '';
			if ($priceID != 0)
				$sqlWhere .= ' WHERE '.STAGESHOW_TICKETS_TABLE.'.priceID="'.$priceID.'"';			
			if ($saleID != 0)
				$sqlWhere .= $this->SaleIDSQLFilter($saleID, (strlen($sqlWhere)>0?'AND':'WHERE'));
					
			$sql .= $sqlWhere;
			$this->ShowSQL($sql); 
			
			$salesListArray = $this->get_results($sql);
			
			return $salesListArray;
		}			
		
		function DeleteShowByShowID($ID)
		{
			// Get the current name and then clear it
			$ourOptions = get_option(STAGESHOW_OPTIONS_NAME);
			$rtnVal = $ourOptions['showName'];
			$ourOptions['showName'] = '';
			update_option(STAGESHOW_OPTIONS_NAME, $ourOptions);

			return $rtnVal;
		}			
		
		function DeletePerformanceByPerfID($ID)
		{
			return $this->DeletePerformance($ID, 'perfID');
		}			
		
		private function DeletePerformance($ID = 0, $IDfield = 'perfID')
		{
			global $wpdb;
			
			// Delete a performance entry
			$sql  = 'DELETE FROM '.STAGESHOW_PERFORMANCES_TABLE;
			$sql .= ' WHERE '.STAGESHOW_PERFORMANCES_TABLE.".$IDfield=$ID";
			$this->ShowSQL($sql); 
			$wpdb->query($sql);
		}			
		
		function DeletePriceByPriceID($ID)
		{
			return $this->DeletePrice($ID, 'priceID');
		}			
		
		function DeletePriceByPerfID($ID)
		{
			return $this->DeletePrice($ID, 'perfID');
		}			
		
		private function DeletePrice($ID, $IDfield)
		{
			global $wpdb;
			
			$sql  = 'DELETE FROM '.STAGESHOW_PRICES_TABLE;
			$sql .= ' WHERE '.STAGESHOW_PRICES_TABLE.".$IDfield=$ID";
			$this->ShowSQL($sql); 
			$wpdb->query($sql);
		}			
		
		// Add Sale - Address details are optional
		function AddSale($SaleDateTime, $PayerName, $PayerEmail, $salePrice, $Txnid, $Status, $salePPName = '', $salePPStreet = '', $salePPCity = '', $salePPState = '', $salePPZip = '', $salePPCountry = '')
		{
			global $wpdb;
			
			$sql  = 'INSERT INTO '.STAGESHOW_SALES_TABLE.'(saleDateTime, saleName, saleEMail, salePaid, saleTxnId, saleStatus, salePPName, salePPStreet, salePPCity, salePPState, salePPZip, salePPCountry)';
			$sql .= ' VALUES("'.$SaleDateTime.'", "'.$PayerName.'", "'.$PayerEmail.'", "'.$salePrice.'", "'.$Txnid.'", "'.$Status.'", "'.$salePPName.'", "'.$salePPStreet.'", "'.$salePPCity.'", "'.$salePPState.'", "'.$salePPZip.'", "'.$salePPCountry.'")';
			$this->ShowSQL($sql); 
			$wpdb->query($sql);
			$saleID = mysql_insert_id();
	
			return $saleID;
		}			
		
		function UpdateSaleStatus($Txn_id, $Payment_status)
		{
			global $wpdb;
			
			$sql  = 'UPDATE '.STAGESHOW_SALES_TABLE;
			$sql .= ' SET saleStatus="'.$Payment_status.'"';		
			$sql .= ' WHERE saleTxnId="'.$Txn_id.'"';							
			$this->ShowSQL($sql); 
			
			$wpdb->query($sql);			
		}
		
		function DeleteSale($SaleId)
		{
			global $wpdb;
			
			// Delete a show entry
			$sql  = 'DELETE FROM '.STAGESHOW_SALES_TABLE;
			$sql .= ' WHERE '.STAGESHOW_SALES_TABLE.".saleID=$SaleId";
			$this->ShowSQL($sql); 
			$wpdb->query($sql);
				
			// Delete a show entry
			$sql  = 'DELETE FROM '.STAGESHOW_TICKETS_TABLE;
			$sql .= ' WHERE '.STAGESHOW_TICKETS_TABLE.".saleID=$SaleId";
			$this->ShowSQL($sql); 
			$wpdb->query($sql);
		}			
		
		function DeleteSales($salesList)
		{
			global $wpdb;
			
			// Delete a show entry
			$sql  = 'DELETE FROM '.STAGESHOW_SALES_TABLE;
			$sql .= ' WHERE '.STAGESHOW_SALES_TABLE.".saleID IN ($salesList)";
			$this->ShowSQL($sql); 
			$wpdb->query($sql);
				
			// Delete a show entry
			$sql  = 'DELETE FROM '.STAGESHOW_TICKETS_TABLE;
			$sql .= ' WHERE '.STAGESHOW_TICKETS_TABLE.".saleID IN ($salesList)";
			$this->ShowSQL($sql); 
			$wpdb->query($sql);
		}
		
		function GetOrphanedSales()
		{
			// This query gets a list of all sales where all the referenced performances have been deleted
			$sql  = 'SELECT '.STAGESHOW_SALES_TABLE.'.saleID FROM '.STAGESHOW_SALES_TABLE;
			$sql .= ' LEFT JOIN '.STAGESHOW_TICKETS_TABLE.' ON '.STAGESHOW_SALES_TABLE.'.saleID='.STAGESHOW_TICKETS_TABLE.'.saleID';
			$sql .= ' LEFT JOIN '.STAGESHOW_PRICES_TABLE.' ON '.STAGESHOW_PRICES_TABLE.'.priceID='.STAGESHOW_TICKETS_TABLE.'.priceID';
			$sql .= ' LEFT JOIN '.STAGESHOW_PERFORMANCES_TABLE.' ON '.STAGESHOW_PERFORMANCES_TABLE.'.perfID='.STAGESHOW_PRICES_TABLE.'.perfID';
			$sql .= ' GROUP BY '.STAGESHOW_SALES_TABLE.'.saleID';
			$sql .= ' HAVING COUNT('.STAGESHOW_PERFORMANCES_TABLE.'.perfID)=0';
			
			$this->ShowSQL($sql); 
			$salesList = $this->get_results($sql);

			if (count($salesList) == 0)
				return'';
			
			$salesIDs = '0';
			foreach ($salesList as $saleEntry)
			{
				$salesIDs .= ','.$saleEntry->saleID;
			}
			//echo "<br>\nsalesIDs: $salesIDs<br>\n";
				
			return $salesIDs;
		}
		
		function DeleteOrphanedSales()
		{
			$orphanedSales = $this->GetOrphanedSales();
			if ($orphanedSales != '') $this->DeleteSales($orphanedSales);
		}
		
		function GetTxnStatus($Txnid)
		{
			global $wpdb;
			
			$sql = 'SELECT saleStatus FROM '.STAGESHOW_SALES_TABLE.' WHERE saleTxnId="'.$Txnid.'"';
			$this->ShowSQL($sql); 
			$txnEntries = $this->get_results($sql);
			
			if (count($txnEntries) == 0) 
				return '';
			
			return $txnEntries[0]->saleStatus;
		}
		

		function AddTicket($saleID, $priceID, $itemName, $ticketType, $qty)
		{
			global $wpdb;
			
			$sql  = 'INSERT INTO '.STAGESHOW_TICKETS_TABLE.'(saleID, priceID, ticketName, ticketType, ticketQty)';
			$sql .= ' VALUES('.$saleID.', '.$priceID.', "'.$itemName.'", "'.$ticketType.'", "'.$qty.'")';
			$this->ShowSQL($sql); 
			$wpdb->query($sql);
			$ticketID = mysql_insert_id();
	
			return $ticketID;
		}			
		
		function LogSale($txdDate)
		{
			$ourOptions = get_option(STAGESHOW_OPTIONS_NAME);
			
			$Txnid = HTTPParam('txn_id');
			$PayerName = HTTPParam('first_name') . ' ' . HTTPParam('last_name');
			$PayerEmail = HTTPParam('payer_email');
			$SaleStatus = HTTPParam('payment_status');
			$salePrice = HTTPParam('mc_gross');

			$salePPName = HTTPParam('address_name');
			$salePPStreet = HTTPParam('address_street');
			$salePPCity = HTTPParam('address_city');
			$salePPState = HTTPParam('address_state');
			$salePPZip = HTTPParam('address_zip');
			$salePPCountry = HTTPParam('address_country');

			// Log sale to Database
			$saleID = $this->AddSale(
				$txdDate, 
				$PayerName, 
				$PayerEmail, 
				$salePrice, 
				$Txnid,
				$SaleStatus, 
				$salePPName, 
				$salePPStreet, 
				$salePPCity, 
				$salePPState, 
				$salePPZip, 
				$salePPCountry);
		  		  
			$itemNo = 1;
			$lineNo = 1;
			While (true)
			{
				$itemID = HTTPParam('item_number' . $itemNo);
				if (strlen($itemID) == 0)
				{
					break;
				}

				$itemName = HTTPParam('item_name' . $itemNo);
				$itemRef = HTTPParam('item_number' . $itemNo);
				$ticketType = HTTPParam('option_selection1_' . $itemNo);
				$qty = HTTPParam('quantity' . $itemNo);

				if ($qty > 0)
				{
					// itemRef format: {showID}-{perfID}
					$itemRefs = explode('-', $itemRef);
					$showID = $itemRefs[0];
					$perfID = $itemRefs[1];
			    
					// Find PriceID from Database	    
					$priceEntries = $this->GetPricesList($showID, $perfID, STAGESHOW_PRICES_TABLE.'.priceType="'.$ticketType.'"');
					if (count($priceEntries) > 0) 
						$priceID = $priceEntries[0]->priceID;
					else
						$priceID = 0;
					
					AddToLog('---------------------------------------------');
					AddToLog('Line ' . $lineNo);
					AddToLog('Item Name:    ' . $itemName);			// itemName = showName + showDateTime
					AddToLog('Item Ref:     ' . $itemRef);			// itemRef = showID + perfID
					AddToLog('Ticket Type:  ' . $ticketType);		// ticketType = priceType
					AddToLog('Quantity:     ' . $qty);
			    
					// Log ticket to Database
					$ticketID = $this->AddTicket($saleID, $priceID, $itemName, $ticketType, $qty);
			    
					AddToLog('showID:' . $showID. '  perfID:' . $perfID. '  priceID:' . $priceID. '  ticketID:' . $ticketID);
			    
					$lineNo++;
				} // End of if ($qty > 0)
				$itemNo++;
			}
		  
			return $saleID;
		}

		function ReadTemplateFile($Filepath)
		{
			$hfile = fopen($Filepath,"r");
			if ($hfile != 0)
			{
				$fileLen = filesize($Filepath);
				$fileContents = fread($hfile, $fileLen);
				fclose($hfile);
			}
			else
			{
				echo "Error reading $Filepath<br>\n";
				//echo "Error was $php_errormsg<br>\n";
				$fileContents = '';
			}

			return $fileContents;
		}

		function LogToFile($Filepath, $LogLine, $OpenMode)
		{
			// Use global values for XXX_OpenMode
			global $ForReading;
			global $ForWriting;
			global $ForAppending;
			
			// Create a filesystem object
			if ($OpenMode == $ForAppending)
			{
				$logFile = fopen($Filepath,"ab");
			}
			else
			{
				$logFile = fopen($Filepath,"wb");
			}

			// Write log entry
			if ($logFile != 0)
			{
				fwrite($logFile, $LogLine, strlen($LogLine));
				fclose($logFile);

				$rtnStatus = true;
			}
			else
			{
				echo "Error writing to $Filepath<br>\n";
				//echo "Error was $php_errormsg<br>\n";
				$rtnStatus = false;
			}

			return $rtnStatus;
		}

		function GetURL($optionURL)
		{
			// If URL contains a : treat is as an absolute URL
			if (!strpos($optionURL, ':'))
				return get_site_url().'/'.$optionURL;
			else
				return $optionURL;
		}
		
		function AddBoxOfficeFields($currOptions, $BoxOfficeTemplate, $saleDetails, $ticketDetails)
		{
			$AddBoxOfficeFields = $BoxOfficeTemplate;
			
			$AddBoxOfficeFields = str_replace('[saleDateTime]', $saleDetails->saleDateTime, $AddBoxOfficeFields);
			$AddBoxOfficeFields = str_replace('[saleName]', $saleDetails->saleName, $AddBoxOfficeFields);
			$AddBoxOfficeFields = str_replace('[saleBoxOffice]', $saleDetails->saleBoxOffice, $AddBoxOfficeFields);
			$AddBoxOfficeFields = str_replace('[salePaid]', $saleDetails->salePaid, $AddBoxOfficeFields);
			$AddBoxOfficeFields = str_replace('[saleTxnId]', $saleDetails->saleTxnId, $AddBoxOfficeFields);
			$AddBoxOfficeFields = str_replace('[saleStatus]', $saleDetails->saleStatus, $AddBoxOfficeFields);

			return $AddBoxOfficeFields;
		}
		
		function AddEMailFields($currOptions, $EMailTemplate, $saleDetails, $ticketDetails)
		{
			$AddEMailFields = $EMailTemplate;
			
			$AddEMailFields = str_replace('[saleDateTime]', $saleDetails->saleDateTime, $AddEMailFields);
			$AddEMailFields = str_replace('[saleName]', $saleDetails->saleName, $AddEMailFields);
			$AddEMailFields = str_replace('[saleEMail]', $saleDetails->saleEMail, $AddEMailFields);
			$AddEMailFields = str_replace('[salePaid]', $saleDetails->salePaid, $AddEMailFields);
			$AddEMailFields = str_replace('[saleTxnId]', $saleDetails->saleTxnId, $AddEMailFields);
			$AddEMailFields = str_replace('[saleStatus]', $saleDetails->saleStatus, $AddEMailFields);

			$AddEMailFields = str_replace('[salePPName]', $saleDetails->salePPName, $AddEMailFields);
			$AddEMailFields = str_replace('[salePPStreet]', $saleDetails->salePPStreet, $AddEMailFields);
			$AddEMailFields = str_replace('[salePPCity]', $saleDetails->salePPCity, $AddEMailFields);
			$AddEMailFields = str_replace('[salePPState]', $saleDetails->salePPState, $AddEMailFields);
			$AddEMailFields = str_replace('[salePPZip]', $saleDetails->salePPZip, $AddEMailFields);
			$AddEMailFields = str_replace('[salePPCountry]', $saleDetails->salePPCountry, $AddEMailFields);

			$AddEMailFields = str_replace('[ticketName]', $ticketDetails->ticketName, $AddEMailFields);
			$AddEMailFields = str_replace('[ticketType]', $ticketDetails->ticketType, $AddEMailFields);
			$AddEMailFields = str_replace('[ticketQty]', $ticketDetails->ticketQty, $AddEMailFields);
			$AddEMailFields = str_replace('[ticketSeat]', $ticketDetails->ticketSeat, $AddEMailFields);
			
			$AddEMailFields = str_replace('[organisation]', $currOptions['OrganisationID'], $AddEMailFields);
			$AddEMailFields = str_replace('[adminEMail]', $currOptions['AdminEMail'], $AddEMailFields);
			
			$AddEMailFields = str_replace('[url]', get_option('siteurl'), $AddEMailFields);
			
			return $AddEMailFields;
		}
		
		function EMailSale($saleID, $EMailTo = '')
		{
			global $stageShowDBaseObj;
			
			$ourOptions = get_option(STAGESHOW_OPTIONS_NAME);
		
			// Get sale	and ticket details
			$salesList = $this->GetSalesListBySaleID($saleID);
			if (count($salesList) < 1) 
				return 'salesList Empty';
			$saleDetails = $salesList[0];
			
			$ticketDetails = $this->GetTicketsList($saleID);
			if (count($ticketDetails) < 1) 
				return 'ticketDetails Empty';
		
			$filePath = STAGESHOW_FILE_PATH.$ourOptions['EMailTemplatePath'];			
			$mailTemplate = $this->ReadTemplateFile($filePath);
			if (strlen($mailTemplate) == 0)
				return "EMail Template Not Found ($filePath)";
				
			$bookingConfirmation = '';
			
			// Find the line with the open php entry then find the end of the line
			$posnPHP = stripos($mailTemplate, '<?php');
			if ($posnPHP !== false) $posnPHP = strpos($mailTemplate, "\n", $posnPHP);
			if ($posnPHP !== false) $posnEOL = strpos($mailTemplate, "\n", $posnPHP+1);
			if (($posnPHP !== false) && ($posnEOL !== false)) 
			{
				$EMailSubject = $this->AddEMailFields($ourOptions, substr($mailTemplate, $posnPHP, $posnEOL-$posnPHP), $saleDetails, $ticketDetails[0]);
				$mailTemplate = substr($mailTemplate, $posnEOL);
			}
			
			// Find the line with the close php entry then find the start of the line
			$posnPHP = stripos($mailTemplate, '?>');
if ($posnPHP !== false) $posnPHP = strrpos(substr($mailTemplate, 0, $posnPHP), "\n");
if ($posnPHP !== false) $mailTemplate = substr($mailTemplate, 0, $posnPHP);

//
while (true)
{
$loopStart = stripos($mailTemplate, '[startloop]');
$loopEnd = stripos($mailTemplate, '[endloop]');

if (($loopStart === false) || ($loopEnd === false))
break;

$section = substr($mailTemplate, 0, $loopStart);
$bookingConfirmation .= $this->AddEMailFields($ourOptions, $section, $saleDetails, $ticketDetails[0]);

$loopStart += strlen('[startloop]');
$loopLen = $loopEnd - $loopStart;

foreach($ticketDetails as $ticket)
{
$section = substr($mailTemplate, $loopStart, $loopLen);
$bookingConfirmation .= $this->AddEMailFields($ourOptions, $section, $saleDetails, $ticket);
}

$loopEnd += strlen('[endloop]');
$mailTemplate = substr($mailTemplate, $loopEnd);
}

// Process the rest of the mail template
$bookingConfirmation .= $this->AddEMailFields($ourOptions, $mailTemplate, $saleDetails, $ticketDetails[0]);

// Get email address and organisation name from settings
$EMailFrom = $this->GetEmail($ourOptions);

if (strlen($EMailTo) == 0) $EMailTo = $saleDetails->saleEMail;

$stageShowDBaseObj->sendMail($EMailTo, $EMailFrom, $EMailSubject, $bookingConfirmation);

echo '<div id="message" class="updated"><p>'.__('EMail Sent to', STAGESHOW_DOMAIN_NAME).' '.$EMailTo.'</p></div>';
			
			return 'OK';
		}
		
		function echoHTML($html)
		{
			$search = array ("'<br>[\s]*\n'i",				
											 "'<p>[\s]*\n'i",					
											 "'<br>'i",								// End of line
											 "'<p>'i",								// Paragraph
											 "'<'i",									// Less than
											 "'>'i",									// Less than
											 "'\n'i");									// Greater than

			$replace = array ("<br>",
												"<p>",
												"<br>\n",
												"<p>\n",
												"&lt;",								
												"&gt;",								
												"<br>\n");

			return preg_replace($search, $replace, $html);
		}
		
		function sendMail($to, $from, $subject, $content, $content2 = '', $headers = '')
		{
			$BccEMail = $this->adminOptions['SentCopyEMail'];
			
			// Define the email headers - separated with \r\n
			if (strlen($headers) > 0) $headers .= "\r\n";
			$headers .= "From: $from";	
				
			// Bcc emails to Admin Email	
      if (strlen($BccEMail)) $headers .= "\r\nBcc: $BccEMail";
			$headers .= "\r\nReply-To: $from";	
				
			if ($this->adminOptions['Dev_ShowEMailMsgs'])
			{
				echo "Headers:<br>\n".$this->echoHTML($headers)."<br>\n<br>\n";
				echo "Message:<br>\n".$this->echoHTML($content)."<br>\n<br>\n";
			}
						
			//send the email
			wp_mail($to, $subject, $content, $headers);
		}
		
    function ShowSQL($sql)
    {
			if ($this->adminOptions['Dev_ShowSQL'] == 1) echo "$sql<br>\n"; 
		}
		
	}
}

if (!isset($stageShowDBaseObj) && class_exists('StageShowDBaseClass')) 
{
	global $stageShowDBaseObj;				
	$stageShowDBaseObj = new StageShowDBaseClass();
}


?>