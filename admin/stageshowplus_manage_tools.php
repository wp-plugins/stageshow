<?php
/* 
Description: Code for Admin Tools
 
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

include STAGESHOW_ADMIN_PATH.'stageshow_manage_tools.php';      

if (!class_exists('StageShowPlusToolsAdminClass')) 
{
	class StageShowPlusToolsAdminClass extends StageShowToolsAdminClass // Define class
	{
		function __construct($env) //constructor	
		{
			// Call base constructor
			parent::__construct($env);
		}

		function ValidateSale($env)
		{
			// FUNCTIONALITY: Tools - StageShow+ - Log Sales Verify Calls
			$saleID = parent::ValidateSale($env);
			if ($saleID <= 0) return;
			
			$myDBaseObj = $this->myDBaseObj;
			
			$verifyList = $myDBaseObj->GetVerifysList($saleID);
			$myDBaseObj->LogVerify($saleID);
			
			$salesList = new StageShowSalesAdminVerifyListClass($env);							
			echo '<tr><td colspan="2">'."\n";
			$salesList->OutputList($verifyList);	
			echo "</tr></td>\n";
		}
		
		function OutputExportFormatOptions()
		{
			parent::OutputExportFormatOptions();
?>	
	<option value="ofx"><?php _e('OFX', $this->myDomain); ?>&nbsp;&nbsp;</option>
<?php
		}
		
	}
}

?>