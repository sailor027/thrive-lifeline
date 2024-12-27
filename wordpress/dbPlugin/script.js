jQuery(document).ready(function($) {
    console.log('Custom plugin script loaded.');
});

//==================================================================================================

document.addEventListener('DOMContentLoaded', function() {
    let resources = [];
    let selectedTags = new Set();
    
    // Load and initialize the resources
    async function loadResources() {
        try {
            // Use WordPress-provided URL
            const response = await fetch(csvToPhpData.csvUrl);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const csvText = await response.text();
            
            // Parse CSV with error handling
            const rows = csvText.split('\n')
                .filter(row => row.trim()) // Remove empty rows
                .map(row => {
                    // Handle quoted CSV values properly
                    const matches = row.match(/(".*?"|[^",]+)(?=\s*,|\s*$)/g);
                    return matches ? matches.map(val => val.replace(/^"|"$/g, '').trim()) : [];
                });
            
            const headers = rows[0];
            
            // Convert to array of objects with validation
            resources = rows.slice(1)
                .filter(row => row.length === headers.length) // Ensure row has all columns
                .map(row => {
                    const resource = {};
                    headers.forEach((header, index) => {
                        resource[header.trim()] = row[index]?.trim() || '';
                    });
                    return resource;
                });

            console.log('Resources loaded:', resources.length);
            
            // Initialize tags
            initializeTags();
            // Render initial table
            renderTable(resources);
        } catch (error) {
            console.error('Error loading resources:', error);
            document.getElementById('resourceTableBody').innerHTML = 
                '<tr><td colspan="4">Error loading resources. Please try refreshing the page.</td></tr>';
        }
    }

    // Rest of the functions remain the same...
    
    // Initialize on page load
    loadResources();
});