// assets/js/script.js
jQuery(document).ready(function($) {
    const container = $('.content-analyzer-container');
    const table = $('.content-analyzer-table');
    const tbody = table.find('tbody');
    const pagination = $('.pagination');
    
    let currentPage = 1;
    let currentSort = {
        column: 'title',
        direction: 'asc'
    };
    let debounceTimeout = null;

    // Update sort indicators
    function updateSortIndicators() {
        table.find('th[data-sort]').each(function() {
            const column = $(this).data('sort');
            $(this).removeClass('sort-asc sort-desc');
            
            if (column === currentSort.column) {
                $(this).addClass(`sort-${currentSort.direction}`);
            }
        });
    }

    function debounce(func, wait) {
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(debounceTimeout);
                func(...args);
            };
            clearTimeout(debounceTimeout);
            debounceTimeout = setTimeout(later, wait);
        };
    }

    function loadData() {
        const keyword = $('#keyword-input').val();
        
        if (!keyword) {
            tbody.html('<tr><td colspan="3">Please enter a keyword</td></tr>');
            return;
        }

        $.ajax({
            url: contentAnalyzerData.restUrl + '/analyze',
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', contentAnalyzerData.nonce);
                tbody.html('<tr><td colspan="3">Loading...</td></tr>');
            },
            data: {
                keyword: keyword,
                page: currentPage,
                per_page: 10
            },
            dataType: 'json',
            success: function(response) {
                if (response.error) {
                    tbody.html(`<tr><td colspan="3">Error: ${response.message}</td></tr>`);
                    return;
                }
                displayData(response);
                updatePagination(response.pages);
                updateSortIndicators(); // Update sort indicators after loading data
            },
            error: function(xhr, status, error) {
                console.error('Response:', xhr.responseText);
                console.error('Status:', status);
                console.error('Error:', error);
                let errorMessage = 'Error loading data';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMessage = response.message;
                    }
                } catch (e) {
                    errorMessage = 'Invalid response from server';
                }
                tbody.html(`<tr><td colspan="3">${errorMessage}</td></tr>`);
            }
        });
    }

    function displayData(data) {
        if (!data.posts || !data.posts.length) {
            tbody.html('<tr><td colspan="3">No posts found</td></tr>');
            return;
        }

        let html = '';
        
        data.posts.sort((a, b) => {
            let compareValue;
            
            switch(currentSort.column) {
                case 'title':
                    compareValue = a.title.localeCompare(b.title);
                    break;
                case 'word_count':
                    compareValue = a.word_count - b.word_count;
                    break;
                case 'density':
                    compareValue = a.keyword_density - b.keyword_density;
                    break;
                default:
                    compareValue = 0;
            }
            
            return currentSort.direction === 'asc' ? compareValue : -compareValue;
        });

        data.posts.forEach(post => {
            html += `
                <tr>
                    <td><a href="${post.url}">${post.title}</a></td>
                    <td>${post.word_count}</td>
                    <td>${post.keyword_density}%</td>
                </tr>
            `;
        });

        tbody.html(html);
    }

    function updatePagination(totalPages) {
        let html = '';
        
        if (totalPages > 1) {
            if (currentPage > 1) {
                html += '<button class="prev-page">Previous</button>';
            }
            
            html += `<span>Page ${currentPage} of ${totalPages}</span>`;
            
            if (currentPage < totalPages) {
                html += '<button class="next-page">Next</button>';
            }
        }
        
        pagination.html(html);
    }

    $('#keyword-input').on('input', debounce(loadData, 500));

    table.on('click', 'th[data-sort]', function() {
        const column = $(this).data('sort');
        
        if (currentSort.column === column) {
            currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
        } else {
            currentSort.column = column;
            currentSort.direction = 'asc';
        }
        
        updateSortIndicators();
        loadData();
    });

    updateSortIndicators();

    pagination.on('click', '.prev-page', function() {
        if (currentPage > 1) {
            currentPage--;
            loadData();
        }
    });

    pagination.on('click', '.next-page', function() {
        currentPage++;
        loadData();
    });

    const initialKeyword = $('#keyword-input').val();
    if (initialKeyword) {
        loadData();
    } else {
        tbody.html('<tr><td colspan="3">Please enter a keyword</td></tr>');
    }
});