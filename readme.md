# Export User Data #

**Contributors:** qlstudio  
**Tags:** users, export, usermeta, excel  
**Requires PHP:** 6.0  
**Requires at least:** 5.0  
**Tested up to:** 5.8.2  
**Stable tag:** 2.2.6  
**License:** GPLv2  

Export users data and metadata to a csv or Excel file

## Description ##

A plugin that exports WordPress user data and metadata.

Includes an option to export the users by role, registration date range, usermeta option and two export formats.

This plugin is designed to export user data stored in the 2 standard WordPress user data tables wp_users and wp_usermeta, if you use a plugin which stores data in its own database tables, this plugin will not export this data, without customization.

In version 2 + we added some additional filters and API controls which control aspects such as the returned value formats and keys, pulling data from custom post types and builing lists of "common" usermeta fields to export - you can read more on the [Q Studio Website](https://qstudio.us/releases/export-user-data-wordpress-plugin/)

---

For feature requests and bug reports, [please use the Q Support Website](https://qstudio.us/support/topic/export-user-data/).

Please do not use the Wordpress.org forum to report bugs, as we no longer monitor or respond to questions there.

### Features ###

* Exports all standard users fields
* Exports users meta
* Exports users by role
* Exports users by date range
* NEW: Filters to control format, add common
