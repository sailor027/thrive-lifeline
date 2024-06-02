<?php
/*
Plugin Name: CSV to PHP
Plugin URI: https://github.com/khruc-sail/thrive-lifeline/tree/d59726f87327825c7547e7f6fae340d5a9a5359e/wordpress/CSVtoPHP
Description: WP plugin to read a CSV file and display its contents in PHP.
Version: 2.3.0
Author: Ko Horiuchi
*/

// Path to the CSV file relative to this plugin directory
$resourcesFile = plugin_dir_path(__FILE__) . 'TESTthrive_resources.csv';
//TODO: update CSV file name to actual file
//TODO: delte first few rows of the CSV file
$docsFile = plugin_dir_path(__FILE__) . 'documentations.html';

// enqueue custom styles
function CSVtoPHP_enqueueStyles() {
    wp_enqueue_style('csv-to-php-styles', plugin_dir_url(__FILE__) . 'CSVtoPHP.css');
}
wq_add_action('wp_enqueue_scripts', 'CSVtoPHP_enqueueStyles');

// register shortcode
add_shortcode('displayResources', 'displayResourcesShortcode');

function displayResourcesShortcode() {
    global $resourcesFile;

    // handle the search query
    $searchQuery = isset($_GET['resources_search']) ? sanitize_text_field($_GET['resources_search']) : '';

    // buffer output to return it properly
    ob_start();

    // display search form
    echo '<form method="get" action="' . esc_url($_SERVER['REQUEST_URI']) . '">';
    echo '<input type="text" name="resources_search" placeholder="search database..." value="' . esc_attr($searchQuery) . '">';
    echo '<input type="submit" value="Search">';
    echo '</form>';

    // Open the CSV file for reading
    if (($fileHandle = fopen($resourcesFile, 'r')) !== false) {
        echo '<div style="overflow-x:auto;">';
        echo '<table class="csv-table">';
        echo '
        <tr>
            <th>Resource</th>
            <th>Hotline Phone/Text</th>
            <th>Resource Description</th>
            <th>Keywords</th>
            <th>Region/Language</th>
        </tr>
        ';
        
        // skip first 2 rows
        $rowCount = 0;
        while ($rowCount < 2 && fgetcsv($fileHandle) !== false) {
            $rowCount++;
        }

        // read the CSV file line by line
        while (($row = fgetcsv($fileHandle)) !== false) {
            // skip commented rows
            if (isset($row[0]) && strpos($row[0], '#') === 0) {
                continue;
            }
            // if there's a search query, filter the rows
            if ($searchQuery && stripos(implode(' ', $row), $searchQuery) === false) {
                continue;
            }

            echo '<tr>';
            // read only the first 5 columns
            for ($i = 0; $i < 5; $i++) {
                if ($i == 3) {
                    // format keywords as clickable tags
                    $keywords = explode(',', $row[$i]);
                    echo '<td>';
                    foreach ($keywords as $keyword) {
                        $keyword = trim($keyword);
                        echo '<a href="" class="tag">' . htmlspecialchars($keyword) . '</a> ';
                    }
                    echo '</td>';
                } else {
                    echo '<td>' . htmlspecialchars(isset($row[$i]) ? $row[$i] : '') . '</td>';
                }
            }
            echo '</tr>';
        }
        echo '</table>';    
        echo '</div>';

        // Close the file handle
        fclose($fileHandle);
    } else {
        // Error opening the file
        return '<div class="notice notice-error is-dismissible">Error opening ' . $resourcesFile . '</div>';
    }

    // Return the buffered content as a string
    return ob_get_clean();
}

// Add a menu item to the plugin settings page
add_action('admin_menu', 'CSVtoPHP_pluginMenu');

function CSVtoPHP_pluginMenu() {
    $hook = add_menu_page(
        'CSV to PHP Instructions',  // Page title
        'CSV to PHP',               // Menu title
        'manage_options',           // Capability
        'csv-to-php',               // Menu slug
        'CSVtoPHP_displayInstructions', // Callback function
    );

    add_action("load-$hook", 'csv_to_php_add_help_tab');
}

function CSVtoPHP_displayInstructions() {
    global $docsFile;

    // Get the contents of the file
    $fileContents = file_get_contents($docsFile);

    // Check if the file was successfully read
    if ($fileContents !== false) {
        echo $fileContents;
    } else {
        return '<div class="notice notice-error is-dismissible">Error opening ' . $docsFile . '</div>';
    }
}

function csv_to_php_add_help_tab() {
    $screen = get_current_screen();
    $screen->add_help_tab(array(
        'id'      => 'csv_to_php_help_tab',
        'title'   => 'Usage Instructions',
        'content' => '<h2>CSV to PHP Plugin Instructions</h2>
                        <ol>
                            <li>Ensure the CSV file <span class="code">TESTthrive_resources.csv</code> is placed in the plugin directory: <code>' . plugin_dir_path(__FILE__) . '</code>.</li>
                            <li>Activate the plugin through the "Plugins" menu in WordPress.</li>
                            <li>To display the CSV contents on a page or post, use the shortcode <code>[displayResources]</code>.</li>
                            <li>Insert the shortcode in the content area where you want the CSV contents to appear.</li>
                        </ol>
                        <h2>Example</h2>
                        <p>Edit a page or post and add the following shortcode:</p>
                        <pre><code>[displayResources]</code></pre>
                        <p>The contents of the CSV file will be displayed as a table in the location where you added the shortcode.</p>'
    ));
}
?>