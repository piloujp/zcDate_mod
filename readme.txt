After strftime has been deprecated, date_format was used but it does not provide much internationalization. This mod uses international date object instead which is compatible with any language.
Now, recent versions of Zen Cart although use international date object, which means this mod is not required anymore for internationalization.
Although strftime is not used anymore in Zen Cart, its parameter system is still there (%something). They are all replaced in this mod by the international date formatter ones (see links below) which is more efficient as no conversion is needed.
But to keep backward compatibility, zcDate class has been modified to accept and convert old parameters to new system. Even if you use old plugins or files, it will still work.

Install:
--------
As always backup your cart first.
If you have a fresh Zen Cart v2.0.0 or above installed, then copy all files respecting folder structure. Don't forget to rename 'YOUR_ADMIN' before.
If not, you have to merge them using a file merging tool.

Files list:
-----------
'YOUR_ADMIN/banner_statistics.php'
'YOUR_ADMIN/includes/classes/stats_sales_report_graph.php'
'YOUR_ADMIN/includes/functions/functions_banner_graphs.php'
'YOUR_ADMIN/includes/languages/lang.english.php'
'YOUR_ADMIN/includes/modules/dashboard_widgets/TrafficDashboardWidget.php'
'includes/classes/zcDate.php'
'includes/classes/traits/NotifierManager.php'
'includes/functions/functions_dates.php'
'includes/languages/lang.english.php'
'includes/modules/new_products.php'
'includes/modules/specials_index.php'
'includes/modules/payment/authorizenet.php'
'includes/modules/payment/authorizenet_aim.php'
'includes/modules/payment/paypaldp.php'

Uninstall:
----------
Replace all files by original backups.

Date functions parameter links:
-------------------------------

strftime:
https://www.php.net/manual/en/function.strftime.php

date_format:
https://www.php.net/manual/en/datetime.format.php

intl datefmt_format:
https://www.unicode.org/reports/tr35/tr35-dates.html#Date_Field_Symbol_Table