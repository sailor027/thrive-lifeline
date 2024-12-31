jQuery(document).ready(function($) {
    console.log('Custom plugin script loaded.');
});

//==================================================================================================

document.addEventListener('DOMContentLoaded', function() {
    // Debug logging
    console.log('Script initialized');
    console.log('WordPress data:', dbPluginData);
    let resources = [];
    let selectedTags = new Set();
    
    // Load and initialize the resources
    async function loadResources() {
        try {
            // Fetch resources using WordPress AJAX
            const response = await fetch(dbPluginData.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'get_resources',
                    nonce: dbPluginData.nonce
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.data || 'Failed to load resources');
            }

            resources = data.data;
            console.log('Resources loaded:', resources.length);
            
            initializeTags();
            renderTable(resources);
            
        } catch (error) {
            console.error('Error loading resources:', error);
            document.getElementById('resourceTableBody').innerHTML = 
                '<tr><td colspan="4">Error loading resources. Please try refreshing the page.</td></tr>';
        }
    }

    // Initialize tags from resources
    function initializeTags() {
        const tagsContainer = document.getElementById('filterTags');
        if (!tagsContainer) return;

        const allTags = new Set();
        resources.forEach(resource => {
            const tags = resource.Keywords.split(',').map(tag => tag.trim());
            tags.forEach(tag => {
                if (tag) allTags.add(tag);
            });
        });

        const sortedTags = Array.from(allTags).sort();
        
        tagsContainer.innerHTML = sortedTags.map(tag => 
            `<button type="button" class="tag" data-tag="${tag}">${tag}</button>`
        ).join('');

        // Add click handlers for tags
        tagsContainer.querySelectorAll('.tag').forEach(tagElement => {
            tagElement.addEventListener('click', () => {
                const tag = tagElement.dataset.tag;
                if (selectedTags.has(tag)) {
                    selectedTags.delete(tag);
                    tagElement.classList.remove('selected');
                } else {
                    selectedTags.add(tag);
                    tagElement.classList.add('selected');
                }
                filterResources();
            });
        });
    }

    // Render the resources table
    function renderTable(resourcesToShow) {
        const tbody = document.getElementById('resourceTableBody');
        if (!tbody) return;

        if (!resourcesToShow.length) {
            tbody.innerHTML = '<tr><td colspan="4">No matching resources found</td></tr>';
            return;
        }

        tbody.innerHTML = resourcesToShow.map(resource => `
            <tr>
                <td>${resource.Resource}</td>
                <td>${resource['Hotline Phone/Text']}</td>
                <td>${resource['Resource Description']}</td>
                <td>${formatTags(resource.Keywords)}</td>
            </tr>
        `).join('');
    }

    // Format tags as individual tag elements
    function formatTags(tagsString) {
        return tagsString.split(',')
            .map(tag => tag.trim())
            .filter(tag => tag)
            .map(tag => `<span class="tag">${tag}</span>`)
            .join(' ');
    }

    // Filter resources based on search and selected tags
    function filterResources() {
        const searchInput = document.getElementById('resourceSearch');
        const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';

        const filteredResources = resources.filter(resource => {
            const matchesSearch = !searchTerm || 
                Object.values(resource).some(value => 
                    value.toLowerCase().includes(searchTerm)
                );

            const matchesTags = selectedTags.size === 0 || 
                Array.from(selectedTags).every(tag =>
                    resource.Keywords.toLowerCase().includes(tag.toLowerCase())
                );

            return matchesSearch && matchesTags;
        });

        renderTable(filteredResources);
    }

    // Set up search functionality
    const searchInput = document.getElementById('resourceSearch');
    if (searchInput) {
        searchInput.addEventListener('input', filterResources);
    }

    // Reset filters function
    window.resetFilters = function() {
        selectedTags.clear();
        document.querySelectorAll('.tag.selected').forEach(tag => {
            tag.classList.remove('selected');
        });
        if (searchInput) {
            searchInput.value = '';
        }
        filterResources();
    };

    // Initialize on page load
    loadResources();
});

//==================================================================================================
function toggleTagFilter(tag) {
    // Find the corresponding filter tag button
    const filterTag = document.querySelector(`.tag[data-tag="${tag}"]`);
    if (filterTag) {
        // Simulate a click on the filter tag
        filterTag.click();
        
        // Update table tags to match the filter tag state
        const tableTagButtons = document.querySelectorAll(`.table-tag[data-tag="${tag}"]`);
        tableTagButtons.forEach(button => {
            button.classList.toggle('selected', filterTag.classList.contains('selected'));
        });
    }
}