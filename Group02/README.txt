Here is a summary description of the scripts included in this group:

DATAMODEL.php
A Base Class for all data handling within the application.  It handles the tasks and functions common to all data types and transactions - validation, add, edit, delete and retrieval.  It facilitates the creation of other sub-classes that perform data handling for specific types of data and transactions, such as Project Data.  (NOTE:  This is the Base Class for the DATAPROJECT.php class within this group of scripts)

DATAPROJECT.php
Sub-Class that extends DATAMODEL.php to handle the data and transactions related to Projects within the application.  You will notice many calls to parent::xxxx(yyyy)

PTEROUTINES.php
Class for PTEs (Project Tables Edit) routines.  PTE is not a technical abbreviation (not is Project Tables Edit a technical term).  This is a script to process PTEs for the site owner and it is included here as just one more example of coding skill and technique.

READPOPMAILBOX.php
Script to read the POP mailbox on the server, decode and format the messages and add them to a MySQL database for later review and processing. It then removes the messages just processed from the mailbox (clean-up).

REGISTRY.php
A snippet to handle passing variables between code and functions by using a "registry", similar to the function of the Windows registry.  It also helps eliminate (or drastically reduce) the use of 'Global' within functions and classes.

TOOLS.php
A group of common (static) functions used throughout the application.  It is a group of utility functions that can be easily called from anywhere when needed.

UPLOADER.php
Class to handle all file uploads within the application.  It will:

1.  Handle white-listing / black-listing of types of files to be uploaded
2.  Sanitizes file names for the server by removing undesirable characters, such as '#', '@', '+', '$' and more
3.  Ensures no duplicate filenames on the server
4.  Resize images, either width or height, while maintaining the correct aspect ratio
5.  Cropping images can be accomplished
6.  Will handle keeping the server clean by removing all files that are being replaced, even with files of different names
7.  Can generate thumbs for all image uploads
8.  Handles upload errors such as file exceeded max size allowed, too large for the server, file failed to uploaded and more.
9.  Can handle multiple uploads at one time

All of the above functions controlled by variables set within the calling program.

NOTE:
Some scripts are "private" scripts, meaning they are only used by other programs withing the application.  Line 1 in these scripts ...

if (!defined('EntryAllowed') || !EntryAllowed) die('Not A Valid Entry Point');

... prevents the scripts from being executed directly (hacked).  The CONSTANT 'EntryAllowed' is set as the first line of pages that use these scripts, to allow access to them.  Just another layer of security.
