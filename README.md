SimpleMysqli
=============

PHP class to access MySQL database wrapper using MySQLi

This class can:

- Connect to a given MySQL server
- Execute arbitrary SQL queries
- Retrieve the number of query result rows, result columns and last inserted table identifier
- Retrieve the query results in a single array
- Escape a single string or an array of literal text values to use in queries
- Determine if one value or an array of values contain common MySQL function calls
- Check of a table exists
- Check of a given table record exists
- Return a query result that has just one row
- Execute INSERT, UPDATE and DELETE queries from values that define tables, field names, field values and conditions
- Truncate a table
- Send email messages with MySQL access and query errors
- Display the total number of queries performed during all instances of the class

Full usage examples are provided in example.php, using example data provided in example-data.sql