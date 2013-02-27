<?php
/**
 * example.php
 * Displays some examples of class.db.php usage
 *
 * @author Bennett Stone
 * @version 1.0
 * @date 18-Feb-2013
 * @package class.db.php
 **/

define( 'DB_HOST', 'localhost' ); // set database host
define( 'DB_USER', 'root' ); // set database user
define( 'DB_PASS', 'root' ); // set database password
define( 'DB_NAME', 'yourdatabasename' ); // set database name
define( 'SEND_ERRORS_TO', 'you@yourwebsite.com' ); //set email notification email address
define( 'DISPLAY_DEBUG', true ); //display db errors?

require_once( 'class.db.php' );
$database = new DB();

/**
 * Filter all post data
 */
if( isset( $_POST ) )
{
    foreach( $_POST as $key => $value )
    {
        $_POST[$key] = $database->filter( $value );
    }
}

/**
 * Retrieve results of a standard query
 */
$query = "SELECT group_name FROM example_phpmvc";
$results = $database->get_results( $query );
foreach( $results as $row )
{
    echo $row['group_name'] .'<br />';
}


/**
 * Retrieving a single row of data
 */
$query = "SELECT group_id, group_name, group_parent FROM example_phpmvc WHERE group_name LIKE '%production%'";
if( $database->num_rows( $query ) > 0 )
{
    list( $id, $name, $parent ) = $database->get_row( $query );
    echo "<p>With an ID of $id, $name has a parent of $parent</p>";
}
else
{
    echo 'No results found for a group name like &quot;production&quot;';
}


/**
 * Inserting data
 */
//The fields and values to insert
$names = array(
    'group_parent' => 18,
    'group_name' => mt_rand(0, 500) //Random thing to insert
);
$add_query = $database->insert( 'example_phpmvc', $names );
if( $add_query )
{
    echo '<p>Successfully inserted &quot;'. $names['group_name']. '&quot; into the database.</p>';
}


/**
 * Updating data
 */
//Fields and values to update
$update = array(
    'group_name' => 'Production Tools (updated)', 
    'group_parent' => 91
);
//Add the WHERE clauses
$where_clause = array(
    'group_name' => 'Production Tools', 
    'group_id' => 15
);
$updated = $database->update( 'example_phpmvc', $update, $where_clause, 1 );
if( $updated )
{
    echo '<p>Successfully updated '.$where_clause['group_name']. ' to '. $update['group_name'].'</p>';
}

/**
 * Deleting data
 */
//Run a query to delete rows from table where id = 3 and name = Awesome, LIMIT 1
$delete = array(
    'group_id' => 15,
    'group_name' => 'Production Tools (updated)'
);
$deleted = $database->delete( 'example_phpmvc', $delete, 1 );
if( $deleted )
{
    echo '<p>Successfully deleted '.$delete['group_name'] .' from the database.</p>';
}


/**
 * Checking to see if a value exists
 */
$check_column = 'group_id';
$check_for = array( 'group_name' => 'Resources' );
$exists = $database->exists( 'example_phpmvc', $check_column,  $check_for );
if( $exists )
{
    echo '<p>Bennett DOES exist!</p>';
}


/**
 * Checking to see if a table exists
 */
if( !$database->table_exists( 'example_phpmvc' ) )
{
    //Run a table install, the table doesn't exist
}


/**
 * Truncating tables
 * Commented out intentionally (just in case!)
 */
//Truncate a single table, no output display
//$truncate = $database->truncate( array('example_phpmvc') );

//Truncate multiple tables, display number of tables truncated
//echo $database->truncate( array('example_phpmvc', 'my_other_table') );


/**
 * List the fields in a table
 */
$fields = $database->list_fields( "SELECT * FROM example_phpmvc" );
echo '<pre>';
print_r( $fields );
echo '</pre>';


/**
 * Output the number of fields in a table
 */
echo $database->num_fields( "SELECT * FROM example_phpmvc" );