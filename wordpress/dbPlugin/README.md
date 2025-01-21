# Database Plugin
## Plugin Information

- Version: `2.9.7`
- Last updated: `2024.01.21` ([version history](#version-history))

## How to use the plugin
1. Ensure the CSV file TESTthrive_resources.csv is placed in the plugin directory: `plugin_dir_path(__FILE__);`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. To display the CSV contents on a page or post, use the shortcode `[displayResources]`
4. Insert the shortcode in the content area where you want the CSV contents to appear

## Troubleshooting
- If tags are not displaying: Check browser console for JavaScript errors
- If search isn't working: Verify proper encoding of CSV file
- If styles aren't applying: Clear WordPress cache and refresh

## CSV File Requirements
- UTF-8 encoding required
- No empty rows allowed
- Keywords must be comma-separated
- URLs must include http:// or https://

## Version History
`2.8.6` *2025.01.01*
- Fix table display error
***
`2.8.5` *2024.12.31*
- Remove version control
***
`2.8.4`
- [x] Add page controls
- [x] Edit CSS
  - [x] Smaller font
  - [x] Uniform `border-radius`
  - [x] Disable line break in tags
- [x] Database.csv
  - [x] Combine phone# and description
***
`2.8.3` `2.8.2` *2024.12.27*
- Fix resource table display issue
***
`2.8.1` *2024.12.27*
- Update search function:
  - Add javascript file
  - Display tags as clickable elements in a flex container
  - Allow users to click tags to toggle selection
  - Filter the resource table based on selected tags
  - Maintain the same styling as existing tags
***
`2.8.0` *2024.08.03*
- Enable filtering through "Keywords" dropdown
***
`2.7.0`	*2024.08.02*
- Fix pagination buttons
- Combine Keywords and Language/Region
- Adde "Resource" column to the search
- Edit search function
***
`2.6.3`	*2024.07.07*
- Pagination links are working
- Set action attribute to the correct URL w/o the 'pg' parameter
- Preserve all other GET parameters as hidden inputs
***
`2.5.2`	*2024.07.05*
- Fix search img not showing up
- Update pagination
***
`2.5.0`	*2024.06.25*
- Update pagination
***
`2.4.0`	*2024.06.12*
- Add pagination to show 10 rows at a time
- Hyperlinke "Resource" column
- Improve UI design
***
`2.3.2`	*2024.06.04*
- Updates to table styling
***
`2.3.1`	*2024.06.03*
- Enable error reporting
- Fix error
***
`2.3.0`	*2024.06.02*
- Exclude commente lines in the CSV from being displayed
- Insert documentations.html rather than directly writing to the PHP file
- Add CSVtoPHP.css to style the resources
- Row changes colour on hover
- Keywords should show up as individual "tags"
***
`2.2.0`	*2024.05.31*
- Add search box with placeholder "search database..."
- Add version history to the admin menu
***
`2.1.0`	*2024.05.30*
- Update plugin to display CSV contents as a table
- Add plugin instructions to the admin menu
***
`2.0.0`	*2024.05.28*
- Initial version of the plugin


