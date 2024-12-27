# CSV to PHP
## Plugin Information

- Version: `2.7.5`
- Last updated: `2024.12.26` ([version history](#version-history))

## How to use the plugin
1. Ensure the CSV file TESTthrive_resources.csv is placed in the plugin directory: `plugin_dir_path(__FILE__);`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. To display the CSV contents on a page or post, use the shortcode `[displayResources]`
4. Insert the shortcode in the content area where you want the CSV contents to appear

## Version History
`2.8.1` *2024.12.26*
- Added javascript file.
***
`2.8.0` *2024.08.03*
- Enabled filtering through "Keywords" dropdown.
***
`2.7.0`	*2024.08.02*
- Fixed pagination buttons.
- Combined Keywords and Language/Region.
- Added "Resource" column to the search.
- Editted search function.
***
`2.6.3`	*2024.07.07*
- Pagination links are working.
- Set action attribute to the correct URL w/o the 'pg' parameter.
- Preserved all other GET parameters as hidden inputs.
***
`2.5.2`	*2024.07.05*
- Fixed search img not showing up.
- Updated pagination.
***
`2.5.0`	*2024.06.25*
- Updated pagination.
***
`2.4.0`	*2024.06.12*
- Added pagination to show 10 rows at a time.
- Hyperlinked "Resource" column.
- Improved UI design.
***
`2.3.2`	*2024.06.04*
- Updates to table styling.
***
`2.3.1`	*2024.06.03*
- Enabled error reporting.
- Fixed error.
***
`2.3.0`	*2024.06.02*
- Excluded commented lines in the CSV from being displayed.
- Inserted documentations.html rather than directly writing to the PHP file.
- Added CSVtoPHP.css to style the resources.
- Row changes colour on hover.
- Keywords should show up as individual "tags"
***
`2.2.0`	*2024.05.31*
- Added search box with placeholder "search database..."
- Added version history to the admin menu.
***
`2.1.0`	*2024.05.30*
- Updated plugin to display CSV contents as a table.
- Added plugin instructions to the admin menu.
***
`2.0.0`	*2024.05.28*
- Initial version of the plugin.


