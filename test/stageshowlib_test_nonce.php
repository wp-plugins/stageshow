<?php
/* 
Description: Code for Development Testing
 
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

$folder = dirname(dirname(__FILE__));
include $folder.'/include/stageshowlib_nonce.php';      
	
if (!class_exists('StageShowLib_Test_nonce')) 
{
	class StageShowLib_Test_nonce extends StageShowLibTestBaseClass // Define class
	{
		function __construct($env) //constructor	
		{
			parent::__construct($env);
		}
		
		function Show()
		{			
			$this->Test_OurNOnce();
		}
		
		static function GetOrder()
		{
			return 0.1;	// Determines order tests are output
		}
		
		function Test_OurNOnce()
		{
			$myDBaseObj = $this->myDBaseObj;
			$outputContent = $myDBaseObj->GetWPNonceField();
			$html = htmlspecialchars($outputContent);

			echo '<h3>Test Our NOnce</h3>';
				
			echo 'LOGGED_IN_COOKIE: '.LOGGED_IN_COOKIE."<br>\n";
			echo 'WP_NONCE HTML:'.$html."<br>\n";

			echo "<br>\n";

			$localNOnce = StageShowLibNonce::GetStageShowLibNonce($myDBaseObj->opts['Caller']);
echo '$localNOnce:'.$localNOnce."<br>\n";
		}
		

	}
}

?>