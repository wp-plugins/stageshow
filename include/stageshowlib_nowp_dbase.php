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
		var $con = false;
		var $dbg = false;
		var $last_error = '';
		var $useMySQLi = true;
		
		function __construct()		//constructor		
		{
			$this->dbg = isset($_GET['debug']);
			
			// Create connection - Supress Error Message as MySQLi call may fail
			$this->con = @mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD);
			if (!$this->con)
			{
				$this->useMySQLi = false;
				$this->con = @mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
			}
			
			// Check connection
			if (!$this->con)
			{
				if ($this->useMySQLi)
					echo "Failed to connect to MySQLi: " . mysqli_connect_error();
				else
					echo "Failed to connect to MySQL: " . mysql_error();
				
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
				
			$this->mysqlquery("SET CHARACTER SET $charset");

			if ($this->useMySQLi)
				mysqli_select_db($this->con, DB_NAME); 
			else 			
				mysql_select_db(DB_NAME, $this->con);  			
				
			$this->last_error = $this->getError();
		}
		
		function mysqlquery($sql)
		{
			if ($this->useMySQLi)
				return mysqli_query($this->con, $sql);
			else
				return mysql_query($sql, $this->con);
		}
		
		function query($sql)
		{
			$return_val = $this->mysqlquery($sql);
				
			$this->last_error = $this->getError();
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
				if ($this->useMySQLi)
					$this->rows_affected = mysqli_affected_rows( $this->con );
				else
					$this->rows_affected = mysql_affected_rows( $this->con );
				
				// Take note of the insert_id
				if ( preg_match( '/^\s*(insert|replace)\s/i', $sql ) ) 
				{
					$this->insert_id = $this->LastInsertId();
				}
				// Return number of rows affected
				$return_val = $this->rows_affected;
			} 

			return $return_val;
		}
		
		function LastInsertId()
		{
			if ($this->useMySQLi)
				return mysqli_insert_id( $this->con );
			else
				return mysql_insert_id( $this->con );
		}

		function get_results($sql)
		{
			$mysqlRslt = $this->mysqlquery($sql);
			$this->last_error = $this->getError();
			if ($this->last_error)
			{
				if ($this->dbg) echo "SQL Error: ".$this->last_error;
				return null;
			}
			
			// TODO - Remove redundant code
			// Fetch rows one at a time
			$rowNo = 0;
			$rslts = array();
			while (true)
			{
				if ($this->useMySQLi)
					$row=mysqli_fetch_array($mysqlRslt, MYSQL_ASSOC);				
				else
					$row=mysql_fetch_array($mysqlRslt, MYSQL_ASSOC);				
				if (!$row) break;
				
				$rslts[$rowNo] = $row;
				$rowNo++;
			}

			// Free result set
			if ($this->useMySQLi)
				mysqli_free_result($mysqlRslt);
			else
				mysql_free_result($mysqlRslt);

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
				if ($this->useMySQLi)
					return mysqli_real_escape_string( $this->con, $string );
				else
					return mysql_real_escape_string( $string, $this->con );
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
		
		function getError()
		{
			if ($this->useMySQLi)
				return mysqli_error($this->con);
			else
				return mysql_error($this->con);
		}

	}
}

global $wpdb;
$wpdb = new StageShowLibDirectDBaseClass();

?>