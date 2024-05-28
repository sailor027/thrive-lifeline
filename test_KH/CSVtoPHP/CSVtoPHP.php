<?php
/*
Plugin Name: CSV to PHP
Description: Test script to read a CSV file and display its contents in PHP
Version: 1.0.0
Author: Ko Horiuchi
*/


// path to the CSV file
$csvFile = plugin_dir_path(__FILE__) . 'thrive_resources.csv';
// TODO: Current filepath is relative to this plugin directory. If error, update path to local CSV file

// display CSV contents
add_action('admin_notices', 'display_csv_contents');

function display_csv_contents() {
    global $csvFile;

    // open the CSV file for reading
    if (($fileHandle = fopen($csvFile, 'r')) !== false) {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<h2>CSV Contents:</h2>';

        // read the CSV file line by line
        echo '<table>';
        while (($row = fgetcsv($fileHandle)) !== false) {
            echo '<tr>';
            foreach ($row as $cell) {
                echo '<td>' . htmlspecialchars($cell) . '</td>';
            }
            echo '</tr>';
        }
        echo '</table>';
        echo '</div>';

        // close file handle
        fclose($fileHandle);
        
    } else {
        // error opening the file
        echo '<div class="notice notice-error is-dismissible">';
        echo "Error opening $csvFile";
        echo '</div>';
    }
}

?>