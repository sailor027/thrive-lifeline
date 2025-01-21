document.addEventListener('DOMContentLoaded', function() {
    // Initialize state management
    let selectedTags = new Set();
    
    // Load initial state from URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const urlTags = urlParams.getAll('tags');
    if (urlTags.length > 0) {
        urlTags.forEach(tag => selectedTags.add(tag));
        // Ensure UI reflects initial state
        urlTags.forEach(tag => {
            document.querySelectorAll(`[data-tag="${tag}"]`).forEach(tagElement => {
                tagElement.classList.add('selected');
            });
        });
    }

    // Set up search form handling
    const searchInput = document.getElementById('resourceSearch');
    const searchForm = document.querySelector('.search-wrapper');
    
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitSearch();
        });
    }

    // Handle tag selection
    document.querySelectorAll('.tag, .table-tag').forEach(tag => {
        tag.addEventListener('click', function(e) {
            e.preventDefault();
            const tagValue = this.getAttribute('data-tag');
            if (selectedTags.has(tagValue)) {
                selectedTags.delete(tagValue);
                updateTagState(tagValue, false);
            } else {
                selectedTags.add(tagValue);
                updateTagState(tagValue, true);
            }
            submitSearch();
        });
    });

    // Update tag state in UI
    function updateTagState(tagValue, isSelected) {
        document.querySelectorAll(`[data-tag="${tagValue}"]`)
            .forEach(element => {
                element.classList.toggle('selected', isSelected);
            });
    }

    // Submit search with current filters
    function submitSearch() {
        const urlParams = new URLSearchParams();
        
        // Add search parameter if present
        const searchValue = searchInput?.value.trim() || '';
        if (searchValue) {
            urlParams.set('kw', searchValue);
        }
        
        // Add selected tags
        if (selectedTags.size > 0) {
            Array.from(selectedTags).forEach(tag => {
                urlParams.append('tags', tag);
            });
        }
        
        // Navigate to new URL
        const newUrl = `${window.location.pathname}${urlParams.toString() ? '?' + urlParams.toString() : ''}`;
        window.location.href = newUrl;
    }

    // Reset all filters
    window.resetFilters = function() {
        window.location.href = window.location.pathname;
    };

    // Handle pagination
    window.changePage = function(page) {
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('pg', page);
        window.location.href = `${window.location.pathname}?${urlParams.toString()}`;
    };

    // Toggle tag from table
    window.toggleTagFilter = function(tag) {
        const tagButton = document.querySelector(`.tag[data-tag="${tag}"]`);
        if (tagButton) {
            tagButton.click();
        }
    };

    // Handle browser navigation
    window.addEventListener('popstate', function() {
        window.location.reload();
    });
});