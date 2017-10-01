## Job interview assignment
The following describes how to run and use the solution for CEGO job interview assignment.

### Getting started
Before cloning the repository make sure the following dependencies are installed.

* PHP 7
    * php-sqlite3
* sqlite3

Clone this repository

```
git clone https://github.com/xpherism/interview-assignment.git
```

Install the project dependencies via composer

```
bin/composer install
```

Run the app and the test command for yourself

```
php app query <input.sql> <output.csv>
```

### How it works
1. A query is read from a sql file and executed on the database.

2. All rows are read to memory and then checked for the availability of the "id" column (which will be needed for deletion later on).

3. Rows are then written to file in CSV format (semi-colon separated) with first row begin column names. File is name <output.csv>~ while processing and renamed to <output.csv> when all data has been written to file. If writing data should fail, a cleanup will be attempted ie. <output.csv>~ will be deleted.

4. When data has been written correctly to disk, relevant rows will then be deleted from the database.

### Notes
* When verifying data has been written to disk, data loss due to drive failure, power failure etc. as data may still reside in cache (ie. when write-back cache is enabled. This is OS and filesystem dependent).

* There are lots of differents ways to handle deleting the selected data, but requirement of "id" column is the easiest by far. Other alternatives would be to,
    * Inject *id as __id* into the select query.
    * *select to delete* statement rewrite (which potentially could be very hard, think order by, group by, joins etc.).
    * Column/Value matching, but then again, you are free to use substring, uppercase, lowercase, sub selects, column renaming in the select clause, which would break this approach.
* This has only been tested on Elementary OS (Loki), but should work on platform where php and sqlite3 are available.