document.addEventListener('DOMContentLoaded', function() {
    let resources = [];
    let selectedTags = new Set();
    
    // Initialize selectedTags from URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const urlTags = urlParams.getAll('tags');
    if (urlTags.length > 0) {
        urlTags.forEach(tag => selectedTags.add(tag));
    }
    
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
                await initializeTags();
                
                // Initialize search from URL if present
                const searchQuery = urlParams.get('kw');
                if (searchQuery && searchInput) {
                    searchInput.value = searchQuery;
                }
                
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
        tagsContainer.innerHTML = sortedTags.map(tag => {
            const isSelected = selectedTags.has(tag) ? 'selected' : '';
            return `<button type="button" class="tag ${isSelected}" data-tag="${tag}">${tag}</button>`;
        }).join('');

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
        let visibleCount = 0;
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const tags = Array.from(row.querySelectorAll('.table-tag'))
                .map(tag => tag.textContent.trim());
            
            const matchesSearch = !searchValue || text.includes(searchValue);
            const matchesTags = selectedTags.size === 0 || 
                Array.from(selectedTags).every(tag => tags.includes(tag));
            
            const visible = matchesSearch && matchesTags;
            row.style.display = visible ? '' : 'none';
            
            if (visible) {
                visibleCount++;
            }
        });
        
        // Update URL to reflect current state
        const urlParams = new URLSearchParams(window.location.search);
        
        // Update search parameter
        if (searchValue) {
            urlParams.set('kw', searchInput.value);
        } else {
            urlParams.delete('kw');
        }
        
        // Update tag parameters
        urlParams.delete('tags');
        if (selectedTags.size > 0) {
            Array.from(selectedTags).forEach(tag => {
                urlParams.append('tags', tag);
            });
        }
        
        // Reset to first page when filters change
        if (urlParams.has('pg')) {
            urlParams.set('pg', '1');
        }
        
        // Update URL without reload
        const newUrl = `${window.location.pathname}${urlParams.toString() ? '?' + urlParams.toString() : ''}`;
        window.history.pushState({}, '', newUrl);
        
        updateResultCount(visibleCount);
    }

    // Update the updateResultCount function to use the parameter
    function updateResultCount(visibleCount) {
        const totalRows = document.querySelectorAll('#resourceTableBody tr').length;
        const countDisplay = document.querySelector('.result-count');
        
        if (countDisplay) {
            countDisplay.textContent = `Showing ${visibleCount} of ${totalRows} resources`;
        }
    }

    // Reset filters
    window.resetFilters = function() {
        if (searchInput) searchInput.value = '';
        selectedTags.clear();
        document.querySelectorAll('.tag').forEach(tag => {
            tag.classList.remove('selected');
        });
        
        // Clear URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.delete('kw');
        urlParams.delete('tags');
        urlParams.delete('pg');
        const newUrl = window.location.pathname;
        window.history.pushState({}, '', newUrl);
        
        filterResources();
    };

    // Page navigation
    window.changePage = function(page) {
        // Preserve all current search parameters and tags
        const urlParams = new URLSearchParams(window.location.search);
        
        // Update page number
        urlParams.set('pg', page);
        
        // Preserve search query if it exists
        if (searchInput && searchInput.value) {
            urlParams.set('kw', searchInput.value);
        }
        
        // Preserve selected tags
        if (selectedTags.size > 0) {
            urlParams.delete('tags');
            Array.from(selectedTags).forEach(tag => {
                urlParams.append('tags', tag);
            });
        }
        
        // Update URL and reload
        const newUrl = `${window.location.pathname}?${urlParams.toString()}`;
        window.location.href = newUrl;
    };

    // Toggle tag from table
    window.toggleTagFilter = function(tag) {
        const tagButton = document.querySelector(`.tag[data-tag="${tag}"]`);
        if (tagButton) {
            tagButton.click();
        }
    };

    // Handle browser back/forward
    window.addEventListener('popstate', function() {
        // Reload the page to reflect the URL state
        window.location.reload();
    });

    // Initialize the resources
    loadResources();
});