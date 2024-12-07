document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('navbar-search');
    const searchForm = document.getElementById('navbar-search-form');
    const autocompleteBox = document.getElementById('search-autocomplete');
    let debounceTimer;
    let currentFocus = -1;
    let suggestions = [];
    
    // Initialize search history from localStorage
    const MAX_HISTORY_ITEMS = 5;
    let searchHistory = JSON.parse(localStorage.getItem('searchHistory') || '[]');

    // Create loading spinner
    const spinner = document.createElement('div');
    spinner.className = 'absolute right-10 top-3 hidden';
    spinner.innerHTML = '<i class="fas fa-spinner fa-spin text-gray-400"></i>';
    searchForm.appendChild(spinner);

    // Create clear button
    const clearButton = document.createElement('button');
    clearButton.type = 'button';
    clearButton.className = 'absolute right-12 top-2.5 hidden text-gray-400 hover:text-gray-600';
    clearButton.innerHTML = '<i class="fas fa-times"></i>';
    searchForm.appendChild(clearButton);

    // Handle clear button click
    clearButton.addEventListener('click', () => {
        searchInput.value = '';
        clearButton.classList.add('hidden');
        autocompleteBox.classList.add('hidden');
        searchInput.focus();
    });

    // Show/hide clear button based on input
    searchInput.addEventListener('input', function() {
        clearButton.classList.toggle('hidden', !this.value);
    });

    // Function to add to search history
    function addToSearchHistory(query) {
        searchHistory = searchHistory.filter(item => item.query !== query);
        searchHistory.unshift({ query, timestamp: Date.now() });
        if (searchHistory.length > MAX_HISTORY_ITEMS) {
            searchHistory.pop();
        }
        localStorage.setItem('searchHistory', JSON.stringify(searchHistory));
    }

    // Function to show search history
    function showSearchHistory() {
        if (searchHistory.length > 0 && !searchInput.value) {
            autocompleteBox.innerHTML = '';
            const historyHeader = document.createElement('div');
            historyHeader.className = 'p-2 bg-gray-50 text-sm text-gray-600 flex justify-between items-center';
            historyHeader.innerHTML = `
                <span>Recent Searches</span>
                <button class="text-xs text-blue-600 hover:text-blue-800" id="clear-history">Clear</button>
            `;
            autocompleteBox.appendChild(historyHeader);

            searchHistory.forEach(item => {
                const div = document.createElement('div');
                div.className = 'p-3 hover:bg-gray-100 cursor-pointer flex items-center';
                div.innerHTML = `
                    <i class="fas fa-history text-gray-400 mr-3"></i>
                    <span class="flex-1">${item.query}</span>
                    <span class="text-xs text-gray-400">${getRelativeTime(item.timestamp)}</span>
                `;
                div.addEventListener('click', () => {
                    searchInput.value = item.query;
                    searchInput.dispatchEvent(new Event('input'));
                });
                autocompleteBox.appendChild(div);
            });
            autocompleteBox.classList.remove('hidden');

            // Handle clear history
            document.getElementById('clear-history').addEventListener('click', (e) => {
                e.stopPropagation();
                searchHistory = [];
                localStorage.removeItem('searchHistory');
                autocompleteBox.classList.add('hidden');
            });
        }
    }

    // Function to get relative time
    function getRelativeTime(timestamp) {
        const diff = Date.now() - timestamp;
        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(minutes / 60);
        const days = Math.floor(hours / 24);

        if (days > 0) return `${days}d ago`;
        if (hours > 0) return `${hours}h ago`;
        if (minutes > 0) return `${minutes}m ago`;
        return 'just now';
    }

    // Function to get status badge class
    function getStatusBadgeClass(status) {
        switch (status) {
            case 'active':
                return 'badge bg-success';
            case 'inactive':
                return 'badge bg-danger';
            case 'maintenance':
                return 'badge bg-warning';
            default:
                return 'badge bg-secondary';
        }
    }

    // Function to create suggestion element
    function createSuggestionElement(suggestion) {
        const div = document.createElement('div');
        div.className = 'suggestion-item d-flex align-items-center justify-content-between';
        div.setAttribute('data-url', suggestion.url);

        const leftContent = document.createElement('div');
        leftContent.className = 'd-flex flex-column';

        const nameSpan = document.createElement('span');
        nameSpan.className = 'suggestion-name';
        nameSpan.textContent = suggestion.name;
        leftContent.appendChild(nameSpan);

        if (suggestion.type === 'equipment' && suggestion.category) {
            const categorySpan = document.createElement('small');
            categorySpan.className = 'text-muted';
            categorySpan.textContent = suggestion.category;
            leftContent.appendChild(categorySpan);
        } else if (suggestion.type === 'category') {
            if (suggestion.parent) {
                const parentSpan = document.createElement('small');
                parentSpan.className = 'text-muted';
                parentSpan.textContent = `Parent: ${suggestion.parent}`;
                leftContent.appendChild(parentSpan);
            }
            if (suggestion.equipmentCount !== undefined) {
                const countSpan = document.createElement('small');
                countSpan.className = 'text-muted';
                countSpan.textContent = `${suggestion.equipmentCount} equipment${suggestion.equipmentCount !== 1 ? 's' : ''}`;
                leftContent.appendChild(countSpan);
            }
        }

        div.appendChild(leftContent);

        const rightContent = document.createElement('div');
        rightContent.className = 'd-flex align-items-center';

        if (suggestion.type === 'equipment' && suggestion.status) {
            const statusBadge = document.createElement('span');
            statusBadge.className = getStatusBadgeClass(suggestion.status);
            statusBadge.textContent = suggestion.status;
            rightContent.appendChild(statusBadge);
        }

        const typeSpan = document.createElement('span');
        typeSpan.className = 'badge bg-info ms-2';
        typeSpan.textContent = suggestion.type;
        rightContent.appendChild(typeSpan);

        div.appendChild(rightContent);

        return div;
    }

    // Handle input changes for autocomplete
    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const query = this.value.trim();

        if (query.length >= 2) {
            spinner.classList.remove('hidden');
            debounceTimer = setTimeout(() => {
                fetch(`/search/suggestions?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        suggestions = data;
                        autocompleteBox.innerHTML = '';
                        
                        if (suggestions.length > 0) {
                            // Group suggestions by type
                            const grouped = suggestions.reduce((acc, item) => {
                                acc[item.type] = acc[item.type] || [];
                                acc[item.type].push(item);
                                return acc;
                            }, {});

                            // Render grouped suggestions
                            Object.entries(grouped).forEach(([type, items]) => {
                                const header = document.createElement('div');
                                header.className = 'p-2 bg-gray-50 text-xs font-medium text-gray-600 uppercase';
                                header.textContent = type;
                                autocompleteBox.appendChild(header);

                                items.forEach(item => {
                                    const div = createSuggestionElement(item);
                                    div.addEventListener('click', () => {
                                        addToSearchHistory(query);
                                        window.location.href = item.url;
                                    });
                                    autocompleteBox.appendChild(div);
                                });
                            });
                            autocompleteBox.classList.remove('hidden');
                        } else {
                            const noResults = document.createElement('div');
                            noResults.className = 'p-3 text-sm text-gray-500 text-center';
                            noResults.textContent = 'No results found';
                            autocompleteBox.appendChild(noResults);
                            autocompleteBox.classList.remove('hidden');
                        }
                        spinner.classList.add('hidden');
                    })
                    .catch(error => {
                        console.error('Search error:', error);
                        spinner.classList.add('hidden');
                    });
            }, 300);
        } else {
            showSearchHistory();
        }
    });

    // Handle form submission
    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const query = searchInput.value.trim();
        if (query) {
            addToSearchHistory(query);
            window.location.href = `/search/results?q=${encodeURIComponent(query)}`;
        }
    });

    // Close autocomplete when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchForm.contains(e.target)) {
            autocompleteBox.classList.add('hidden');
        }
    });

    // Handle keyboard navigation
    searchForm.addEventListener('keydown', function(e) {
        const items = autocompleteBox.getElementsByClassName('suggestion-item');
        
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            
            if (e.key === 'ArrowDown') {
                currentFocus = currentFocus < items.length - 1 ? currentFocus + 1 : 0;
            } else {
                currentFocus = currentFocus > 0 ? currentFocus - 1 : items.length - 1;
            }

            Array.from(items).forEach((item, index) => {
                item.classList.toggle('bg-gray-100', index === currentFocus);
            });

            if (items[currentFocus]) {
                items[currentFocus].scrollIntoView({ block: 'nearest' });
            }
        } else if (e.key === 'Enter' && currentFocus > -1 && items[currentFocus]) {
            e.preventDefault();
            items[currentFocus].click();
        } else if (e.key === 'Escape') {
            autocompleteBox.classList.add('hidden');
            currentFocus = -1;
        }
    });

    // Show search history when focusing on empty input
    searchInput.addEventListener('focus', function() {
        if (!this.value) {
            showSearchHistory();
        }
    });
});
