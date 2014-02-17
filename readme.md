# Export User Data #
**Contributors:** qlstudio  
**Tags:** user, users, xprofile, usermeta csv, excel, batch, export, save, download  
**Requires at least:** 3.2  
**Tested up to:** 3.8.1  
**Stable tag:** 0.8.3  
**License:** GPLv2  

Export users data, metadata and buddypress xprofile data to a csv or Excel file

## Description ##

A plugin that exports ALL user data, meta data and BuddyPress xProfile data.

Includes an option to export the users by role, registration date range, usermeta option and two export formats.

### Features ###

* Exports all users fields
* Exports users meta
* Exports users by role
* Exports users by date range
* Export user BuddyPress xProfile data

For feature request and bug reports, [please use the WP Support Website](http://www.wp-support.co/view/categories/export-user-data).

Please do not use the Wordpress.org forum to report bugs, as we no longer monitor or respond to questions there.

## Installation ##

For an automatic installation through WordPress:

1. Go to the 'Add New' plugins screen in your WordPress admin area
2. Search for 'Export User Data'
3. Click 'Install Now' and activate the plugin
4. Go the 'Users' menu, under 'Export User Data'

For a manual installation via FTP:

1. Upload the `export-user-data` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' screen in your WordPress admin area
3. Go the 'Users' menu, under 'Export User Data'

To upload the plugin through WordPress, instead of FTP:

1. Upload the downloaded zip file on the 'Add New' plugins screen (see the 'Upload' tab) in your WordPress admin area and activate.
2. Go the 'Users' menu, under 'Export User Data'

## Frequently Asked Questions ##

### How to use? ###

Click on the 'Export User Data' link in the 'Users' menu, choose the role and the date range or don't select anything if you want to export all users, then click 'Export'. That's all!

## Screenshots ##

###1. User export screen###
[missing image]


## Changelog ##

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
