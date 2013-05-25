<?php
/* 
Description: StageShow Plugin Top Level Code
 
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

include STAGESHOW_INCLUDE_PATH.'stageshowplus_dbase_api.php';

if (!class_exists('StageShowPluginClass'))
	include 'stageshow_main.php';

if (!class_exists('StageShowPlusPluginClass') && class_exists('StageShowPluginClass')) 
{
  class StageShowPlusPluginClass extends StageShowPluginClass // Define class
  {
		function __construct($caller) 
		{
			parent::__construct($caller);	
			
			$this->adminClassFilePrefix = 'stageshowplus';
			$this->adminClassPrefix = 'StageShowPlus';			
		}
		
		function CreateDBClass($caller)
		{			
			return new StageShowPlusDBaseClass($caller);		
		}
		
    	function activate()
		{
			parent::activate();	
			
			$myDBaseObj = $this->myDBaseObj;
			
 			if ($myDBaseObj->adminOptions['ActivationCount'] == 1)
			{
				if (defined('STAGESHOW_ACTIVATE_AUTH_TXNID'))
					$myDBaseObj->adminOptions['AuthTxnId'] = STAGESHOW_ACTIVATE_AUTH_TXNID;
				if (defined('STAGESHOW_ACTIVATE_AUTH_TXNEMAIL'))
					$myDBaseObj->adminOptions['AuthTxnEMail'] = STAGESHOW_ACTIVATE_AUTH_TXNEMAIL;
			}
			
			$myDBaseObj->CheckEmailTemplatePath('EMailSummaryTemplatePath');
			
      		$this->saveStageshowOptions();      
		}
    
		function OutputContent_BoxOffice( $atts )
		{			
			return parent::OutputContent_BoxOffice($atts);
		}
		
	}
}

?>