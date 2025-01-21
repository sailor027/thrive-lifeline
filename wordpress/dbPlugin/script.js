document.addEventListener('DOMContentLoaded', function() {
    // Initialize state management
    let selectedTags = new Set();
    
    // Load initial state from URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const urlTags = urlParams.getAll('tags');
    if (urlTags.length > 0) {
        urlTags.forEach(tag => selectedTags.add(decodeURIComponent(tag)));
        // Ensure UI reflects initial state
        selectedTags.forEach(tag => {
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

    // Handle tag selection - for both filter area and table tags
    function initializeTagHandlers() {
        document.querySelectorAll('.tag, .table-tag').forEach(tag => {
            tag.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const tagValue = this.getAttribute('data-tag');
                toggleTag(tagValue);
            });
        });
    }

    // Toggle tag selection
    function toggleTag(tagValue) {
        const decodedTag = decodeURIComponent(tagValue);
        if (selectedTags.has(decodedTag)) {
            selectedTags.delete(decodedTag);
            updateTagState(decodedTag, false);
        } else {
            selectedTags.add(decodedTag);
            updateTagState(decodedTag, true);
        }
        submitSearch();
    }

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
            selectedTags.forEach(tag => {
                urlParams.append('tags', encodeURIComponent(tag));
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

    // Toggle tag from table - direct implementation
    window.toggleTagFilter = function(tag) {
        toggleTag(tag); // tag is already encoded at this point
    };

    // Initialize tag handlers
    initializeTagHandlers();

    // Handle browser navigation
    window.addEventListener('popstate', function() {
        window.location.reload();
    });
});