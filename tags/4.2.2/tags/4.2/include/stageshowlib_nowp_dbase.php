<?php
/* 
Description: Code for TBD
 
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

if (!class_exists('StageShowLibDirectDBaseClass'))
{
	class StageShowLibDirectDBaseClass // Define class
	{
		var $dbg = false;
		
		function __construct()		//constructor		
		{
			//$this->dbg = isset($_GET['debug']);
			
			// Create connection
			$this->con = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD);

			// Check connection
			if (!$this->con)
			{
				echo "Failed to connect to MySQL: " . mysqli_error();
				return;
			}
			else
			{
				if ($this->dbg) echo "CONNECTED: to MySQL: <br>\n";
			}

			mysqli_select_db($this->con, DB_NAME);  			
		}
		
		function query($sql)
		{
			$return_val = mysqli_query($this->con, $sql);

			if ( preg_match( '/^\s*(create|alter|truncate|drop)\s/i', $sql ) ) 
			{
				//$return_val = $return_val;
			} 
			elseif ( preg_match( '/^\s*(insert|delete|update|replace)\s/i', $sql ) ) 
			{
				$this->rows_affected = mysqli_affected_rows( $this->con );
				// Take note of the insert_id
				if ( preg_match( '/^\s*(insert|replace)\s/i', $sql ) ) 
				{
					$this->insert_id = mysqli_insert_id( $this->con );
				}
				// Return number of rows affected
				$return_val = $this->rows_affected;
			} 
/*			
			else 
			{
				$num_rows = 0;
				while ( $row = @mysqli_fetch_object( $return_val ) ) 
				{
					$this->last_result[$num_rows] = $row;
					$num_rows++;
				}

				// Log number of rows the query returned
				// and return number of rows selected
				$this->num_rows = $num_rows;
				$return_val     = $num_rows;
			}
*/

			return $return_val;
		}
		
		function LastInsertId()
		{
			return mysqli_insert_id($this->con);
		}

		function get_results($sql)
		{
			$mysqlRslt = mysqli_query($this->con, $sql);
			if ($mysqlRslt == false)
			{
				if ($this->dbg) echo "SQL Error: ".mysqli_error($this->con);
				return null;
			}
			
			$rslts = mysqli_fetch_all($mysqlRslt, MYSQLI_ASSOC);
			
			// Free result set
			mysqli_free_result($mysqlRslt);

			$rsltArray = array();
			foreach ($rslts as $rowNo => $rsltRow)
			{
				$rsltArray[$rowNo] = new stdClass();
				foreach ($rsltRow as $key => $val)
				{
					$rsltArray[$rowNo]->$key = $val;
				}
			}
					
			return $rsltArray;
		}
	}
}

global $wpdb;
$wpdb = new StageShowLibDirectDBaseClass();

?>