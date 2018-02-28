Here is a summary description of the scripts included in this group.  Note that the HTML and CSS code are not front-end Responsive Code.  Responsive HTML5 / CSS3 examples are on the way ... stay tuned.

AXCHECKLOGIN.php
Server-side PHP script called from client-side JavaScript to validate User ID and Password when logging in (AJAX)

EXPORT.php
Class to export / create a CSV file.  Can export an entire file of data or process single line items.  This was the manual way using PHP statements and functions rather than pre-build PHP functions.

INITIALIZE.php
A small script included at the start of all programs in the application to perform initialization routines and security checks.  Handles standard functions needed in every program.

LOGIN.php
The login script for this particular application

MENU.php
The Main Menu for this particular application.  The page has some embedded jQuery code for your review.

CSS/FORMS.css
The CSS used in the various forms in the application.  Just some CSS examples, although not Responsive CSS3.

CSS/GLOBAL.css
The CSS used globally for the entire application.  Just more CSS examples, although not Responsive CSS3

JS/VALIDATELOGIN.js
Login form validation using jQuery and utilizes AJAX (calls axchecklogin.php included in this group of code) to validate without the user leaving the page.

NOTE:
Some scripts are "private" scripts, meaning they are only used by other programs withing the application.  Line 1 in these scripts ...

if (!defined('EntryAllowed') || !EntryAllowed) die('Not A Valid Entry Point');

... prevents the scripts from being executed directly (hacked).  The CONSTANT 'EntryAllowed' is set as the first line of pages that use these scripts, to allow access to them.  Just another layer of security.
