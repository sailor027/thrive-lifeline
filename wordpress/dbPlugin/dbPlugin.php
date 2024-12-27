<?php
//================================================================================================
/*
Plugin Name: Database Plugin
Plugin URI: https://github.com/khruc-sail/thrive-lifeline/tree/d59726f87327825c7547e7f6fae340d5a9a5359e/wordpress/CSVtoPHP
Description: WP plugin to read a CSV file and display its contents in PHP.
Version: 2.8.1
Date: 2024.12.27
Author: Ko Horiuchi
License: MIT
*/
//================================================================================================

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

//-----------------------------------------------------------------------------------------------

// Path to the CSV file relative to this plugin directory
$resourcesFile = plugin_dir_path(__FILE__) . 'crisisResources.csv'; 
// $docsFile = plugin_dir_path(__FILE__) . 'documentations.html';
$docsFile = plugin_dir_path(__FILE__) . 'documentations.md'; //TODO change back to HTML if needed
$searchImg = plugin_dir_path(__FILE__) . '/media/search.svg'; //do not change this

//-----------------------------------------------------------------------------------------------

// Enqueue custom styles
function CSVtoPHP_enqueueStyles() {
    wp_enqueue_style('csv-to-php-styles', plugin_dir_url(__FILE__) . 'style.css');
}
add_action('wp_enqueue_scripts', 'CSVtoPHP_enqueueStyles');

function CSVtoPHP_enqueueScript() {
    wp_enqueue_script('csv-to-php-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0', true);
}
add_action('wp_enqueue_scripts', 'CSVtoPHP_enqueueScript');

//-----------------------------------------------------------------------------------------------

// Register shortcode
add_shortcode('displayResources', 'displayResourcesShortcode');

//================================================================================================

function displayResourcesShortcode() {
    global $resourcesFile, $searchImg;

    if (!file_exists($resourcesFile)) {
        return '<div class="notice notice-error">Resource file not found: ' . esc_html($resourcesFile) . '</div>';
    }
    if (!file_exists($searchImg)) {
        return '<div class="notice notice-error">Search image not found: ' . esc_html($searchImg) . '</div>';
    }

    // Handle the search query
    $searchQuery = isset($_GET['kw']) ? sanitize_text_field($_GET['kw']) : '';
    $searchTerms = array_filter(explode(' ', $searchQuery)); // Split search query into individual terms

    // Capture the selected tags
    $selectedTags = isset( $_GET['tags'] ) ? $_GET['tags'] :'';

    // Handle pagination
    $currentPage = isset($_GET['pg']) ? intval($_GET['pg']) : 1;
    $rowsPerPage = 10;
    $startRow = ($currentPage - 1) * $rowsPerPage;
    $paginationRange = 1; // Number of pagination links to show around the current page
    
    ob_start(); // Buffer output to return it properly

    // Open the CSV file for reading
    if (($fileHandle = @fopen($resourcesFile, 'r')) !== false) {
        if (!is_readable($resourcesFile)) {
            return '<div class="notice notice-error">File is not readable: ' . esc_html($resourcesFile) . '</div>';
        }
        // Read all keywords/tags from the CSV file
        $allKeywords = [];
        while (($row = fgetcsv($fileHandle)) !== false) {
            if (isset($row[0]) && strpos($row[0], '#') === 0) {
                continue; // Skip commented rows
            }
            if (isset($row[3])) {
                $keywords = explode(',', $row[3]);
                foreach ($keywords as $keyword) {
                    $keyword = trim($keyword);
                    if ($keyword !== '' && !in_array($keyword, $allKeywords)) {
                        $allKeywords[] = $keyword;
                    }
                }
            }
        }
        fclose($fileHandle);

        // Sort keywords alphabetically
        sort($allKeywords);

        //----------------------------------------------------------------------------------------

        // Display search form and keywords dropdown in a container

        echo '<div class="resources-search-container">';
        echo '<div class="search-controls">';
        echo '<div class="search-wrapper">';
        echo '<input type="text" id="resourceSearch" name="kw" placeholder="Search database..." value="' . esc_attr($searchQuery) . '">';
        echo '<button type="button" class="search-button" aria-label="Search">';
        echo '<img src="' . esc_url($searchImg) . '" alt="Search">'; // Use $searchImg for image source
        echo '</button>';
        echo '</div>';
        echo '<button type="button" class="reset-button" onclick="resetFilters()">';
        echo '<span>×</span> Reset Filters';
        echo '</button>';
        echo '</div>';

        // Tags section
        echo '<div class="tags-container" id="filterTags">';
        $allTags = [];
        if (($handle = fopen($resourcesFile, 'r')) !== false) {
            while (($row = fgetcsv($handle)) !== false) {
                if (isset($row[3])) {  // Check if Keywords column exists
                    $tags = array_map('trim', explode(',', $row[3]));
                    foreach ($tags as $tag) {
                        if (!empty($tag) && !in_array($tag, $allTags)) {
                            $allTags[] = $tag;
                        }
                    }
                }
            }
            fclose($handle);
            sort($allTags);  

            // Display tags as interactive buttons
            foreach ($allTags as $tag) {
                if (!empty($tag)) {
                    $isSelected = in_array($tag, $_GET['tags'] ?? []) ? 'selected' : '';
                    echo sprintf(
                        '<button type="button" class="tag %s" data-tag="%s">%s</button>',
                        $isSelected,
                        htmlspecialchars($tag),
                        htmlspecialchars($tag)
                    );
                }
            }
        } else {
            echo '<div class="notice notice-error">Unable to read tags from file</div>';
        }
        echo '</div>'; // Close tags-container

        echo '</div>'; // Close resources-search-container

        //javascript for clearing filters
        echo "<script>
        function clearFilters() {
            var select = document.querySelector('select[name=\"tags[]\"]');
            for (var i = 0; i < select.options.length; i++) {
                select.options[i].selected = false;
            }
            document.querySelector('.resources-search').submit();
        }
        </script>";

        //----------------------------------------------------------------------------------------

        // Display the table of resources

        $selectedTags = $_GET['tags'] ?? [];

        if (($fileHandle = fopen($resourcesFile, 'r')) !== false) {
            echo '<table class="csv-table">';
            echo '<tr><th>Resource</th><th>Hotline Phone/Text</th><th>Resource Description</th><th>Keywords</th></tr>';
            
            while (($row = fgetcsv($fileHandle)) !== false) {
                $rowKeywords = array_map('trim', explode(',', $row[3]));
                $showRow = empty($selectedTags) || count(array_intersect($selectedTags, $rowKeywords)) > 0;
                
                if ($showRow) {
                    echo '<tr>';
                    foreach ($row as $i => $cell) {
                        if ($i == 3) {
                            // Format keywords as clickable tags
                            $keywords = explode(',', $cell);
                            echo '<td>';
                            foreach ($keywords as $keyword) {
                                $keyword = trim($keyword);
                                $isSelected = in_array($keyword, $selectedTags) ? 'selected' : '';
                                echo "<a href='#' class='tag $isSelected'>" . htmlspecialchars($keyword) . "</a> ";
                            }
                            echo '</td>';
                        } else {
                            echo '<td>' . htmlspecialchars($cell) . '</td>';
                        }
                    }
                    echo '</tr>';
                }
            }
            echo '</table>';
            fclose($fileHandle);
        } else {
            // Error opening the file
            return '<div class="notice notice-error is-dismissible">Error opening ' . esc_html($resourcesFile) . '</div>';
        }
    } else {
        // Error opening the file
        return '<div class="notice notice-error is-dismissible">Error opening ' . esc_html($resourcesFile) . '</div>';
    }

    // Return the buffered content as a string
    return ob_get_clean();
}

//================================================================================================

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
