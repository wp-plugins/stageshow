<?php
/* 
Description: Code for Managing StageShow Settings
 
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

include STAGESHOW_INCLUDE_PATH.'stageshowlib_paypal_settings.php';

if (!class_exists('StageShowSettingsAdminListClass')) 
{
	define('STAGESHOW_ORGANISATIONID_TEXTLEN',60);
	define('STAGESHOW_MAIL_TEXTLEN',127);
		
	define('STAGESHOW_ORGANISATIONID_EDITLEN',60);
	define('STAGESHOW_MAIL_EDITLEN', 60);
	
	class StageShowSettingsAdminListClass extends PayPalSettingsAdminListClass // Define class
	{		
		function __construct($env, $editMode = false) //constructor
		{	
			// Call base constructor
			parent::__construct($env, $editMode);
			
			$this->defaultTabId = 'stageshow-settings-tab';
		}
		
		function GetTableID($result)
		{
			return "stageshow-settings";
		}
		
		function GetMainRowsDefinition()
		{
			$this->isTabbedOutput = true;
			
			$rowDefs = array(			
				array(self::TABLEPARAM_LABEL => 'General',    self::TABLEPARAM_ID => 'stageshow-settings-tab', ),
				array(self::TABLEPARAM_LABEL => 'Advanced',      self::TABLEPARAM_ID => 'advanced-settings-tab',  self::TABLEPARAM_AFTER => 'stageshow-settings-tab',  ),				
			);
			
			$rowDefs = $this->MergeSettings(parent::GetMainRowsDefinition(), $rowDefs);
			return $rowDefs;
		}		
		
		function GetDetailsRowsDefinition()
		{
			$pluginID = basename(dirname(dirname(__FILE__)));	// Library files should be in 'include' folder			
			$templatePath = WP_CONTENT_DIR . '/uploads/'.$pluginID.'/emails/';
			
			$checkoutNoteOptions = array(
				'header|'.__('In Header', $this->myDomain),
				'titles|'.__('Above Titles', $this->myDomain),
				'above|'.__('Above Buttons', $this->myDomain),
				'below|'.__('Below Buttons', $this->myDomain),
				'bottom|'.__('At Bottom', $this->myDomain),
			);
			
			$rowDefs = array(
				array(self::TABLEPARAM_LABEL => 'Organisation ID',                 self::TABLEPARAM_TAB => 'stageshow-settings-tab', self::TABLEPARAM_ID => 'OrganisationID',		   self::TABLEPARAM_TYPE => self::TABLEENTRY_TEXT,     self::TABLEPARAM_LEN => STAGESHOW_ORGANISATIONID_TEXTLEN, self::TABLEPARAM_SIZE => STAGESHOW_ORGANISATIONID_EDITLEN, ),				
				array(self::TABLEPARAM_LABEL => 'Sale EMail Template',             self::TABLEPARAM_TAB => 'stageshow-settings-tab', self::TABLEPARAM_ID => 'EMailTemplatePath',       self::TABLEPARAM_TYPE => self::TABLEENTRY_SELECT,   self::TABLEPARAM_DIR => $templatePath, self::TABLEPARAM_EXTN => 'php', self::TABLEPARAM_BEFORE => 'AdminEMail', ),
				array(self::TABLEPARAM_LABEL => 'StageShow Sales EMail',           self::TABLEPARAM_TAB => 'stageshow-settings-tab', self::TABLEPARAM_ID => 'AdminEMail',			   self::TABLEPARAM_TYPE => self::TABLEENTRY_TEXT,     self::TABLEPARAM_LEN => STAGESHOW_MAIL_TEXTLEN,      self::TABLEPARAM_SIZE => STAGESHOW_MAIL_EDITLEN, ),
				array(self::TABLEPARAM_LABEL => 'Bcc EMails to WP Admin',          self::TABLEPARAM_TAB => 'stageshow-settings-tab', self::TABLEPARAM_ID => 'BccEMailsToAdmin',	       self::TABLEPARAM_TYPE => self::TABLEENTRY_CHECKBOX, self::TABLEPARAM_TEXT => 'Send EMail confirmation to Administrator' ),
				array(self::TABLEPARAM_LABEL => 'Currency Symbol',		           self::TABLEPARAM_TAB => 'stageshow-settings-tab', self::TABLEPARAM_ID => 'UseCurrencySymbol',       self::TABLEPARAM_TYPE => self::TABLEENTRY_CHECKBOX, self::TABLEPARAM_TEXT => 'Include in Box Office Output' ),
				array(self::TABLEPARAM_LABEL => 'Items per Page',                  self::TABLEPARAM_TAB => 'stageshow-settings-tab', self::TABLEPARAM_ID => 'PageLength',			   self::TABLEPARAM_TYPE => self::TABLEENTRY_TEXT,     self::TABLEPARAM_LEN => 3, self::TABLEPARAM_DEFAULT => STAGESHOWLIB_EVENTS_PER_PAGE),
				array(self::TABLEPARAM_LABEL => 'Max Ticket Qty',                  self::TABLEPARAM_TAB => 'stageshow-settings-tab', self::TABLEPARAM_ID => 'MaxTicketQty',            self::TABLEPARAM_TYPE => self::TABLEENTRY_TEXT,     self::TABLEPARAM_LEN => 2, self::TABLEPARAM_DEFAULT => STAGESHOW_MAXTICKETCOUNT),

				array(self::TABLEPARAM_LABEL => 'Checkout Note Position',          self::TABLEPARAM_TAB => 'advanced-settings-tab',  self::TABLEPARAM_ID => 'CheckoutNotePosn',        self::TABLEPARAM_TYPE => self::TABLEENTRY_SELECT,   self::TABLEPARAM_ITEMS => $checkoutNoteOptions, ),
				array(self::TABLEPARAM_LABEL => 'Checkout Note',                   self::TABLEPARAM_TAB => 'advanced-settings-tab',  self::TABLEPARAM_ID => 'CheckoutNote',            self::TABLEPARAM_TYPE => self::TABLEENTRY_TEXTBOX,  self::TABLEPARAM_ROWS  => 4, self::TABLEPARAM_COLS => 60, ),
			);
			
			$rowDefs = $this->MergeSettings(parent::GetDetailsRowsDefinition(), $rowDefs);
			return $rowDefs;
		}
	}
}
		
if (!class_exists('StageShowSettingsAdminClass')) 
{
	class StageShowSettingsAdminClass extends PayPalSettingsAdminClass // Define class
	{		
		function __construct($env)
		{
			// Call base constructor
			parent::__construct($env);
		}
		
		function GetAdminListClass()
		{
			return 'StageShowSettingsAdminListClass';			
		}
		
	}
}
		

?>