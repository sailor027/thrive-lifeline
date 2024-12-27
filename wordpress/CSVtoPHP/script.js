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
            const response = await fetch('path/to/your/crisisResources.csv');
            const csvText = await response.text();
            
            // Parse CSV
            const rows = csvText.split('\n').map(row => row.split(','));
            const headers = rows[0];
            
            // Convert to array of objects
            resources = rows.slice(1).map(row => {
                const resource = {};
                headers.forEach((header, index) => {
                    resource[header.trim()] = row[index]?.trim() || '';
                });
                return resource;
            });

            // Initialize tags
            initializeTags();
            // Render initial table
            renderTable(resources);
        } catch (error) {
            console.error('Error loading resources:', error);
        }
    }

    // Initialize tag filters
    function initializeTags() {
        const tagsContainer = document.getElementById('filterTags');
        const uniqueTags = new Set();

        resources.forEach(resource => {
            if (resource.Keywords) {
                resource.Keywords.split(',').forEach(tag => {
                    uniqueTags.add(tag.trim());
                });
            }
        });

        Array.from(uniqueTags).sort().forEach(tag => {
            const tagButton = document.createElement('button');
            tagButton.className = 'tag';
            tagButton.textContent = tag;
            tagButton.onclick = () => toggleTag(tag, tagButton);
            tagsContainer.appendChild(tagButton);
        });
    }

    // Toggle tag selection
    function toggleTag(tag, button) {
        if (selectedTags.has(tag)) {
            selectedTags.delete(tag);
            button.classList.remove('selected');
        } else {
            selectedTags.add(tag);
            button.classList.add('selected');
        }
        filterResources();
    }

    // Filter resources based on search and tags
    function filterResources() {
        const searchQuery = document.getElementById('resourceSearch').value.toLowerCase();
        
        const filteredResources = resources.filter(resource => {
            const matchesSearch = !searchQuery || 
                Object.values(resource).some(value => 
                    value.toLowerCase().includes(searchQuery)
                );
                
            const matchesTags = selectedTags.size === 0 || 
                Array.from(selectedTags).some(tag => 
                    resource.Keywords.includes(tag)
                );
                
            return matchesSearch && matchesTags;
        });
        
        renderTable(filteredResources);
    }

    // Render table with filtered resources
    function renderTable(resources) {
        const tbody = document.getElementById('resourceTableBody');
        tbody.innerHTML = '';

        resources.forEach(resource => {
            const row = document.createElement('tr');
            
            // Resource name with link
            const nameCell = document.createElement('td');
            const link = document.createElement('a');
            link.href = resource.Website;
            link.target = '_blank';
            link.textContent = resource.Resource;
            nameCell.appendChild(link);
            
            // Hotline
            const hotlineCell = document.createElement('td');
            hotlineCell.textContent = resource['Hotline Phone/Text'];
            
            // Description
            const descCell = document.createElement('td');
            descCell.textContent = resource['Resource Description'];
            
            // Keywords as clickable tags
            const keywordsCell = document.createElement('td');
            if (resource.Keywords) {
                resource.Keywords.split(',').forEach(tag => {
                    const tagSpan = document.createElement('button');
                    tagSpan.className = `tag ${selectedTags.has(tag.trim()) ? 'selected' : ''}`;
                    tagSpan.textContent = tag.trim();
                    tagSpan.onclick = () => toggleTag(tag.trim(), tagSpan);
                    keywordsCell.appendChild(tagSpan);
                });
            }

            row.appendChild(nameCell);
            row.appendChild(hotlineCell);
            row.appendChild(descCell);
            row.appendChild(keywordsCell);
            tbody.appendChild(row);
        });
    }

    // Reset filters
    window.resetFilters = function() {
        document.getElementById('resourceSearch').value = '';
        selectedTags.clear();
        document.querySelectorAll('.tag').forEach(tag => {
            tag.classList.remove('selected');
        });
        filterResources();
    };

    // Search input handler
    document.getElementById('resourceSearch').addEventListener('input', filterResources);
    
    // Search button handler
    document.querySelector('.search-button').addEventListener('click', filterResources);

    // Initialise
    loadResources();
});