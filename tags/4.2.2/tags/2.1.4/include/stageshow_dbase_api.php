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
	
include 'stageshowlib_sales_dbase_api.php';      

if (!class_exists('StageShowDBaseClass')) 
{
	// Set the DB tables names
	global $wpdb;
	
	define('STAGESHOW_TABLE_PREFIX', $wpdb->prefix.'sshow_');
	define('STAGESHOW_SHOWS_TABLE', STAGESHOW_TABLE_PREFIX.'shows');
	define('STAGESHOW_PERFORMANCES_TABLE', STAGESHOW_TABLE_PREFIX.'perfs');
	define('STAGESHOW_PRICES_TABLE', STAGESHOW_TABLE_PREFIX.'prices');
	define('STAGESHOW_SALES_TABLE', STAGESHOW_TABLE_PREFIX.'sales');
	define('STAGESHOW_TICKETS_TABLE', STAGESHOW_TABLE_PREFIX.'tickets');

	if (!defined('PAYPAL_APILIB_DEFAULT_LOGOIMAGE_FILE'))
		define('PAYPAL_APILIB_DEFAULT_LOGOIMAGE_FILE', 'StageShowLogo.jpg');
	if (!defined('PAYPAL_APILIB_DEFAULT_HEADERIMAGE_FILE'))
		define('PAYPAL_APILIB_DEFAULT_HEADERIMAGE_FILE', 'StageShowHeader.gif');
	
	define('STAGESHOW_DATETIME_TEXTLEN', 19);
	
	define('STAGESHOW_SHOWNAME_TEXTLEN', 80);
	define('STAGESHOW_PERFREF_TEXTLEN', 16);
	define('STAGESHOW_PRICETYPE_TEXTLEN', 10);
	define('STAGESHOW_PRICEVISIBILITY_TEXTLEN', 10);	
	define('STAGESHOW_PLANREF_TEXTLEN', 20);
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

	define('STAGESHOW_STATE_ACTIVE', 'activate');
	define('STAGESHOW_STATE_INACTIVE', 'deactivate');
	define('STAGESHOW_STATE_DELETED', 'deleted');

	define('STAGESHOW_VISIBILITY_PUBLIC', 'public');
	define('STAGESHOW_VISIBILITY_ADMIN', 'admin');

	define('STAGESHOW_SALESTATUS_RESERVED', 'Reserved');

	define('PRICEID1_A1', '12.50');
	define('PRICEID1_A2', '5.50');
	define('PRICEID1_A3', '4.00');
	define('PRICEID1_A4', '6.00');
	define('PRICEID1_C2', '3.00');
	define('PRICEID1_C3', '2.00');
	
	class StageShowDBaseClass extends StageShowLibSalesDBaseClass // Define class
  	{
		const STAGESHOW_DATE_FORMAT = 'Y-m-d';
		
		var $perfJoined = false;
		
		function __construct($caller) //constructor	
		{
			$opts = array (
				'Caller'             => $caller,
				'PluginFolder'       => dirname(plugin_basename(dirname(__FILE__))),
				'DownloadFilePath'   => '/wp-content/plugins/stageshow/stageshow_download.php',
				'SalesTableName'     => STAGESHOW_SALES_TABLE,
				'OrdersTableName'    => STAGESHOW_TICKETS_TABLE,
				'CfgOptionsID'       => STAGESHOW_OPTIONS_NAME,
			);			
			
			// Call base constructor
			parent::__construct($opts);
			
			$this->setPayPalCredentials(STAGESHOW_PAYPAL_IPN_NOTIFY_URL);			
		}
		
		function upgradeDB()
		{
			
			// Call upgradeDB() in base class
			parent::upgradeDB();
			
			// FUNCTIONALITY: DBase - On upgrade ... Add any database fields
			// Add DB Tables
			$this->createDB();
			
			// Remove priceRef field
			if ($this->RemovePriceRefsField())
			{
				// Check that paypal buttons are OK if database changed
				$perfsList = $this->GetAllPerformancesList();
				$this->UpdateCartButtons($perfsList);
			}
					
			// FUNCTIONALITY: DBase - On upgrade ... Add administrator capabilities
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

		function RemovePriceRefsField()
		{
			if (!$this->IfColumnExists(STAGESHOW_PRICES_TABLE, 'priceRef'))
				return false;
				
			$this->deleteColumn(STAGESHOW_PRICES_TABLE, 'priceRef');
					
			return true;
		}

		function PurgeDeletedEntries($delField, $dbFields, $sqlOpts = '')
		{
			for ($i=0; $i<count($dbFields); $i++)
			{
				$dbField[$i] = $dbFields[$i];
			
				$dbFieldParts = explode('.', $dbField[$i]);
				$dbTable[$i] = $dbFieldParts[0];
				$dbColumn[$i] = $dbFieldParts[1];
			}
			
			$subTableIndex = count($dbFields)-1;
			
			// Delete performances have been marked as deleted that have no sales
			$sqlSelect  = 'SELECT '.$dbField[0].', '.$delField.' ';
			$sql  = 'FROM '.$dbTable[0].' ';
			for ($i=1; $i<count($dbFields); $i++)
			{
				$sql .= 'LEFT JOIN '.$dbTable[$i].' ON '.$dbTable[$i].'.'.$dbColumn[$i].'='.$dbTable[$i-1].'.'.$dbColumn[$i].'  ';
			}
			if ($sqlOpts != '')
			{
				$sql .= $sqlOpts.$dbField[0].' ';
			}
			
			if ($this->getOption('Dev_ShowDBOutput'))
			{
				$this->get_results('SELECT * '.$sql);
			}
			
			$sql .= ') AS delresults ';
			$sql .= 'WHERE (delresults.'.$delField.' = "'.STAGESHOW_STATE_DELETED.'") ';
			
			$innerSql = $sqlSelect.$sql;
			
			$sql  = 'DELETE FROM '.$dbTable[0].' ';
			$sql .= 'WHERE '.$dbColumn[0].' IN ( ';
			$sql .= 'SELECT '.$dbColumn[0].' FROM ( ';
			$sql .= $innerSql;
			$sql .= ') ';

			$this->query($sql);
		}
		
		function PurgeOrphans($dbFields, $condition = '')
		{
			$masterCol = $dbFields[0];
			
			$dbFieldParts = explode('.', $masterCol);
			$masterTable = $dbFieldParts[0];
			$masterIndex = $dbFieldParts[1];
			
			$subCol = $dbFields[1];
			
			$dbFieldParts = explode('.', $subCol);
			$subTable = $dbFieldParts[0];
			$subIndex = $dbFieldParts[1];
			$subFieldID = str_replace('.', '_', $subCol);
			
			$subCol = $subTable.'.'.$subIndex;
			
			$sqlSelect = 'SELECT '.$masterCol.', '.$subCol.' AS '.$subFieldID.' ';
			$sql  = 'FROM '.$masterTable.' ';
			$sql .= 'LEFT JOIN '.$subTable.' ON '.$masterTable.'.'.$subIndex.'='.$subTable.'.'.$subIndex.' ';
			$sql .= 'WHERE '.$subTable.'.'.$subIndex.' IS NULL ';
			
			if ($condition != '')
			{
				$sql .= 'AND '.$condition.' ';
			}
			
			if ($this->getOption('Dev_ShowDBOutput'))
			{
				$this->get_results('SELECT * '.$sql);
			}
			
			$innerSql = $sqlSelect.$sql;
			
			$sql  = 'DELETE FROM '.$masterTable.' ';
			$sql .= 'WHERE '.$masterIndex.' IN ( ';
			$sql .= 'SELECT '.$masterIndex.' FROM ( ';
			$sql .= $innerSql;
			$sql .= ') AS subresults ';
			$sql .= ') ';
			
			$this->query($sql);
		}
		
		function PurgeDB()	// TODO - TEST
		{
			// Call PurgeDB() in base class
			parent::PurgeDB();
			
			// Delete all Sales where all tickets are for shows that are deleted
			$this->PurgeDeletedEntries('showState', array(
				STAGESHOW_SALES_TABLE.'.saleID',
				STAGESHOW_TICKETS_TABLE.'.saleID',
				STAGESHOW_PRICES_TABLE.'.priceID',
				STAGESHOW_PERFORMANCES_TABLE.'.perfID',
				STAGESHOW_SHOWS_TABLE.'.showID',
				),
				'GROUP BY '
				);

			// Delete all Sales where all tickets are for performances that are deleted
			$this->PurgeDeletedEntries('perfState', array(
				STAGESHOW_SALES_TABLE.'.saleID',
				STAGESHOW_TICKETS_TABLE.'.saleID',
				STAGESHOW_PRICES_TABLE.'.priceID',
				STAGESHOW_PERFORMANCES_TABLE.'.perfID',
				),
				'GROUP BY '
				);

			// Delete orphaned Tickets entries (no corresponding Sale)
			$this->PurgeOrphans(array(STAGESHOW_TICKETS_TABLE.'.ticketID', STAGESHOW_SALES_TABLE.'.saleID'));
			
			// Delete all Performances marked as deleted that have no corresponding Sales
			$this->PurgeDeletedEntries('perfState', array(
				STAGESHOW_PERFORMANCES_TABLE.'.perfID',
				STAGESHOW_PRICES_TABLE.'.perfID',
				STAGESHOW_TICKETS_TABLE.'.priceID',
				));

			// Delete orphaned Prices entries
			$this->PurgeOrphans(array(STAGESHOW_PRICES_TABLE.'.priceID', STAGESHOW_PERFORMANCES_TABLE.'.perfID'));
			
			// Delete Shows marked as deleted that have no Performance
			$condition = STAGESHOW_SHOWS_TABLE.'.showState = "'.STAGESHOW_STATE_DELETED.'"';
			$this->PurgeOrphans(array(STAGESHOW_SHOWS_TABLE.'.showID', STAGESHOW_PERFORMANCES_TABLE.'.showID'), $condition);						
		}
		
		function init()
		{
			// This function should be called by the 'init' action of the Plugin
			// Action requiring setting of Cookies should be done here
		}

	    function GetDefaultOptions()
	    {
			// FUNCTIONALITY: DBase - StageShow - On Activate ... Set EMail Template Path
			$defOptions = array(
		    	'EMailTemplatePath' => STAGESHOW_ACTIVATE_EMAIL_TEMPLATE_PATH,
			);
			
			return $defOptions;			
		}
		
		function uninstall()
		{
			// FUNCTIONALITY: DBase - StageShow - Uninstall - Delete Performance, Prices and Tickets tables and Capabilities
			
			parent::uninstall();
      		$this->DropTable(STAGESHOW_SHOWS_TABLE);
			$this->DropTable(STAGESHOW_PERFORMANCES_TABLE);      
			$this->DropTable(STAGESHOW_PRICES_TABLE);      
			$this->DropTable(STAGESHOW_TICKETS_TABLE);  			
			
			$this->DeleteCapability(STAGESHOW_CAPABILITY_RESERVEUSER);
			$this->DeleteCapability(STAGESHOW_CAPABILITY_VALIDATEUSER);
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
		        
				'SetupUserRole' => STAGESHOW_DEFAULT_SETUPUSER,
		        'AuthTxnEMail' => '',                
		        
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
				// FUNCTIONALITY: DBase - StageShow - Options - Merge PayPal settings after version 0.9.3
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
        
		function get_domain()
		{
			// This function returns the domain id (for translations) 
			// The domain is the same for all stageshow derivatives
			return 'stageshow';
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
      		global $wpdb;
     
			if ($dropTable && isset($this->adminOptions['showName']))
			{
				unset($this->adminOptions['showName']);
				$this->saveOptions();
			}
			
			parent::createDB($dropTable);

			$ticketNameLen = STAGESHOW_SHOWNAME_TEXTLEN + strlen(STAGESHOW_TICKETNAME_DIVIDER) + STAGESHOW_DATETIME_TEXTLEN;

			$table_name = $this->opts['OrdersTableName'];

			if ($dropTable)
				$this->DropTable($table_name);
			
			$sql = "CREATE TABLE ".$table_name.' ( 
					ticketID INT UNSIGNED NOT NULL AUTO_INCREMENT,
					saleID INT UNSIGNED NOT NULL,
					priceID INT UNSIGNED NOT NULL,
					ticketName VARCHAR('.$ticketNameLen.') NOT NULL DEFAULT "",
					ticketType VARCHAR('.STAGESHOW_PRICETYPE_TEXTLEN.') NOT NULL DEFAULT "",
					ticketQty INT NOT NULL,
					ticketSeat VARCHAR('.STAGESHOW_TICKETSEAT_TEXTLEN.'),
					ticketPaid DECIMAL(9,2) NOT NULL DEFAULT 0.0,
					UNIQUE KEY ticketID (ticketID)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;';

			//excecute the query
			$this->dbDelta($sql);

			// ------------------- STAGESHOW_SHOWS_TABLE -------------------
			$table_name = STAGESHOW_SHOWS_TABLE;

			if ($dropTable)
				$this->DropTable($table_name);
			else
			{
				if( mysql_num_rows( mysql_query("SHOW TABLES LIKE '".STAGESHOW_PERFORMANCES_TABLE."'")) > 0)
					$addingShowsTable = ($wpdb->get_var("SHOW TABLES LIKE '".STAGESHOW_SHOWS_TABLE."'") != STAGESHOW_SHOWS_TABLE);
				else
					$addingShowsTable = false;			
			}
				
			$sql = "CREATE TABLE ".$table_name.' ( 
				showID INT UNSIGNED NOT NULL AUTO_INCREMENT,
				showName VARCHAR('.STAGESHOW_SHOWNAME_TEXTLEN.') NOT NULL,
				showState VARCHAR('.STAGESHOW_ACTIVESTATE_TEXTLEN.'), 
				UNIQUE KEY showID (showID)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;';

			//excecute the query
			$this->dbDelta($sql);
			
			// StageShow to StageShow-Plus Update
			if ($addingShowsTable && isset($this->adminOptions['showName']))
			{
				// See if we have a show configured for StageShow before adding SHOWS table
				if ($this->adminOptions['showName'] != '')
				{
					$showName = $this->adminOptions['showName'];
					$showState = $this->adminOptions['showState'];
					
					//$this->LogToDebugFile("Activate.log", "StageShow Shows: ");		
					//$this->LogToDebugFile("Activate.log", print_r($showName, true));		
					
					$this->AddShow($showName, $showState);
					
					if (isset($this->adminOptions['showName']))
					{
						unset($this->adminOptions['showName']);
						unset($this->adminOptions['showState']);
						$this->saveOptions();
					}
					
					//$this->LogToDebugFile("Activate.log", "Updated in StageShowDBaseClass() constructor ");	
					//$this->LogToDebugFile("Activate.log", "adminOptions: ");																					
					//$this->LogToDebugFile("Activate.log", print_r($this->adminOptions, true));												
				}
			}
			
			// ------------------- STAGESHOW_PERFORMANCES_TABLE -------------------
			$table_name = STAGESHOW_PERFORMANCES_TABLE;
			
			if ($dropTable)
				$this->DropTable($table_name);
			
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
			$this->dbDelta($sql);

			$table_name = STAGESHOW_PRICES_TABLE;

			if ($dropTable)
				$this->DropTable($table_name);
			
			$sql = "CREATE TABLE ".$table_name.' ( 
					priceID INT UNSIGNED NOT NULL AUTO_INCREMENT,
					perfID INT UNSIGNED NOT NULL,
					priceType VARCHAR('.STAGESHOW_PRICETYPE_TEXTLEN.') NOT NULL,
					priceValue DECIMAL(9,2) NOT NULL,
					priceVisibility VARCHAR('.STAGESHOW_PRICEVISIBILITY_TEXTLEN.') NOT NULL DEFAULT "public",
					UNIQUE KEY priceID (priceID)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;';

			//excecute the query
			$this->dbDelta($sql);
    	}
        
		function DBField($fieldName)
		{
			switch($fieldName)
			{
				case 'stockID':	return 'priceID';
				case 'orderQty':	return 'ticketQty';
				case 'orderPaid':	return 'ticketPaid';
			}
		}
		
		function CreateSample()
		{
			// FUNCTIONALITY: DBase - StageShow - Implement "Create Sample"
			$showName1 = "The Wordpress Show";
			if ( isset($this->testModeEnabled) ) 
			{
				$showName1 .= " (".StageShowLibUtilsClass::GetSiteID().")";
			}
			// Sample dates to reflect current date/time
			$showTime1 = date(self::STAGESHOW_DATE_FORMAT, strtotime("+28 days"))." 20:00:00";
			$showTime2 = date(self::STAGESHOW_DATE_FORMAT, strtotime("+29 days"))." 20:00:00";
			$showTime3 = date(self::STAGESHOW_DATE_FORMAT, strtotime("+30 days"))." 14:30:00";
			$showTime4 = date(self::STAGESHOW_DATE_FORMAT, strtotime("+30 days"))." 20:00:00";
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
				echo '<div id="message" class="error"><p>'.__('Cannot Add Performances', $this->get_domain()).' - '.$statusMsg.'</p></div>';
				return;
			}
			// Populate prices table
			$priceID1_A1 = $this->AddPrice($perfID1, 'All',   PRICEID1_A1);
			$priceID1_A2 = $this->AddPrice($perfID2, 'Adult', PRICEID1_A2);
			$priceID1_A3 = $this->AddPrice($perfID3, 'Adult', PRICEID1_A3);
			$priceID1_A4 = $this->AddPrice($perfID4, 'All',   PRICEID1_A4);
			$priceID1_C2 = $this->AddPrice($perfID2, 'Child', PRICEID1_C2);
			$priceID1_C3 = $this->AddPrice($perfID3, 'Child', PRICEID1_C3);
			
			$perfsList = $this->GetPerformancesListByShowID($showID1);
			{
				$this->UpdateCartButtons($perfsList);
			}
			
			if (!$this->isOptionSet('Dev_NoSampleSales'))
			{
				// Add some ticket sales
				$saleTime1 = date(self::STAGESHOW_DATE_FORMAT, strtotime("-4 days"))." 17:32:47";
				$saleTime2 = date(self::STAGESHOW_DATE_FORMAT, strtotime("-3 days"))." 10:14:51";
				$saleEMail = 'other@someemail.co.zz';
				if (defined('STAGESHOW_SAMPLE_EMAIL'))
					$saleEMail = STAGESHOW_SAMPLE_EMAIL;
				$saleID = $this->AddSampleSale($saleTime1, 'A.N.Other', $saleEMail, 12.00, 0.60, 'ABCD1234XX', PAYPAL_APILIB_SALESTATUS_COMPLETED,
				'Andrew Other', '1 The Street', 'Somewhere', 'Bigshire', 'BG1 5AT', 'UK');
				$this->AddSaleItem($saleID, $priceID1_C3, 4, PRICEID1_C3);
				$this->AddSaleItem($saleID, $priceID1_A3, 1, PRICEID1_A3);
				$saleEMail = 'mybrother@someemail.co.zz';
				if (defined('STAGESHOW_SAMPLE_EMAIL'))
					$saleEMail = STAGESHOW_SAMPLE_EMAIL;
				$saleID = $this->AddSampleSale($saleTime2, 'M.Y.Brother', $saleEMail, 24.00, 1.01, '87654321qa', PAYPAL_APILIB_SALESTATUS_COMPLETED,
				'Matt Brother', 'The Bungalow', 'Otherplace', 'Littleshire', 'LI1 9ZZ', 'UK');
				$this->AddSaleItem($saleID, $priceID1_A4, 4, PRICEID1_A4);
				$timeStamp = current_time('timestamp');
				if (defined('STAGESHOW_EXTRA_SAMPLE_SALES'))
				{
					// Add a lot of ticket sales
					for ($sampleSaleNo = 1; $sampleSaleNo<=STAGESHOW_EXTRA_SAMPLE_SALES; $sampleSaleNo++)
					{
						$saleDate = date(self::MYSQL_DATETIME_FORMAT, $timeStamp);
						$saleName = 'Sample Buyer'.$sampleSaleNo;
						$saleEMail = 'extrasale'.$sampleSaleNo.'@sample.org.uk';
						$saleID = $this->AddSampleSale($saleDate, $saleName, $saleEMail, 12.50, 0.62, 'TXNID_'.$sampleSaleNo, PAYPAL_APILIB_SALESTATUS_COMPLETED,
						'Almost', 'Anywhere', 'Very Rural', 'Tinyshire', 'TN55 8XX', 'UK');
						$this->AddSaleItem($saleID, $priceID1_A3, 3, PRICEID1_A3);
						$timeStamp = strtotime("+1 hour +7 seconds", $timeStamp);
					}
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
			if ($showID <= 0) 
			{
				$rtnMsg = __('Internal Error - showID', $this->get_domain());
				return 0;
			}
			
			$perfState = '';
			$perfID = 0;
			
			// Get the show name
			$shows = $this->GetShowsList($showID);
			$showName = $shows[0]->showName;
			
			if ($this->UseIntegratedTrolley())
			{
				$hostedButtonID = 0;
			}
			else
			{
				// Create PayPal buttons ....
				$ButtonStatus = $this->payPalAPIObj->CreateButton($hostedButtonID, $showName);				
				
				if ($ButtonStatus === PayPalButtonsAPIClass::PAYPAL_APILIB_CREATEBUTTON_ERROR)
				{
					// Error creating at least one button ... tidy up and report error
					if ($ButtonStatus === PayPalButtonsAPIClass::PAYPAL_APILIB_CREATEBUTTON_OK)
						$this->payPalAPIObj->DeleteButton($hostedButtonID);
							
					$rtnMsg = __('Error Creating PayPal Button(s)', $this->get_domain());
					return $perfID;			
				}
				else if ($ButtonStatus === PayPalButtonsAPIClass::PAYPAL_APILIB_CREATEBUTTON_NOLOGIN)
				{
					$rtnMsg = __('PayPal Login Settings Invalid', $this->get_domain());
					return $perfID;			
				}
			}
		
			// PayPal button(s) created - Add performance to database					
			// Give performance unique Ref - Check what default reference IDs already exist in database
			$perfID = $this->AddPerformance($showID, $perfState, $perfDateTime, $perfRef, $perfSeats, $hostedButtonID);
			if ($perfID == 0)
				$rtnMsg = __('Performance Reference is not unique', $this->get_domain());
			else
				$rtnMsg = __('New Performance Added', $this->get_domain());
			
			return $perfID;			
		}
		
		static function FormatDateForDisplay($dateInDB)
		{
			// Convert time string to UNIX timestamp
			$timestamp = strtotime( $dateInDB );
			return StageShowDBaseClass::FormatTimestampForDisplay($timestamp);
		}
		
		static function FormatTimestampForDisplay($timestamp)
		{
			if (defined('STAGESHOW_DATETIME_BOXOFFICE_FORMAT'))
				$dateFormat = STAGESHOW_DATETIME_BOXOFFICE_FORMAT;
			else
				// Use Wordpress Date and Time Format
				$dateFormat = get_option( 'date_format' ).' '.get_option( 'time_format' );
				
			// Get Time & Date formatted for display to user
			$dateAndTime = date($dateFormat, $timestamp);
			if (strlen($dateAndTime) < 2)
			{
				$dateAndTime = '[Invalid WP Date/Time Format]';
			}
			
			return $dateAndTime;
		}
		
		function UpdateCartButtons($perfsList)
		{
			if ($this->UseIntegratedTrolley())
				return;
				
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
						
				// FUNCTIONALITY: DBase Update Performance Inventory
				$this->payPalAPIObj->UpdateButton($perfEntry->perfPayPalButtonID, $description, $reference, $ticketPrices, $priceIDs);
				$this->payPalAPIObj->UpdateInventory($perfEntry->perfPayPalButtonID, $quantity, $siteurl, $reference);
			}
		}
		
		function GetEmail($ourOptions, $emailRole = '')
		{
			// FUNCTIONALITY: DBase - GetEmail - Uses AdminEMail (otherwise WP admin email) - Optionally with OrganisationID from settings
			// StageShow ignores the "emailRole" parameter and always uses the AdminEMail entry
			$ourEmail = '';

			if (strlen($ourOptions['AdminEMail']) > 0)
				$ourEmail = $ourOptions['AdminEMail'];
			else
				$ourEmail = get_bloginfo('admin_email');
				
			// Get from email address from settings
			if ($ourOptions['AdminID'] !== '')
				$ourEmail = $ourOptions['AdminID'].' <'.$ourEmail.'>';
			else if ($ourOptions['OrganisationID'] !== '')
				$ourEmail = $ourOptions['OrganisationID'].' <'.$ourEmail.'>';
				
			return $ourEmail;
		}
		
		function IsStateActive($state)
		{
			switch ($state)
			{
				case STAGESHOW_STATE_INACTIVE:
				case STAGESHOW_STATE_DELETED:
					return false;
					
				case STAGESHOW_STATE_ACTIVE:
				case 'Active':
				case '':
					return true;
			}
			
			return false;
		}

		function StateActiveText($state)
		{
			switch ($state)
			{
				case STAGESHOW_STATE_DELETED:
					return __("DELETED", $this->get_domain());
					
				default:
					if ($this->IsStateActive($state))
						return __("Active", $this->get_domain());
					else
						return __("INACTIVE", $this->get_domain());
					break;
			}
			
			return '';
		}

		function GetActiveShowsList()
		{
			$timeNow = current_time('mysql');
			
			$selectFields  = '*';
			$selectFields .= ','.STAGESHOW_PERFORMANCES_TABLE.'.perfID';
			//$selectFields .= ', MAX(perfDateTime) AS maxPerfDateTime';

			$sqlFilters['groupBy'] = 'showID';
			$sqlFilters['JoinType'] = 'RIGHT JOIN';
			$sqlFilters['showState'] = STAGESHOW_STATE_ACTIVE;
			$sqlFilters['perfState'] = STAGESHOW_STATE_ACTIVE;
			
			$this->perfJoined = true;

			$sql  = "SELECT $selectFields FROM ".STAGESHOW_PERFORMANCES_TABLE;
			$sql .= " JOIN ".STAGESHOW_SHOWS_TABLE.' ON '.STAGESHOW_SHOWS_TABLE.'.showID='.STAGESHOW_PERFORMANCES_TABLE.'.showID';
			
			// Add SQL filter(s)
			$sql .= $this->GetWhereSQL($sqlFilters);
			$sql .= ' AND perfDateTime>"'.$timeNow.'" ';
			$sql .= $this->GetOptsSQL($sqlFilters);
			
			$sql .= ' ORDER BY '.STAGESHOW_PERFORMANCES_TABLE.'.perfDateTime';
			
			$results = $this->get_results($sql, true, $sqlFilters);

			return $results;
		}
		
		function GetAllShowsList()
		{
			return $this->GetShowsList(0);
		}
		
		function GetShowsList($showID = 0)
		{
			$selectFields  = '*';
			$selectFields .= ','.STAGESHOW_SHOWS_TABLE.'.showID';
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
			
			$sql = "SELECT $selectFields FROM ".STAGESHOW_SHOWS_TABLE;
			$sql .= " LEFT JOIN ".STAGESHOW_PERFORMANCES_TABLE.' ON '.STAGESHOW_PERFORMANCES_TABLE.'.showID='.STAGESHOW_SHOWS_TABLE.'.showID';
			$sql .= " LEFT JOIN ".STAGESHOW_PRICES_TABLE.' ON '.STAGESHOW_PRICES_TABLE.'.perfID='.STAGESHOW_PERFORMANCES_TABLE.'.perfID';
			$sql .= " LEFT JOIN ".STAGESHOW_TICKETS_TABLE.' ON '.STAGESHOW_TICKETS_TABLE.'.priceID='.STAGESHOW_PRICES_TABLE.'.priceID';
			$sql .= " LEFT JOIN ".STAGESHOW_SALES_TABLE.' ON '.STAGESHOW_SALES_TABLE.'.saleID='.STAGESHOW_TICKETS_TABLE.'.saleID';
			
			// Add SQL filter(s)
			$sql .= $this->GetWhereSQL($sqlFilters);
			$sql .= $this->GetOptsSQL($sqlFilters);
			
			$sql .= ' ORDER BY '.STAGESHOW_PERFORMANCES_TABLE.'.perfDateTime';
			
			$results = $this->get_results($sql, true, $sqlFilters);

			return $results;
		}
		
		function UpdateSettings($result, $tableId, $settingId, $indexId, $index)
		{
			$newVal = $_POST[$settingId.$index];
			if ($newVal == $result->$settingId)
				return;
				
			$sql  = 'UPDATE '.$tableId;
			$sql .= ' SET '.$settingId.'="'.$newVal.'"';
			$sql .= ' WHERE '.$indexId.'='.$index;;
			 
			$this->query($sql);	
		}
		
		function IsShowNameUnique($showName)
		{
			$sql  = 'SELECT * FROM '.STAGESHOW_SHOWS_TABLE;
			$sql .= ' WHERE '.STAGESHOW_SHOWS_TABLE.'.showName="'.$showName.'"';
			
			$showsEntries = $this->get_results($sql);
			return (count($showsEntries) > 0) ? false : true;
		}
		
		function SetShowActivated($showID, $showState = STAGESHOW_STATE_ACTIVE)
		{
			$sql  = 'UPDATE '.STAGESHOW_SHOWS_TABLE;
			$sql .= ' SET showState="'.$showState.'"';
			$sql .= ' WHERE '.STAGESHOW_SHOWS_TABLE.'.showID='.$showID;;

			$this->query($sql);	
			return "OK";							
		}
		
		function CanAddShow()
		{		
			// FUNCTIONALITY: Shows - StageShow only supports a single show
			$rtnVal = true;
			
			$sql  = 'SELECT COUNT(*) AS showLen FROM '.STAGESHOW_SHOWS_TABLE;
			 
			$results = $this->get_results($sql);
			$rtnVal = ($results[0]->showLen < 1);
			
			return $rtnVal;
		}
		 
		function AddShow($showName = '', $showState = STAGESHOW_STATE_ACTIVE)
		{
			// FUNCTIONALITY: Shows - StageShow - Add Show
			// Check if a show can be added
			if (!$this->CanAddShow())
				return 0;
				
	      	if ($showName === '')
	      	{
				$newNameNo = 1;
				while (true)
				{
					$showName = __('Unnamed Show', $this->get_domain());
					if ($newNameNo > 1) $showName .= ' '.$newNameNo;
					if ( isset($this->testModeEnabled) ) 
						$showName .= " (".StageShowLibUtilsClass::GetSiteID().")";
						
					if ($this->IsShowNameUnique($showName))
						break;
					$newNameNo++;
				}
			}
			else
			{
				if (!$this->IsShowNameUnique($showName))
					return 0;	// Error - Show Name is not unique
			}
						
			$sql = 'INSERT INTO '.STAGESHOW_SHOWS_TABLE.'(showName, showState) VALUES("'.$showName.'", "'.$showState.'")';
			$this->query($sql);	
					
     		return mysql_insert_id();
		}
				
		function UpdateShowName($showID, $showName)
		{
			if (!$this->IsShowNameUnique($showName))
				return "ERROR";
				
			$sql  = 'UPDATE '.STAGESHOW_SHOWS_TABLE;
			$sql .= ' SET showName="'.$showName.'"';
			$sql .= ' WHERE '.STAGESHOW_SHOWS_TABLE.'.showID='.$showID;;
			$this->query($sql);	

			// FUNCTIONALITY: Shows - StageShow - Show Name Changed ... Updated Any Hosted Buttons
			$perfsList = $this->GetPerformancesListByShowID($showID);
			$this->UpdateCartButtons($perfsList);
													
			return "OK";
		}
		
		function GetShowID($showName)
		{
			$sql  = 'SELECT * FROM '.STAGESHOW_SHOWS_TABLE;
			$sql .= ' WHERE '.STAGESHOW_SHOWS_TABLE.'.showName="'.$showName.'"';
			
			$showsEntries = $this->get_results($sql);
			return (count($showsEntries) > 0) ? $showsEntries[0]->showID : 0;
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
		
		function get_results($sql, $debugOutAllowed = true, $sqlFilters = array())
		{
			$this->perfJoined = false;
			
			$results = parent::get_results($sql, $debugOutAllowed);
			
			return $results;
		}
		
		function renameColumn($table_name, $oldColName, $newColName)
		{
 			$colSpec = $this->getColumnSpec($table_name, $oldColName);
			if (!isset($colSpec->Field))
				return __("DB Error", $this->get_domain()).": $oldColName ".__("Column does not exist", $this->get_domain());
				
			$sql = "ALTER TABLE $table_name CHANGE $oldColName $newColName ".$colSpec->Type;
			if ($colSpec->Null == 'NO')
				$sql .= " NOT NULL";
			if ($colSpec->Default != '')
				$sql .= " DEFAULT = '".$colSpec->Default."'";

			$this->query($sql);	
			return "OK";							
		}
		
		function deleteColumn($table_name, $colName)
		{
 			$sql = "ALTER TABLE $table_name DROP $colName";

			$this->query($sql);	
			return "OK";							
		}
		
		function IfColumnExists($table_name, $colName)
		{
			$colSpec = $this->getColumnSpec($table_name, $colName);
			return (isset($colSpec->Field));
		}
		
		function getColumnSpec($table_name, $colName)
		{
			$sql = "SHOW COLUMNS FROM $table_name WHERE field = '$colName'";
			 

			$typesArray = $this->get_results($sql);

			return isset($typesArray[0]) ? $typesArray[0] : '';
		}
		
		function CanEditPayPalSettings()
		{					
			$results = $this->GetAllShowsList();		
			return (count($results) == 0);			
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
			
			$this->perfJoined = true;

			$sql = "SELECT $selectFields FROM ".STAGESHOW_PERFORMANCES_TABLE;
			$sql .= " LEFT JOIN ".STAGESHOW_SHOWS_TABLE.' ON '.STAGESHOW_SHOWS_TABLE.'.showID='.STAGESHOW_PERFORMANCES_TABLE.'.showID';
			$sql .= " LEFT JOIN ".STAGESHOW_PRICES_TABLE.' ON '.STAGESHOW_PRICES_TABLE.'.perfID='.STAGESHOW_PERFORMANCES_TABLE.'.perfID';
			$sql .= " LEFT JOIN ".STAGESHOW_TICKETS_TABLE.' ON '.STAGESHOW_TICKETS_TABLE.'.priceID='.STAGESHOW_PRICES_TABLE.'.priceID';
			$sql .= " LEFT JOIN ".STAGESHOW_SALES_TABLE.' ON '.STAGESHOW_SALES_TABLE.'.saleID='.STAGESHOW_TICKETS_TABLE.'.saleID';

			// Add SQL filter(s)
			$sql .= $this->GetWhereSQL($sqlFilters);
			$sql .= $this->GetOptsSQL($sqlFilters);
			
			$sql .= ' ORDER BY '.STAGESHOW_PERFORMANCES_TABLE.'.showID, '.STAGESHOW_PERFORMANCES_TABLE.'.perfDateTime';
			
			$perfsListArray = $this->get_results($sql);

			return $perfsListArray;
		}
		
		function GetOurButtonsList()
		{
			$sql = "SELECT perfPayPalButtonID FROM ".STAGESHOW_PERFORMANCES_TABLE;
			
			$results = $this->get_results($sql);

			$buttonsListArray = array();			
			for($index=0; $index<count($results); $index++)
				$buttonsListArray[$index] = $results[$index]->perfPayPalButtonID;
				
			return $buttonsListArray;
		}
		
		function GetPriceFromButtonId($perfPayPalButtonID, $priceType)
		{
			$sql = 'SELECT * FROM '.STAGESHOW_PERFORMANCES_TABLE;
			$sql .= " LEFT JOIN ".STAGESHOW_PRICES_TABLE.' ON '.STAGESHOW_PRICES_TABLE.'.perfID='.STAGESHOW_PERFORMANCES_TABLE.'.perfID';
			$sql .= ' WHERE '.STAGESHOW_PERFORMANCES_TABLE.'.perfPayPalButtonID ="'.$perfPayPalButtonID.'"';
			$sql .= ' AND '.STAGESHOW_PRICES_TABLE.'.priceType 	="'.$priceType.'"';
			
			$results = $this->get_results($sql);
			
			return $results;
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
		
		function SetPerfActivated($perfID, $perfState = STAGESHOW_STATE_ACTIVE)
		{
			$sqlFilters['perfID'] = $perfID;
				 
			$sql  = 'UPDATE '.STAGESHOW_PERFORMANCES_TABLE;
			$sql .= ' SET perfState="'.$perfState.'"';
			$sql .= $this->GetWhereSQL($sqlFilters);

			$this->query($sql);	
			return "OK";							
		}
		
		private function GetLastPerfDateTime($showID = 0)
		{
			$sql  = 'SELECT MAX(perfDateTime) AS LastPerf FROM '.STAGESHOW_PERFORMANCES_TABLE;
			$sql .= ' WHERE '.STAGESHOW_PERFORMANCES_TABLE.'.showID='.$showID;
			
			$results = $this->get_results($sql);
			
			if (count($results) == 0) return 0;
			return $results[0]->LastPerf;
		}
		
		function IsShowEnabled($result)
		{
			//echo "Show:$result->showID $result->showState<br>\n";
			return $this->IsStateActive($result->showState);
		}
		
		function IsPerfEnabled($result)
		{
			//echo "Show:$result->showID $result->showState Perf:$result->perfID $result->perfState<br>\n";
			return $this->IsStateActive($result->showState) && $this->IsStateActive($result->perfState);
		}
		
		function IsPerfRefUnique($perfRef)
		{
			$sql  = 'SELECT COUNT(*) AS MatchCount FROM '.STAGESHOW_PERFORMANCES_TABLE;
			$sql .= ' WHERE '.STAGESHOW_PERFORMANCES_TABLE.'.perfRef="'.$perfRef.'"';
			 
			$perfsCount = $this->get_results($sql);
			return ($perfsCount[0]->MatchCount > 0) ? false : true;
		}
		
		function CanAddPerformance()
		{
			$rtnVal = true;
			
			$sql  = 'SELECT COUNT(*) AS perfLen FROM '.STAGESHOW_PERFORMANCES_TABLE;
			 
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
			 
			$this->query($sql);
			
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
			{
				return "ERROR";
			}
				
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
			$sqlFilters['perfID'] = $perfID;
				 
			$sql  = 'UPDATE '.STAGESHOW_PERFORMANCES_TABLE;
			$sql .= ' SET '.$sqlSET;
			$sql .= $this->GetWhereSQL($sqlFilters);

			$this->query($sql);	
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
			{
				$sqlFilters['publicPrices'] = true;
				$sqlFilters['activePrices'] = true;
				$sqlFilters['perfState'] = STAGESHOW_STATE_ACTIVE;
			}
				 
			$sqlFilters['showID'] = $showID;
			return $this->GetPricesList($sqlFilters);
		}
				
		function GetPricesListByPerfID($perfID)
		{
			$sqlFilters['perfID'] = $perfID;
			return $this->GetPricesList($sqlFilters);
		}
				
		function GetPricesListByPriceID($priceID)
		{
			$sqlFilters['priceID'] = $priceID;
			return $this->GetPricesList($sqlFilters);
		}
				
		function GetPricesList($sqlFilters)
		{
			$selectFields  = '*';
			if (isset($sqlFilters['saleID']))
			{
				// Explicitly add joined fields from "base" tables (otherwise values will be NULL if there is no matching JOIN)
				$selectFields .= ', '.$this->opts['SalesTableName'].'.saleID';

				$joinCmd = ' LEFT JOIN ';
			}
			else
				$joinCmd = ' JOIN ';
						
			$sql  = 'SELECT '.$selectFields.' FROM '.STAGESHOW_PRICES_TABLE;
      		$sql .= ' '.$joinCmd.STAGESHOW_PERFORMANCES_TABLE.' ON '.STAGESHOW_PERFORMANCES_TABLE.'.perfID='.STAGESHOW_PRICES_TABLE.'.perfID';
      		$sql .= ' '.$joinCmd.STAGESHOW_SHOWS_TABLE.' ON '.STAGESHOW_SHOWS_TABLE.'.showID='.STAGESHOW_PERFORMANCES_TABLE.'.showID';
			$sql .= $this->GetWhereSQL($sqlFilters);
			
			$sql .= ' ORDER BY '.STAGESHOW_PERFORMANCES_TABLE.'.showID';
			$sql .= ' , '.STAGESHOW_PERFORMANCES_TABLE.'.perfDateTime';			
			$sql .= ' , '.STAGESHOW_PRICES_TABLE.'.priceType';
			
			return $this->get_results($sql);
		}

		function IsPriceValid($newPriceValue, $result)
		{
			// Verify that the price value is not empty
			if (strlen($newPriceValue) == 0)
			{
				return __('Price Not Specified', $this->get_domain());
			}

			// Verify that the price value is a numeric value
			if (!is_numeric($newPriceValue))
			{
				return __('Invalid Price Entry', $this->get_domain());
			}

			if (!$this->UseIntegratedTrolley()) 
			{
				// Verify that the price value is non-zero
				if ($newPriceValue == 0.0)
				{
					return __('Zero Price only valid with Integrated Trolley', $this->get_domain());
				}
			}

			// Verify that the price value is positive!
			if ($newPriceValue < 0.0)
			{
				return __('Price Entry cannot be negative', $this->get_domain());
			}
			
			return '';
		}

		function IsPriceTypeUnique($perfID, $priceType)
		{
			$sql  = 'SELECT COUNT(*) AS MatchCount FROM '.STAGESHOW_PRICES_TABLE;
			$sql .= ' WHERE '.STAGESHOW_PRICES_TABLE.'.priceType="'.$priceType.'"';
			$sql .= ' AND '.STAGESHOW_PRICES_TABLE.'.perfID="'.$perfID.'"';

			$pricesEntries = $this->get_results($sql);
			return ($pricesEntries[0]->MatchCount > 0) ? false : true;
		}
		
		function AddPrice($perfID, $priceType, $priceValue = STAGESHOW_PRICE_UNKNOWN, $priceVisibility = STAGESHOW_VISIBILITY_PUBLIC)
		{
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
			
			$sql  = 'INSERT INTO '.STAGESHOW_PRICES_TABLE.' (perfID, priceType, priceValue, priceVisibility)';
			$sql .= ' VALUES('.$perfID.', "'.$priceType.'", "'.$priceValue.'", "'.$priceVisibility.'")';
			 
			
			$this->query($sql);
			
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
				
		function UpdatePriceVisibility($priceID, $newPriceVisibility)
		{
			$sqlSET = 'priceVisibility="'.$newPriceVisibility.'"';
			return $this->UpdatePriceEntry($priceID, $sqlSET);
		}								
				
		function UpdatePriceEntry($priceID, $sqlSET)
		{
			$sql  = 'UPDATE '.STAGESHOW_PRICES_TABLE;
			$sql .= ' SET '.$sqlSET;			
			$sql .= ' WHERE priceID='.$priceID;			

			$this->query($sql);	
			return "OK";							
		}

		function DeleteShowByShowID($showID)
		{
			// Get the show name
			$sql = 'SELECT * FROM '.STAGESHOW_SHOWS_TABLE;
			$sql .= ' WHERE '.STAGESHOW_SHOWS_TABLE.".showID=$showID";
			$results = $this->get_results($sql);
			
			if (count($results) == 0) return '';
			
			$this->SetShowActivated($showID, STAGESHOW_STATE_DELETED);

			return $results[0]->showName;
		}			
		
		function DeletePerformanceByPerfID($perfID)
		{
			$this->SetPerfActivated($perfID, STAGESHOW_STATE_DELETED);
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
			$sql  = 'DELETE FROM '.STAGESHOW_PRICES_TABLE;
			$sql .= ' WHERE '.STAGESHOW_PRICES_TABLE.".$IDfield=$ID";
			 
			$this->query($sql);
		}					

		function GetAllTicketTypes()
		{
			$sql  = 'SELECT priceType FROM '.STAGESHOW_PRICES_TABLE;
			$sql .= ' GROUP BY priceType';
			$sql .= ' ORDER BY priceType';
			 
			return $this->get_results($sql);
		}
		
		function GetTicketTypes($perfID)
		{
			$sql  = 'SELECT * FROM '.STAGESHOW_PRICES_TABLE;
			$sql .= ' JOIN '.STAGESHOW_PERFORMANCES_TABLE.' ON '.STAGESHOW_PERFORMANCES_TABLE.'.perfID='.STAGESHOW_PRICES_TABLE.'.perfID';
			$sql .= ' WHERE '.STAGESHOW_PERFORMANCES_TABLE.'.perfID="'.$perfID.'"';
			$sql .= ' ORDER BY priceType';
			 			
			return $this->get_results($sql);
		}

		function GetTicketsListByPerfID($perfID)
		{
			$sqlFilters['orderBy'] = 'saleName,'.STAGESHOW_SALES_TABLE.'.saleID DESC';
			$sqlFilters['perfID'] = $perfID;
			return $this->GetSalesList($sqlFilters);
		}
		
		function GetAllSalesQty($sqlFilters = null)
		{
			$sqlFilters['groupBy'] = 'perfID';
			$sqlFilters['JoinType'] = 'RIGHT JOIN';

			$sql  = 'SELECT *,'.$this->TotalSalesField($sqlFilters).' FROM '.STAGESHOW_SALES_TABLE;	
			$sql .= $this->GetJoinedTables($sqlFilters, __CLASS__);
			$sql .= $this->GetWhereSQL($sqlFilters);
			$sql .= $this->GetOptsSQL($sqlFilters);
			$sql .= ' ORDER BY '.STAGESHOW_PERFORMANCES_TABLE.'.showID, '.STAGESHOW_PERFORMANCES_TABLE.'.perfDateTime';
					
			$salesListArray = $this->get_results($sql);
			if (count($salesListArray) == 0)
					return 0;
							 
			return $salesListArray;
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
			
			$joinType = isset($sqlFilters['JoinType']) ? $sqlFilters['JoinType'] : 'JOIN';
			
			// JOIN parent tables
			$sqlJoin .= " $joinType ".STAGESHOW_TICKETS_TABLE.' ON '.STAGESHOW_TICKETS_TABLE.'.saleID='.STAGESHOW_SALES_TABLE.'.saleID';
			$sqlJoin .= " $joinType ".STAGESHOW_PRICES_TABLE.' ON '.STAGESHOW_PRICES_TABLE.'.priceID='.STAGESHOW_TICKETS_TABLE.'.priceID';
			$sqlJoin .= " $joinType ".STAGESHOW_PERFORMANCES_TABLE.' ON '.STAGESHOW_PERFORMANCES_TABLE.'.perfID='.STAGESHOW_PRICES_TABLE.'.perfID';
			
			$this->perfJoined = true;						
			
			$sqlJoin .= " $joinType ".STAGESHOW_SHOWS_TABLE.' ON '.STAGESHOW_SHOWS_TABLE.'.showID='.STAGESHOW_PERFORMANCES_TABLE.'.showID';
						
			$this->showJoined = true;
						
			return $sqlJoin;
		}
		
		function GetWhereSQL($sqlFilters)
		{
			$sqlWhere = parent::GetWhereSQL($sqlFilters);
			$sqlCmd = ($sqlWhere === '') ? ' WHERE ' : ' AND ';
			
			if (isset($sqlFilters['priceID']))
			{
				$sqlWhere .= $sqlCmd.STAGESHOW_PRICES_TABLE.'.priceID="'.$sqlFilters['priceID'].'"';
				$sqlCmd = ' AND ';
			}
			
			if (isset($sqlFilters['perfID']) && ($sqlFilters['perfID'] > 0))
			{
				// Select a specified Performance Record
				$sqlWhere .= $sqlCmd.STAGESHOW_PERFORMANCES_TABLE.'.perfID="'.$sqlFilters['perfID'].'"';
				$sqlCmd = ' AND ';
			}
			else 
			{
				if ($this->perfJoined)
				{
					// Select multi performance records
					if (isset($sqlFilters['perfState'])) 
					{
						if ($sqlFilters['perfState'] == STAGESHOW_STATE_ACTIVE)
						{
							$sqlCond  = '('.STAGESHOW_PERFORMANCES_TABLE.'.perfState="")';
							$sqlCond .= ' OR ';
							$sqlCond .= '('.STAGESHOW_PERFORMANCES_TABLE.'.perfState="'.STAGESHOW_STATE_ACTIVE.'")';
							$sqlWhere .= $sqlCmd.'('.$sqlCond.')';							
						}
						else
						{
							$sqlWhere .= $sqlCmd.STAGESHOW_PERFORMANCES_TABLE.'.perfState="'.$sqlFilters['perfState'].'"';
						}
					}
					else
					{
						$sqlCond  = '('.STAGESHOW_PERFORMANCES_TABLE.'.perfState IS NULL)';
						$sqlCond .= ' OR ';
						$sqlCond .= '('.STAGESHOW_PERFORMANCES_TABLE.'.perfState<>"'.STAGESHOW_STATE_DELETED.'")';
						$sqlWhere .= $sqlCmd.'('.$sqlCond.')';
					}
						$sqlCmd = ' AND ';
				}				
			}
						
			if (isset($sqlFilters['priceType']))
			{
				$sqlWhere .= $sqlCmd.STAGESHOW_PRICES_TABLE.'.priceType="'.$sqlFilters['priceType'].'"';
				$sqlCmd = ' AND ';
			}
						
			if (isset($sqlFilters['publicPrices']))
			{
				$sqlWhere .= $sqlCmd.STAGESHOW_PRICES_TABLE.'.priceVisibility="'.STAGESHOW_VISIBILITY_PUBLIC.'"';
				$sqlCmd = ' AND ';
			}
			
			if (isset($sqlFilters['activePrices']))
			{
				$sqlWhere .= $sqlCmd.STAGESHOW_PRICES_TABLE.'.priceValue>="0"';
				$sqlCmd = ' AND ';
			}			
			
			if (isset($sqlFilters['showID']) && ($sqlFilters['showID'] > 0))
			{
				$sqlWhere .= $sqlCmd.STAGESHOW_SHOWS_TABLE.'.showID="'.$sqlFilters['showID'].'"';
				$sqlCmd = ' AND ';
			}
			else if (!isset($sqlFilters['perfID']) && isset($this->showJoined) )
			{
				if (!isset($sqlFilters['showState']))
				{
					$sqlWhere .= $sqlCmd.STAGESHOW_SHOWS_TABLE.'.showState<>"'.STAGESHOW_STATE_DELETED.'"';
					$sqlCmd = ' AND ';
				}
				else
				{
					$sqlWhere .= $sqlCmd.STAGESHOW_SHOWS_TABLE.'.showState="'.$sqlFilters['showState'].'"';
					$sqlCmd = ' AND ';				
				}
			}
			
			if (isset($sqlFilters['searchtext']))
			{
				$searchFields = array('saleEMail', 'saleName', 'salePPName');
				
				$sqlWhere .= $sqlCmd.'(';
				$sqlOr = '';				
				foreach ($searchFields as $searchField)
				{
					$sqlWhere .= $sqlOr;
					$sqlWhere .= STAGESHOW_SALES_TABLE.'.'.$searchField.' LIKE "'.$sqlFilters['searchtext'].'"';
					$sqlOr = ' OR ';
				}
				$sqlWhere .= ')';
				$sqlCmd = ' AND ';
			}
			
			return $sqlWhere;
		}
		
		function GetOptsSQL($sqlFilters, $sqlOpts = '')
		{
			if (isset($sqlFilters['groupBy']))
			{
				switch ($sqlFilters['groupBy'])
				{
					case 'saleID':
						$sqlOpts = $this->AddSQLOpt($sqlOpts, ' GROUP BY ', STAGESHOW_SALES_TABLE.'.saleID');
						break;
						
					case 'showID':
						$sqlOpts = $this->AddSQLOpt($sqlOpts, ' GROUP BY ', STAGESHOW_SHOWS_TABLE.'.showID');
						break;
						
					case 'perfID':
						$sqlOpts = $this->AddSQLOpt($sqlOpts, ' GROUP BY ', STAGESHOW_PERFORMANCES_TABLE.'.perfID');
						break;
						
					case 'priceID':
						$sqlOpts = $this->AddSQLOpt($sqlOpts, ' GROUP BY ', STAGESHOW_PRICES_TABLE.'.priceID');
						break;
						
					default:
						break;
				}
			}
			
			$sqlOpts = parent::GetOptsSQL($sqlFilters, $sqlOpts);
			return $sqlOpts;
		}
				
// ----------------------------------------------------------------------
//
//			Start of GENERIC SALES functions
//
// ----------------------------------------------------------------------
    
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
			// totalQty may not include Pending sales (i.e. saleStatus=Checkout)) - add it here!
			$sql  = '  SUM(ticketQty) AS totalQty ';
			//$sql .= ', SUM(priceValue * ticketQty) AS totalValue ';
			if ($this->UseIntegratedTrolley())
			{
				$statusOptions  = '(saleStatus="'.PAYPAL_APILIB_SALESTATUS_COMPLETED.'")';
				$statusOptions .= ' OR ';
				$statusOptions .= '(saleStatus="'.STAGESHOW_SALESTATUS_RESERVED.'")';
				$sql .= ', SUM(IF('.$statusOptions.', priceValue * ticketQty, 0)) AS soldValue ';
				$sql .= ', SUM(IF('.$statusOptions.', ticketQty, 0)) AS soldQty ';				
			}
			else
			{
				$sql .= ', SUM(priceValue * ticketQty) AS soldValue ';
				$sql .= ', SUM(ticketQty) AS soldQty ';
			}
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
			$sqlFilters['orderBy'] = $this->opts['SalesTableName'].'.saleID DESC';
			return $this->GetSalesList($sqlFilters);
		}

		function SearchSalesList($searchtext)
		{
			$sqlFilters['searchtext'] = '%'.$searchtext.'%';
			return $this->GetAllSalesList($sqlFilters);
		}						
		
		function GetAllSalesListBySaleTxnId($saleTxnId)
		{
			// Add TotalSalesField .... groupBy does the trick!
			$sqlFilters['saleTxnId'] = $saleTxnId;
			//$sqlFilters['groupBy'] = 'saleID';
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
			
			$sqlWhere = ' WHERE '.STAGESHOW_TICKETS_TABLE.'.saleID = "'.$saleID.'"';
			
			$sql  = 'SELECT '.$selectFields;
			$sql .= ' FROM ( SELECT * FROM '.STAGESHOW_TICKETS_TABLE.$sqlWhere.' ) AS sales';
			$sql .= ' RIGHT JOIN ( SELECT * FROM '.STAGESHOW_PRICES_TABLE.' ) AS prices';
			$sql .= ' ON sales.priceID = prices.priceID';
			$sql .= ' JOIN '.STAGESHOW_PERFORMANCES_TABLE.' ON '.STAGESHOW_PERFORMANCES_TABLE.'.perfID=prices.perfID';			
			$sql .= ' JOIN '.STAGESHOW_SHOWS_TABLE.' ON '.STAGESHOW_SHOWS_TABLE.'.showID='.STAGESHOW_PERFORMANCES_TABLE.'.showID';
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
		
		function GetWhereParam($fieldID, $whereID)
		{
			if (is_array($whereID))
			{
				$whereList = '';
				foreach ($whereID as $whereItem)
				{
					if ($whereList != '') $whereList .= ',';
					$whereList .= $whereItem->$fieldID;
				}
				$sqlWhere = " IN ($whereList)";
			}
			else
				$sqlWhere = "=$whereID";
				
			return $sqlWhere;
		}
		
		function DeleteSale($saleID)
		{
			parent::DeleteSale($saleID);

			// Delete a show entry
			$sql  = 'DELETE FROM '.$this->opts['OrdersTableName'];
			$sql .= ' WHERE '.$this->opts['OrdersTableName'].".saleID".$this->GetWhereParam('saleID', $saleID);
		 
			$this->query($sql);
		}			
		
// ----------------------------------------------------------------------
//
//			End of SALES functions
//
// ----------------------------------------------------------------------
    
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

		function GetSalesEMail()
		{
			return $this->adminOptions['AdminEMail'];
		}
		
		function AddSalesDetailsEMailFields($EMailTemplate, $saleDetails)
		{
			foreach ($saleDetails as $key => $value)
			{
				$EMailTemplate = str_replace("[$key]", $value, $EMailTemplate);
			}
			
			return parent::AddSalesDetailsEMailFields($EMailTemplate, $saleDetails);
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
		function GetInfoServerURL($pagename)
		{
			$filename = $pagename.'.php';
			
			if (defined('STAGESHOW_INFO_SERVER_URL'))
				$updateCheckURL = STAGESHOW_INFO_SERVER_URL;
			else
				$updateCheckURL = $this->get_pluginURI().'/';
			$updateCheckURL .= $filename;

			$updateCheckURL = str_replace('\\', '/', $updateCheckURL);
			$updateCheckURL = str_replace("//$filename", "/$filename", $updateCheckURL);
			
			//$updateCheckURL = add_query_arg('email', urlencode($this->adminOptions['AdminEMail']), $updateCheckURL);
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
			return parent::CheckIsConfigured();
		}
				
		function GetLatestNews()
		{
			$news = $this->CheckLatestNews();
			return $news['LatestNews'];
		}
				
		function CheckLatestNews()
		{
			$latest['LatestNews'] = '';
			$latest['Status'] = "UNKNOWN";
			$getUpdate = true;
			
			if (isset($this->adminOptions['NewsUpdateTime']) && ($this->adminOptions['NewsUpdateTime'] !== '') )
			{
				$lastUpdate = $this->adminOptions['NewsUpdateTime'];
				$latest['LastUpdate'] = date(StageShowLibDBaseClass::MYSQL_DATETIME_FORMAT, $lastUpdate);
				
				$updateInterval = STAGESHOW_NEWS_UPDATE_INTERVAL*24*60*60;
				$nextUpdate = $lastUpdate + $updateInterval;
				$latest['NextUpdate'] = date(StageShowLibDBaseClass::MYSQL_DATETIME_FORMAT, $nextUpdate);
				
				if ($nextUpdate > current_time('timestamp'))
				{
					$latest['Status'] = "UpToDate";
					$latest['LatestNews'] = $this->adminOptions['LatestNews'];
					$getUpdate = false;
				}			
			}
			
			if ($getUpdate)
			{
				// Get URL of StagsShow News server from Plugin Info
				$updateCheckURL = $this->GetInfoServerURL('news');
				$latest['LatestNews'] = $this->GetHTTPPage($updateCheckURL);	
				if (strlen($latest['LatestNews']) <= 2)
				{
					$latest['Status'] = "HTTP_Empty";
					$latest['LatestNews'] = '';
				}
				else
				{
					$latest['Status'] = "HTTP_OK";
				}
					
				$this->adminOptions['LatestNews'] = $latest['LatestNews'];
					
				$this->adminOptions['NewsUpdateTime'] = current_time('timestamp');
				$this->saveOptions();
				//echo "News Updated<br>\n";
			}				
			
			return $latest;
		}
		
		function AddTableLocks($sql)
		{
			$sql = parent::AddTableLocks($sql);
			$sql .= ', '.STAGESHOW_PRICES_TABLE.' READ';
			$sql .= ', '.STAGESHOW_PERFORMANCES_TABLE.' READ';
			$sql .= ', '.STAGESHOW_SHOWS_TABLE.' READ';
			return $sql;
		}
				
		function query($sql)
		{
			$this->perfJoined = false;
			return parent::query($sql);
		}
		
		function ShowSQL($sql, $values = null)
		{
			parent::ShowSQL($sql, $values);
			
			unset($this->showJoined);
		}	
		
	}
}

?>