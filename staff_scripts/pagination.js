class TablePaginator {
    constructor(tableId, rowsPerPage = 5) {
        this.tableId = tableId;
        this.tableBody = document.getElementById(tableId);
        this.rowsPerPage = rowsPerPage;
        this.currentPage = 1;
        this.rows = Array.from(this.tableBody.getElementsByTagName('tr'));
        this.filteredRows = [...this.rows]; // Initially all rows are included
        this.totalPages = Math.ceil(this.rows.length / this.rowsPerPage);
        
        // Create pagination container if it doesn't exist
        if (!document.getElementById(`${tableId}_pagination`)) {
            const table = this.tableBody.closest('table');
            const paginationContainer = document.createElement('div');
            paginationContainer.id = `${tableId}_pagination`;
            paginationContainer.className = 'pagination-container d-flex justify-content-between align-items-center mt-3';
            table.parentNode.insertBefore(paginationContainer, table.nextSibling);
        }
        
        this.paginationContainer = document.getElementById(`${tableId}_pagination`);
    }
    
    updateFilteredRows(filteredRows) {
        this.filteredRows = filteredRows;
        this.totalPages = Math.ceil(this.filteredRows.length / this.rowsPerPage);
        this.currentPage = 1;
        this.update();
    }
    
    goToPage(page) {
        if (page < 1 || page > this.totalPages) return;
        this.currentPage = page;
        this.update();
    }
    
    update() {
        // Hide all rows
        this.rows.forEach(row => {
            row.style.display = 'none';
        });
        
        // Show only rows for current page that are in filtered rows
        const startIndex = (this.currentPage - 1) * this.rowsPerPage;
        const endIndex = startIndex + this.rowsPerPage;
        
        for (let i = startIndex; i < endIndex && i < this.filteredRows.length; i++) {
            this.filteredRows[i].style.display = '';
        }
        
        this.updatePaginationUI();
    }
    
    updatePaginationUI() {
        // Clear existing pagination UI
        this.paginationContainer.innerHTML = '';
        
        if (this.filteredRows.length === 0) {
            const noResultsDiv = document.createElement('div');
            noResultsDiv.className = 'text-center text-white w-100';
            noResultsDiv.textContent = 'No results found';
            this.paginationContainer.appendChild(noResultsDiv);
            return;
        }
        
        // Create pagination info
        const paginationInfo = document.createElement('div');
        paginationInfo.className = 'pagination-info text-white';
        paginationInfo.textContent = `Page ${this.currentPage} of ${this.totalPages} (${this.filteredRows.length} items)`;
        
        // Create pagination controls
        const paginationControls = document.createElement('div');
        paginationControls.className = 'pagination-controls';
        
        const btnFirst = this.createPaginationButton('«', () => this.goToPage(1), this.currentPage === 1);
        const btnPrev = this.createPaginationButton('‹', () => this.goToPage(this.currentPage - 1), this.currentPage === 1);
        
        const pageButtons = document.createElement('div');
        pageButtons.className = 'd-inline-block mx-2';
        
        // Determine which page buttons to show
        const maxButtonsToShow = 5;
        let startPage = Math.max(1, this.currentPage - Math.floor(maxButtonsToShow / 2));
        let endPage = Math.min(this.totalPages, startPage + maxButtonsToShow - 1);
        
        if (endPage - startPage + 1 < maxButtonsToShow && startPage > 1) {
            startPage = Math.max(1, endPage - maxButtonsToShow + 1);
        }
        
        for (let i = startPage; i <= endPage; i++) {
            const btnPage = this.createPaginationButton(i.toString(), () => this.goToPage(i), false, i === this.currentPage);
            pageButtons.appendChild(btnPage);
        }
        
        const btnNext = this.createPaginationButton('›', () => this.goToPage(this.currentPage + 1), this.currentPage === this.totalPages);
        const btnLast = this.createPaginationButton('»', () => this.goToPage(this.totalPages), this.currentPage === this.totalPages);
        
        // Assemble pagination controls
        paginationControls.appendChild(btnFirst);
        paginationControls.appendChild(btnPrev);
        paginationControls.appendChild(pageButtons);
        paginationControls.appendChild(btnNext);
        paginationControls.appendChild(btnLast);
        
        // Add to container
        this.paginationContainer.appendChild(paginationInfo);
        this.paginationContainer.appendChild(paginationControls);
    }
    
    createPaginationButton(text, clickHandler, isDisabled = false, isActive = false) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `btn btn-sm ${isActive ? 'btn-primary' : 'btn-secondary'} mx-1`;
        button.textContent = text;
        button.disabled = isDisabled;
        button.addEventListener('click', clickHandler);
        return button;
    }
}

// Initialize paginators when document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Add CSS for pagination
    const style = document.createElement('style');
    style.textContent = `
        .pagination-container {
            background-color: rgba(33, 37, 41, 0.8);
            padding: 10px;
            border-radius: 0 0 12px 12px;
            margin-top: -3px !important;
        }
        .pagination-info {
            font-size: 0.9rem;
        }
        @media (max-width: 768px) {
            .pagination-container {
                flex-direction: column;
                gap: 10px;
            }
        }
    `;
    document.head.appendChild(style);
    
    // Initialize paginators for each table
    window.clientsPaginator = new TablePaginator('clientsTableBody');
    window.lockersPaginator = new TablePaginator('lockersTableBody');
    window.rentalsPaginator = new TablePaginator('rentalsTableBody');
    
    // Initial update for all tables
    if (window.clientsPaginator) window.clientsPaginator.update();
    if (window.lockersPaginator) window.lockersPaginator.update();
    if (window.rentalsPaginator) window.rentalsPaginator.update();
});