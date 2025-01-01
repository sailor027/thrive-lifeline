jQuery(document).ready(function($) {
    // console.log('Custom plugin script loaded.')
});

//==================================================================================================

document.addEventListener('DOMContentLoaded', function() {
    let resources = [];
    let selectedTags = new Set();
    
    // Ensure dbPluginData is available
    if (typeof dbPluginData === 'undefined') {
        console.error('dbPluginData not found. Please check WordPress plugin configuration.');
        return;
    }

    // Initialize search functionality
    const searchInput = document.getElementById('resourceSearch');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(filterResources, 300));
    }

    // Load resources via AJAX
    async function loadResources() {
        try {
            const formData = new FormData();
            formData.append('action', 'get_resources');
            formData.append('nonce', dbPluginData.nonce);

            const response = await fetch(dbPluginData.ajaxurl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            if (data.success && Array.isArray(data.data)) {
                resources = data.data;
                console.log('Resources loaded:', resources.length);
                initializeTags();
                filterResources();
            } else {
                throw new Error('Invalid response format');
            }
        } catch (error) {
            console.error('Error loading resources:', error);
            document.getElementById('resourceTableBody').innerHTML = 
                '<tr><td colspan="3">Error loading resources. Please try refreshing the page.</td></tr>';
        }
    }

    // Initialize tags from loaded resources
    function initializeTags() {
        const tagsContainer = document.getElementById('filterTags');
        if (!tagsContainer) return;

        const uniqueTags = new Set();
        resources.forEach(resource => {
            if (resource.Keywords) {
                resource.Keywords.split(',').forEach(tag => {
                    uniqueTags.add(tag.trim());
                });
            }
        });

        const sortedTags = Array.from(uniqueTags).sort();
        tagsContainer.innerHTML = sortedTags.map(tag => 
            `<button type="button" class="tag" data-tag="${tag}">${tag}</button>`
        ).join('');

        // Add click handlers to tags
        document.querySelectorAll('.tag').forEach(tag => {
            tag.addEventListener('click', function() {
                const tagValue = this.getAttribute('data-tag');
                if (selectedTags.has(tagValue)) {
                    selectedTags.delete(tagValue);
                    this.classList.remove('selected');
                } else {
                    selectedTags.add(tagValue);
                    this.classList.add('selected');
                }
                filterResources();
            });
        });
    }

    // Debounce function
    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this;
            const args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), wait);
        };
    }

    // Filter resources based on search and tags
    function filterResources() {
        const searchValue = searchInput?.value.toLowerCase() || '';
        const rows = document.querySelectorAll('#resourceTableBody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const tags = Array.from(row.querySelectorAll('.table-tag'))
                .map(tag => tag.textContent.trim());
            
            const matchesSearch = !searchValue || text.includes(searchValue);
            const matchesTags = selectedTags.size === 0 || 
                Array.from(selectedTags).every(tag => tags.includes(tag));
            
            row.style.display = matchesSearch && matchesTags ? '' : 'none';

            // Update table tags to match selected state
            row.querySelectorAll('.table-tag').forEach(tableTag => {
                const tagValue = tableTag.textContent.trim();
                tableTag.classList.toggle('selected', selectedTags.has(tagValue));
            });
        });

        updateResultCount();
    }

    // Update result count display
    function updateResultCount() {
        const visibleRows = document.querySelectorAll('#resourceTableBody tr[style=""]').length;
        const totalRows = document.querySelectorAll('#resourceTableBody tr').length;
        const countDisplay = document.querySelector('.result-count');
        
        if (countDisplay) {
            countDisplay.textContent = `Showing ${visibleRows} of ${totalRows} resources`;
        }
    }

    // Reset filters
    window.resetFilters = function() {
        if (searchInput) searchInput.value = '';
        selectedTags.clear();
        document.querySelectorAll('.tag').forEach(tag => {
            tag.classList.remove('selected');
        });
        filterResources();
    };

    // Page navigation
    window.changePage = function(page) {
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('pg', page);
        window.location.search = urlParams.toString();
    };

    // Toggle tag from table
    window.toggleTagFilter = function(tag) {
        const tagButton = document.querySelector(`.tag[data-tag="${tag}"]`);
        if (tagButton) {
            tagButton.click();
        }
    };

    // Initialize the resources
    loadResources();
});