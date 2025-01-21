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

    // Set up tag handlers
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

    // Toggle tag selection with UI update
    function toggleTag(tagValue) {
        if (selectedTags.has(tagValue)) {
            selectedTags.delete(tagValue);
            updateTagState(tagValue, false);
        } else {
            selectedTags.add(tagValue);
            updateTagState(tagValue, true);
        }
        submitSearch();
    }

    // Update all instances of a tag in the UI
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
                urlParams.append('tags', encodeURIComponent(tag));
            });
        }

        // Preserve current page if it exists
        const currentPage = new URLSearchParams(window.location.search).get('pg');
        if (currentPage) {
            urlParams.set('pg', currentPage);
        }
        
        // Navigate to new URL
        const newUrl = `${window.location.pathname}${urlParams.toString() ? '?' + urlParams.toString() : ''}`;
        window.location.href = newUrl;
    }

    // Initialize tag handlers
    initializeTagHandlers();
});