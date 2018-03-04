The scripts in this group are not part of any one project or application.  They are just a collection of various scripts to further demonstrate PHP and JS capabilities, style and technique.

Please Note:  You will see some HTML / CSS code intermixed in some of these scripts.  Keep in mind that this is not intended to be responsive front-end code, rather the scripts are intended to depict PHP and JavaScript capabilities.

/* -------------------- */
ENCRYPTION.php
A small script to Encrypt and Decrypt data wherever encryption or privacy is needed, such as in database passwords, cookie data, URLs and more.


/* -------------------- */
PAGINATE.php
A script to calculate and set a navigation line of various formats, to include:

  << prev :: next >>
  Page x of y
  Item x to y of z Total Items
  
In addition to creating the pagination line, it passes back to the calling script (via setting associated Class variables) the starting and ending records to retrieve, based on the navigation line settings.

The interesting feature of this routine is the ability to set not only the maximum number of entries per page (ie. display 10 per page) but also the minimum number per page.  This prevents scenarios where you click on "next" only to have 1 item appear on the page (a personal aggravation of mine).  So if you set the minimum to 3, for example, and the last page will have only 1 or 2 items, those last 1 or 2 items will be included in the last full page.  "next" will only appear if there are 3 or more items to display on the next page.


/* -------------------- */
REGISTRATION.php
A script used to handle all functions related to a user registration (signing up) at a website.  It includes validation, adding the registration, editing the registration, email functions and more.


/* -------------------- */
ajax/ajaxRoutines.js  --and--  ajax/ajaxGetValues.php

A form has 4 drop-down boxes:  >>  Country [ ]  State [ ]  City [ ]  Region [ ]

Only the Country is populated at the start of the program.  When a user selects a Country, the State drop-down is populated with the states in that country.  When the user then selects a state the City drop-down is likewise populated.  And finally when a city is selected the Region drop-down is populated.  A few notes:
1.  Depending on the Country and State, 'Regions' are defined by the site owner.  For instance for the State "NC' and the City 'Raleigh', there may be 2 regions ... 'North Raleigh' and 'South Raleigh'.  Again these are self-defined.
2.  Obviously "State" and "City" are not named like this in every country.  For instance Canada has 'Provinces' and 'Territories'.  This application was initially set up for the United States, but could be easily modified to include other Countries and associated areas.
3.  If a user was to select a new Country, the State drop-down would be populated with the states for that country, the City and Region drop-downs would be cleared.

These JavaScript and PHP routines accomplish this.  ajaxRoutines.js is the client-side JavaScript 'ajax' and ajaxGetValues.php is the server-side script to pull associated values from the database.


/* -------------------- */
inheritance/Dataxxxx.php
The scripts in this group are an example of standard Base Class and Sub-Class Inheritance.  These four scripts did come from the same application, however, and do tie together.  They are used to process various data types within the project.
  DataModel.php - The base class from which all other data classes inherit
  DataDoctype.php - Process all Doctype data
  DataEzine.php - Process all E-zine data
  DataUser.php - Process all User data
  
  