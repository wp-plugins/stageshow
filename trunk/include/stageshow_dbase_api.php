<?php
/* 
Description: StageShow Plugin Database Access functions
 
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

if (!defined('STAGESHOW_DBASE_CLASS'))
	define('STAGESHOW_DBASE_CLASS', 'StageShowDBaseClass');
	
include STAGESHOW_INCLUDE_PATH.'mjslib_paypaladmin.php';   

if (!class_exists('StageShowSettingsAdminClass'))
{
	class StageShowSettingsAdminClass extends PayPalSettingsAdminClass // Define class
	{
		function __construct($settingsOpts = array())
		{
			$controlsOpts = array
			(
				array('Label' => 'Organisation ID',       'Id' => 'OrganisationID',		'Type' => MJSLibTableClass::TABLEENTRY_TEXT, 'Len' => STAGESHOW_ORGANISATIONID_TEXTLEN, 'Size' => 60, ),				
				array('Label' => 'StageShow Sales EMail', 'Id' => 'AdminEMail',				'Type' => MJSLibTableClass::TABLEENTRY_TEXT, 'Len' => STAGESHOW_ADMINMAIL_TEXTLEN,      'Size' => STAGESHOW_ADMINMAIL_EDITLEN, ),
				array('Label' => 'Bcc EMails to WP Admin','Id' => 'BccEMailsToAdmin',	'Type' => MJSLibTableClass::TABLEENTRY_CHECKBOX,   'Text' => 'Send EMail confirmation to Administrator' ),
				array('Label' => 'Currency Symbol',				'Id' => 'UseCurrencySymbol','Type' => MJSLibTableClass::TABLEENTRY_CHECKBOX,   'Text' => 'Include in Box Office Output' ),
				array('Label' => 'Items per Page',        'Id' => 'PageLength',				'Type' => MJSLibTableClass::TABLEENTRY_TEXT, 'Len' => 3, 'Default' => MJSLIB_EVENTS_PER_PAGE),
				array('Label' => 'Max Ticket Qty',        'Id' => 'MaxTicketQty',			'Type' => MJSLibTableClass::TABLEENTRY_TEXT, 'Len' => 2, 'Default' => STAGESHOW_MAXTICKETCOUNT),
			);
			$settings['StageShow Settings'] = $controlsOpts;
			
			$settings = $this->MergeSettings($settings, $settingsOpts);
			
			parent::__construct($settings);		
		}
	}
}

include 'mjslib_sales_dbase_api.php';      

if (!class_exists('StageShowDBaseClass')) 
{
	// Set the DB tables names
	global $wpdb;
	
	define('STAGESHOW_TABLE_PREFIX', $wpdb->prefix.'sshow_');
	define('STAGESHOW_PERFORMANCES_TABLE', STAGESHOW_TABLE_PREFIX.'perfs');
	define('STAGESHOW_PRICES_TABLE', STAGESHOW_TABLE_PREFIX.'prices');
	define('STAGESHOW_SALES_TABLE', STAGESHOW_TABLE_PREFIX.'sales');
	define('STAGESHOW_TICKETS_TABLE', STAGESHOW_TABLE_PREFIX.'tickets');

	if (!defined('PAYPAL_APILIB_DEFAULT_LOGOIMAGE_URL'))
		define('PAYPAL_APILIB_DEFAULT_LOGOIMAGE_URL', STAGESHOW_IMAGES_URL.'StageShowLogo.jpg');
	if (!defined('PAYPAL_APILIB_DEFAULT_HEADERIMAGE_URL'))
		define('PAYPAL_APILIB_DEFAULT_HEADERIMAGE_URL', STAGESHOW_IMAGES_URL.'StageShowHeader.gif');
	
	define('STAGESHOW_DATETIME_TEXTLEN', 19);
	
	define('STAGESHOW_SHOWNAME_TEXTLEN', 80);
	define('STAGESHOW_PERFREF_TEXTLEN', 16);
	define('STAGESHOW_PRICETYPE_TEXTLEN', 10);
	define('STAGESHOW_PRICEREF_TEXTLEN', 20);
	define('STAGESHOW_TICKETNAME_TEXTLEN', 110);
	define('STAGESHOW_TICKETTYPE_TEXTLEN', 32);
	define('STAGESHOW_TICKETSEAT_TEXTLEN', 10);
		
	define('STAGESHOW_PPLOGIN_USER_TEXTLEN', 127);
	define('STAGESHOW_PPLOGIN_PWD_TEXTLEN', 65);
	define('STAGESHOW_PPLOGIN_SIG_TEXTLEN', 65);
	define('STAGESHOW_PPLOGIN_EMAIL_TEXTLEN', 65);
	
	define('STAGESHOW_PPLOGIN_EDITLEN', 70);
	
	define('STAGESHOW_PPBUTTONID_TEXTLEN',16);
	define('STAGESHOW_ACTIVESTATE_TEXTLEN',10);

	define('STAGESHOW_TICKETNAME_DIVIDER', ' - ');
	
  class StageShowDBaseClass extends MJSLibSalesDBaseClass // Define class
  {
		const STAGESHOW_DATE_FORMAT = 'Y-m-d';
		
		function __construct() { //constructor		
			// Call base constructor
			$opts = array (
				'PluginRootFilePath' => STAGESHOW_PLUGIN_FILE,
				'DownloadFilePath'   => '/wp-content/plugins/stageshow/stageshow_download.php',
				'SalesTableName'     => STAGESHOW_SALES_TABLE,
				'OrdersTableName'    => STAGESHOW_TICKETS_TABLE,
				'CfgOptionsID'       => STAGESHOW_OPTIONS_NAME,
			);			
			
			parent::__construct($opts);
			
			$this->setPayPalCredentials(STAGESHOW_PAYPAL_IPN_NOTIFY_URL);
		}

    function activate()
    {
      global $wpdb;
      
			// Add DB Tables
			$this->createDB();
			
			// Add administrator capabilities
			$adminRole = get_role('administrator');
			
			// Add Capabilities for StageShow 
			if ( !empty($adminRole) ) 
			{
				$adminRole->add_cap(STAGESHOW_CAPABILITY_SALESUSER);
				$adminRole->add_cap(STAGESHOW_CAPABILITY_ADMINUSER);
				$adminRole->add_cap(STAGESHOW_CAPABILITY_SETUPUSER);
				$adminRole->add_cap(STAGESHOW_CAPABILITY_DEVUSER);
			}
			
			$this->GetLatestNews();
		}

    function GetDefaultOptions()
    {
			$defOptions = array(
		    'EMailTemplatePath' => STAGESHOW_ACTIVATE_EMAIL_TEMPLATE_PATH,
			);
			
			return $defOptions;
		}

		function DeleteCapability($capID)
		{
			// TODO - DeleteCapability doesn't work - Fix it!
			if (!isset($wp_roles)) {
				$wp_roles = new WP_Roles();
				$wp_roles->use_db = true;
			}
			
			// Get all roles
			global $wp_roles;
			$roleIDs = $wp_roles->get_names();
 
			foreach ($roleIDs as $roleID) 
			{
				$wp_roles->remove_cap($roleID, $capID) ;
			}
		}
			
    function uninstall()
    {
      global $wpdb;
      
			parent::uninstall();
			
      $wpdb->query('DROP TABLE IF EXISTS '.STAGESHOW_PERFORMANCES_TABLE);      
      $wpdb->query('DROP TABLE IF EXISTS '.STAGESHOW_PRICES_TABLE);      
      $wpdb->query('DROP TABLE IF EXISTS '.STAGESHOW_TICKETS_TABLE);  
						
			$this->DeleteCapability(STAGESHOW_CAPABILITY_SALESUSER);
			$this->DeleteCapability(STAGESHOW_CAPABILITY_ADMINUSER);
			$this->DeleteCapability(STAGESHOW_CAPABILITY_SETUPUSER);
			$this->DeleteCapability(STAGESHOW_CAPABILITY_DEVUSER);
    }

		//Returns an array of admin options
		function getOptions($childOptions = array())
		{
			// Initialise settings array with default values
			$ourOptions = array(        
        'loaded' => true,
        'showName' => '',
        
				'SetupUserRole' => STAGESHOW_DEFAULT_SETUPUSER,
        'AuthTxnEMail' => '',                
        
				'DeleteOrphans' => false,
				
        'SLen' => 0,                
        'PLen' => 4,
        
        'MaxTicketQty' => STAGESHOW_MAXTICKETCOUNT,
        
        'LatestNews' => '',
        'NewsUpdateTime' => '',
        
        'Unused_EndOfList' => ''
			);
				
			$ourOptions = array_merge($ourOptions, $childOptions);
			
			// Get current values from MySQL
			$currOptions = parent::getOptions($ourOptions);
			
			// Check for Upgrading from separate settings for Live and Test API Settings 
			if (isset($currOptions['PayPalAPITestUser']))
			{
				// Update from Ver 0.9.3 or earlier setup
				
				if ($currOptions['PayPalEnv'] == 'sandbox')
				{
					$currOptions['PayPalAPIUser'] = $currOptions['PayPalAPITestUser'];
					$currOptions['PayPalAPISig']  = $currOptions['PayPalAPITestSig'];
					$currOptions['PayPalAPIPwd'] = $currOptions['PayPalAPITestPwd'];
					$currOptions['PayPalAPIEMail'] = $currOptions['PayPalAPITestEMail'];
					
					$this->renameColumn(STAGESHOW_PERFORMANCES_TABLE, 'perfPayPalTESTButtonID', 'perfPayPalButtonID');
					
					// Delete any buttons with perfPayPalLIVEButtonID set
					$this->DeleteHostedButtons('LIVE');
					
					$this->deleteColumn(STAGESHOW_PERFORMANCES_TABLE, 'perfPayPalLIVEButtonID');
				}
				else
				{
					$currOptions['PayPalAPIUser'] = $currOptions['PayPalAPILiveUser'];
					$currOptions['PayPalAPISig']  = $currOptions['PayPalAPILiveSig'];
					$currOptions['PayPalAPIPwd'] = $currOptions['PayPalAPILivePwd'];
					$currOptions['PayPalAPIEMail'] = $currOptions['PayPalAPILiveEMail'];
					
					$this->renameColumn(STAGESHOW_PERFORMANCES_TABLE, 'perfPayPalLIVEButtonID', 'perfPayPalButtonID');
					
					// Delete any buttons with perfPayPalTESTButtonID set
					$this->DeleteHostedButtons('TEST');
					
					$this->deleteColumn(STAGESHOW_PERFORMANCES_TABLE, 'perfPayPalTESTButtonID');					
				}

				unset($currOptions['PayPalAPILiveUser']);
				unset($currOptions['PayPalAPILiveSig']);
				unset($currOptions['PayPalAPILivePwd']);
				unset($currOptions['PayPalAPILiveEMail']);
				
				unset($currOptions['PayPalAPITestUser']);
				unset($currOptions['PayPalAPITestSig']);
				unset($currOptions['PayPalAPITestPwd']);
				unset($currOptions['PayPalAPITestEMail']);
			}
			
			if ($currOptions['SetupUserRole'] == '') 
				$currOptions['SetupUserRole'] = STAGESHOW_DEFAULT_SETUPUSER;
				
			$this->saveOptions($currOptions);
			
			return $currOptions;
		}
    
		function DeleteHostedButtons($buttonType)
		{
			$sql = 'SELECT perfPayPal'.$buttonType.'ButtonID FROM '.STAGESHOW_PERFORMANCES_TABLE;
			$buttonList = $this->get_results($sql);
			foreach ($buttonList as $buttonId)
				$this->payPalAPIObj->DeleteButton($buttonId);						
		}
		
		function createDB($dropTable = false)
		{
			parent::createDB($dropTable);

      $ticketNameLen = STAGESHOW_SHOWNAME_TEXTLEN + strlen(STAGESHOW_TICKETNAME_DIVIDER) + STAGESHOW_DATETIME_TEXTLEN;

			$table_name = $this->opts['OrdersTableName'];
			{
				$sql = "CREATE TABLE ".$table_name.' ( 
					ticketID INT UNSIGNED NOT NULL AUTO_INCREMENT,
					saleID INT UNSIGNED NOT NULL,
					priceID INT UNSIGNED NOT NULL,
					ticketName VARCHAR('.$ticketNameLen.') NOT NULL DEFAULT "",
					ticketType VARCHAR('.STAGESHOW_PRICETYPE_TEXTLEN.') NOT NULL DEFAULT "",
					ticketQty INT NOT NULL,
					ticketSeat VARCHAR('.STAGESHOW_TICKETSEAT_TEXTLEN.'),
					UNIQUE KEY ticketID (ticketID)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;';

				//excecute the query
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				$this->ShowSQL($sql);
				dbDelta($sql);
			}

			$table_name = STAGESHOW_PERFORMANCES_TABLE;
			{
				$sql = "CREATE TABLE ".$table_name.' ( 
					perfID INT UNSIGNED NOT NULL AUTO_INCREMENT,
					showID INT UNSIGNED NOT NULL,
					perfState VARCHAR('.STAGESHOW_ACTIVESTATE_TEXTLEN.'),
					perfDateTime DATETIME NOT NULL,
					perfRef VARCHAR('.STAGESHOW_PERFREF_TEXTLEN.') NOT NULL,
					perfSeats INT NOT NULL,
					perfPayPalButtonID VARCHAR('.STAGESHOW_PPBUTTONID_TEXTLEN.'),
					perfOpens DATETIME,
					perfExpires DATETIME,				
					perfNote TEXT,
					perfNotePosn VARCHAR(6),
					UNIQUE KEY perfID (perfID)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;';

				//excecute the query
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				$this->ShowSQL($sql);
				dbDelta($sql);
			}

			$table_name = STAGESHOW_PRICES_TABLE;
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
				$this->ShowSQL($sql);
				dbDelta($sql);
			}

    }
		
		function GetSettingsObj()
		{
			return new StageShowSettingsAdminClass();	
		}
    
		function DBField($fieldName)
		{
			switch($fieldName)
			{
				case 'stockID':	return 'priceID';
				case 'orderQty':	return 'ticketQty';
			}
		}
		
		function CreateSample()
		{
      $showName1 = "The Wordpress Show";
      
      // Sample dates to reflect current date/time
      $showTime1 = date(self::STAGESHOW_DATE_FORMAT, strtotime("-1 days"))." 20:00:00";
      $showTime2 = date(self::STAGESHOW_DATE_FORMAT, strtotime("-0 days"))." 20:00:00";
      $showTime3 = date(self::STAGESHOW_DATE_FORMAT, strtotime("+1 days"))." 14:30:00";
      $showTime4 = date(self::STAGESHOW_DATE_FORMAT, strtotime("+1 days"))." 20:00:00";
      
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
				echo '<div id="message" class="error"><p>'.__('Cannot Add Performances', STAGESHOW_DOMAIN_NAME).' - '.$statusMsg.'</p></div>';
				return;
			}
			
	    // Populate prices table
	    $priceID1_A1 = $this->AddPrice($perfID1, 'Adult', '12.50');
	    $priceID1_A2 = $this->AddPrice($perfID2, 'Adult', '5.50');
	    $priceID1_A3 = $this->AddPrice($perfID3, 'Adult', '4.00');
	    $priceID1_A4 = $this->AddPrice($perfID4, 'Adult', '6.00');
	    
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
			$saleID = $this->AddSale(date(self::STAGESHOW_DATE_FORMAT, strtotime("-4 days"))." 17:32:47", 'A.N.Other', $saleEMail, 12.00, 'ABCD1234XX', 'Completed',
								'Andrew Other', '1 The Street', 'Somewhere', 'Bigshire', 'BG1 5AT', 'UK');
			$this->AddSaleItem($saleID, $priceID1_C3, 4);
			$this->AddSaleItem($saleID, $priceID1_A3, 1);
			
			$saleEMail = 'mybrother@someemail.co.uk';
			if (defined('STAGESHOW_SAMPLE_EMAIL'))
				$saleEMail = STAGESHOW_SAMPLE_EMAIL;
			$saleID = $this->AddSale(date(self::STAGESHOW_DATE_FORMAT, strtotime("-2 days"))." 10:14:51", 'M.Y.Brother', $saleEMail, 48.00, '87654321qa', 'Pending',
								'Matt Brother', 'The Bungalow', 'Otherplace', 'Littleshire', 'LI1 9ZZ', 'UK');
			$this->AddSaleItem($saleID, $priceID1_A4, 4);
			
			$timeStamp = time();
			
			if (defined('STAGESHOW_EXTRA_SAMPLE_SALES'))
			{
				// Add a lot of ticket sales
				for ($sampleSaleNo = 1; $sampleSaleNo<=STAGESHOW_EXTRA_SAMPLE_SALES; $sampleSaleNo++)
				{
					$saleDate = date(self::MYSQL_DATETIME_FORMAT, $timeStamp);
					$saleName = 'Sample Buyer'.$sampleSaleNo;
					$saleEMail = 'extrasale'.$sampleSaleNo.'@sample.org.uk';
					
					$saleID = $this->AddSale($saleDate, $saleName, $saleEMail, 12.50, 'TXNID_'.$sampleSaleNo, 'Completed',
										'Almost', 'Anywhere', 'Very Rural', 'Tinyshire', 'TN55 8XX', 'UK');
					$this->AddSaleItem($saleID, $priceID1_A3, 3);
					
					$timeStamp = strtotime("+1 hour +7 seconds", $timeStamp);
				}
			}
		}
		
		function prepareBoxOffice($showID)
		{
			if (isset($_GET['sc']))
			{
				// Output counts
				$results = $this->GetSalesListByShowID($showID);
				echo "<!-- Sh:$showID C:".count($results)." -->\n";	// TODP - Remove debug code ...
			}
		}
		
		function CreateNewPerformance(&$rtnMsg, $showID, $perfDateTime, $perfRef = '', $perfSeats = -1)
		{
			if ($showID <= 0) return 0;
			
			$perfState = '';
			$perfID = 0;
			
			// Get the show name
			$shows = $this->GetShowsList($showID);
			$showName = $shows[0]->showName;
			
			// Create PayPal buttons ....
			$ButtonStatus = $this->payPalAPIObj->CreateButton($hostedButtonID, $showName);
				
			if ($ButtonStatus === PayPalAPIClass::PAYPAL_APILIB_CREATEBUTTON_ERROR)
			{
				// Error creating at least one button ... tidy up and report error
				if ($ButtonStatus === PayPalAPIClass::PAYPAL_APILIB_CREATEBUTTON_OK)
					$this->payPalAPIObj->DeleteButton($hostedButtonID);
						
				$rtnMsg = __('Error Creating PayPal Button(s)', STAGESHOW_DOMAIN_NAME);
			}
			else if ($ButtonStatus === PayPalAPIClass::PAYPAL_APILIB_CREATEBUTTON_NOLOGIN)
			{
				$rtnMsg = __('PayPal Login Settings Invalid', STAGESHOW_DOMAIN_NAME);
			}
			else
			{
				// PayPal button(s) created - Add performance to database					
				// Give performance unique Ref - Check what default reference IDs already exist in database
				$perfID = $this->AddPerformance($showID, $perfState, $perfDateTime, $perfRef, $perfSeats, $hostedButtonID);
				if ($perfID == 0)
					$rtnMsg = __('Performance Reference is not unique', STAGESHOW_DOMAIN_NAME);
				else
					$rtnMsg = __('Settings have been saved', STAGESHOW_DOMAIN_NAME);
			}
			
			return $perfID;
		}
		
    static function FormatDateForDisplay($dateInDB)
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

		function UpdateCartButtons($perfsList)
		{
			$siteurl = get_option('siteurl');
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
								
				$this->payPalAPIObj->UpdateButton($perfEntry->perfPayPalButtonID, $description, $reference, $ticketPrices, $priceIDs);
				$this->payPalAPIObj->UpdateInventory($perfEntry->perfPayPalButtonID, $quantity, $siteurl, $reference);
			}
		}
		
		function GetEmail($ourOptions)
		{
			$ourEmail = '';
			
			// Get from email address from settings
			if (strlen($ourOptions['OrganisationID']) > 0)
				$ourEmail .= ' '.$ourOptions['OrganisationID'];
				
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
			return $this->GetShowsList(0);
		}
		
		function GetShowsList($showID = 0)
		{
			$sqlFilters['derivedJoins'] = true;
			
			$selectFields  = '*';
			$selectFields .= ','.STAGESHOW_PERFORMANCES_TABLE.'.perfID';
			$selectFields .= ','.STAGESHOW_PRICES_TABLE.'.priceID';

			$sqlFilters['showID'] = $showID;
			$sqlFilters['groupBy'] = 'showID';
			$sqlFilters['JoinType'] = 'RIGHT JOIN';
			
			if (isset($sqlFilters['groupBy']))	
			{			
				$totalSalesField = $this->TotalSalesField($sqlFilters);
				if ($totalSalesField != '')
					$selectFields .= ','.$totalSalesField;
			}
			
			$sql = "SELECT $selectFields FROM ".STAGESHOW_PERFORMANCES_TABLE;
			$sql .= $this->GetJoinedTables($sqlFilters, __CLASS__);
			$sql .= " LEFT JOIN ".STAGESHOW_PRICES_TABLE.' ON '.STAGESHOW_PRICES_TABLE.'.perfID='.STAGESHOW_PERFORMANCES_TABLE.'.perfID';
			$sql .= " LEFT JOIN ".STAGESHOW_TICKETS_TABLE.' ON '.STAGESHOW_TICKETS_TABLE.'.priceID='.STAGESHOW_PRICES_TABLE.'.priceID';
			
			// Add SQL filter(s)
			$sql .= $this->GetWhereSQL($sqlFilters);
			$sql .= $this->GetOptsSQL($sqlFilters);
			
			$sql .= ' ORDER BY '.STAGESHOW_PERFORMANCES_TABLE.'.showID, '.STAGESHOW_PERFORMANCES_TABLE.'.perfDateTime';
			
			$this->ShowSQL($sql); 
			
			$results = $this->get_results($sql, $sqlFilters);

			return $results;
		}
		
		function UpdateSettings($result, $tableId, $settingId, $indexId, $index)
		{
			global $wpdb;
			
			$newVal = $_POST[$settingId.$index];
			if ($newVal == $result->$settingId)
				return;
				
			$sql  = 'UPDATE '.$tableId;
			$sql .= ' SET '.$settingId.'="'.$newVal.'"';
			$sql .= ' WHERE '.$indexId.'='.$index;;
			$this->ShowSQL($sql); 

			$wpdb->query($sql);	
		}
		
		function OutputButton($buttonId, $buttonText, $buttonClass = "button-secondary")
		{
			$buttonText = __($buttonText, STAGESHOW_DOMAIN_NAME);
			
			echo "<input class=\"$buttonClass\" type=\"submit\" name=\"$buttonId\" value=\"$buttonText\" />\n";
		}
		
		function IsShowNameUnique($showName)
		{
			return true;
		}
		
		function SetShowActivated($showID, $showState = 'activate')
		{
			// Set Show Activated Flag in Options
			$this->adminOptions['showState'] = $showState;
			$this->saveOptions();
		}
		
		function CanAddShow()
		{		
			// Check if a show is already configured
			if ( (isset($this->adminOptions['showName'])) && (strlen($this->adminOptions['showName']) > 0) )
				return false;
						
			return true;
		}
		 
		function AddShow($showName = '', $showState = 'activate')
		{
			// Check if a show is already configured
			if ( (isset($this->adminOptions['showName'])) && (strlen($this->adminOptions['showName']) > 0) )
				return 0;
				
			if ($showName === '') $showName = 'New Show';
			
			$this->adminOptions['showName'] = $showName;
			$this->saveOptions();
			
     	return 1;
		}
				
		function UpdateShowName($showID, $showName)
		{
			if (!$this->IsShowNameUnique($showName))
				return "ERROR";
				
			if ($showID != 1)	// Only one show supported
				return "ERROR";
				
			// Save Show Name()
			$this->adminOptions['showName'] = $showName;
			$this->saveOptions();
			
			// Show Name Changed ... Updated Any Hosted Buttons
			$perfsList = $this->GetPerformancesListByShowID($showID);
			$this->UpdateCartButtons($perfsList);
													
			return "OK";
		}
		
		function GetShowID($showName)
		{
			if ($showName != $this->adminOptions['showName']) 
				return 0;
			
			return 0;
		}
		
		function CanDeleteShow($showEntry)
		{
			$lastPerfDate = $this->GetLastPerfDateTime($showEntry->showID);

			if (($perfDate = strtotime($lastPerfDate)) === false)
				$canDelete = false;
			else
			{
				$dateDiff = strtotime("now")-$perfDate;
				$canDelete = ($dateDiff > 60*60*24);
			}

			$showSales = $showEntry->totalQty;
			$canDelete |= ($showSales == 0);		
			
			if ($this->getOption('Dev_ShowMiscDebug') == 1) 
			{
				echo "CanDeleteShow(".$showEntry->showID.") returns $canDelete <br>\n";
			}
			
			return $canDelete;		
		}
		
		function get_results($sql, $sqlFilters = array())
		{
			global $wpdb;
      
			$results = $wpdb->get_results($sql);

			// Add Show Name
			if (isset($this->adminOptions['showName']) && ($this->adminOptions['showName'] !== ''))
			{
				if ((count($results) == 0) && isset($sqlFilters['JoinType']) && ($sqlFilters['JoinType'] === 'RIGHT JOIN'))
				{				
					$results[0] = new stdClass();
				}
				
				$addTotalQty = (strpos($sql, 'totalQty') !== false);
			
				for ($i=0; $i<count($results); $i++)
				{
					if (isset($results[$i]->showName))
						break;
							
					$results[$i]->showName = $this->adminOptions['showName'];
					$results[$i]->showID = 1;
					$results[$i]->showState = (isset($this->adminOptions['showState'])) ? $this->adminOptions['showState'] : 'activate';
					
					if ($addTotalQty && !isset($results[$i]->totalQty))	// Check if we need the totalQty field
						$results[$i]->totalQty = 0;
				}
			}
			
			if ($this->getOption('Dev_ShowDBOutput') == 1) 
			{
				echo "<br>Database Results:<br>\n"; 
				for ($i=0; $i<count($results); $i++)
					echo "Array[$i] = ".print_r($results[$i], true)."<br>\n"; 
			}
			
			return $results;
		}
		
		function renameColumn($table_name, $oldColName, $newColName)
		{
      global $wpdb;
			
			$colSpec = $this->getColumnType($table_name, $oldColName);
			if (!isset($colSpec->Field))
				return __("DB Error: $oldColName Column does not exist");
				
			$sql = "ALTER TABLE $table_name CHANGE $oldColName $newColName ".$colSpec->Type;
			if ($colSpec->Null == 'NO')
				$sql .= " NOT NULL";
			if ($colSpec->Default != '')
				$sql .= " DEFAULT = '".$colSpec->Default."'";
			
			$this->ShowSQL($sql); 

			$wpdb->query($sql);	
			return "OK";							
		}
		
		function deleteColumn($table_name, $colName)
		{
      global $wpdb;
			
			$sql = "ALTER TABLE $table_name DROP $colName";
			$this->ShowSQL($sql); 

			$wpdb->query($sql);	
			return "OK";							
		}
		
		function getColumnType($table_name, $colName)
		{
			$sql = "SHOW COLUMNS FROM $table_name WHERE field = '$colName'";
			$this->ShowSQL($sql); 

			$typesArray = $this->get_results($sql);

			return isset($typesArray[0]) ? $typesArray[0] : '';
		}
		
		function GetAllPerformancesList()
		{
			return $this->GetPerformancesList();
		}
				
		function GetPerformancesListByShowID($showID)
		{
			$sqlFilters['showID'] = $showID;
			return $this->GetPerformancesList($sqlFilters);
		}
				
		function GetPerformancesListByPerfID($perfID)
		{
			$sqlFilters['perfID'] = $perfID;
			return $this->GetPerformancesList($sqlFilters);
		}
				
		private function GetPerformancesList($sqlFilters = null)
		{
			$sqlFilters['derivedJoins'] = true;
			
			$selectFields  = '*';
			$selectFields .= ','.STAGESHOW_PERFORMANCES_TABLE.'.perfID';
			$selectFields .= ','.STAGESHOW_PRICES_TABLE.'.priceID';
			
			$sqlFilters['groupBy'] = 'perfID';
			
			if (isset($sqlFilters['groupBy']))	
			{			
				$totalSalesField = $this->TotalSalesField($sqlFilters);
				if ($totalSalesField != '')
					$selectFields .= ','.$totalSalesField;
			}
			
			$sql = "SELECT $selectFields FROM ".STAGESHOW_PERFORMANCES_TABLE;
			$sql .= $this->GetJoinedTables($sqlFilters, __CLASS__);
			$sql .= " LEFT JOIN ".STAGESHOW_PRICES_TABLE.' ON '.STAGESHOW_PRICES_TABLE.'.perfID='.STAGESHOW_PERFORMANCES_TABLE.'.perfID';
			$sql .= " LEFT JOIN ".STAGESHOW_TICKETS_TABLE.' ON '.STAGESHOW_TICKETS_TABLE.'.priceID='.STAGESHOW_PRICES_TABLE.'.priceID';
			
			// Add SQL filter(s)
			$sql .= $this->GetWhereSQL($sqlFilters);
			$sql .= $this->GetOptsSQL($sqlFilters);
			
			$sql .= ' ORDER BY '.STAGESHOW_PERFORMANCES_TABLE.'.showID, '.STAGESHOW_PERFORMANCES_TABLE.'.perfDateTime';
			
			$this->ShowSQL($sql); 
			
			$perfsListArray = $this->get_results($sql);

			return $perfsListArray;
		}
		
		function GetOurButtonsList()
		{
			$sql = "SELECT perfPayPalButtonID FROM ".STAGESHOW_PERFORMANCES_TABLE;
			
			$this->ShowSQL($sql); 
			
			$results = $this->get_results($sql);

			$buttonsListArray = array();			
			for($index=0; $index<count($results); $index++)
				$buttonsListArray[$index] = $results[$index]->perfPayPalButtonID;
				
			return $buttonsListArray;
		}

		function CanDeletePerformance($perfsEntry)
		{
			$perfDateTime = $perfsEntry->perfDateTime;
				
			// Performances can be deleted if there are no tickets sold or 24 hours after start date/time
			if (($perfDate = strtotime($perfDateTime)) === false)
				$canDelete = false;
			else
			{
				$dateDiff = strtotime("now")-$perfDate;
				$canDelete = ($dateDiff > 60*60*24);
			}
			
			$perfSales = $perfsEntry->ticketQty;
			$canDelete |= ($perfSales == 0);
			
			return $canDelete;
		}
		
		function SetPerfActivated($perfID, $perfState = 'activate')
		{
      global $wpdb;
      
			$sqlFilters['perfID'] = $perfID;
				 
			$sql  = 'UPDATE '.STAGESHOW_PERFORMANCES_TABLE;
			$sql .= ' SET perfState="'.$perfState.'"';
			$sql .= $this->GetWhereSQL($sqlFilters);
			$this->ShowSQL($sql); 

			$wpdb->query($sql);	
			return "OK";							
		}
		
		private function GetLastPerfDateTime($showID = 0)
		{
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
			$sql  = 'SELECT COUNT(*) AS MatchCount FROM '.STAGESHOW_PERFORMANCES_TABLE;
			$sql .= ' WHERE '.STAGESHOW_PERFORMANCES_TABLE.'.perfRef="'.$perfRef.'"';
			$this->ShowSQL($sql); 
			
			$perfsCount = $this->get_results($sql);
			return ($perfsCount[0]->MatchCount > 0) ? false : true;
		}
		
		function CanAddPerformance()
		{
			$rtnVal = true;
			
			$sql  = 'SELECT COUNT(*) AS perfLen FROM '.STAGESHOW_PERFORMANCES_TABLE;
			$this->ShowSQL($sql); 
			
			$results = $this->get_results($sql);
			$rtnVal = (($this->adminOptions['PLen']==0) || ($results[0]->perfLen<$this->adminOptions['PLen']));
			
			return $rtnVal;
		}

		function GetAllPlansList()
		{
			return array();
		}
		
		function AddPerformance($showID, $perfState, $perfDateTime, $perfRef, $perfSeats, $perfPayPalButtonID)
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
			
			$sql  = 'INSERT INTO '.STAGESHOW_PERFORMANCES_TABLE.'(showID, perfState, perfDateTime, perfRef, perfSeats, perfPayPalButtonID)';
			$sql .= ' VALUES('.$showID.', "'.$perfState.'", "'.$perfDateTime.'", "'.$perfRef.'", "'.$perfSeats.'", "'.$perfPayPalButtonID.'")';
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
      
			$sqlFilters['perfID'] = $perfID;
				 
			$sql  = 'UPDATE '.STAGESHOW_PERFORMANCES_TABLE;
			$sql .= ' SET '.$sqlSET;
			$sql .= $this->GetWhereSQL($sqlFilters);
			$this->ShowSQL($sql); 

			$wpdb->query($sql);	
			return "OK";							
		}
		
		function GetPricesListByShowID($showID, $activeOnly = false)
		{
			if (!is_numeric($showID))
			{
				$showID = $this->GetShowID($showID);
				if ($showID == 0) 
					return array();
			}
			
			if ($activeOnly)
				$sqlFilters['activePrices'] = true;
				
			$sqlFilters['showID'] = $showID;
			return $this->GetPricesList($sqlFilters);
		}
				
		function GetPricesListByPerfID($perfID)
		{
			$sqlFilters['perfID'] = $perfID;
			return $this->GetPricesList($sqlFilters);
		}
				
		function GetPricesList($sqlFilters)
		{
			$sqlFilters['derivedJoins'] = true;

			$selectFields  = '*';
			if (isset($sqlFilters['saleID']) || isset($sqlFilters['priceID']))
			{
				// Explicitly add joined fields from "base" tables (otherwise values will be NULL if there is no matching JOIN)
				$selectFields .= ', '.$this->opts['SalesTableName'].'.saleID';

				$joinCmd = ' LEFT JOIN ';
			}
			else
				$joinCmd = ' JOIN ';
						
			$sql  = 'SELECT '.$selectFields.' FROM '.STAGESHOW_PRICES_TABLE;
      $sql .= ' '.$joinCmd.STAGESHOW_PERFORMANCES_TABLE.' ON '.STAGESHOW_PERFORMANCES_TABLE.'.perfID='.STAGESHOW_PRICES_TABLE.'.perfID';
			$sql .= $this->GetJoinedTables($sqlFilters, __CLASS__);
			$sql .= $this->GetWhereSQL($sqlFilters);
			
			$sql .= ' ORDER BY '.STAGESHOW_PERFORMANCES_TABLE.'.showID';
			$sql .= ' , '.STAGESHOW_PERFORMANCES_TABLE.'.perfDateTime';			
			$sql .= ' , '.STAGESHOW_PRICES_TABLE.'.priceType';
			
			$this->ShowSQL($sql); 
			
			return $this->get_results($sql);
		}

		function IsPriceTypeUnique($perfID, $priceType)
		{
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

		function DeleteShowByShowID($ID)
		{
			// Get the current name and then clear it
			$rtnVal = $this->adminOptions['showName'];
			$this->adminOptions['showName'] = '';
			$this->saveOptions();

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
		
// ----------------------------------------------------------------------
//
//			Start of CUSTOM SALES functions
//
// ----------------------------------------------------------------------
    
		function GetSalesQtyByShowID($showID)
		{
			$sqlFilters['showID'] = $showID;
			return $this->GetSalesQty($sqlFilters);
		}
				
		function GetSalesQtyByPerfID($perfID)
		{
			$sqlFilters['perfID'] = $perfID;
			return $this->GetSalesQty($sqlFilters);
		}
				
		function GetSalesQtyBySaleID($saleID)
		{
			$sqlFilters['saleID'] = $saleID;
			return $this->GetSalesQty($sqlFilters);
		}
				
		function GetJoinedTables($sqlFilters = null, $classID = '')
		{
			$sqlJoin = '';
			if ($classID != __CLASS__)
				$sqlJoin .= parent::GetJoinedTables($sqlFilters, $classID);
			else if (isset($sqlFilters['derivedJoins']))
				return $sqlJoin;
			
			$joinType = isset($sqlFilters['JoinType']) ? $sqlFilters['JoinType'] : 'JOIN';
			
			// JOIN parent tables
			if (!isset($sqlFilters['PerfsJoined']))
			{
				$sqlJoin .= " $joinType ".STAGESHOW_TICKETS_TABLE.' ON '.STAGESHOW_TICKETS_TABLE.'.saleID='.STAGESHOW_SALES_TABLE.'.saleID';
				$sqlJoin .= " $joinType ".STAGESHOW_PRICES_TABLE.' ON '.STAGESHOW_PRICES_TABLE.'.priceID='.STAGESHOW_TICKETS_TABLE.'.priceID';
				$sqlJoin .= " $joinType ".STAGESHOW_PERFORMANCES_TABLE.' ON '.STAGESHOW_PERFORMANCES_TABLE.'.perfID='.STAGESHOW_PRICES_TABLE.'.perfID';
			}
			
			return $sqlJoin;
		}
		
		function GetWhereSQL($sqlFilters)
		{
			$sqlWhere = parent::GetWhereSQL($sqlFilters);
			$sqlCmd = ($sqlWhere === '') ? ' WHERE ' : ' AND ';
			
			if (isset($sqlFilters['priceID']))
			{
				$sqlWhere .= $sqlCmd.STAGESHOW_TICKETS_TABLE.'.priceID="'.$sqlFilters['priceID'].'"';
				$sqlCmd = ' AND ';
			}
			
			if (isset($sqlFilters['perfID']) && ($sqlFilters['perfID'] > 0))
			{
				$sqlWhere .= $sqlCmd.STAGESHOW_PERFORMANCES_TABLE.'.perfID="'.$sqlFilters['perfID'].'"';
				$sqlCmd = ' AND ';
			}
			
			if (isset($sqlFilters['priceType']))
			{
				$sqlWhere .= $sqlCmd.STAGESHOW_PRICES_TABLE.'.priceType="'.$sqlFilters['priceType'].'"';
				$sqlCmd = ' AND ';
			}
			
			if (isset($sqlFilters['activePrices']))
			{
				$sqlWhere .= $sqlCmd.STAGESHOW_PRICES_TABLE.'.priceValue>"0"';
				$sqlCmd = ' AND ';
			}
			
			return $sqlWhere;
		}
		
		function GetOptsSQL($sqlFilters)
		{
			$sqlOpts = parent::GetOptsSQL($sqlFilters);
			if (isset($sqlFilters['groupBy']))
			{
				switch ($sqlFilters['groupBy'])
				{
					case 'saleID':
						$sqlOpts .= ' GROUP BY '.STAGESHOW_SALES_TABLE.'.saleID';
						break;
						
					case 'showID':
						$sqlOpts .= ' GROUP BY '.STAGESHOW_PERFORMANCES_TABLE.'.showID';
						break;
						
					case 'perfID':
						$sqlOpts .= ' GROUP BY '.STAGESHOW_PERFORMANCES_TABLE.'.perfID';
						break;
						
					case 'priceID':
						$sqlOpts .= ' GROUP BY '.STAGESHOW_PRICES_TABLE.'.priceID';
						break;
						
					default:
						break;
				}
			}
			
			return $sqlOpts;
		}
		
// ----------------------------------------------------------------------
//
//			Start of GENERIC SALES functions
//
// ----------------------------------------------------------------------
    
		function TODO_REMOVE_AddSaleItem($saleID, $stockID, $qty)
		{
			global $wpdb;
			
			$sql  = 'INSERT INTO '.$this->opts['OrdersTableName'].'(saleID, '.$this->DBField('stockID').', '.$this->DBField('orderQty').')';
			$sql .= ' VALUES('.$saleID.', '.$stockID.', "'.$qty.'")';
			$this->ShowSQL($sql); 
			$wpdb->query($sql);
			$orderID = mysql_insert_id();
	
			return $orderID;
		}			
		
		function TODO_REMOVE_UpdateSaleItem($saleID, $priceID, $qty)
		{
			global $wpdb;

			// Delete a show entry
			$sql  = 'UPDATE '.$this->opts['OrdersTableName'];
			$sql .= ' SET ticketQty="'.$qty.'"';
			$sql .= ' WHERE '.$this->opts['OrdersTableName'].".saleID=$saleID";
			$sql .= ' AND   '.$this->opts['OrdersTableName'].".priceID=$priceID";

			$this->ShowSQL($sql); 
			$wpdb->query($sql);
		}
		
		function TODO_REMOVE_DeleteSaleItem($saleID, $priceID)
		{
			global $wpdb;

			// Delete a show entry
			$sql  = 'DELETE FROM '.$this->opts['OrdersTableName'];
			$sql .= ' WHERE '.$this->opts['OrdersTableName'].".saleID=$saleID";
			$sql .= ' AND   '.$this->opts['OrdersTableName'].".priceID=$priceID";

			$this->ShowSQL($sql); 
			$wpdb->query($sql);
		}
		
		function GetSaleStockID($itemRef, $itemOption)
		{
			// itemRef format: {showID}-{perfID}
			$itemRefs = explode('-', $itemRef);
			$sqlFilters['showID'] = $itemRefs[0];
			$sqlFilters['perfID'] = $itemRefs[1];
			$sqlFilters['priceType'] = $itemOption;
					
			$priceEntries = $this->GetPricesList($sqlFilters);
			
			if (count($priceEntries) > 0) 
				$stockID = $priceEntries[0]->priceID;
			else
				$stockID = 0;
			
			return $stockID;
		}
				
		function TotalSalesField($sqlFilters)
		{
			$sql  = ' SUM('.$this->DBField('orderQty').') AS totalQty ';
			return $sql;
		}
		
		function GetSalesListByShowID($showID)
		{
			$sqlFilters['showID']= $showID;
			$sqlFilters['groupBy']= 'saleID';
			return $this->GetSalesList($sqlFilters);
		}
				
		function GetSalesListByPerfID($perfID)
		{
			$sqlFilters['perfID'] = $perfID;
			$sqlFilters['groupBy']= 'saleID';
			return $this->GetSalesList($sqlFilters);
		}
				
		function GetSalesListByPriceID($priceID)
		{
			$sqlFilters['priceID']= $priceID;
			$sqlFilters['groupBy']= 'saleID';
			return $this->GetSalesList($sqlFilters);
		}
				
		function GetAllSalesList($sqlFilters = null)
		{
			$sqlFilters['groupBy'] = 'saleID';
			$sqlFilters['orderBy'] = 'saleID DESC';
			return $this->GetSalesList($sqlFilters);
		}

		function GetAllSalesListBySaleTxnId($saleTxnId)
		{
			$sqlFilters['saleTxnId'] = $saleTxnId;
			return $this->GetSalesList($sqlFilters);
		}
				
		function GetSale($saleID)
		{
			$sqlFilters['saleID'] = $saleID;
			return $this->GetSalesList($sqlFilters);
		}
				
		function AddSaleFields(&$salesListArray)
		{
			for ($i=0; $i<count($salesListArray); $i++)
			{
				$salesListArray[$i]->ticketName = $salesListArray[$i]->showName.' - '.$salesListArray[$i]->perfDateTime;
				$salesListArray[$i]->ticketType = $salesListArray[$i]->priceType;
			}			
		}
		
		function GetPricesListWithSales($saleID)
		{
			$selectFields  = '*';
			
			$sqlFilters['PerfsJoined'] = true;		// Prices table is joined in base query
			
			$sqlWhere = ' WHERE '.STAGESHOW_TICKETS_TABLE.'.saleID = "'.$saleID.'"';
			
			$sql  = 'SELECT '.$selectFields;
			$sql .= ' FROM ( SELECT * FROM '.STAGESHOW_TICKETS_TABLE.$sqlWhere.' ) AS sales';
			$sql .= ' RIGHT JOIN ( SELECT * FROM '.STAGESHOW_PRICES_TABLE.' ) AS prices';
			$sql .= ' ON sales.priceID = prices.priceID';
			$sql .= ' JOIN '.STAGESHOW_PERFORMANCES_TABLE.' ON '.STAGESHOW_PERFORMANCES_TABLE.'.perfID=prices.perfID';			
			$sql .= $this->GetJoinedTables($sqlFilters, __CLASS__);
			$sql .= ' LEFT JOIN '.STAGESHOW_SALES_TABLE.' ON '.STAGESHOW_SALES_TABLE.'.saleID=sales.saleID';
			$sql .= ' ORDER BY '.STAGESHOW_PERFORMANCES_TABLE.'.perfDateTime, prices.priceType';

			$this->ShowSQL($sql); 
			
			$showOutput = $this->getOption('Dev_ShowDBOutput'); 
			$this->adminOptions['Dev_ShowDBOutput'] = '';
			
			$salesListArray = $this->get_results($sql);			
			$this->AddSaleFields($salesListArray);
			
			$this->adminOptions['Dev_ShowDBOutput'] = $showOutput;
			$this->show_results($salesListArray);
								
			return $salesListArray;
		}
		
		function GetOrphanedSales()
		{
			$sqlFilters['JoinType'] = 'LEFT JOIN';
			
			// This query gets a list of all sales where all the referenced performances have been deleted
			$sql  = 'SELECT '.STAGESHOW_SALES_TABLE.'.saleID FROM '.STAGESHOW_SALES_TABLE;
			$sql .= $this->GetJoinedTables($sqlFilters, __CLASS__);
			$sql .= ' GROUP BY '.STAGESHOW_SALES_TABLE.'.saleID';
			$sql .= ' HAVING COUNT('.STAGESHOW_PERFORMANCES_TABLE.'.perfID)=0';
			
			$this->ShowSQL($sql); 
			$salesList = $this->get_results($sql);
			
			return $salesList;
		}
		
		function DeleteSale($saleID)
		{
			global $wpdb;
			
			parent::DeleteSale($saleID);

			// Delete a show entry
			$sql  = 'DELETE FROM '.$this->opts['OrdersTableName'];
			if (is_array($saleID))
			{
				$salesList = '';
				foreach ($saleID as $saleItemID)
				{
					if ($salesList != '') $salesList .= ',';
					$salesList .= $saleItemID->saleID;
				}
				$sql .= ' WHERE '.$this->opts['OrdersTableName'].".saleID IN ($salesList)";
			}
			else
				$sql .= ' WHERE '.$this->opts['OrdersTableName'].".saleID=$saleID";

			$this->ShowSQL($sql); 
			$wpdb->query($sql);
		}			
		
		function DeleteOrphanedSales()
		{
			$orphanedSales = $this->GetOrphanedSales();
			if (count($orphanedSales) > 0) $this->DeleteSale($orphanedSales);
		}
		
// ----------------------------------------------------------------------
//
//			End of SALES functions
//
// ----------------------------------------------------------------------
    
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

		function LogToFile($Filepath, $LogLine, $OpenMode = 0)
		{
			// Use global values for OpenMode
			
			// Create a filesystem object
			if (($OpenMode == self::ForAppending) || ($OpenMode == 0))
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
				$LogLine .= "\n";
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
		
		function GetSalesEMail($currOptions)
		{
			return $currOptions['AdminEMail'];
		}
		
		function AddSalesDetailsEMailFields($currOptions, $EMailTemplate, $saleDetails)
		{
			$EMailTemplate = str_replace('[ticketName]', $saleDetails->ticketName, $EMailTemplate);
			$EMailTemplate = str_replace('[ticketType]', $saleDetails->ticketType, $EMailTemplate);
			$EMailTemplate = str_replace('[ticketQty]', $saleDetails->ticketQty, $EMailTemplate);
			$EMailTemplate = str_replace('[ticketSeat]', $saleDetails->ticketSeat, $EMailTemplate);
			
			return parent::AddSalesDetailsEMailFields($currOptions, $EMailTemplate, $saleDetails);
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
		
// ----------------------------------------------------------------------
//
//			Generic Utilities Function
//
// ----------------------------------------------------------------------
		function GetVersionServerURL($pagename)
		{
			$filename = $pagename.'.php';
			
			if (defined('STAGESHOW_PLUS_UPDATE_SERVER_PATH'))
				$updateCheckURL = STAGESHOW_PLUS_UPDATE_SERVER_PATH;
			else
				$updateCheckURL = $this->get_pluginURI().'/';
			$updateCheckURL .= $filename;

			$updateCheckURL = str_replace('\\', '/', $updateCheckURL);
			$updateCheckURL = str_replace("//$filename", "/$filename", $updateCheckURL);
			
			$updateCheckURL = add_query_arg('email', urlencode($this->adminOptions['AdminEMail']), $updateCheckURL);
			$updateCheckURL = add_query_arg('ver', urlencode($this->get_version()), $updateCheckURL);
			$updateCheckURL = add_query_arg('url', urlencode(get_option('siteurl')), $updateCheckURL);
			
			//echo "updateCheckURL: $updateCheckURL<br>\n";
			return $updateCheckURL;
		}
		    
		function GetHTTPPage($reqURL)
		{
			$rtnVal = '';
			$response = $this->HTTPAction($reqURL);
						
			if ($response['APIStatus'] == 200)
				$rtnVal = $response['APIResponseText'];
			
			//if ($rtnVal == '')
			//	echo "GetHTTPPage($reqURL) Failed<br>\n";
				
			return $rtnVal;
		}

		function CheckIsConfigured()
		{
			$this->GetLatestNews();
			return $this->payPalAPIObj->CheckIsConfigured();
		}
				
		function GetLatestNews()
		{
			$latest = '';
			if (isset($this->adminOptions['LatestNews']))
			{
				$lastUpdate = $this->adminOptions['NewsUpdateTime'];
				$updateInterval = STAGESHOW_NEWS_UPDATE_INTERVAL*24*60*60;
				if ($lastUpdate + $updateInterval > time())
					$latest = $this->adminOptions['LatestNews'];			
			}
			
			if ($latest === '')
			{
				// Get URL of StagsShow News server from Plugin Info
				$updateCheckURL = $this->GetVersionServerURL('news');
				$latest = $this->GetHTTPPage($updateCheckURL);	
				if (strlen($latest) <= 2)
					$latest = '';
					
				$this->adminOptions['LatestNews'] = $latest;
					
				$this->adminOptions['NewsUpdateTime'] = time();
				$this->saveOptions();
				//echo "News Updated<br>\n";
			}
			
			return $latest;
		}
		
	}
}

$stageShowDBaseClass = STAGESHOW_DBASE_CLASS;
if (defined('WP_UNINSTALL_PLUGIN') && !isset($stageShowObj) && $stageShowDBaseClass === 'StageShowDBaseClass') 
{
	global $stageShowObj;
	$stageShowObj = new stdClass();
	$stageShowObj->myDBaseObj = new StageShowDBaseClass();
}


?>