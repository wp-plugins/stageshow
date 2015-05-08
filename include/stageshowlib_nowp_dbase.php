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
		var $last_error = '';
		
		function __construct()		//constructor		
		{
			//$this->dbg = isset($_GET['debug']);
			
			// Create connection
			$this->con = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD);
			
			// Check connection
			//if (mysqli_connect_errno())			
			if (!$this->con)
			{
				echo "Failed to connect to MySQL: " . mysqli_connect_error();
				return;
			}
			else
			{
				if ($this->dbg) echo "CONNECTED: to MySQL: <br>\n";
			}

			// Set the character set for the connection
			if ( defined( 'DB_CHARSET' ) )
				$charset = DB_CHARSET;
			else
				$charset = 'utf8';
			mysqli_query($this->con, "SET CHARACTER SET $charset");

			mysqli_select_db($this->con, DB_NAME);  			
			$this->last_error = mysqli_error( $this->con );
		}
		
		function query($sql)
		{
			$return_val = mysqli_query($this->con, $sql);
			$this->last_error = mysqli_error( $this->con );
			if ($this->last_error)
			{
				return false;
			}
			
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
			$this->last_error = mysqli_error( $this->con );
			if ($this->last_error)
			{
				if ($this->dbg) echo "SQL Error: ".$this->last_error;
				return null;
			}
			
			// TODO - Remove redundant code
			// Fetch rows one at a time
			$rowNo = 0;
			$rslts = array();
			while ($row=mysqli_fetch_array($mysqlRslt, MYSQL_ASSOC))
			{
				$rslts[$rowNo] = $row;
				$rowNo++;
			}

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
		
		/*
			Functions taken from wpdb class (wp-db.php)
		*/
		function _real_escape( $string ) 
		{
			if ( $this->con ) 
			{
				return mysqli_real_escape_string( $this->con, $string );
			}
			return addslashes( $string );
		}
		
		public function escape_by_ref( &$string ) 
		{
			if ( ! is_float( $string ) )
				$string = $this->_real_escape( $string );
		}

		public function prepare( $query, $args ) 
		{
			if ( is_null( $query ) )
				return;

			// This is not meant to be foolproof -- but it will catch obviously incorrect usage.
			if ( strpos( $query, '%' ) === false ) {
				_doing_it_wrong( 'wpdb::prepare', sprintf( __( 'The query argument of %s must have a placeholder.' ), 'wpdb::prepare()' ), '3.9' );
			}

			$args = func_get_args();
			array_shift( $args );
			// If args were passed as an array (as in vsprintf), move them up
			if ( isset( $args[0] ) && is_array($args[0]) )
				$args = $args[0];
			$query = str_replace( "'%s'", '%s', $query ); // in case someone mistakenly already singlequoted it
			$query = str_replace( '"%s"', '%s', $query ); // doublequote unquoting
			$query = preg_replace( '|(?<!%)%f|' , '%F', $query ); // Force floats to be locale unaware
			$query = preg_replace( '|(?<!%)%s|', "'%s'", $query ); // quote the strings, avoiding escaped strings like %%s
			array_walk( $args, array( $this, 'escape_by_ref' ) );
			return @vsprintf( $query, $args );
		}

	}
}

global $wpdb;
$wpdb = new StageShowLibDirectDBaseClass();

?>