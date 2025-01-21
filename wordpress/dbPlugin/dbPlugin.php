<?php
//========================================================================================================
/*
Plugin Name: Database Plugin
Plugin URI: https://github.com/sailor027/thrive-lifeline/tree/main/wordpress/dbPlugin
Description: WP plugin to read a CSV file and display its contents in PHP.
Version: 2.9.6
Date: 2025.01.20
Author: Ko Horiuchi
License: MIT
*/
//========================================================================================================

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin version and constants
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
// Enqueue custom styles and scripts
function dbPlugin_enqueueStyles() {
    wp_enqueue_style(
        'dbPlugin-styles', 
        plugin_dir_url(DBPLUGIN_FILE) . 'style.css',
        array()
    );
}
add_action('wp_enqueue_scripts', 'dbPlugin_enqueueStyles');

function dbPlugin_enqueueScript() {
    wp_enqueue_script(
        'dbPlugin-script', 
        plugin_dir_url(DBPLUGIN_FILE) . 'script.js', 
        array('jquery'), 
        true
    );
    wp_localize_script(
        'dbPlugin-script',
        'dbPluginData',
        array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dbPlugin_nonce')
        )
    );
}
add_action('wp_enqueue_scripts', 'dbPlugin_enqueueScript');

//--------------------------------------------------------------------------------------------
// AJAX handlers
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
        try {
            $headers = fgetcsv($handle);
            
            while (($row = fgetcsv($handle)) !== false) {
                if (isset($row[0]) && strpos($row[0], '#') === 0) {
                    continue;
                }
                
                if (count($row) === count($headers)) {
                    $resource = array_combine($headers, $row);
                    $resources[] = $resource;
                }
            }
        } catch (Exception $e) {
            wp_send_json_error('Error processing CSV: ' . $e->getMessage());
            return;
        } finally {
            fclose($handle);
        }
        
        wp_send_json_success($resources);
    } else {
        wp_send_json_error('Failed to open resource file');
    }
}

//--------------------------------------------------------------------------------------------
// Register shortcode
add_shortcode('displayResources', 'displayResourcesShortcode');

// function sanitize_tag_array($tags) {
//     if (!is_array($tags)) {
//         return array();
//     }
//     return array_map(function($tag) {
//         return trim(sanitize_text_field(urldecode($tag)));
//     }, $tags);
// }
function sanitize_tag_array($tags) {
    if (!is_array($tags)) {
        return array();
    }
    return array_map(function($tag) {
        return sanitize_text_field(urldecode($tag));
    }, $tags);
}

function displayResourcesShortcode($atts = array()) {
    global $resourcesFile, $searchImg, $phoneImg;

    if (!file_exists($resourcesFile) || !is_readable($resourcesFile)) {
        return '<div class="notice notice-error">Resource file not found or not readable: ' . esc_html($resourcesFile) . '</div>';
    }

    // Handle search and filtering
    $searchQuery = isset($_GET['kw']) ? sanitize_text_field($_GET['kw']) : '';
    $searchTerms = array_filter(explode(' ', $searchQuery));
    $selectedTags = isset($_GET['tags']) ? sanitize_tag_array($_GET['tags']) : array();

    // Initialize counters and arrays
    $totalRows = 0;
    $filteredRows = array();
    $allTags = array();

    // Read and process data
    $fileHandle = fopen($resourcesFile, 'r');
    if ($fileHandle === false) {
        return '<div class="notice notice-error">Failed to open resource file</div>';
    }

    try {
        $headers = fgetcsv($fileHandle);
        
        while (($row = fgetcsv($fileHandle)) !== false) {
            // Skip commented rows
            if (isset($row[0]) && strpos($row[0], '#') === 0) {
                continue;
            }
            
            // Collect tags and clean them
            if (isset($row[3])) {
                $rowTags = array_map('trim', explode(',', $row[3]));
                foreach ($rowTags as $tag) {
                    if (!empty($tag) && !in_array($tag, $allTags)) {
                        $allTags[] = $tag;
                    }
                }
            }

            // Check if row matches selected filters
            // AND operator for search terms
            $matchesSearch = empty($searchTerms) || array_reduce($searchTerms, function($carry, $term) use ($row) {
                return $carry && stripos(implode(' ', $row), $term) !== false;
            }, true);
            
            
            $matchesTags = empty($selectedTags);
            // if (!empty($selectedTags) && isset($row[3])) {
            //     $rowTags = array_map('trim', explode(',', $row[3]));
            //     $matchesTags = true;
            //     foreach ($selectedTags as $tag) {
            //         if (!in_array($tag, $rowTags)) {
            //             $matchesTags = false;
            //             break;
            //         }
            //     }
            // }
            if (!empty($selectedTags) && isset($row[3])) {
                $rowTags = array_map('trim', explode(',', $row[3]));
                $matchesTags = count(array_intersect($selectedTags, $rowTags)) === count($selectedTags);
            }
            
            if ($matchesSearch && $matchesTags) {
                $filteredRows[] = $row;
                $totalRows++;
            }
        }
    } catch (Exception $e) {
        fclose($fileHandle);
        return '<div class="notice notice-error">Error processing data: ' . esc_html($e->getMessage()) . '</div>';
    }
    
    fclose($fileHandle);
    sort($allTags);

    // Set up pagination
    $rowsPerPage = 10;
    $totalPages = max(1, ceil($totalRows / $rowsPerPage));
    $currentPage = isset($_GET['pg']) ? min(max(1, intval($_GET['pg'])), $totalPages) : 1;
    $startRow = ($currentPage - 1) * $rowsPerPage;
    
    // Get rows for current page
    $paginatedRows = array_slice($filteredRows, $startRow, $rowsPerPage);

    ob_start();

    // Display search form
    echo '<div class="resources-search-container">';
    echo '<div class="search-controls">';
    echo '<form class="search-wrapper">';  // Removed onsubmit="return false;"
    echo '<input type="text" id="resourceSearch" name="kw" placeholder="Search database..." value="' . esc_attr($searchQuery) . '">';
    echo '<button type="submit" class="search-button" aria-label="Search">';
    echo '<img src="' . esc_url(plugin_dir_url(DBPLUGIN_FILE) . 'media/search.svg') . '" alt="Search">';
    echo '</button>';
    echo '</form>';
    echo '<button type="button" class="reset-button" onclick="resetFilters()">';
    echo '<span>×</span> Reset Filters';
    echo '</button>';
    echo '</div>';

    // Updated results count message
    $countMessage = ($totalRows === count($filteredRows)) 
        ? sprintf('Showing all %d resources', $totalRows)
        : sprintf('Showing %d filtered resources', $totalRows);
    echo '<div class="result-count">' . esc_html($countMessage) . '</div>';

    // Display tags
    echo '<div class="tags-container" id="filterTags">';
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
    echo '</div>'; // Close tags-container
    echo '</div>'; // Close resources-search-container

    // Display table
    echo '<div id="resourceTableContainer">';
    echo '<table class="csv-table">';
    echo '<thead><tr><th>Resource</th><th>Resource Description</th><th>Keywords</th></tr></thead>';
    echo '<tbody id="resourceTableBody">';

    foreach ($paginatedRows as $row) {
        $resource = isset($row[0]) ? $row[0] : '';
        $phoneNum = isset($row[1]) ? $row[1] : '';
        $description = isset($row[2]) ? $row[2] : '';
        $keywords = isset($row[3]) ? array_map('trim', explode(',', $row[3])) : array();
        $website = isset($row[4]) ? $row[4] : '';

        echo '<tr>';
        echo '<td>';
        if (!empty($website)) {
            echo '<a href="' . esc_url($website) . '" target="_blank" rel="noopener noreferrer">' . 
                 esc_html($resource) . '</a>';
        } else {
            echo esc_html($resource);
        }
        echo '</td>';
        
        echo '<td>';
        if (!empty($phoneNum)) {
            $phoneIconHtml = '<img src="' . esc_url(plugin_dir_url(DBPLUGIN_FILE) . 'media/phone.svg') . 
                            '" alt="Phone" class="phone-icon">';
            $phoneNumHtml = '<div class="phone-num-container">' . $phoneIconHtml . 
                           '<span class="phone-num">' . esc_html($phoneNum) . '</span></div>';
            echo $phoneNumHtml;
        }
        echo '<div class="description">' . esc_html($description) . '</div>';
        echo '</td>';
        
        echo '<td><div class="tag-container">';
        foreach ($keywords as $keyword) {
            if (!empty($keyword)) {
                $isSelected = in_array($keyword, $selectedTags) ? 'selected' : '';
                printf(
                    '<button type="button" class="table-tag %s" onclick="toggleTagFilter(\'%s\')" data-tag="%s">%s</button>',
                    esc_attr($isSelected),
                    esc_attr($keyword),
                    esc_attr($keyword),
                    esc_html($keyword)
                );
            }
        }
        echo '</div></td>';
        echo '</tr>';
    }
    
    echo '</tbody></table>';
    
    // Add pagination controls
    if ($totalRows > $rowsPerPage) {
        echo '<div class="pagination" role="navigation" aria-label="Resource list pagination">';
        
        if ($currentPage > 1) {
            printf(
                '<button type="button" class="page-np" onclick="changePage(%d)" aria-label="Go to previous page">&lt;</button>',
                $currentPage - 1
            );
        }
        
        $paginationRange = 2;
        for ($i = max(1, $currentPage - $paginationRange); 
             $i <= min($totalPages, $currentPage + $paginationRange); $i++) {
            if ($i == $currentPage) {
                printf(
                    '<span class="current-page" aria-current="page">%d</span>',
                    $i
                );
            } else {
                printf(
                    '<button type="button" class="page-n" onclick="changePage(%d)" aria-label="Go to page %d">%d</button>',
                    $i, $i, $i
                );
            }
        }
        
        if ($currentPage < $totalPages) {
            printf(
                '<button type="button" class="page-np" onclick="changePage(%d)" aria-label="Go to next page">&gt;</button>',
                $currentPage + 1
            );
        }
        
        echo '</div>';
    }
    
    echo '</div>'; // Close resourceTableContainer
    
    return ob_get_clean();
}

// Admin menu functions
add_action('admin_menu', 'dbPlugin_pluginMenu');

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

function dbPlugin_displayInstructions() {
    global $docsFile;
    $fileContents = file_get_contents($docsFile);
    if ($fileContents !== false) {
        echo wp_kses_post($fileContents);
    } else {
        echo '<div class="notice notice-error is-dismissible">Error opening ' . esc_html($docsFile) . '</div>';
    }
}

function dbPlugin_add_help_tab() {
    $screen = get_current_screen();
    $screen->add_help_tab(array(
        'id'       => 'dbPlugin_help',
        'title'    => 'Plugin Usage',
        'content'  => '<p>Use the shortcode [displayResources] to show the database table on any page.</p>'
    ));
}