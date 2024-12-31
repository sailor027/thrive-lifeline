<?php
//========================================================================================================
/*
Plugin Name: Database Plugin
Plugin URI: https://github.com/sailor027/thrive-lifeline/tree/main/wordpress/dbPlugin
Description: WP plugin to read a CSV file and display its contents in PHP.
Version: 2.8.4
Date: 2024.12.30
Author: Ko Horiuchi
License: MIT
*/
//========================================================================================================

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin version and constants
define('DBPLUGIN_VERSION', '2.8.3');
define('DBPLUGIN_FILE', __FILE__);

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define plugin paths securely
$plugin_dir = wp_normalize_path(plugin_dir_path(DBPLUGIN_FILE));
$resourcesFile = $plugin_dir . 'crisisResources.csv';
$docsFile = $plugin_dir . 'README.md';
$searchImg = $plugin_dir . 'media/search.svg';
$phoneImg = $plugin_dir . 'media/phone.svg';

//--------------------------------------------------------------------------------------------
// Enqueue custom styles
function dbPlugin_enqueueStyles() {
    wp_enqueue_style(
        'dbPlugin-styles', 
        plugin_dir_url(DBPLUGIN_FILE) . 'style.css',
        array(),
        DBPLUGIN_VERSION
    );
}
add_action('wp_enqueue_scripts', 'dbPlugin_enqueueStyles');

// Enqueue JavaScript
function dbPlugin_enqueueScript() {
    wp_enqueue_script(
        'dbPlugin-script', 
        plugin_dir_url(DBPLUGIN_FILE) . 'script.js', 
        array('jquery'), 
        DBPLUGIN_VERSION, 
        true
    );
    wp_localize_script(
        'dbPlugin-script', 
        'dbPluginData', 
        array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dbPlugin_nonce'),
            'version' => DBPLUGIN_VERSION
        )
    );
}
add_action('wp_enqueue_scripts', 'dbPlugin_enqueueScript');

//--------------------------------------------------------------------------------------------
// Add AJAX handlers for both logged-in and non-logged-in users
add_action('wp_ajax_get_resources', 'handle_get_resources');
add_action('wp_ajax_nopriv_get_resources', 'handle_get_resources');

function handle_get_resources() {
    check_ajax_referer('dbPlugin_nonce', 'nonce');
    
    global $resourcesFile;
    
    if (!file_exists($resourcesFile)) {
        wp_send_json_error('Resource file not found');
        return;
    }
    
    if (!is_readable($resourcesFile)) {
        wp_send_json_error('Resource file is not readable');
        return;
    }
    
    $resources = array();
    $handle = fopen($resourcesFile, 'r');
    
    if ($handle !== false) {
        // Skip header row
        $headers = fgetcsv($handle);
        
        // Read data rows
        while (($row = fgetcsv($handle)) !== false) {
            // Skip commented rows
            if (isset($row[0]) && strpos($row[0], '#') === 0) {
                continue;
            }
            
            // Create associative array using headers
            if (count($row) === count($headers)) {
                $resource = array_combine($headers, $row);
                $resources[] = $resource;
            }
        }
        fclose($handle);
        
        wp_send_json_success($resources);
    } else {
        wp_send_json_error('Failed to open resource file');
    }
}

//--------------------------------------------------------------------------------------------
// Register shortcode
add_shortcode('displayResources', 'displayResourcesShortcode');

//========================================================================================================
/**
 * Sanitize an array of tags
 *
 * @param array $tags Array of tags to sanitize
 * @return array Sanitized array of tags
 */
function sanitize_tag_array($tags) {
    if (!is_array($tags)) {
        return array();
    }
    return array_map('sanitize_text_field', $tags);
}

//========================================================================================================
/**
 * Display resources table with search and filtering capabilities
 *
 * @param array $atts Shortcode attributes (unused)
 * @return string HTML output of the resources table
 */
function displayResourcesShortcode($atts = array()) {
    global $resourcesFile, $searchImg, $phoneImg;

    // Verify file existence
    if (!file_exists($resourcesFile)) {
        return '<div class="notice notice-error">Resource file not found: ' . esc_html($resourcesFile) . '</div>';
    }
    if (!file_exists($searchImg)) {
        return '<div class="notice notice-error">Search image not found: ' . esc_html($searchImg) . '</div>';
    }

    // Handle search and filtering
    $searchQuery = isset($_GET['kw']) ? sanitize_text_field($_GET['kw']) : '';
    $searchTerms = array_filter(explode(' ', $searchQuery));
    $selectedTags = isset($_GET['tags']) ? sanitize_tag_array($_GET['tags']) : array();

    // Set up pagination variables
    $rowsPerPage = 10; // Number of rows per page
    $currentPage = isset($_GET['pg']) ? max(1, intval($_GET['pg'])) : 1;
    $startRow = ($currentPage - 1) * $rowsPerPage;
    $paginationRange = 2; // Number of page numbers to show on each side of current page

    ob_start();

    // Display search form
    echo '<div class="resources-search-container">';
    echo '<div class="search-controls">';
    echo '<div class="search-wrapper">';
    echo '<input type="text" id="resourceSearch" name="kw" placeholder="Search database..." value="' . esc_attr($searchQuery) . '">';
    echo '<button type="button" class="search-button" aria-label="Search">';
    echo '<img src="' . esc_url(plugin_dir_url(DBPLUGIN_FILE) . $searchImg) . '" alt="Search">';
    echo '</button>';
    echo '</div>';
    echo '<button type="button" class="reset-button" onclick="resetFilters()">';
    echo '<span>×</span> Reset Filters';
    echo '</button>';
    echo '</div>';

    // Display tags section
    echo '<div class="tags-container" id="filterTags">';
    $allTags = array();
    $handle = fopen($resourcesFile, 'r');
    if ($handle !== false) {
        while (($row = fgetcsv($handle)) !== false) {
            if (isset($row[3])) {
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

        foreach ($allTags as $tag) {
            if (!empty($tag)) {
                $isSelected = in_array($tag, $selectedTags) ? 'selected' : '';
                printf(
                    '<button type="button" class="tag %s" data-tag="%s">%s</button>',
                    esc_attr($isSelected),
                    esc_attr($tag),
                    esc_html($tag)
                );
            }
        }
    } else {
        echo '<div class="notice notice-error">Unable to read tags from file</div>';
    }
    echo '</div>'; // Close tags-container
    echo '</div>'; // Close resources-search-container

    // Display resource table
    echo '<div id="resourceTableContainer" data-version="' . esc_attr(DBPLUGIN_VERSION) . '">';
    echo '<table class="csv-table">';
    echo '<thead>';
    echo '<tr><th>Resource</th><th>Resource Description</th><th>Keywords</th></tr>';
    echo '</thead>';
    echo '<tbody id="resourceTableBody">';

    $fileHandle = fopen($resourcesFile, 'r');
    if ($fileHandle !== false) {
        $headers = fgetcsv($fileHandle);
        $displayedRows = 0;
        $totalRows = 0;

        while (($row = fgetcsv($fileHandle)) !== false) {
            if (isset($row[0]) && strpos($row[0], '#') === 0) {
                continue;
            }
        
            $resource = isset($row[0]) ? $row[0] : '';
            $phoneNum = isset($row[1]) ? $row[1] : '';
            $description = isset($row[2]) ? $row[2] : '';
            $keywords = isset($row[3]) ? array_map('trim', explode(',', $row[3])) : array();
            $website = isset($row[4]) ? $row[4] : '';
        
            // Combine phone number with description if it exists
            $combinedDescription = $description;
            if (!empty($phoneNum)) {
                $phoneIconHtml = '<img src="' . esc_url(plugin_dir_url(DBPLUGIN_FILE) . $phoneImg) . '" alt="Phone" class="phone-icon">';
                $phoneNumHtml = '<div class="phone-num-container">' . $phoneIconHtml . '<span class="phone-num">' . esc_html($phoneNum) . '</span></div>';
                $combinedDescription = $phoneNumHtml . '<div class="description">' . esc_html($description) . '</div>';
            }
        
            // Check if row matches search criteria
            $matchesSearch = empty($searchTerms) || array_reduce($searchTerms, function($carry, $term) use ($row) {
                return $carry && stripos(implode(' ', $row), $term) !== false;
            }, true);
        
            $matchesTags = empty($selectedTags) || array_reduce($selectedTags, function($carry, $tag) use ($keywords) {
                return $carry && in_array($tag, array_map('trim', $keywords));
            }, true);
        
            if ($matchesSearch && $matchesTags) {
                $totalRows++;
                if ($totalRows > $startRow && $displayedRows < $rowsPerPage) {
                    echo '<tr>';
                    
                    // Resource column with website link
                    echo '<td>';
                    if (!empty($website)) {
                        echo '<a href="' . esc_url($website) . '" target="_blank" rel="noopener noreferrer">' . 
                             esc_html($resource) . '</a>';
                    } else {
                        echo esc_html($resource);
                    }
                    echo '</td>';
                    
                    // Combined description column with phone number if present
                    echo '<td>' . $combinedDescription . '</td>';
                    
                    // Keywords column with clickable tags
                    echo '<td><div class="tag-container">';
                    foreach ($keywords as $keyword) {
                        if (!empty($keyword)) {
                            $isSelected = in_array($keyword, $selectedTags) ? 'selected' : '';
                            printf(
                                '<button type="button" class="table-tag %s" ' .
                                'onclick="toggleTagFilter(\'%s\')" ' .
                                'data-tag="%s">%s</button>',
                                esc_attr($isSelected),
                                esc_attr($keyword),
                                esc_attr($keyword),
                                esc_html($keyword)
                            );
                        }
                    }
                    echo '</div></td>';
                    
                    echo '</tr>';
                    $displayedRows++;
                }
            }
        }
        fclose($fileHandle);
    }

    echo '</tbody>';
    echo '</table>';

    // Add pagination
    if ($totalRows > $rowsPerPage) {
        $totalPages = ceil($totalRows / $rowsPerPage);
        echo '<div class="pagination">';
        
        // Previous page button
        if ($currentPage > 1) {
            echo '<button class="page-np" onclick="changePage(' . ($currentPage - 1) . ')">&lt;</button>';
        }

        // Page numbers
        for ($i = max(1, $currentPage - $paginationRange); 
             $i <= min($totalPages, $currentPage + $paginationRange); $i++) {
            if ($i == $currentPage) {
                echo '<span class="current-page">' . $i . '</span>';
            } else {
                echo '<button class="page-n" onclick="changePage(' . $i . ')">' . $i . '</button>';
            }
        }

        // Next page button
        if ($currentPage < $totalPages) {
            echo '<button class="page-np" onclick="changePage(' . ($currentPage + 1) . ')">&gt;</button>';
        }
        
        echo '</div>';
    }

    echo '</div>'; // Close resourceTableContainer

    return ob_get_clean();
}

//--------------------------------------------------------------------------------------------
// Add admin menu
add_action('admin_menu', 'dbPlugin_pluginMenu');

/**
 * Add plugin menu to WordPress admin
 */
function dbPlugin_pluginMenu() {
    $hook = add_menu_page(
        'Database Plugin Instructions',
        'Database Plugin',
        'manage_options',
        'dbPlugin',
        'dbPlugin_displayInstructions'
    );
    add_action("load-$hook", 'dbPlugin_add_help_tab');
}

/**
 * Display plugin instructions in admin
 */
function dbPlugin_displayInstructions() {
    global $docsFile;
    $fileContents = file_get_contents($docsFile);
    if ($fileContents !== false) {
        echo wp_kses_post($fileContents);
    } else {
        echo '<div class="notice notice-error is-dismissible">Error opening ' . esc_html($docsFile) . '</div>';
    }
}

/**
 * Add help tab to plugin page
 */
function dbPlugin_add_help_tab() {
    $screen = get_current_screen();
    $screen->add_help_tab(array(
        'id'       => 'dbPlugin_help',
        'title'    => 'Plugin Usage',
        'content'  => '<p>Use the shortcode [displayResources] to show the database table on any page.</p>'
    ));
}