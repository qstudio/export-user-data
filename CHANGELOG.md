## Changelog ##

*** 2.2.6 ***

* New: WordPress Security release 

*** 2.2.1 ***

* New: cleanup export methods, improvied sanitization 
* New: array and object data is now passed in JSON_ENCODED string object

*** 2.2.0 ***

* New: Move to cleaner OOP pattern and PHP version bump to 7.0
* Update: Tested on WP 5.6

*** 2.1.3 ***

* FIX: wrong name for our own plugin :( thanks @kgagne !

*** 2.1.2 ***

* Readme updates

*** 2.1.1 ***

* FIX: saving and loading selected usermeta fields works as expected.

*** 2.1.0 ***

* Excel 2007 export option added - thanks to @reyneke-vosz - https://github.com/qstudio/export-user-data/pull/5
* Excell 2003 export option removed, as no suitable open-source library available
* Validated as working in WP 5.5.0
* BuddyPress support removed... sorry, but this plugin now only supports exporting data from native WordPress tables

*** 2.0.3 ***

* Removed remote call to jQuery UI CSS
* Added extra sanitization to $_POST data
* Made main class name "more" unique

*** 2.0.2 ***

* Cleanup and tagging for WP Repo

*** 2.0.1 ***

* Deprecated BuddyPress support as untested in 4 years

*** 2.0.0 ***

* Fork to new name Q Report
* namespaced and moved to standard Q plugin setup model
* buddypress support might be flaky due to limited testing

*** 1.3.1 ***

* Moved all internal action hooks to admin_init to allow for internal function loading

*** 1.3.0 ***

* Added extra data sanitization before outputting to file - thanks to Hely Shah <helyhshah@gmail.com> for the heads-up

*** 1.2.8 ***

* New: Added load_buddypress() methods to test for buddypress and load up if missing
* New: move action hooks and priority to load later
* New: Plugin no longer uses singleton model to instatiate - instead called from action hook to public function
* New: added log() to debug.log file to help debugging issues
* Update: jQuery datepickers pull start_of_week value from WordPress
* Tested on 4.4.2

*** 1.2.7 ***

* Added: Spanish translation - thanks Elías Gómez Sainz ( elias@estudions.es )

*** 1.2.6 ***
* Update: WP 4.4.1

### 1.2.3 ###
* Fix: to remove minor security loop hole
* New: Added option to remove standard wp_users data from export
* Fix: removed roles and groups columns from export when options hidden

### 1.2.2 ###
* Minor FIxes

### 1.2.1 ###
* Checked on WP 4.3.1
* Moved text-domain to string in preperation for addition to translate.wordpress.org
* Added Log() method to allow for debugging to WP Error Log
* Added Greek translation - Thanks @Leonidas Mi
* Added option to limit export by last_updated date of specific xprofile field - Thanks to @cwjordan

### 1.2.0 ###
* Data stored in recursive and serialized arrays is now exported in a flat string format with safe delimiters ( ||, ||| - etc. )

### 1.1.1 ###
* Removed accidently included .git files

### 1.1.0 ###
* Version change to sync SVN on wordpress.org

### 1.0.4 ###
* Added unserialize function with @ fallback
* Removed anonymous function to allow support for PHP < 5.2

### 1.0.3 ###
* Tested as working on WordPress 4.1.0.

### 1.0.2 ###
* Removed get_user_meta method, as not effective.
* Added registration date from and to pickers - to replace monthly <select> lists.

### 1.0.1 ###
* Added recursive_implode() method to flatten data stored in arrays ( exported with keys and values divided by "|" )

### 1.0.0 ###
* Reduced all get_user_meta queries to a single call to improve performance
* Serialized data is now returned in it's pure stored format - not imploded or unserialized to avoid data structure loss

### 0.9.9 ###
* get_uermeta renamed get_user_meta to be more consistent with WP
* get_user_meta tidied up and tested on larger exports
* added option to export user BP Groups
* added option to export all user WP Roles

### 0.9.8 ###
* added get_usermeta() to check if meta keys are unique and return an array if not
* removed known_arrays() filter to allow for array data to be returned correctly - too hacky

### 0.9.7 ###
* Added known_arrays() filter to allow for array data to be returned correctly

### 0.9.6 ###
* Save, load and delete stored export settings - thanks to @cwjordan
* Overcome memory outages on large exports - thanks to @grexican
* Tested on WP 4.0.0 & BP 2.1.0

### 0.9.5 ###
* BP Serialized data fixes - thanks to @nicmare & @grexican
* Tested on WP 3.9.2 & BP 2.0.2

### 0.9.4 ###
* BP X Profile Export Fix ( > version 2.0 )

### 0.9.3 ###
* fix for hidden admin bar

### 0.9.2 ###
* removed $key assignment casting to integer

### 0.9.1 ###
* Tested with WP 3.9
* Fix for BuddyPress 2.0 bug

### 0.9.0 ###
* Moved plugin class to singleton model
* Improved language handling
* French translation - thanks @bastho - http://wordpress.org/support/profile/bastho

### 0.8.3 ###
* clarified export limit options

### 0.8.2 ###
* corrected buddypress export option - broken in 0.8.1
* changed get_users arguments, in attempt to reduce memory usage

### 0.8.1 ###
* Added experimental range limiter for exports
* Extra input data sanitizing

### 0.8 ###
* moved plugin instatiation to the WP hook: init
* moved bp calls outside export loop
* added extra isset calls on values in export loop to clean up error log not sets

### 0.7.8 ###
* added xml template for Excel exports - thanks to phil@fixitlab.com :)

### 0.7.2 ###
* fixes to allow exports without selecting extra user date from usermeta or x-profile

### 0.6.3 ###
* added multiselect to pick usermeta and xprofile fields

### 0.5 ###
* First public release.

## Upgrade Notice ##

### 0.6.3 ###
Latest.

### 0.5 ###
First release.
