<?php
/* 
Description: StageShow-Plus extension for StageShow Plugin 
 
Copyright 2012 Malcolm Shergold, Corondeck Ltd. All rights reserved.

You must be a registered user to use this software
*/

if (!defined('STAGESHOW_DBASE_CLASS'))
	define('STAGESHOW_DBASE_CLASS', 'StageShowPlusDBaseClass');

if (!defined('STAGESHOWPLUS_ACTIVATE_EMAIL_TEMPLATE_PATH'))
	define('STAGESHOWPLUS_ACTIVATE_EMAIL_TEMPLATE_PATH', 'stageshowplus_HTMLEMail.php');
	
if (!defined('STAGESHOWPLUS_ACTIVATE_EMAILSUMMARY_TEMPLATE_PATH'))
	define('STAGESHOWPLUS_ACTIVATE_EMAILSUMMARY_TEMPLATE_PATH', 'stageshowplus_SummaryEMail.php');
	
if (!class_exists('StageShowDBaseClass')) 
	include STAGESHOW_INCLUDE_PATH.'stageshow_dbase_api.php';

include STAGESHOW_INCLUDE_PATH.'stageshowplus_email_api.php';   

if (!class_exists('StageShowPlusDBaseClass')) 
{
	// Set the DB tables names
	global $wpdb;	
	
	define('STAGESHOW_SHOWS_TABLE', STAGESHOW_TABLE_PREFIX.'shows');
	define('STAGESHOW_PLANS_TABLE', STAGESHOW_TABLE_PREFIX.'plans');
	define('STAGESHOW_PRESETS_TABLE', STAGESHOW_TABLE_PREFIX.'presets');
	define('STAGESHOW_VERIFYS_TABLE', STAGESHOW_TABLE_PREFIX.'verifys');
	
	define('PRICEID2_A1',  '5.50');
	define('PRICEID2_C1',  '3.50');

	class StageShowPlusDBaseClass extends StageShowDBaseClass // Define class
	{
		var $sshow_update;
		
		function __construct($caller) //constructor	
		{
			// Create EMail Object for HTML Emails (must be created before parent constructor is called)
			$this->emailObj = new StageShowLibStageShowEMailAPIClass($this);
			
			// Call base constructor
			parent::__construct($caller);
			
			// Check if plugin database has been created ... 
			if( mysql_num_rows( mysql_query("SHOW TABLES LIKE '".STAGESHOW_PERFORMANCES_TABLE."'")) > 0)
			{
				// Check if DB needs upgrading
				if( mysql_num_rows( mysql_query("SHOW TABLES LIKE '".STAGESHOW_SHOWS_TABLE."'")) == 0)
					$this->createDB();
			}

			// Add filter to intercept site_transient update (during update plugins check)
			// set_site_transient('update_plugins', {DATA} ); is used to store the plugins info from Wordpress.org
			// See call to wp_remote_post() in wp_update_plugins() for source code ...
			add_filter( 'pre_set_site_transient_'.'update_plugins', array(&$this, 'stageshow_update_check') );						 
									 
			// Add filter on getting plugin info
			add_filter( 'plugins_api', array(&$this, 'stageshow_plugins_api'), 10, 3 );						 
									 
			// Add filter on getting plugin info results
			add_filter( 'plugins_api_result', array(&$this, 'stageshow_plugins_result'), 10, 3 );						 
		}

	    function upgradeDB()
	    {
			// FUNCTIONALITY: DBase - On upgrade ... Copy stageshow templates to stageshowplus working folder
			// Copy any templates from stageshow to stageshowplus
			$stageshowTemplatesPath = WP_CONTENT_DIR . '/uploads/stageshow';
			$stageshowplusTemplatesPath = WP_CONTENT_DIR . '/uploads/stageshowplus';
			
			// Copy stageshow templates (if they exist) to the stageshowplus templates folder
			if (file_exists($stageshowTemplatesPath))
			{
				// Copy any templates and then delete old locatioon
				StageShowLibUtilsClass::recurse_copy($stageshowTemplatesPath, $stageshowplusTemplatesPath);
				StageShowLibUtilsClass::deleteDir($stageshowTemplatesPath);
			}
			
			// Call upgradeDB() in base class
			parent::upgradeDB();
			
			if (defined('STAGESHOW_ACTIVATE_ADMIN_ID'))
				$this->adminOptions['AdminID'] = STAGESHOW_ACTIVATE_ADMIN_ID;
				
	  		// FUNCTIONALITY: Upgrade to StageShow+ - Default EMail template to HTML EMail
			// StageShow+ EMail template upgrades
			$emailTemplatePath = strtolower(basename($this->GetEmailTemplatePath('EMailTemplatePath')));
			if ( ($emailTemplatePath === 'stageshow_email.php')
			  || ($emailTemplatePath === 'stageshow_htmlemail.php') )
			{
				$this->adminOptions['EMailTemplatePath'] = STAGESHOWPLUS_ACTIVATE_EMAIL_TEMPLATE_PATH;	
			}
				
			$this->saveOptions();
			
			// Force reloading of site plugin update info
			delete_site_transient('update_plugins');
			wp_cache_delete( 'plugins', 'plugins' );
		}

		function RemovePriceRefsField()
		{
			if (!$this->IfColumnExists(STAGESHOW_PRESETS_TABLE, 'priceRef'))
				return false;
				
			// "priceType"	column removed and "" renamed ""	
			$this->deleteColumn(STAGESHOW_PRESETS_TABLE, 'priceType');
			$this->renameColumn(STAGESHOW_PRESETS_TABLE, 'priceRef', 'priceType');
					
			return parent::RemovePriceRefsField();
		}
		
		function PurgeDB()
		{
			// Call PurgeDB() in base class
			parent::PurgeDB();
			
/*
			1. Get List of Deleted Stock Items

			2. For each deleted item ... Get List of Sales that include a item that is NOT deleted 

			3. If this list is empty then delete Stock Items


			SELECT * FROM wp_sshow_perfs 
			RIGHT JOIN wp_sshow_shows 
			ON wp_sshow_shows.showID=wp_sshow_perfs.showID
			LEFT JOIN wp_sshow_prices ON wp_sshow_prices.perfID=wp_sshow_perfs.perfID 
			LEFT JOIN wp_sshow_tickets ON wp_sshow_tickets.priceID=wp_sshow_prices.priceID 
			LEFT JOIN wp_sshow_sales ON wp_sshow_sales.saleID=wp_sshow_tickets.saleID 

			LEFT JOIN wp_sshow_tickets AS wp_sshow_tickets2 ON wp_sshow_sales.saleID=wp_sshow_tickets2.saleID 
			LEFT JOIN wp_sshow_prices AS wp_sshow_prices2 ON wp_sshow_tickets2.priceID=wp_sshow_prices2.priceID 
*/			
		}
		
		function init($caller)
		{
			// This function should be called by the 'init' action of the Plugin
			// Action requiring setting of Cookies should be done here
			
			if (isset($_POST['TerminalLocation'])) 
			{
				check_admin_referer(plugin_basename($caller)); // check nonce created by wp_nonce_field()
						
				// Cookies must be set here (if required))					
				$TerminalLocation = stripslashes($_POST['TerminalLocation']);
				setcookie('TerminalLocation', $TerminalLocation, time()+(86400*365)); // , COOKIEPATH, COOKIE_DOMAIN, false);
				
				// Update the $_COOKIE global so the location appears on this pass of the code
				$_COOKIE['TerminalLocation'] = $TerminalLocation;
			}	
					
			// Call init() in base class
			parent::init();			
		}
		
    	function GetDefaultOptions()
    	{
			// FUNCTIONALITY: DBase - StageShow - On Activate ... Set EMail Template Paths, and Summary EMail address
			$defOptions = array(
			    'EMailTemplatePath' => STAGESHOWPLUS_ACTIVATE_EMAIL_TEMPLATE_PATH,
			    'EMailSummaryTemplatePath' => STAGESHOWPLUS_ACTIVATE_EMAILSUMMARY_TEMPLATE_PATH,
			    'SaleSummaryEMail' => '',
			);
			
			return $defOptions;
		}

 		//Returns an array of admin options
		function getOptions($childOptions = array()) 
		{
			$ourOptions = array(
		        'AuthTxnId' => '',
		        'PerfExpireLimit' => 0,
		        'PerfExpireUnits' => 1,
		        
		        'Unused_EndOfList' => ''
			);
				
			$ourOptions = array_merge($ourOptions, $childOptions);
			
			return parent::getOptions($ourOptions);
		}
		
		function createDB($dropTable = false)
   		{
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			
			global $wpdb;
      
			if( mysql_num_rows( mysql_query("SHOW TABLES LIKE '".STAGESHOW_PERFORMANCES_TABLE."'")) > 0)
				$updatingToSSPlus = ($wpdb->get_var("SHOW TABLES LIKE '".STAGESHOW_SHOWS_TABLE."'") != STAGESHOW_SHOWS_TABLE);
			else
				$updatingToSSPlus = false;
			
			parent::createDB($dropTable);

      		global $wpdb;
     
			// ------------------- STAGESHOW_SHOWS_TABLE -------------------
			$table_name = STAGESHOW_SHOWS_TABLE;

			if ($dropTable)
				$wpdb->query("DROP TABLE IF EXISTS $table_name");

			$updatingToSSPlus = ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name);
			
			$sql = "CREATE TABLE ".$table_name.' ( 
				showID INT UNSIGNED NOT NULL AUTO_INCREMENT,
				showName VARCHAR('.STAGESHOW_SHOWNAME_TEXTLEN.') NOT NULL,
				showNote TEXT,
				showState VARCHAR('.STAGESHOW_ACTIVESTATE_TEXTLEN.'), 
				showOpens DATETIME,
				showExpires DATETIME,
				showEMail VARCHAR('.STAGESHOW_SHOWNAME_TEXTLEN.') NOT NULL DEFAULT "",
				UNIQUE KEY showID (showID)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;';

			// TODO-WISHLIST Implement Seat Number Allocation etc.
			      
			//excecute the query
			$this->ShowSQL($sql);
			$this->dbDelta($sql);
			
			// ------------------- STAGESHOW_PLANS_TABLE -------------------
			$table_name = STAGESHOW_PLANS_TABLE;

			if ($dropTable)
				$wpdb->query("DROP TABLE IF EXISTS $table_name");

			$sql = "CREATE TABLE ".$table_name.' ( 
					planID INT UNSIGNED NOT NULL AUTO_INCREMENT,
					planRef VARCHAR('.STAGESHOW_PLANREF_TEXTLEN.'),
				UNIQUE KEY planID (planID)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;';

			//excecute the query
			$this->ShowSQL($sql);
			$this->dbDelta($sql);
			
			// ------------------- STAGESHOW_PRESETS_TABLE -------------------
			$table_name = STAGESHOW_PRESETS_TABLE;

			if ($dropTable)
				$wpdb->query("DROP TABLE IF EXISTS $table_name");

			$sql = "CREATE TABLE ".$table_name.' ( 
					presetID INT UNSIGNED NOT NULL AUTO_INCREMENT,
					planID INT UNSIGNED NOT NULL,
					priceType VARCHAR('.STAGESHOW_PRICETYPE_TEXTLEN.') NOT NULL,
					priceValue DECIMAL(9,2) NOT NULL,
				UNIQUE KEY presetID (presetID)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;';

			//excecute the query
			$this->ShowSQL($sql);
			$this->dbDelta($sql);
			
			// ------------------- STAGESHOW_VERIFYS_TABLE -------------------
			$table_name = STAGESHOW_VERIFYS_TABLE;

			if ($dropTable)
				$wpdb->query("DROP TABLE IF EXISTS $table_name");

			$sql = "CREATE TABLE ".$table_name.' ( 
					verifyID INT UNSIGNED NOT NULL AUTO_INCREMENT,
					saleID INT UNSIGNED NOT NULL,
					verifyDateTime DATETIME,
					verifyLocation VARCHAR('.STAGESHOW_LOCATION_TEXTLEN.') NOT NULL,
				UNIQUE KEY presetID (verifyID)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;';

			//excecute the query
			$this->ShowSQL($sql);
			$this->dbDelta($sql);
			
			// StageShow to StageShow-Plus Update
			if ($updatingToSSPlus)
			{
				// See if we have a show configured for basic StageShow plugin - ShowID is always 1
				$showsList = parent::GetShowsList(1);	
					
				//$this->LogToDebugFile("Activate.log", "StageShow Shows: ");		
				//$this->LogToDebugFile("Activate.log", print_r($showsList, true));		
					
				if (count($showsList) > 0)
				{
					$this->AddShow($showsList[0]->showName, $showsList[0]->showState);
					
					$this->adminOptions['showName'] = '';
					$this->saveOptions();
					
					//$this->LogToDebugFile("Activate.log", "Updated in StageShowPlusDBaseClass() constructor ");	
					//$this->LogToDebugFile("Activate.log", "adminOptions: ");																					
					//$this->LogToDebugFile("Activate.log", print_r($this->adminOptions, true));												
				}
			}
		}
     
	    function deactivate()
	    {
      		// Call deactivate() in base class
			parent::deactivate();
    	}

		function uninstall()
		{
			// FUNCTIONALITY: DBase - StageShow+ - Uninstall - Delete Shows, Price Plans and Verifys tables and Capabilities
      		global $wpdb;

      		$wpdb->query('DROP TABLE IF EXISTS '.STAGESHOW_SHOWS_TABLE);
      		$wpdb->query('DROP TABLE IF EXISTS '.STAGESHOW_PLANS_TABLE);
      		$wpdb->query('DROP TABLE IF EXISTS '.STAGESHOW_PRESETS_TABLE);
      		$wpdb->query('DROP TABLE IF EXISTS '.STAGESHOW_VERIFYS_TABLE);
            
      		// Call uninstall() in base class
			parent::uninstall();
    	}

		function stageshow_plugins_result($res, $action, $args)
		{
			return $res;
		}
		
		function stageshow_plugins_api($res, $action, $args)
		{
			// Only interested in requests for 'plugin_information'
			if ($action != 'plugin_information') return $res;
			
			if ($this->getOption('Dev_ShowMiscDebug'))
			{
				echo "<br>stageshow_plugins_api() Called:<br>\n";
				echo "action: $action<br>\n";
				StageShowLibUtilsClass::print_r($res, 'res');
				StageShowLibUtilsClass::print_r($args, 'args');
			}
			
			if (!isset($args->slug)) return $res;
			
			if ($args->slug !== 'stageshowplus') return $res;
			
			$serverObj = $this->ReadVersionServer('info', 'O:');
			if ($serverObj != null)
				$res = $serverObj;
				
			return $res;			
		}
		
		function stageshow_update_check($pluginsChecked)
		{
			// MJS .. check stageshow for updates
			//ToDebugLog(__FILE__, __LINE__, 'stageshow_update_check - ENTRY', print_r($pluginsChecked, true));
			
			$basename = plugin_basename($this->opts['Caller']);
			unset($pluginsChecked->response[$basename]);
				
			// Limit number of update checks ... 
			//		only checks for updates the first time the site_transient is updated
			if (isset($this->sshow_update))
			{
				if (isset($this->sshow_update->new_version))
					$pluginsChecked->response[$basename] = $this->sshow_update;
			
				//ToDebugLog(__FILE__, __LINE__, 'stageshow_update_check - bail out (already checked)', $pluginsChecked);
			}
			else
			{
						
			if ( ! is_object($pluginsChecked) )
				$pluginsChecked = new stdClass;
			if (!isset($pluginsChecked->last_checked)) 
				$pluginsChecked->last_checked = 0;
							
			$versionCheck = $this->DoVersionCheck($pluginsChecked);
				 
			if ($versionCheck['UpdateAvailable'])
			{
				// Add version number and URLs etc.
				$this->sshow_update = new stdClass();				
				$this->sshow_update->id = $versionCheck['Id'];
				$this->sshow_update->slug = $versionCheck['Slug'];
				$this->sshow_update->new_version = $versionCheck['NewVersion'];
				$this->sshow_update->url = $versionCheck['URL'];
				$this->sshow_update->package = $versionCheck['Package'];
					
				$pluginsChecked->response[$basename] = $this->sshow_update;
			}
			else
			{
				$this->sshow_update = false;
			}
				
			
			//ToDebugLog(__FILE__, __LINE__, 'stageshow_update_check - Update available', print_r($pluginsChecked, true));
			}
			
			return $pluginsChecked;
		}
		 
		function GetInfoServerURL($pagename)
		{
			$url = parent::GetInfoServerURL($pagename);
			
			$url = add_query_arg('txnid', urlencode($this->adminOptions['AuthTxnId']), $url);
			$url = add_query_arg('saleemail', urlencode($this->adminOptions['AuthTxnEMail']), $url);
			
			return $url;
		}
		
		function ReadVersionServer($remotePage, $dataHeader)
		{
			$updateCheckURL = $this->GetInfoServerURL($remotePage);
			
			//$updateCheckURL = add_query_arg('txnid', urlencode($this->adminOptions['AuthTxnId']), $updateCheckURL);
			//$updateCheckURL = add_query_arg('saleemail', urlencode($this->adminOptions['AuthTxnEMail']), $updateCheckURL);

			$response = $this->HTTPAction($updateCheckURL);
			if ($this->getOption('Dev_ShowMiscDebug'))
			{
				echo "<br><br>updateCheckURL: $updateCheckURL <br>\n";			
				StageShowLibUtilsClass::print_r($response, 'response');
			}
			if ($response['APIStatus'] != 200)
			{
				//echo "<br>stageshow_plugins_api - bail out (HTTP request failed)<br>\n";
				return null;
			}
			
			$rtnData = $response['APIResponseText'];
			if (substr($rtnData, strlen($dataHeader)) !== $dataHeader)
			{
				$dataPosn = strpos($rtnData, $dataHeader);
				if ($dataPosn > 0) 
					$rtnData = substr($rtnData, $dataPosn);
			}
			
			try 
			{
				$res = @unserialize($rtnData);
			} 
			catch (Exception $e) 
			{
				$res = null;
			}
			
			return $res;
		}
		
		function DoVersionCheck()
		{
			// Get Version information from custom version server
			/*
				Update check on Wordpress.org is as follows:
					URL:	http://api.wordpress.org/plugins/update-check/1.0
					
			*/
			$chkResult = $this->ReadVersionServer('version', 'a:');
			
			if ($this->getOption('Dev_ShowMiscDebug') == 1)
				StageShowLibUtilsClass::print_r($chkResult, '<br>chkResult');
			
			return $chkResult;
		}

		function SendSaleReport()
		{
			if (!isset($this->adminOptions['EMailSummaryTemplatePath']) || ($this->adminOptions['EMailSummaryTemplatePath'] == '')) 
				return 'EMailSummaryTemplatePath not defined';			
			$templatePath = $this->GetEmailTemplatePath('EMailSummaryTemplatePath');
			
			if (!isset($this->adminOptions['SaleSummaryEMail']) || ($this->adminOptions['SaleSummaryEMail'] == '')) 
				return 'SaleSummaryEMail not defined';			
			$EMailTo = $this->adminOptions['SaleSummaryEMail'];
	
			$salesSummary = $this->GetAllSalesQty();
			return $this->SendEMailFromTemplate($salesSummary, $templatePath, $EMailTo);
		}
		
		function LogSale($results, $isCheckout = false)
		{
			$saleID = parent::LogSale($results, $isCheckout);
			
			// FUNCTIONALITY: DBase - StageShow+ - Send Sale Summary EMail
			$this->SendSaleReport();		
				
			return $saleID;			
		}
		
		function CreateSample()
		{
			// FUNCTIONALITY: DBase - StageShow+ - Implement "Create Sample"
			// Call CreateSample() in base class
			parent::CreateSample();
			
			$showName2 = "Real Programmers dont write PASCAL";
			if ( isset($this->testModeEnabled) ) 
			{ 
				$showName2 .= " (".StageShowLibUtilsClass::GetSiteID().")"; 
			}
			
			// Sample dates to reflect current date/time
			$showTime2 = date(self::STAGESHOW_DATE_FORMAT, strtotime("+3 days"))." 20:00:00";
			
			// Populate table
			$showID2 = $this->AddShow($showName2);
			
			// Populate performances table	    
			$perfID1 = $this->CreateNewPerformance($statusMsg, $showID2, $showTime2, "EveningPerf", 80);
			if ($perfID1 == 0)
			{
				echo '<div id="message" class="error"><p>'.__('Cannot Add Performances', $this->get_domain()).' - '.$statusMsg.'</p></div>';
				return;
			}
			
			// Populate prices table
			$priceID2_A1 = $this->AddPrice($perfID1, 'Adult', PRICEID2_A1);
			$priceID2_C1 = $this->AddPrice($perfID1, 'Child', PRICEID2_C1);

			$perfsList = $this->GetPerformancesListByShowID($showID2);
			{
				$this->UpdateCartButtons($perfsList);
			}
			
			if (!$this->isOptionSet('Dev_NoSampleSales'))
			{
				$priceID1_A1 = $priceID2_A1-7;
				$priceID1_A4 = $priceID2_A1-4;
				
				// Add some ticket sales
				$saleTime1 = date(self::STAGESHOW_DATE_FORMAT, strtotime("-2 days"))." 11:09:22";
				$saleTime2 = date(self::STAGESHOW_DATE_FORMAT, strtotime("-1 days"))." 14:27:09";
				
				$saleEMail = 'sample@extns.co.zz';
				if (defined('STAGESHOW_SAMPLE_EMAIL'))
					$saleEMail = STAGESHOW_SAMPLE_EMAIL;
				$saleID = $this->AddSaleWithFee($saleTime1, 'A.Bloke', $saleEMail, 14.50, 0.69, 'poiuytre', PAYPAL_APILIB_SALESTATUS_COMPLETED,
				'Another Bloke', 'Castle Grand', 'Bigcity', 'Dyfbluedd', 'DY1 7ZZ', 'UK');
				$this->AddSaleItem($saleID, $priceID2_A1, 2, PRICEID2_A1);
				$this->AddSaleItem($saleID, $priceID2_C1, 1, PRICEID2_C1);
				
				// Add some ticket sales
				$saleEMail = 'me@selse.org.uk';
				if (defined('STAGESHOW_SAMPLE_EMAIL'))
					$saleEMail = STAGESHOW_SAMPLE_EMAIL;
				$saleID = $this->AddSaleWithFee($saleTime2, 'Mr S.Else', $saleEMail, 64.50, 2.40, 'LQKWJS55', PAYPAL_APILIB_SALESTATUS_COMPLETED,
				'Somebody Else', 'Down and Out', 'Very Rural', 'Tinyshire', 'TN55 8XX', 'UK');
				$this->AddSaleItem($saleID, $priceID1_A1, 3, PRICEID1_A1);
				$this->AddSaleItem($saleID, $priceID1_A4, 4, PRICEID1_A4);					
			}
			
			// Create a couple of price plans
			$planID1 = $this->AddPlan('Matinee', 'Adult', 5.25);
			if ($planID1 > 0)
				$this->AddPreset($planID1, 'Child', 2.75);
			
			$planID2 = $this->AddPlan('Evening', 'Adult', 8.00);
			if ($planID2 > 0)
				$this->AddPreset($planID2, 'Child', 5.50);											
		}
			
		function CreateNewPerformance(&$rtnMsg, $showID, $perfDateTime, $perfRef = '', $perfSeats = -1)
		{
			$perfID = parent::CreateNewPerformance($rtnMsg, $showID, $perfDateTime, $perfRef, $perfSeats);
			if ($perfID <= 0)
				return 0;
				
			// Use Price Plan for New Performance
			if (isset($_POST['pricePlan']))
			{
				$planID = $_POST['pricePlan'];
					
				$results = $this->GetPricePlansListByPlanID($planID);
					
				foreach ($results as $result)
				{
					// Add price entries to performance
					$priceID1_C3 = $this->AddPrice($perfID, $result->priceType, $result->priceValue);
				}
					
				// Get the performance entry from DB and update the PayPal button      
				$perfsList = $this->GetPerformancesListByPerfID($perfID);
				$this->UpdateCartButtons($perfsList);
			}
				
			return $perfID;
		}
			
		function IsShowNameUnique($showName)
		{
			global $wpdb;
      
			$sql  = 'SELECT * FROM '.STAGESHOW_SHOWS_TABLE;
			$sql .= ' WHERE '.STAGESHOW_SHOWS_TABLE.'.showName="'.$showName.'"';
			$this->ShowSQL($sql); 
			
			$showsEntries = $this->get_results($sql);
			return (count($showsEntries) > 0) ? false : true;
		}
		
		function SetShowActivated($showID, $showState = STAGESHOW_STATE_ACTIVE)
		{
			global $wpdb;
      
			$sql  = 'UPDATE '.STAGESHOW_SHOWS_TABLE;
			$sql .= ' SET showState="'.$showState.'"';
			$sql .= ' WHERE '.STAGESHOW_SHOWS_TABLE.'.showID='.$showID;;
			$this->ShowSQL($sql); 

			$wpdb->query($sql);	
			return "OK";							
		}
		
		function UpdateShowName($showID, $showName)
		{
			global $wpdb;
      
			if (!$this->IsShowNameUnique($showName))
				return "ERROR";
				
			$sql  = 'UPDATE '.STAGESHOW_SHOWS_TABLE;
			$sql .= ' SET showName="'.$showName.'"';
			$sql .= ' WHERE '.STAGESHOW_SHOWS_TABLE.'.showID='.$showID;;
			$this->ShowSQL($sql); 
			$wpdb->query($sql);	

			// Show Name Changed ... Updated Any Hosted Buttons
			$perfsList = $this->GetPerformancesListByShowID($showID);
			$this->UpdateCartButtons($perfsList);
													
			return "OK";							
		}
		
		function GetShowID($showName)
		{
			global $wpdb;
      
			$sql  = 'SELECT * FROM '.STAGESHOW_SHOWS_TABLE;
			$sql .= ' WHERE '.STAGESHOW_SHOWS_TABLE.'.showName="'.$showName.'"';
			$this->ShowSQL($sql); 
			
			$showsEntries = $this->get_results($sql);
			return (count($showsEntries) > 0) ? $showsEntries[0]->showID : 0;
		}
		
		function CanAddShow()
		{
			return true;
		}

		function AddShow($showName = '', $showState = STAGESHOW_STATE_ACTIVE)
		{
			global $wpdb;
      
	      	if ($showName === '')
	      	{
				$newNameNo = 1;
				while (true)
				{
					$showName = __('Unnamed Show', $this->get_domain()).' '.$newNameNo;
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
			$this->ShowSQL($sql); 
			$wpdb->query($sql);	
					
     		return mysql_insert_id();
		}
				
		function GetJoinedTables($sqlFilters = null, $classID = '')
		{
			$sqlJoin = '';
			if ($classID != __CLASS__)
			{
				$sqlJoin .= parent::GetJoinedTables($sqlFilters, $classID);
			}
			else if (isset($sqlFilters['derivedJoins']))
			{
				return $sqlJoin;
			}
			
			$joinType = isset($sqlFilters['JoinType']) ? $sqlFilters['JoinType'] : 'JOIN';
			
			$sqlJoin .= " $joinType ".STAGESHOW_SHOWS_TABLE.' ON '.STAGESHOW_SHOWS_TABLE.'.showID='.STAGESHOW_PERFORMANCES_TABLE.'.showID';
			
			return $sqlJoin;
		}
		
		function GetWhereSQL($sqlFilters)
		{
			$sqlWhere = parent::GetWhereSQL($sqlFilters);
			$sqlCmd = ($sqlWhere === '') ? ' WHERE ' : ' AND ';
			
			if (isset($sqlFilters['showID']) && ($sqlFilters['showID'] > 0))
			{
				$sqlWhere .= $sqlCmd.STAGESHOW_SHOWS_TABLE.'.showID="'.$sqlFilters['showID'].'"';
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
					case 'showID':
						$sqlOpts = $this->AddSQLOpt($sqlOpts, ' GROUP BY ', STAGESHOW_SHOWS_TABLE.'.showID');
						break;
												
					default:
						break;
				}
			}

			$sqlOpts = parent::GetOptsSQL($sqlFilters, $sqlOpts);
			return $sqlOpts;
		}
		
		function DeleteShowByShowID($ID)
		{
			return $this->DeleteShow($ID, 'showID');
		}			
		
		private function DeleteShow($ID = 0, $IDfield = 'showID')
		{
			global $wpdb;
			
			// Get the show name
			$sql = 'SELECT * FROM '.STAGESHOW_SHOWS_TABLE;
			$sql .= ' WHERE '.STAGESHOW_SHOWS_TABLE.".$IDfield=$ID";
			$this->ShowSQL($sql); 
			$results = $this->get_results($sql);
			
			if (count($results) == 0) return '';
			
			// Delete a show entry
			$sql  = 'DELETE FROM '.STAGESHOW_SHOWS_TABLE;
			$sql .= ' WHERE '.STAGESHOW_SHOWS_TABLE.".$IDfield=$ID";
			$this->ShowSQL($sql); 
			$wpdb->query($sql);
			
			return $results[0]->showName;
		}			
		
		function CanAddPerformance()
		{
			// PLen in options is ignored ... just return true
			return true;
		}

 		function IsPerfEnabled($result)
		{
				// Calculate how long before the booking window closes ...
				if (strlen($result->perfExpires) == 0)
				{
					$result->perfExpires = $result->perfDateTime;
					$expireLimit = ($this->adminOptions['PerfExpireLimit'] * $this->adminOptions['PerfExpireUnits']);
				}
				else
				{
					// Expire time overridden by setting for this performance
					$expireLimit = 0;
				}
				$timeToPerf = strtotime($result->perfExpires) - current_time('timestamp') - $expireLimit;				
								
				if ($timeToPerf < 0) 
				{					
					$timeToPerf *= -1;
					
					echo "<!-- Performance Expired ".$timeToPerf." seconds ago -->\n";
					// TODO-PRIORITY - Disable Performance Button (using Inventory Control) when it expires
					return false;
				}
				//echo "<!-- Performance Expires in ".$timeToPerf." seconds -->\n";
				
				return parent::IsPerfEnabled($result);
		}
				
		function GetAllPlansList()
		{
			$sql  = 'SELECT * FROM '.STAGESHOW_PLANS_TABLE;
			
			$this->ShowSQL($sql); 
			
			$presetsListArray = $this->get_results($sql);
			
			return $presetsListArray;
		}
		
		function GetPricePlansListByPlanID($planID)
		{
			$sql  = 'SELECT * FROM '.STAGESHOW_PRESETS_TABLE;
			$sql .= " LEFT JOIN ".STAGESHOW_PLANS_TABLE.' ON '.STAGESHOW_PLANS_TABLE.'.planID='.STAGESHOW_PRESETS_TABLE.'.planID';
			$sql .= ' WHERE '.STAGESHOW_PRESETS_TABLE.'.planID="'.$planID.'"';	
			
			$this->ShowSQL($sql); 
			
			$presetsListArray = $this->get_results($sql);
			
			return $presetsListArray;
		}
		
		function GetAllPricePlansList()
		{
			$sql  = 'SELECT * FROM '.STAGESHOW_PRESETS_TABLE;
			
			$this->ShowSQL($sql); 
			
			$presetsListArray = $this->get_results($sql);
			
			return $presetsListArray;
		}
		
		function AddPlan($planRef = '', $priceType='', $priceValue=0.0)
		{
			global $wpdb;
      
      		if ($planRef === '')
      		{
				$newNameNo = 1;
				while (true)
				{
					$planRef = 'Unnamed Price Plan '.$newNameNo;
					if ($this->IsPlanRefUnique($planRef))
						break;
					$newNameNo++;
				}
			}
			else
			{
				if (!$this->IsPlanRefUnique($planRef))
					return 0;	// Error - Show Name is not unique
			}
						
			$sql = 'INSERT INTO '.STAGESHOW_PLANS_TABLE.'(planRef) VALUES("'.$planRef.'")';
			$this->ShowSQL($sql); 
			$wpdb->query($sql);	
					
	     	$planId = mysql_insert_id();
	     	
	     	// Add a preset - default settings if nothing passed in call to this function
	     	$this->AddPreset($planId, $priceType, $priceValue);
	     	
	     	return $planId;
		}
				
		function IsPlanRefUnique($planRef)
		{
			global $wpdb;
      
			$sql  = 'SELECT * FROM '.STAGESHOW_PLANS_TABLE;
			$sql .= ' WHERE '.STAGESHOW_PLANS_TABLE.'.planRef="'.$planRef.'"';
			$this->ShowSQL($sql); 
			
			$groupsEntries = $this->get_results($sql);
			return (count($groupsEntries) > 0) ? false : true;
		}
		
		function UpdatePlanRef($planID, $planRef)
		{
			global $wpdb;
      
			if (!$this->IsPlanRefUnique($planRef))
				return false;
				
			$sql  = 'UPDATE '.STAGESHOW_PLANS_TABLE;
			$sql .= ' SET planRef="'.$planRef.'"';
			$sql .= ' WHERE '.STAGESHOW_PLANS_TABLE.'.planID='.$planID;;
			$this->ShowSQL($sql); 
			$wpdb->query($sql);	

			return true;							
		}
		
		function DeletePlan($planID = '')
		{
			global $wpdb;
			
			// Delete all preset entries for this group
			$sql  = 'DELETE FROM '.STAGESHOW_PRESETS_TABLE;
			$sql .= ' WHERE '.STAGESHOW_PRESETS_TABLE.".planID=$planID";
			$this->ShowSQL($sql); 
			$wpdb->query($sql);
			
			// Delete the group entry for this group
			$sql  = 'DELETE FROM '.STAGESHOW_PLANS_TABLE;
			$sql .= ' WHERE '.STAGESHOW_PLANS_TABLE.".planID=$planID";
			$this->ShowSQL($sql); 
			$wpdb->query($sql);
		}
		
		function AddPreset($planId, $priceType='', $priceValue=1.0)
		{
			global $wpdb;
      
			if ($priceType === '')
			{
				$newNameNo = 1;
				while (true)
				{
					$priceType = 'TYPE'.$newNameNo;
					if ($this->IsPriceRefUnique($planId, $priceType))
						break;
					$newNameNo++;
				}
			}
			else
			{
				if (!$this->IsPriceRefUnique($planId, $priceType))
					return 0;	// Error - Ref is not unique
			}
						
			$sql  = 'INSERT INTO '.STAGESHOW_PRESETS_TABLE.'(planId, priceType, priceValue)';
			$sql .= ' VALUES('.$planId.', "'.$priceType.'", "'.$priceValue.'")';
			$this->ShowSQL($sql); 
			$wpdb->query($sql);	
					
     		return mysql_insert_id();
		}
				
		function UpdatePreset($presetID, $presetField, $presetValue)
		{
			global $wpdb;
      
			if ($presetField == 'priceType')
		    {
				// TODO-PRIORITY - Call IsPriceRefUnique()
				/*
				if (!$this->IsPriceRefUnique($planID, $presetValue))
					return "ERROR";
				*/ 
			}
							
			$sql  = 'UPDATE '.STAGESHOW_PRESETS_TABLE;
			$sql .= ' SET '.$presetField.'="'.$presetValue.'"';
			$sql .= ' WHERE '.STAGESHOW_PRESETS_TABLE.'.presetID='.$presetID;;
			$this->ShowSQL($sql); 
			$wpdb->query($sql);	

			return "OK";							
		}
		
		function DeletePreset($presetID)
		{
			global $wpdb;
			
			// Delete a preset entry
			$sql  = 'DELETE FROM '.STAGESHOW_PRESETS_TABLE;
			$sql .= ' WHERE '.STAGESHOW_PRESETS_TABLE.".presetID=$presetID";
			$this->ShowSQL($sql); 
			$wpdb->query($sql);
			
			$this->DeleteEmptyPlans();
		}
		
		function DeleteEmptyPlans()
		{
			global $wpdb;
			
			// Delete all empty preset entries
			$sql  = 'DELETE plan.* FROM '.STAGESHOW_PLANS_TABLE.' plan';
			$sql .= " LEFT JOIN ".STAGESHOW_PRESETS_TABLE.' preset ON plan.planID=preset.planID';
			$sql .= ' WHERE preset.planID IS NULL';
			$this->ShowSQL($sql); 
			$wpdb->query($sql);
		}
		
		function IsPriceRefUnique($planID, $priceType)
		{
			global $wpdb;
      
			$sql  = 'SELECT * FROM '.STAGESHOW_PRESETS_TABLE;
			$sql .= " LEFT JOIN ".STAGESHOW_PLANS_TABLE.' ON '.STAGESHOW_PLANS_TABLE.'.planID='.STAGESHOW_PRESETS_TABLE.'.planID';
			$sql .= ' WHERE '.STAGESHOW_PRESETS_TABLE.'.planID="'.$planID.'"';	
			$sql .= ' AND '.STAGESHOW_PRESETS_TABLE.'.priceType="'.$priceType.'"';
			$this->ShowSQL($sql); 
			
			$presetsEntries = $this->get_results($sql);
			return (count($presetsEntries) > 0) ? false : true;
		}
		
		function DeleteSale($saleID)
		{
			global $wpdb;
			
			parent::DeleteSale($saleID);
			
			$this->DeleteVerify($saleID);
		}
		
		function GetLocation()
		{
			// FUNCTIONALITY: DBase - StageShow+ - Get Terminal Location
			$terminalLocation = '';
			
			// Get the terminal location from site cookies
			if (isset($_COOKIE['TerminalLocation']))
				$terminalLocation = $_COOKIE['TerminalLocation'];
			else
				$terminalLocation = '';

			// If terminal location not defined use "Unknown"
			if (strlen($terminalLocation) == 0)
				$terminalLocation = 'Unknown';
				
			return $terminalLocation;
		}
		
		function LogVerify($saleID)
		{
			$verifyDateTime = current_time('mysql');
			$verifyLocation = $this->GetLocation();

			global $wpdb;
			
			$sql  = 'INSERT INTO '.STAGESHOW_VERIFYS_TABLE.'(saleID, verifyDateTime, verifyLocation)';
			$sql .= ' VALUES('.$saleID.', "'.$verifyDateTime.'", "'.$verifyLocation.'")';
			$this->ShowSQL($sql); 
			$wpdb->query($sql);	
					
     	return mysql_insert_id();
		}
		
		function DeleteVerify($saleID)
		{
			global $wpdb;
					
			$sql  = 'DELETE FROM '.STAGESHOW_VERIFYS_TABLE;	
			$sql .= ' WHERE '.STAGESHOW_VERIFYS_TABLE.".saleID".$this->GetWhereParam('saleID', $saleID);
					
			$this->ShowSQL($sql); 
			$wpdb->query($sql);
		}
		
		function GetVerifysList($saleID)
		{
			$sql  = 'SELECT * FROM '.STAGESHOW_VERIFYS_TABLE;	
			$sql .= ' WHERE '.STAGESHOW_VERIFYS_TABLE.".saleID=$saleID";
					
			$this->ShowSQL($sql); 
			
			$salesListArray = $this->get_results($sql);
			
			return $salesListArray;
		}
		
		function AddEMailFields($EMailTemplate, $saleDetails)
		{
			// FUNCTIONALITY: DBase - StageShow+ - Add DB fields to EMail
			// Call base class
			$eMailFields = parent::AddEMailFields($EMailTemplate, $saleDetails);
					
			$eMailFields = str_replace('[logoimg]', $this->getImageURL('PayPalLogoImageFile'), $eMailFields);
			$eMailFields = str_replace('[headerimg]', $this->getImageURL('PayPalHeaderImageFile'), $eMailFields);
			
			if (strpos($eMailFields, '[saleBarcode]'))
			{
				// Add Barcode Image Link to Email
				$eMailFields = str_replace('[saleBarcode]', 
					"<img alt=\"Sale Barcode\" src=\"cid:".STAGESHOW_BARCODE_IDENTIFIER.$saleDetails->saleTxnId."\">", 
					$eMailFields);
			}
			
			return $eMailFields;
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

		function AddTableLocks($sql)
		{
			$sql = parent::AddTableLocks($sql);
			$sql .= ', '.STAGESHOW_SHOWS_TABLE.' READ';
			return $sql;
		}
		
	}			// class StageShowPlusDBaseClass
}				// if (!class_exists('StageShowPlusDBaseClass'))

?>