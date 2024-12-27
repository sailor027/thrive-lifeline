<?php
//================================================================================================
/*
Plugin Name: Database Plugin
Plugin URI: https://github.com/khruc-sail/thrive-lifeline/tree/d59726f87327825c7547e7f6fae340d5a9a5359e/wordpress/dbPlugin
Description: WP plugin to read a CSV file and display its contents in PHP.
Version: 2.8.3
Date: 2024.12.27
Author: Ko Horiuchi
License: MIT
*/
//================================================================================================

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin version - update this single constant to change all version references
define('DBPLUGIN_VERSION', '2.8.3'); //TODO Update version number
define('DBPLUGIN_FILE', __FILE__);

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

//-----------------------------------------------------------------------------------------------

// Define plugin paths securely
$plugin_dir = wp_normalize_path(plugin_dir_path(DBPLUGIN_FILE));
$resourcesFile = $plugin_dir . 'crisisResources.csv';
$docsFile = $plugin_dir . 'documentations.md';
$searchImg = $plugin_dir . 'media/search.svg';

//-----------------------------------------------------------------------------------------------

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
            'csvPath' => wp_normalize_path(plugin_dir_path(DBPLUGIN_FILE) . 'crisisResources.csv'),
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dbPlugin_nonce'),
            'version' => DBPLUGIN_VERSION
        )
    );
}
add_action('wp_enqueue_scripts', 'dbPlugin_enqueueScript');

//-----------------------------------------------------------------------------------------------

// Register shortcode
add_shortcode('displayResources', 'displayResourcesShortcode');

//================================================================================================

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

/**
 * Display resources table with search and filtering capabilities
 *
 * @param array $atts Shortcode attributes (unused)
 * @return string HTML output of the resources table
 */
function displayResourcesShortcode($atts = array()) {
    global $resourcesFile, $searchImg;

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

    // Handle pagination
    $currentPage = isset($_GET['pg']) ? max(1, intval($_GET['pg'])) : 1;
    $rowsPerPage = 10;
    $startRow = ($currentPage - 1) * $rowsPerPage;
    $paginationRange = 1;
    
    ob_start();

    // Process the CSV file
    $fileHandle = @fopen($resourcesFile, 'r');
    if ($fileHandle === false) {
        return '<div class="notice notice-error">Unable to open resource file</div>';
    }

    if (!is_readable($resourcesFile)) {
        fclose($fileHandle);
        return '<div class="notice notice-error">File is not readable: ' . esc_html($resourcesFile) . '</div>';
    }

    // Read all keywords/tags
    $allKeywords = array();
    while (($row = fgetcsv($fileHandle)) !== false) {
        if (isset($row[0]) && strpos($row[0], '#') === 0) {
            continue;
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
    sort($allKeywords);

    // Display search form
    echo '<div class="resources-search-container">';
    echo '<div class="search-controls">';
    echo '<div class="search-wrapper">';
    echo '<input type="text" id="resourceSearch" name="kw" placeholder="Search database..." value="' . esc_attr($searchQuery) . '">';
    echo '<button type="button" class="search-button" aria-label="Search">';
    echo '<img src="' . esc_url(plugin_dir_url(DBPLUGIN_FILE) . 'media/search.svg') . '" alt="Search">';
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
    echo '<tr><th>Resource</th><th>Hotline Phone/Text</th><th>Resource Description</th><th>Keywords</th></tr>';
    echo '</thead>';
    echo '<tbody id="resourceTableBody">';
    echo '<tr><td colspan="4" class="text-center">Loading resources...</td></tr>';
    echo '</tbody>';
    echo '</table>';
    echo '</div>';

    return ob_get_clean();
}

//================================================================================================

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