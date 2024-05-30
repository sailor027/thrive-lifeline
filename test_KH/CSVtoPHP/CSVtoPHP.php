<?php
/*
Plugin Name: CSV to PHP
Plugin URI: https://github.com/khruc-sail/thrive-lifeline/tree/d59726f87327825c7547e7f6fae340d5a9a5359e/test_KH/CSVtoPHP
Description: Test script to read a CSV file and display its contents in PHP
Version: 2.0.0
Author: Ko Horiuchi
*/


// path to the CSV file
$resourcesFile = plugin_dir_path(__FILE__) . 'TESTthrive_resources.csv';
// TODO: Current filepath is relative to this plugin directory. If error, update path to local CSV file

// display CSV contents
// add_action('admin_notices', 'display_csv_contents');
add_shortcode('displayResources', 'displayResourcesShortcode');

function displayResourcesShortcode() {

    global $resourcesFile;

    // open CSV file for reading
    if (($fileHandle = fopen($resourcesFile, 'r')) !== false) {
        // Start output buffering to capture the table HTML
        ob_start();
        
        echo '<table style="border-collapse: collapse; width: 100%;">';

        // Read the CSV file line by line
        while (($row = fgetcsv($fileHandle)) !== false) {
            echo '<tr>';
            foreach ($row as $cell) {
                echo '<td style="border: 1px solid #ddd; padding: 8px;">' . htmlspecialchars($cell) . '</td>';
            }
            echo '</tr>';
        }
        echo '</table>';

        // Close the file handle
        fclose($fileHandle);

        // Return the buffered content as a string
        return ob_get_clean();

    } else {
        // Error opening the file
        return '<div class="notice notice-error is-dismissible">Error opening ' . $resourcesFile . '</div>';
    }
}
?>