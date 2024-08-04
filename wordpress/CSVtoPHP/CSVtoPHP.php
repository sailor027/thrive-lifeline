<?php
/*
Plugin Name: CSV to PHP
Plugin URI: https://github.com/khruc-sail/thrive-lifeline/tree/d59726f87327825c7547e7f6fae340d5a9a5359e/wordpress/CSVtoPHP
Description: WP plugin to read a CSV file and display its contents in PHP.
Version: 2.7.2
Author: Ko Horiuchi
*/
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Path to the CSV file relative to this plugin directory
$resourcesFile = plugin_dir_path(__FILE__) . 'crisisResources.csv';
$docsFile = plugin_dir_path(__FILE__) . 'documentations.html';
$searchImg = plugin_dir_path(__FILE__) . '/media/search.svg'; //do not change this

// Enqueue custom styles
function CSVtoPHP_enqueueStyles() {
    wp_enqueue_style('csv-to-php-styles', plugin_dir_url(__FILE__) . 'CSVtoPHP.css');
}
add_action('wp_enqueue_scripts', 'CSVtoPHP_enqueueStyles');

// Register shortcode
add_shortcode('displayResources', 'displayResourcesShortcode');

function displayResourcesShortcode() {
    global $resourcesFile, $searchImg;

    // Handle the search query
    $searchQuery = isset($_GET['kw']) ? sanitize_text_field($_GET['kw']) : '';
    $searchTerms = array_filter(explode(' ', $searchQuery)); // Split search query into individual terms

    // Handle pagination
    $currentPage = isset($_GET['pg']) ? intval($_GET['pg']) : 1;
    $rowsPerPage = 10;
    $startRow = ($currentPage - 1) * $rowsPerPage;
    $paginationRange = 1; // Number of pagination links to show around the current page
    // Buffer output to return it properly

    ob_start();

    // Display search form in a container
    echo '<div class="resources-search-container">';
    echo '<form method="get" action="' . esc_url($_SERVER['REQUEST_URI']) . '" class="resources-search">';
    echo '<input type="text" name="kw" placeholder="Search database..." value="' . esc_attr($searchQuery) . '">';
    echo '<input type="image" src="' . esc_url($searchImg) . '" alt="Search" class="img">'; // Use $searchImg for image source
    echo '</form>';
    echo '</div>';

    // Open the CSV file for reading
    if (($fileHandle = fopen($resourcesFile, 'r')) !== false) {
        echo '<div style="overflow-x:auto;">';
        echo '<table class="csv-table">';
        echo '
        <tr>
            <th width="20%">Resource</th>
            <th width="15%">Hotline Phone/Text</th>
            <th width="50%">Resource Description</th>
            <th width="15%">Keywords</th>
        </tr>
        ';
        
        // Skip first row
        $rowCount = 0;
        while ($rowCount < 1 && fgetcsv($fileHandle) !== false) {
            $rowCount++;
        }

        // Initialize an array to store all rows
        $allRows = [];

        while (($row = fgetcsv($fileHandle)) !== false) {
            // Skip commented rows
            if (isset($row[0]) && strpos($row[0], '#') === 0) {
                continue;
            }
            // If there's a search query, filter the rows
            if ($searchQuery) {
                $rowString = implode(' ', $row);
                $allTermsFound = true;
                foreach ($searchTerms as $term) {
                    if (stripos($rowString, $term) === false) {
                        $allTermsFound = false;
                        break;
                    }
                }
                if (!$allTermsFound) {
                    continue;
                }
            }
            $allRows[] = $row;
        }
        
        $totalRows = count($allRows);
        $totalPages = ceil($totalRows / $rowsPerPage);
        $displayRows = array_slice($allRows, $startRow, $rowsPerPage);

        foreach ($displayRows as $row) {
            echo '<tr>';
            // Read only the first 4 columns, and hyperlink the first column with the link from the 5th column
            for ($i = 0; $i < 4; $i++) {
                if ($i == 0 && !empty($row[4])) {
                    // Wrap the first column's content in an anchor tag
                    echo '<td><a href="' . esc_url($row[4]) . '" target="_blank">' . htmlspecialchars($row[$i]) . '</a></td>';
                } elseif ($i == 3) {
                    // Format keywords as clickable tags
                    $keywords = explode(',', $row[$i]);
                    echo '<td>';
                    foreach ($keywords as $keyword) {
                        $keyword = trim($keyword);
                        echo '<a href="?kw=' . urlencode($keyword) . '" class="tag">' . htmlspecialchars($keyword) . '</a> ';
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
        echo '<form method="get" action="' . esc_url(remove_query_arg('pg', $_SERVER['REQUEST_URI'])) . '">';

        // Preserve all other GET parameters as hidden inputs
        foreach ($_GET as $key => $value) {
            if ($key != 'page') { // Skip 'page' parameter to avoid duplication
                echo '<input type="hidden" name="'. esc_attr($key) .'" value="'. esc_attr($value) .'">';
            }
        }

        if ($currentPage > 1) {
            echo '<button type="submit" name="pg" class="page-np" value="' . ($currentPage - 1) . '">&laquo; Previous</button>';
        }

        // Show first page link
        if ($currentPage > 1) {
            echo '<button type="submit" name="pg" class="page-n" value="1">1</button>';
            if ($currentPage > $paginationRange + 2) {
                echo '<span>...</span>';
            }
        }

        // Show pagination links
        for ($page = max(1, $currentPage - $paginationRange); $page <= min($totalPages, $currentPage + $paginationRange); $page++) {
            if ($page == $currentPage) {
                echo '<span class="current-page">' . $page . '</span>';
            } else {
                echo '<button type="submit" name="pg" class="page-n" value="' . $page . '">' . $page . '</button>';
            }
        }

        // Show last page link
        if ($currentPage < $totalPages - $paginationRange - 1) {
            echo '<span>...</span>';
        }
        if ($currentPage < $totalPages) {
            echo '<button type="submit" name="pg" class="page-n" value="' . $totalPages . '">' . $totalPages . '</button>';
        }

        if ($currentPage < $totalPages) {
            echo '<button type="submit" name="pg" class="page-np" value="' . ($currentPage + 1) . '">Next &raquo;</button>';
        }

        echo '</form>';
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
