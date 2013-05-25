<?php
/* 
Description: Code for Managing StageShow+ Settings
 
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

include STAGESHOW_ADMIN_PATH.'stageshow_manage_settings.php';

if (!class_exists('StageShowPlusSettingsAdminListClass')) 
{
	define('STAGESHOW_ADMINID_TEXTLEN',110);
	
	define('STAGESHOW_ADMINID_EDITLEN', 60);
	
	class StageShowPlusSettingsAdminListClass extends StageShowSettingsAdminListClass // Define class
	{				
		function __construct($env, $editMode = false) //constructor
		{
			// Call base constructor
			parent::__construct($env, $editMode);
		}
		
		function GetMainRowsDefinition()
		{
	  		// FUNCTIONALITY: Settings - StageShow+ - Auto Update Settings
			$this->isTabbedOutput = true;
			
			$rowDefs = array(			
				array(self::TABLEPARAM_LABEL => 'Auto Update Settings',  self::TABLEPARAM_ID => 'auto-update-settings-tab', ),
			);
			
			$rowDefs = $this->MergeSettings(parent::GetMainRowsDefinition(), $rowDefs);
			return $rowDefs;
		}		
		
		function GetDetailsRowsDefinition()
		{
			$pluginID = basename(dirname(dirname(__FILE__)));	// Library files should be in 'include' folder			
			$templatePath = WP_CONTENT_DIR . '/uploads/'.$pluginID.'/emails/';
			
			$rowDefs = array(			
				array(self::TABLEPARAM_LABEL => 'StageShow Sales EMail Name',		self::TABLEPARAM_TAB => 'stageshow-settings-tab',  self::TABLEPARAM_ID => 'AdminID',                  self::TABLEPARAM_TYPE => self::TABLEENTRY_TEXT,   self::TABLEPARAM_LEN => STAGESHOW_ADMINID_TEXTLEN,  self::TABLEPARAM_SIZE => STAGESHOW_ADMINID_EDITLEN,  self::TABLEPARAM_BEFORE => 'AdminEMail', ),

				array(self::TABLEPARAM_LABEL => 'Sale Summary Report EMail',        self::TABLEPARAM_TAB => 'stageshow-settings-tab',  self::TABLEPARAM_ID => 'SaleSummaryEMail',         self::TABLEPARAM_TYPE => self::TABLEENTRY_TEXT,   self::TABLEPARAM_LEN => STAGESHOW_MAIL_TEXTLEN, self::TABLEPARAM_SIZE => STAGESHOW_MAIL_EDITLEN, self::TABLEPARAM_BEFORE => 'UseCurrencySymbol', ),
				array(self::TABLEPARAM_LABEL => 'Summary EMail Template',	    	self::TABLEPARAM_TAB => 'stageshow-settings-tab',  self::TABLEPARAM_ID => 'EMailSummaryTemplatePath', self::TABLEPARAM_TYPE => self::TABLEENTRY_SELECT, self::TABLEPARAM_DIR => $templatePath, self::TABLEPARAM_EXTN => 'php', self::TABLEPARAM_BEFORE => 'UseCurrencySymbol', ),
				
				array(self::TABLEPARAM_LABEL => 'Performance Expires Limit',		self::TABLEPARAM_TAB => 'stageshow-settings-tab',  self::TABLEPARAM_ID => 'PerfExpireLimit',          self::TABLEPARAM_TYPE => self::TABLEENTRY_TEXT,   self::TABLEPARAM_LEN => 7, self::TABLEPARAM_SIZE => 7, self::TABLEPARAM_NEXTINLINE => true, ),
				array(self::TABLEPARAM_LABEL => '',				                    self::TABLEPARAM_TAB => 'stageshow-settings-tab',  self::TABLEPARAM_ID => 'PerfExpireUnits',          self::TABLEPARAM_TYPE => self::TABLEENTRY_SELECT, self::TABLEPARAM_ITEMS => array('1|'.__('Seconds', $this->myDomain), '60|'.__('Minutes', $this->myDomain), '3600|'.__('Hours', $this->myDomain)), ),
				array(self::TABLEPARAM_LABEL => 'Terminal Location',				self::TABLEPARAM_TAB => 'stageshow-settings-tab',  self::TABLEPARAM_ID => 'TerminalLocation',         self::TABLEPARAM_TYPE => self::TABLEENTRY_COOKIE, self::TABLEPARAM_LEN => STAGESHOW_LOCATION_TEXTLEN, ),
				array(self::TABLEPARAM_LABEL => 'Log Files Folder Path',			self::TABLEPARAM_TAB => 'stageshow-settings-tab',  self::TABLEPARAM_ID => 'LogsFolderPath',           self::TABLEPARAM_TYPE => self::TABLEENTRY_TEXT,   self::TABLEPARAM_LEN => STAGESHOW_FILEPATH_TEXTLEN, self::TABLEPARAM_SIZE => STAGESHOW_FILEPATH_EDITLEN, ),
				
				array(self::TABLEPARAM_LABEL => 'Sale Transaction ID',              self::TABLEPARAM_TAB => 'auto-update-settings-tab', self::TABLEPARAM_ID => 'AuthTxnId',                self::TABLEPARAM_TYPE => self::TABLEENTRY_TEXT,   self::TABLEPARAM_LEN => PAYPAL_APILIB_PPSALETXNID_TEXTLEN, self::TABLEPARAM_SIZE => PAYPAL_APILIB_PPSALETXNID_EDITLEN, ),
				array(self::TABLEPARAM_LABEL => 'Sale Txn EMail Address',           self::TABLEPARAM_TAB => 'auto-update-settings-tab', self::TABLEPARAM_ID => 'AuthTxnEMail',             self::TABLEPARAM_TYPE => self::TABLEENTRY_TEXT,   self::TABLEPARAM_LEN => STAGESHOW_MAIL_TEXTLEN,       self::TABLEPARAM_SIZE => STAGESHOW_MAIL_EDITLEN, ),
			);
			
			$rowDefs = $this->MergeSettings(parent::GetDetailsRowsDefinition(), $rowDefs);
			return $rowDefs;
		}
	}
}

if (!class_exists('StageShowPlusSettingsAdminClass')) 
{
  class StageShowPlusSettingsAdminClass extends StageShowSettingsAdminClass // Define class
  {
		function __construct($env) //constructor
		{
			// Call base constructor
			parent::__construct($env);			
		}
		
		function GetAdminListClass()
		{
			return 'StageShowPlusSettingsAdminListClass';			
		}
		
	}
}

?>