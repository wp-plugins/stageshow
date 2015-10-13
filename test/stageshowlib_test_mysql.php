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
	
if (!class_exists('StageShowLib_Test_mysql')) 
{
	class StageShowLib_Test_mysql extends StageShowLibTestBaseClass // Define class
	{
		function __construct($env) //constructor	
		{
			parent::__construct($env);
		}
		
		static function GetOrder()
		{
			return 0.7;	// Determines order tests are output
		}
		
		function Show()
		{	
			global $wpdb;
					
			$myDBaseObj = $this->myDBaseObj;

			echo '<h3>Test MySQL</h3>';
			
			echo "Wordpress is using <strong><u>";
			if ($wpdb->use_mysqli)
			{
				echo "MySQLi";
			}
			else
			{
				echo "MySQL";
			}
			echo "</u></strong><br><br>\n";
					
			if (isset($_POST['Test_ConnectMySQL'])) 
			{
				echo "<h2>Testing Connection with MySQLi</h2>";
				
				$con = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD);
				if (!$con)
					echo "Failed to connect to MySQLi: " . mysqli_connect_error();
				else
					echo "CONNECTED: to MySQLi <br>\n";

				$con = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
				if (!$con)
					echo "Failed to connect to MySQL: " . mysqli_connect_error();
				else
					echo "CONNECTED: to MySQL <br>\n";
			}
?>
		<input class="button-primary" type="submit" name="Test_ConnectMySQL" value="Test Connection" />
<?php		

		}
		

	}
}

?>