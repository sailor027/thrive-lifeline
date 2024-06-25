<?php
/*
Plugin Name: CSV to PHP
Plugin URI: https://github.com/khruc-sail/thrive-lifeline/tree/d59726f87327825c7547e7f6fae340d5a9a5359e/wordpress/CSVtoPHP
Description: WP plugin to read a CSV file and display its contents in PHP.
Version: 2.5.0
Author: Ko Horiuchi
*/

// enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// path to the CSV file relative to this plugin directory
$resourcesFile = plugin_dir_path(__FILE__) . 'crisisResources.csv';
$docsFile = plugin_dir_path(__FILE__) . 'documentations.html';
$searchImg = plugin_dir_url(__FILE__) . 'media/search.svg';

// enqueue custom styles
function CSVtoPHP_enqueueStyles() {
    wp_enqueue_style('csv-to-php-styles', plugin_dir_url(__FILE__) . 'CSVtoPHP.css');
}
add_action('wp_enqueue_scripts', 'CSVtoPHP_enqueueStyles');

// register shortcode
add_shortcode('displayResources', 'displayResourcesShortcode');

function displayResourcesShortcode() {
    global $resourcesFile;

    // handle the search query
    $searchQuery = isset($_GET['resources_search']) ? sanitize_text_field($_GET['resources_search']) : '';

    // handle pagination
    $currentPage = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $rowsPerPage = 10;
    $startRow = ($currentPage - 1) * $rowsPerPage;
    $paginationRange = 2; // Number of pagination links to show around the current page

    // buffer output to return it properly
    ob_start();

    // display search form in a container aligned to the right
    echo '<div class="resources-search-container" style="text-align: right;">';
    echo '<form method="get" action="' . esc_url($_SERVER['REQUEST_URI']) . '" class="resources-search">';
    echo '<input type="text" name="resources_search" placeholder="Search database..." value="' . esc_attr($searchQuery) . '">';
    echo '<input type="image" src="' . $searchImg . '" alt="Search" class="img">';
    echo '</form>';
    echo '</div>';

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
        
        // skip first row
        $rowCount = 0;
        while ($rowCount < 1 && fgetcsv($fileHandle) !== false) {
            $rowCount++;
        }

        // Initialize an array to store all rows
        $allRows = [];
        
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

            $allRows[] = $row;
        }
        
        $totalRows = count($allRows);
        $totalPages = ceil($totalRows / $rowsPerPage);
        $displayRows = array_slice($allRows, $startRow, $rowsPerPage);

        foreach ($displayRows as $row) {
            echo '<tr>';
            // Read only the first 5 columns, and hyperlink the first column with the link from the seventh column
            for ($i = 0; $i < 5; $i++) {
                if ($i == 0 && !empty($row[6])) {
                    // Wrap the first column's content in an anchor tag
                    echo '<td><a href="' . esc_url($row[6]) . '">' . htmlspecialchars($row[$i]) . '</a></td>';
                } elseif ($i == 3) {
                    // Format keywords as clickable tags
                    $keywords = explode(',', $row[$i]);
                    echo '<td>';
                    foreach ($keywords as $keyword) {
                        $keyword = trim($keyword);
                        echo '<a href="?resources_search=' . urlencode($keyword) . '" class="tag">' . htmlspecialchars($keyword) . '</a> ';
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

        // Pagination controls
        echo '<div class="pagination">';
        if ($currentPage > 1) {
            echo '<a href="' . add_query_arg('page', $currentPage - 1) . '">&laquo; Previous</a>';
        }
        
        // Show pagination links
        for ($page = max(1, $currentPage - $paginationRange); $page <= min($totalPages, $currentPage + $paginationRange); $page++) {
            if ($page == $currentPage) {
                echo '<span class="current-page">' . $page . '</span>';
            } else {
                echo '<a href="' . add_query_arg('page', $page) . '">' . $page . '</a>';
            }
        }
        
        // Show first and last page links if necessary
        if ($currentPage + $paginationRange < $totalPages) {
            echo '<span>...</span>';
            echo '<a href="' . add_query_arg('page', $totalPages) . '">' . $totalPages . '</a>';
        }
        
        if ($currentPage < $totalPages) {
            echo '<a href="' . add_query_arg('page', $currentPage + 1) . '">Next &raquo;</a>';
        }
        echo '</div>';

        // Close the file handle
        fclose($fileHandle);
    } else {
        // Error opening the file
        return '<div class="notice notice-error is-dismissible">Error opening ' . esc_html($resourcesFile) . '</div>';
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
        return '<div class="notice notice-error is-dismissible">Error opening ' . esc_html($docsFile) . '</div>';
    }
}
?>
