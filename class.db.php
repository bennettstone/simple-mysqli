<?php
/*------------------------------------------------------------------------------
** File:        class.db.php
** Class:       Simply MySQLi
** Description: PHP MySQLi wrapper class to handle common database queries and operations 
** Version:     2.0.3
** Updated:     27-Sep-2013
** Author:      Bennett Stone
** Homepage:    www.phpdevtips.com 
**------------------------------------------------------------------------------
** COPYRIGHT (c) 2012 - 2013 BENNETT STONE
**
** The source code included in this package is free software; you can
** redistribute it and/or modify it under the terms of the GNU General Public
** License as published by the Free Software Foundation. This license can be
** read at:
**
** http://www.opensource.org/licenses/gpl-license.php
**
** This program is distributed in the hope that it will be useful, but WITHOUT 
** ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS 
** FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details. 
**------------------------------------------------------------------------------ */

class DB
{
    private $link;
    public $filter;
    static $inst = null;
    public static $counter = 0;

    /**
     * Allow the class to send admins a message alerting them to errors
     * on production sites
     *
     * @access public
     * @param string $error
     * @param string $query
     * @return mixed
     */
    public function log_db_errors( $error, $query )
    {
        $message = '<p>Error at '. date('Y-m-d H:i:s').':</p>';
        $message .= '<p>Query: '. htmlentities( $query ).'<br />';
        $message .= 'Error: ' . $error;
        $message .= '</p>';

        if( defined( 'SEND_ERRORS_TO' ) )
        {
            $headers  = 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
            $headers .= 'To: Admin <'.SEND_ERRORS_TO.'>' . "\r\n";
            $headers .= 'From: Yoursite <system@your-site.com>' . "\r\n";

            mail( SEND_ERRORS_TO, 'Database Error', $message, $headers);
        }

        if( DISPLAY_DEBUG )
        {
            echo $message;
        }
    }


    public function __construct()
    {
        global $connection;
        mb_internal_encoding( 'UTF-8' );
        mb_regex_encoding( 'UTF-8' );
        $this->link = new mysqli( DB_HOST, DB_USER, DB_PASS, DB_NAME );
        if( $this->link->connect_errno )
        {
            $this->log_db_errors( "Connect failed", $this->link->connect_error );
            exit();
        }
        $this->link->set_charset( "utf8" );
    }

    public function __destruct()
    {
        $this->disconnect();
    }


    /**
     * Sanitize user data
     *
     * @access public
     * @param mixed $data
     * @return mixed $data
     */
    public function filter( $data )
    {
        if( !is_array( $data ) )
        {
            $data = trim( htmlentities( $data, ENT_QUOTES ) );
            $data = $this->link->real_escape_string( $data );
        }
        else
        {
            //Self call function to sanitize array data
            $data = array_map( array( 'DB', 'filter' ), $data );
        }
        return $data;
    }

    /**
     * Determine if common non-encapsulated fields are being used
     * @param mixed $value
     * @return bool
     */
    public function db_common( $value = '' )
    {
        if( is_array( $value ) )
        {
            foreach( $value as $v )
            {
                if( preg_match( '/AES_DECRYPT/i', $v ) || preg_match( '/AES_ENCRYPT/i', $v ) || preg_match( '/now()/i', $v ) )
                {
                    return true;
                }
                else
                {
                    return false;
                }
            }
        }
        else
        {
            if( preg_match( '/AES_DECRYPT/i', $value ) || preg_match( '/AES_ENCRYPT/i', $value ) || preg_match( '/now()/i', $value ) )
            {
                return true;
            }
        }
    }


    /**
     * Perform queries
     * All following functions run through this function
     * All data run through this function is automatically sanitized using the filter function
     *
     * @access public
     * @param string $query
     * @return string
     * @return array
     * @return bool
     *
     */
    public function query( $query )
    {
        $full_query = $this->link->query( $query );
        if( $this->link->error )
        {
            $this->log_db_errors( $this->link->error, $query );
            $full_query->free();
            return false;
        }
        else
        {
            $full_query->free();
            return true;
        }
    }


    /**
     * Determine if database table exists
     *
     * @access public
     * @param string $name
     * @return bool
     *
     */
    public function table_exists( $name )
    {
        self::$counter++;
        $check = $this->link->query( "SELECT 1 FROM $name" );
        if($check !== false)
        {
            if( $check->num_rows > 0 )
            {
                return true;
            }
            else
            {
                return false;
            }
        }
        else
        {
            return false;
        }
    }


    /**
     * Count number of rows found matching a specific query
     *
     * @access public
     * @param string $query
     * @return int
     *
     */
    public function num_rows( $query )
    {
        self::$counter++;
        $num_rows = $this->link->query( $query );
        if( $this->link->error )
        {
            $this->log_db_errors( $this->link->error, $query );
            return $this->link->error;
        }
        else
        {
            return $num_rows->num_rows;
        }
    }


    /**
     * Run check to see if value exists, returns true or false
     *
     * Example Usage:
     * $check_user = array(
     *    'user_email' => 'someuser@gmail.com',
     *    'user_id' => 48
     * );
     * $exists = exists( 'your_table', 'user_id', $check_user );
     *
     * @access public
     * @param string $table database table name
     * @param string $check_val field to check (i.e. 'user_id' or COUNT(user_id))
     * @param array $params column name => column value to match
     * @return bool
     *
     */
    public function exists( $table = '', $check_val = '', $params = array() )
    {
        self::$counter++;
        if( empty($table) || empty($check_val) || empty($params) )
        {
            return false;
        }
        $check = array();
        foreach( $params as $field => $value )
        {

            if( !empty( $field ) && !empty( $value ) )
            {
                //Check for frequently used mysql commands and prevent encapsulation of them
                if( $this->db_common( $value ) )
                {
                    $check[] = "$field = $value";
                }
                else
                {
                    $check[] = "$field = '$value'";
                }
            }

        }
        $check = implode(' AND ', $check);

        $rs_check = "SELECT $check_val FROM ".$table." WHERE $check";
        $number = $this->num_rows( $rs_check );
        if( $number === 0 )
        {
            return false;
        }
        else
        {
            return true;
        }
    }


    /**
     * Return specific row based on db query
     *
     * @access public
     * @param string
     * @return array
     *
     */
    public function get_row( $query )
    {
        self::$counter++;
        $row = $this->link->query( $query );
        if( $this->link->error )
        {
            $this->log_db_errors( $this->link->error, $query );
            return false;
        }
        else
        {
            $r = $row->fetch_row();
            return $r;
        }
    }


    /**
     * Perform query to retrieve array of associated results
     *
     * @access public
     * @param string
     * @return array
     *
     */
    public function get_results( $query )
    {
        self::$counter++;
        //Overwrite the $row var to null
        $row = null;

        $results = $this->link->query( $query );
        if( $this->link->error )
        {
            $this->log_db_errors( $this->link->error, $query );
            return false;
        }
        else
        {
            $row = array();
            while( $r = $results->fetch_assoc() )
            {
                $row[] = $r;
            }
            return $row;
        }
    }


    /**
     * Insert data into database table
     *
     * @access public
     * @param string $table table name
     * @param array $variables table column => column value
     * @return bool
     *
     */
    public function insert( $table, $variables = array() )
    {
        self::$counter++;
        //Make sure the array isn't empty
        if( empty( $variables ) )
        {
            return false;
        }

        $sql = "INSERT INTO ". $table;
        $fields = array();
        $values = array();
        foreach( $variables as $field => $value )
        {
            $fields[] = $field;
            $values[] = "'".$value."'";
        }
        $fields = ' (' . implode(', ', $fields) . ')';
        $values = '('. implode(', ', $values) .')';

        $sql .= $fields .' VALUES '. $values;

        $query = $this->link->query( $sql );

        if( $this->link->error )
        {
            //return false; 
            $this->log_db_errors( $this->link->error, $sql );
            return false;
        }
        else
        {
            return true;
        }
    }


    /**
     * Insert data KNOWN TO BE SECURE into database table
     * Ensure that this function is only used with safe data
     * No class-side sanitizing is performed on values found to contain common sql commands
     * As dictated by the db_common function
     * All fields are assumed to be properly encapsulated before initiating this function
     *
     * @access public
     * @param string $table table name
     * @param array $variables table column => column value
     * @return bool
     */
    public function insert_safe( $table, $variables = array() )
    {
        self::$counter++;
        //Make sure the array isn't empty
        if( empty( $variables ) )
        {
            return false;
        }

        $sql = "INSERT INTO ". $table;
        $fields = array();
        $values = array();
        foreach( $variables as $field => $value )
        {
            $fields[] = $this->filter( $field );
            //Check for frequently used mysql commands and prevent encapsulation of them
            $values[] = $value;
        }
        $fields = ' (' . implode(', ', $fields) . ')';
        $values = '('. implode(', ', $values) .')';

        $sql .= $fields .' VALUES '. $values;
        $query = $this->link->query( $sql );

        if( $this->link->error )
        {
            $this->log_db_errors( $this->link->error, $sql );
            return false;
        }
        else
        {
            return true;
        }
    }


    /**
     * Update data in database table
     *
     * @access public
     * @param string $table table name
     * @param array variables  values to update table column => column value
     * @param array $where parameters table column => column value
     * @param string $limit
     * @return bool
     *
     */
    public function update( $table, $variables = array(), $where = array(), $limit = '' )
    {
        self::$counter++;
        //Make sure the required data is passed before continuing
        //This does not include the $where variable as (though infrequently)
        //queries are designated to update entire tables
        if( empty( $variables ) )
        {
            return false;
        }
        $sql = "UPDATE ". $table ." SET ";
        foreach( $variables as $field => $value )
        {

            $updates[] = "`$field` = '$value'";
        }
        $sql .= implode(', ', $updates);

        //Add the $where clauses as needed
        if( !empty( $where ) )
        {
            foreach( $where as $field => $value )
            {
                $value = $value;

                $clause[] = "$field = '$value'";
            }
            $sql .= ' WHERE '. implode(' AND ', $clause);
        }

        if( !empty( $limit ) )
        {
            $sql .= ' LIMIT '. $limit;
        }

        $query = $this->link->query( $sql );

        if( $this->link->error )
        {
            $this->log_db_errors( $this->link->error, $sql );
            return false;
        }
        else
        {
            return true;
        }
    }


    /**
     * Delete data from table
     *
     * @access public
     * @param string $table table name
     * @param array $where parameters table column => column value
     * @param string $limit max number of rows to remove.
     * @return bool
     *
     */
    public function delete( $table, $where = array(), $limit = '' )
    {
        self::$counter++;
        //Delete clauses require a where param, otherwise use "truncate"
        if( empty( $where ) )
        {
            return false;
        }

        $sql = "DELETE FROM ". $table;
        foreach( $where as $field => $value )
        {
            $value = $value;
            $clause[] = "$field = '$value'";
        }
        $sql .= " WHERE ". implode(' AND ', $clause);

        if( !empty( $limit ) )
        {
            $sql .= " LIMIT ". $limit;
        }

        $query = $this->link->query( $sql );

        if( $this->link->error )
        {
            //return false; //
            $this->log_db_errors( $this->link->error, $sql );
            return false;
        }
        else
        {
            return true;
        }
    }


    /**
     * Get last auto-incrementing ID associated with an insertion
     *
     * @access public
     * @param none
     * @return int
     *
     */
    public function lastid()
    {
        self::$counter++;
        return $this->link->insert_id;
    }


    /**
     * Get number of fields
     *
     * @access public
     * @param $query
     * @return int
     */
    public function num_fields( $query )
    {
        self::$counter++;
        $query = $this->link->query( $query );
        $fields = $query->field_count;
        return $fields;
    }


    /**
     * Get field names associated with a table
     *
     * @access public
     * @param $query
     * @return array
     */
    public function list_fields( $query )
    {
        self::$counter++;
        $query = $this->link->query( $query );
        $listed_fields = $query->fetch_fields();
        return $listed_fields;
    }


    /**
     * Truncate entire tables
     *
     * @access public
     * @param array $tables database table names
     * @return int number of tables truncated
     *
     */
    public function truncate( $tables = array() )
    {
        if( !empty( $tables ) )
        {
            $truncated = 0;
            foreach( $tables as $table )
            {
                $truncate = "TRUNCATE TABLE `".trim($table)."`";
                $this->link->query( $truncate );
                if( !$this->link->error )
                {
                    $truncated++;
                    self::$counter++;
                }
            }
            return $truncated;
        }
    }


    /**
     * Output results of queries
     *
     * @access public
     * @param string $variable
     * @param bool $echo [true,false] defaults to true
     * @return string
     *
     */
    public function display( $variable, $echo = true )
    {
        $out = '';
        if( !is_array( $variable ) )
        {
            $out .= $variable;
        }
        else
        {
            $out .= '<pre>';
            $out .= print_r( $variable, TRUE );
            $out .= '</pre>';
        }
        if( $echo === true )
        {
            echo $out;
        }
        else
        {
            return $out;
        }
    }


    /**
     * Output the total number of queries
     */
    public function total_queries()
    {
        return self::$counter;
    }


    /**
     * Singleton function
     * @access private
     * @return self
     */
    static function getInstance()
    {
        if( self::$inst == null )
        {
            self::$inst = new DB();
        }
        return self::$inst;
    }


    /**
     * Disconnect from db server
     * Called automatically from __destruct function
     */
    public function disconnect()
    {
        $this->link->close();
    }

} //end class DB