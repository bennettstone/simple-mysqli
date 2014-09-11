<?php
/*------------------------------------------------------------------------------
** File:        class.db.php
** Class:       Simply MySQLi
** Description: PHP MySQLi wrapper class to handle common database queries and operations 
** Version:     2.1.4
** Updated:     11-Sep-2014
** Author:      Bennett Stone
** Homepage:    www.phpdevtips.com 
**------------------------------------------------------------------------------
** COPYRIGHT (c) 2012 - 2014 BENNETT STONE
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
/*******************************
 Example initialization:
 
 define( 'DB_HOST', 'localhost' ); // set database host
 define( 'DB_USER', 'root' ); // set database user
 define( 'DB_PASS', 'root' ); // set database password
 define( 'DB_NAME', 'yourdatabasename' ); // set database name
 define( 'SEND_ERRORS_TO', 'you@yourwebsite.com' ); //set email notification email address
 define( 'DISPLAY_DEBUG', true ); //display db errors?
 require_once( 'class.db.php' );

 //Initiate the class
 $database = new DB();

 //OR...
 $database = DB::getInstance();
 
 NOTE:
 All examples provided below assume that this class has been initiated
 Examples below assume the class has been iniated using $database = DB::getInstance();
********************************/
class DB
{
    private $link = null;
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
            $headers .= 'From: Yoursite <system@'.$_SERVER['SERVER_NAME'].'.com>' . "\r\n";

            mail( SEND_ERRORS_TO, 'Database Error', $message, $headers );   
        }
        else
        {
            trigger_error( $message );
        }

        if( !defined( 'DISPLAY_DEBUG' ) || ( defined( 'DISPLAY_DEBUG' ) && DISPLAY_DEBUG ) )
        {
            echo $message;   
        }
    }
    
    
    public function __construct()
    {
        mb_internal_encoding( 'UTF-8' );
        mb_regex_encoding( 'UTF-8' );
        mysqli_report( MYSQLI_REPORT_STRICT );
        try {
            $this->link = new mysqli( DB_HOST, DB_USER, DB_PASS, DB_NAME );
            $this->link->set_charset( "utf8" );
        } catch ( Exception $e ) {
            die( 'Unable to connect to database' );
        }
    }

    public function __destruct()
    {
        if( $this->link)
        {
            $this->disconnect();
        }
    }


    /**
     * Sanitize user data
     *
     * Example usage:
     * $user_name = $database->filter( $_POST['user_name'] );
     * 
     * Or to filter an entire array:
     * $data = array( 'name' => $_POST['name'], 'email' => 'email@address.com' );
     * $data = $database->filter( $data );
     *
     * @access public
     * @param mixed $data
     * @return mixed $data
     */
     public function filter( $data )
     {
         if( !is_array( $data ) )
         {
             $data = $this->link->real_escape_string( $data );
             $data = trim( htmlentities( $data, ENT_QUOTES, 'UTF-8', false ) );
         }
         else
         {
             //Self call function to sanitize array data
             $data = array_map( array( $this, 'filter' ), $data );
         }
         return $data;
     }
     
     
     /**
      * Extra function to filter when only mysqli_real_escape_string is needed
      * @access public
      * @param mixed $data
      * @return mixed $data
      */
     public function escape( $data )
     {
         if( !is_array( $data ) )
         {
             $data = $this->link->real_escape_string( $data );
         }
         else
         {
             //Self call function to sanitize array data
             $data = array_map( array( $this, 'escape' ), $data );
         }
         return $data;
     }
    
    
    /**
     * Normalize sanitized data for display (reverse $database->filter cleaning)
     *
     * Example usage:
     * echo $database->clean( $data_from_database );
     *
     * @access public
     * @param string $data
     * @return string $data
     */
     public function clean( $data )
     {
         $data = stripslashes( $data );
         $data = html_entity_decode( $data, ENT_QUOTES, 'UTF-8' );
         $data = nl2br( $data );
         $data = urldecode( $data );
         return $data;
     }
    
    
    /**
     * Determine if common non-encapsulated fields are being used
     *
     * Example usage:
     * if( $database->db_common( $query ) )
     * {
     *      //Do something
     * }
     * Used by function exists
     *
     * @access public
     * @param string
     * @param array
     * @return bool
     *
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
     *
     * @access public
     * @param string
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
            return false; 
        }
        else
        {
            return true;
        }
    }
    
    
    /**
     * Determine if database table exists
     * Example usage:
     * if( !$database->table_exists( 'checkingfortable' ) )
     * {
     *      //Install your table or throw error
     * }
     *
     * @access public
     * @param string
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
     * Example usage:
     * $rows = $database->num_rows( "SELECT id FROM users WHERE user_id = 44" );
     *
     * @access public
     * @param string
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
     * $exists = $database->exists( 'your_table', 'user_id', $check_user );
     *
     * @access public
     * @param string database table name
     * @param string field to check (i.e. 'user_id' or COUNT(user_id))
     * @param array column name => column value to match
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
     * Example usage:
     * list( $name, $email ) = $database->get_row( "SELECT name, email FROM users WHERE user_id = 44" );
     *
     * @access public
     * @param string
     * @param bool $object (true returns results as objects)
     * @return array
     *
     */
    public function get_row( $query, $object = false )
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
            $r = ( !$object ) ? $row->fetch_row() : $row->fetch_object();
            return $r;   
        }
    }
    
    
    /**
     * Perform query to retrieve array of associated results
     *
     * Example usage:
     * $users = $database->get_results( "SELECT name, email FROM users ORDER BY name ASC" );
     * foreach( $users as $user )
     * {
     *      echo $user['name'] . ': '. $user['email'] .'<br />';
     * }
     *
     * @access public
     * @param string
     * @param bool $object (true returns object)
     * @return array
     *
     */
    public function get_results( $query, $object = false )
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
            while( $r = ( !$object ) ? $results->fetch_assoc() : $results->fetch_object() )
            {
                $row[] = $r;
            }
            return $row;   
        }
    }
    
    
    /**
     * Insert data into database table
     *
     * Example usage:
     * $user_data = array(
     *      'name' => 'Bennett', 
     *      'email' => 'email@address.com', 
     *      'active' => 1
     * );
     * $database->insert( 'users_table', $user_data );
     *
     * @access public
     * @param string table name
     * @param array table column => column value
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
    * @param string table name
    * @param array table column => column value
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
     * Insert multiple records in a single query into a database table
     *
     * Example usage:
     * $fields = array(
     *      'name', 
     *      'email', 
     *      'active'
     *  );
     *  $records = array(
     *     array(
     *          'Bennett', 'bennett@email.com', 1
     *      ), 
     *      array(
     *          'Lori', 'lori@email.com', 0
     *      ), 
     *      array(
     *          'Nick', 'nick@nick.com', 1, 'This will not be added'
     *      ), 
     *      array(
     *          'Meghan', 'meghan@email.com', 1
     *      )
     * );
     *  $database->insert_multi( 'users_table', $fields, $records );
     *
     * @access public
     * @param string table name
     * @param array table columns
     * @param nested array records
     * @return bool
     * @return int number of records inserted
     *
     */
    public function insert_multi( $table, $columns = array(), $records = array() )
    {
        self::$counter++;
        //Make sure the arrays aren't empty
        if( empty( $columns ) || empty( $records ) )
        {
            return false;
        }

        //Count the number of fields to ensure insertion statements do not exceed the same num
        $number_columns = count( $columns );

        //Start a counter for the rows
        $added = 0;

        //Start the query
        $sql = "INSERT INTO ". $table;

        $fields = array();
        //Loop through the columns for insertion preparation
        foreach( $columns as $field )
        {
            $fields[] = '`'.$field.'`';
        }
        $fields = ' (' . implode(', ', $fields) . ')';

        //Loop through the records to insert
        $values = array();
        foreach( $records as $record )
        {
            //Only add a record if the values match the number of columns
            if( count( $record ) == $number_columns )
            {
                $values[] = '(\''. implode( '\', \'', array_values( $record ) ) .'\')';
                $added++;
            }
        }
        $values = implode( ', ', $values );

        $sql .= $fields .' VALUES '. $values;

        $query = $this->link->query( $sql );

        if( $this->link->error )
        {
            $this->log_db_errors( $this->link->error, $sql );
            return false;
        }
        else
        {
            return $added;
        }
    }
    
    
    /**
     * Update data in database table
     *
     * Example usage:
     * $update = array( 'name' => 'Not bennett', 'email' => 'someotheremail@email.com' );
     * $update_where = array( 'user_id' => 44, 'name' => 'Bennett' );
     * $database->update( 'users_table', $update, $update_where, 1 );
     *
     * @access public
     * @param string table name
     * @param array values to update table column => column value
     * @param array where parameters table column => column value
     * @param int limit
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
     * Example usage:
     * $where = array( 'user_id' => 44, 'email' => 'someotheremail@email.com' );
     * $database->delete( 'users_table', $where, 1 );
     *
     * @access public
     * @param string table name
     * @param array where parameters table column => column value
     * @param int max number of rows to remove.
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
     * Example usage:
     * $database->insert( 'users_table', $user );
     * $last = $database->lastid();
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
     * Return the number of rows affected by a given query
     * 
     * Example usage:
     * $database->insert( 'users_table', $user );
     * $database->affected();
     *
     * @access public
     * @param none
     * @return int
     */
    public function affected()
    {
        return $this->link->affected_rows;
    }
    
    
    /**
     * Get number of fields
     *
     * Example usage:
     * echo $database->num_fields( "SELECT * FROM users_table" );
     *
     * @access public
     * @param query
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
     * Example usage:
     * $fields = $database->list_fields( "SELECT * FROM users_table" );
     * echo '<pre>';
     * print_r( $fields );
     * echo '</pre>';
     *
     * @access public
     * @param query
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
     * Example usage:
     * $remove_tables = array( 'users_table', 'user_data' );
     * echo $database->truncate( $remove_tables );
     *
     * @access public
     * @param array database table names
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
     * @param string variable
     * @param bool echo [true,false] defaults to true
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
     * Generally designed to be used at the bottom of a page after
     * scripts have been run and initialized as needed
     *
     * Example usage:
     * echo 'There were '. $database->total_queries() . ' performed';
     *
     * @access public
     * @param none
     * @return int
     */
    public function total_queries()
    {
        return self::$counter;
    }
    
    
    /**
     * Singleton function
     *
     * Example usage:
     * $database = DB::getInstance();
     *
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