Here is a summary description of the scripts included in this group:

DASHBOARD.php:
A small snippet of a user "Dashboard" ... the page a user would land on when first logging in to an application.  Nothing too exciting, rather just a snippet of front-end code depicting the dashboard being populated with options and data from a MySQL database.  It will display options the user is authorized to perform.

DATABASE.php
MySQL database routines used throughout the application.  All DB actions pass through these routines,
so there are no direct DB calls anywhere else in any other script.  Features of this script:

1.  All SQL is executed via these routines, to keep DB access centralized
2.  Routines will stop any "blatant" hack attempts by blocking any SQL statement with certain "hack-attempt" keywords from executing  (IE. UNION, DECLARE, EXE, ...)
3.  All DB access and updating is run through SQL-Injection-Prevention
4.  Routines send an e-mail to the Webmaster upon any DB errors, notifying them of an error, the DB error code and error description.  It would also inform the Webmaster of the Host Name, Program Name, Referrer and the actual SQL Statement that was executed when the error occurred.
5.  Script will also display a nicely-formatted message to the user notifying them that an error has occurred, and that the Webmaster has already been notified of the error.

INITIALIZE.php
A small script included at the start of all programs in the application to perform initialization routines and security checks.  Handles standard functions needed in every program. 

SECURITYCHECK.php
A snippet of code to perform security checks on users.  Comments at the beginning of the "securitycheck.php" script outlines the various options and functions.  It handles everything from no-security-needed to super-admin-level privileges.

TOOLS.php
A group of common (static) functions used throughout the application.  It is a group of utility functions that can be easily called from anywhere when needed.

NOTE:
The scripts Database.php and Tools.php are "private" scripts, meaning they are only used by other programs withing the application.  Line 1 in these scripts ...

if (!defined('EntryAllowed') || !EntryAllowed) die('Not A Valid Entry Point');

... prevents the scripts from being executed directly (hacked).  The CONSTANT 'EntryAllowed' is set as the first line of pages that use these scripts, to allow access to them.  Just another layer of security.
