<?php
/* 
Description: Code for Managing StageShow Settings
 
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

include STAGESHOW_INCLUDE_PATH.'stageshowlib_gateway_settings.php';

if (!class_exists('StageShowWPOrgSettingsAdminListClass')) 
{
	define('STAGESHOW_ORGANISATIONID_TEXTLEN',60);
	define('STAGESHOW_MAIL_TEXTLEN',127);
		
	define('STAGESHOW_ORGANISATIONID_EDITLEN',60);
	define('STAGESHOW_MAIL_EDITLEN', 60);
	
	class StageShowWPOrgSettingsAdminListClass extends GatewaySettingsAdminListClass // Define class
	{		
		function __construct($env, $editMode = false) //constructor
		{	
			if (!current_user_can(STAGESHOWLIB_CAPABILITY_SETUPUSER))
			{
				$editMode = false;
			}
			
			// Call base constructor
			parent::__construct($env, $editMode);
			
			if (!$editMode)
			{
				$this->hiddenRowStyle = '';
				$this->hiddenRowsButtonId = '';
				$this->moreText = '';
			}
			
			$this->defaultTabId = 'general-settings-tab';
		}
		
		function GetTableID($result)
		{
			return "stageshow-settings";
		}
		
		function OutputList($results, $updateFailed = false)
		{
			ob_start();
			parent::OutputList($results, $updateFailed);
			$htmlout = ob_get_contents();
			ob_end_clean();
			
			$gatewaySelectIDDef = 'id="GatewaySelected"';
			$gatewaySelectOnClick = ' onchange="stageshow_ClickGateway(this)" ';
			
			$htmlout = str_replace($gatewaySelectIDDef, $gatewaySelectOnClick.$gatewaySelectIDDef, $htmlout);
			echo $htmlout;
		}
		
		function GetMainRowsDefinition()
		{
			$this->isTabbedOutput = true;
			
			$rowDefs = array(			
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'General',    StageShowLibTableClass::TABLEPARAM_ID => 'general-settings-tab', StageShowLibTableClass::TABLEPARAM_AFTER => 'gateway-settings-tab', ),
			);
			
			$rowDefs = $this->MergeSettings(parent::GetMainRowsDefinition(), $rowDefs);
			return $rowDefs;
		}		
		
		function GetDetailsRowsDefinition()
		{
			$pluginID = STAGESHOW_FOLDER;	// Library files should be in 'include' folder			
			$templatePath = WP_CONTENT_DIR . '/uploads/'.$pluginID.'/emails/';
			
			$checkoutNoteOptions = array(
				'header|'.__('In Header', $this->myDomain),
				'titles|'.__('Above Titles', $this->myDomain),
				'above|'.__('Above Buttons', $this->myDomain),
				'below|'.__('Below Buttons', $this->myDomain),
				'bottom|'.__('At Bottom', $this->myDomain),
			);
			
			$rowDefs = array(
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Organisation ID',                 StageShowLibTableClass::TABLEPARAM_TAB => 'general-settings-tab',   StageShowLibTableClass::TABLEPARAM_ID => 'OrganisationID',		 StageShowLibTableClass::TABLEPARAM_TYPE => self::TABLEENTRY_TEXT,     StageShowLibTableClass::TABLEPARAM_LEN => STAGESHOW_ORGANISATIONID_TEXTLEN, StageShowLibTableClass::TABLEPARAM_SIZE => STAGESHOW_ORGANISATIONID_EDITLEN, ),				
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Sale EMail Template',             StageShowLibTableClass::TABLEPARAM_TAB => 'general-settings-tab',   StageShowLibTableClass::TABLEPARAM_ID => 'EMailTemplatePath',     StageShowLibTableClass::TABLEPARAM_TYPE => self::TABLEENTRY_SELECT,   StageShowLibTableClass::TABLEPARAM_DIR => $templatePath, StageShowLibTableClass::TABLEPARAM_EXTN => 'php', StageShowLibTableClass::TABLEPARAM_BEFORE => 'AdminEMail', ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'StageShow Sales EMail',           StageShowLibTableClass::TABLEPARAM_TAB => 'general-settings-tab',   StageShowLibTableClass::TABLEPARAM_ID => 'AdminEMail',			 StageShowLibTableClass::TABLEPARAM_TYPE => self::TABLEENTRY_TEXT,     StageShowLibTableClass::TABLEPARAM_LEN => STAGESHOW_MAIL_TEXTLEN,      StageShowLibTableClass::TABLEPARAM_SIZE => STAGESHOW_MAIL_EDITLEN, ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Bcc EMails to Sales EMail',       StageShowLibTableClass::TABLEPARAM_TAB => 'general-settings-tab',   StageShowLibTableClass::TABLEPARAM_ID => 'BccEMailsToAdmin',	     StageShowLibTableClass::TABLEPARAM_TYPE => self::TABLEENTRY_CHECKBOX, StageShowLibTableClass::TABLEPARAM_TEXT => 'Send EMail confirmation to Administrator' ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Currency Symbol',		           StageShowLibTableClass::TABLEPARAM_TAB => 'general-settings-tab',   StageShowLibTableClass::TABLEPARAM_ID => 'UseCurrencySymbol',     StageShowLibTableClass::TABLEPARAM_TYPE => self::TABLEENTRY_CHECKBOX, StageShowLibTableClass::TABLEPARAM_TEXT => 'Include in Box Office Output' ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Box Office Below Trolley',        StageShowLibTableClass::TABLEPARAM_TAB => 'general-settings-tab',   StageShowLibTableClass::TABLEPARAM_ID => 'ProductsAfterTrolley',  StageShowLibTableClass::TABLEPARAM_TYPE => self::TABLEENTRY_CHECKBOX, StageShowLibTableClass::TABLEPARAM_TEXT => 'Move Box Office below Active Trolley', StageShowLibTableClass::TABLEPARAM_DEFAULT => false ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Items per Page',                  StageShowLibTableClass::TABLEPARAM_TAB => 'general-settings-tab',   StageShowLibTableClass::TABLEPARAM_ID => 'PageLength',			 StageShowLibTableClass::TABLEPARAM_TYPE => self::TABLEENTRY_TEXT,     StageShowLibTableClass::TABLEPARAM_LEN => 3, StageShowLibTableClass::TABLEPARAM_DEFAULT => STAGESHOWLIB_EVENTS_PER_PAGE),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Max Ticket Qty',                  StageShowLibTableClass::TABLEPARAM_TAB => 'general-settings-tab',   StageShowLibTableClass::TABLEPARAM_ID => 'MaxTicketQty',          StageShowLibTableClass::TABLEPARAM_TYPE => self::TABLEENTRY_TEXT,     StageShowLibTableClass::TABLEPARAM_LEN => 2, StageShowLibTableClass::TABLEPARAM_DEFAULT => STAGESHOW_MAXTICKETCOUNT),

				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Checkout Note Position',          StageShowLibTableClass::TABLEPARAM_TAB => 'advanced-settings-tab',  StageShowLibTableClass::TABLEPARAM_ID => 'CheckoutNotePosn',      StageShowLibTableClass::TABLEPARAM_TYPE => self::TABLEENTRY_SELECT,   StageShowLibTableClass::TABLEPARAM_ITEMS => $checkoutNoteOptions, ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Checkout Note',                   StageShowLibTableClass::TABLEPARAM_TAB => 'advanced-settings-tab',  StageShowLibTableClass::TABLEPARAM_ID => 'CheckoutNote',          StageShowLibTableClass::TABLEPARAM_TYPE => self::TABLEENTRY_TEXTBOX,  StageShowLibTableClass::TABLEPARAM_ROWS  => 4, StageShowLibTableClass::TABLEPARAM_COLS => 60, ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Note To Seller',                  StageShowLibTableClass::TABLEPARAM_TAB => 'advanced-settings-tab',  StageShowLibTableClass::TABLEPARAM_ID => 'UseNoteToSeller',       StageShowLibTableClass::TABLEPARAM_TYPE => self::TABLEENTRY_CHECKBOX, StageShowLibTableClass::TABLEPARAM_TEXT => 'Accept Purchaser Text Input',  StageShowLibTableClass::TABLEPARAM_DEFAULT => false ),

				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Seats Available',                 StageShowLibTableClass::TABLEPARAM_TAB => 'advanced-settings-tab',  StageShowLibTableClass::TABLEPARAM_ID => 'ShowSeatsAvailable',    StageShowLibTableClass::TABLEPARAM_TYPE => self::TABLEENTRY_CHECKBOX, StageShowLibTableClass::TABLEPARAM_TEXT => 'Show Seats Available on Box Office',  StageShowLibTableClass::TABLEPARAM_DEFAULT => false ),
			);
			
			$rowDefs = $this->MergeSettings($rowDefs, parent::GetDetailsRowsDefinition());
			return $rowDefs;
		}
		
		function JS_Bottom($defaultTab)
		{
			$jsCode  = parent::JS_Bottom($defaultTab);		
			$jsCode .= "

window.onload = stageshow_OnSettingsLoad;

</script>
			";
			
			return $jsCode;
		}
		
		function GetOnClickHandler()
		{
			return 'stageshow_ClickHeader(this)';
		}
		
	}
}
		
if (!class_exists('StageShowWPOrgSettingsAdminClass')) 
{
	class StageShowWPOrgSettingsAdminClass extends GatewaySettingsAdminClass // Define class
	{		
		function __construct($env)
		{
			// Call base constructor
			parent::__construct($env);
		}
		
	}
}
		

?>