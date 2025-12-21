/**
 * Enhanced Table System with Sorting, Filtering, Search, Pagination
 * Location: assets/js/table-enhancer.js
 */

class TableEnhancer {
  constructor(tableId, options = {}) {
    this.table = document.getElementById(tableId);
    if (!this.table) return;
    
    this.tbody = this.table.querySelector('tbody');
    this.thead = this.table.querySelector('thead');
    
    this.options = {
      sortable: options.sortable !== false,
      searchable: options.searchable !== false,
      filterable: options.filterable !== false,
      paginate: options.paginate !== false,
      itemsPerPage: options.itemsPerPage || 10,
      exportable: options.exportable !== false,
      ...options
    };
    
    this.currentPage = 1;
    this.sortColumn = null;
    this.sortDirection = 'asc';
    this.searchTerm = '';
    this.filters = {};
    this.allRows = [];
    this.filteredRows = [];
    
    this.init();
  }
  
  init() {
    // Store all rows
    this.allRows = Array.from(this.tbody.querySelectorAll('tr'));
    this.filteredRows = [...this.allRows];
    
    if (this.options.sortable) this.initSorting();
    if (this.options.searchable) this.initSearch();
    if (this.options.filterable) this.initFilters();
    if (this.options.paginate) this.initPagination();
    if (this.options.exportable) this.initExport();
    
    this.render();
  }
  
  initSorting() {
    const headers = this.thead.querySelectorAll('th');
    headers.forEach((header, index) => {
      if (header.classList.contains('no-sort')) return;
      
      header.style.cursor = 'pointer';
      header.style.userSelect = 'none';
      header.classList.add('sortable');
      
      // Add sort icon
      const icon = document.createElement('span');
      icon.className = 'sort-icon';
      icon.innerHTML = '⇅';
      header.appendChild(icon);
      
      header.addEventListener('click', () => {
        this.sort(index);
      });
    });
  }
  
  sort(columnIndex) {
    const headers = this.thead.querySelectorAll('th');
    const header = headers[columnIndex];
    
    // Toggle direction if same column
    if (this.sortColumn === columnIndex) {
      this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
      this.sortColumn = columnIndex;
      this.sortDirection = 'asc';
    }
    
    // Update UI
    headers.forEach(h => {
      h.classList.remove('sort-asc', 'sort-desc');
      const icon = h.querySelector('.sort-icon');
      if (icon) icon.innerHTML = '⇅';
    });
    
    header.classList.add(`sort-${this.sortDirection}`);
    const icon = header.querySelector('.sort-icon');
    if (icon) icon.innerHTML = this.sortDirection === 'asc' ? '↑' : '↓';
    
    // Sort data
    this.filteredRows.sort((a, b) => {
      const aCell = a.cells[columnIndex];
      const bCell = b.cells[columnIndex];
      
      if (!aCell || !bCell) return 0;
      
      let aVal = aCell.textContent.trim();
      let bVal = bCell.textContent.trim();
      
      // Try to parse as number
      const aNum = parseFloat(aVal);
      const bNum = parseFloat(bVal);
      
      if (!isNaN(aNum) && !isNaN(bNum)) {
        return this.sortDirection === 'asc' ? aNum - bNum : bNum - aNum;
      }
      
      // Try to parse as date
      const aDate = new Date(aVal);
      const bDate = new Date(bVal);
      
      if (aDate.toString() !== 'Invalid Date' && bDate.toString() !== 'Invalid Date') {
        return this.sortDirection === 'asc' ? aDate - bDate : bDate - aDate;
      }
      
      // String comparison
      const comparison = aVal.localeCompare(bVal);
      return this.sortDirection === 'asc' ? comparison : -comparison;
    });
    
    this.currentPage = 1;
    this.render();
  }
  
  initSearch() {
    const searchContainer = document.createElement('div');
    searchContainer.className = 'table-search';
    searchContainer.innerHTML = `
      <input 
        type="text" 
        class="search-input" 
        placeholder="🔍 Search table..."
        aria-label="Search table"
      >
      <span class="search-count"></span>
    `;
    
    this.table.parentNode.insertBefore(searchContainer, this.table);
    
    const searchInput = searchContainer.querySelector('.search-input');
    searchInput.addEventListener('input', (e) => {
      this.search(e.target.value);
    });
  }
  
  search(term) {
    this.searchTerm = term.toLowerCase();
    this.applyFilters();
  }
  
  initFilters() {
    // Column filters will be added dynamically based on data
    const filterContainer = document.createElement('div');
    filterContainer.className = 'table-filters';
    this.table.parentNode.insertBefore(filterContainer, this.table);
  }
  
  applyFilters() {
    this.filteredRows = this.allRows.filter(row => {
      const text = row.textContent.toLowerCase();
      
      // Search filter
      if (this.searchTerm && !text.includes(this.searchTerm)) {
        return false;
      }
      
      // Column filters
      for (let [column, value] of Object.entries(this.filters)) {
        if (!value) continue;
        const cell = row.cells[column];
        if (!cell || !cell.textContent.toLowerCase().includes(value.toLowerCase())) {
          return false;
        }
      }
      
      return true;
    });
    
    // Highlight search terms
    if (this.searchTerm) {
      this.highlightSearch();
    } else {
      this.removeHighlight();
    }
    
    this.currentPage = 1;
    this.render();
    this.updateSearchCount();
  }
  
  highlightSearch() {
    this.filteredRows.forEach(row => {
      Array.from(row.cells).forEach(cell => {
        const text = cell.textContent;
        if (this.searchTerm && text.toLowerCase().includes(this.searchTerm)) {
          const regex = new RegExp(`(${this.searchTerm})`, 'gi');
          const highlighted = text.replace(regex, '<mark>$1</mark>');
          if (cell.children.length === 0) {
            cell.innerHTML = highlighted;
          }
        }
      });
    });
  }
  
  removeHighlight() {
    this.allRows.forEach(row => {
      Array.from(row.cells).forEach(cell => {
        const marks = cell.querySelectorAll('mark');
        marks.forEach(mark => {
          mark.outerHTML = mark.textContent;
        });
      });
    });
  }
  
  updateSearchCount() {
    const countEl = document.querySelector('.search-count');
    if (countEl) {
      const total = this.allRows.length;
      const filtered = this.filteredRows.length;
      if (this.searchTerm) {
        countEl.textContent = `${filtered} of ${total} results`;
      } else {
        countEl.textContent = `${total} total`;
      }
    }
  }
  
  initPagination() {
    const paginationContainer = document.createElement('div');
    paginationContainer.className = 'table-pagination';
    this.table.parentNode.appendChild(paginationContainer);
  }
  
  renderPagination() {
    const container = document.querySelector('.table-pagination');
    if (!container) return;
    
    const totalPages = Math.ceil(this.filteredRows.length / this.options.itemsPerPage);
    
    if (totalPages <= 1) {
      container.innerHTML = '';
      return;
    }
    
    const start = (this.currentPage - 1) * this.options.itemsPerPage + 1;
    const end = Math.min(this.currentPage * this.options.itemsPerPage, this.filteredRows.length);
    
    container.innerHTML = `
      <div class="pagination-info">
        Showing ${start}-${end} of ${this.filteredRows.length}
      </div>
      <div class="pagination-controls">
        <button class="btn-pagination" ${this.currentPage === 1 ? 'disabled' : ''} data-page="first">
          ⟪
        </button>
        <button class="btn-pagination" ${this.currentPage === 1 ? 'disabled' : ''} data-page="prev">
          ‹
        </button>
        <span class="page-numbers">
          ${this.renderPageNumbers(totalPages)}
        </span>
        <button class="btn-pagination" ${this.currentPage === totalPages ? 'disabled' : ''} data-page="next">
          ›
        </button>
        <button class="btn-pagination" ${this.currentPage === totalPages ? 'disabled' : ''} data-page="last">
          ⟫
        </button>
      </div>
      <div class="pagination-size">
        <select class="items-per-page">
          <option value="10" ${this.options.itemsPerPage === 10 ? 'selected' : ''}>10</option>
          <option value="25" ${this.options.itemsPerPage === 25 ? 'selected' : ''}>25</option>
          <option value="50" ${this.options.itemsPerPage === 50 ? 'selected' : ''}>50</option>
          <option value="100" ${this.options.itemsPerPage === 100 ? 'selected' : ''}>100</option>
        </select>
        <span>per page</span>
      </div>
    `;
    
    // Event listeners
    container.querySelectorAll('.btn-pagination').forEach(btn => {
      btn.addEventListener('click', () => {
        const page = btn.dataset.page;
        if (page === 'first') this.currentPage = 1;
        else if (page === 'prev') this.currentPage--;
        else if (page === 'next') this.currentPage++;
        else if (page === 'last') this.currentPage = totalPages;
        else this.currentPage = parseInt(page);
        
        this.render();
      });
    });
    
    container.querySelector('.items-per-page').addEventListener('change', (e) => {
      this.options.itemsPerPage = parseInt(e.target.value);
      this.currentPage = 1;
      this.render();
    });
  }
  
  renderPageNumbers(totalPages) {
    const pages = [];
    const maxVisible = 5;
    
    let start = Math.max(1, this.currentPage - Math.floor(maxVisible / 2));
    let end = Math.min(totalPages, start + maxVisible - 1);
    
    if (end - start < maxVisible - 1) {
      start = Math.max(1, end - maxVisible + 1);
    }
    
    for (let i = start; i <= end; i++) {
      const active = i === this.currentPage ? 'active' : '';
      pages.push(`
        <button class="btn-pagination ${active}" data-page="${i}">
          ${i}
        </button>
      `);
    }
    
    return pages.join('');
  }
  
  initExport() {
    const exportBtn = document.createElement('button');
    exportBtn.className = 'btn btn-export';
    exportBtn.innerHTML = '📥 Export';
    exportBtn.addEventListener('click', () => this.showExportMenu());
    
    const tools = this.table.closest('.content').querySelector('.table-tools');
    if (tools) {
      tools.appendChild(exportBtn);
    }
  }
  
  showExportMenu() {
    const menu = document.createElement('div');
    menu.className = 'export-menu';
    menu.innerHTML = `
      <button class="export-option" data-format="csv">📄 Export as CSV</button>
      <button class="export-option" data-format="excel">📊 Export as Excel</button>
      <button class="export-option" data-format="pdf">📑 Export as PDF</button>
      <button class="export-option" data-format="print">🖨️ Print</button>
    `;
    
    document.body.appendChild(menu);
    
    menu.querySelectorAll('.export-option').forEach(btn => {
      btn.addEventListener('click', () => {
        this.export(btn.dataset.format);
        menu.remove();
      });
    });
    
    // Close on outside click
    setTimeout(() => {
      document.addEventListener('click', function closeMenu(e) {
        if (!menu.contains(e.target)) {
          menu.remove();
          document.removeEventListener('click', closeMenu);
        }
      });
    }, 0);
  }
  
  export(format) {
    if (format === 'csv') {
      this.exportCSV();
    } else if (format === 'excel') {
      this.exportExcel();
    } else if (format === 'pdf') {
      this.exportPDF();
    } else if (format === 'print') {
      this.print();
    }
  }
  
  exportCSV() {
    const headers = Array.from(this.thead.querySelectorAll('th'))
      .map(th => th.textContent.replace('⇅', '').replace('↑', '').replace('↓', '').trim());
    
    const rows = this.filteredRows.map(row => 
      Array.from(row.cells).map(cell => {
        const text = cell.textContent.replace(/"/g, '""');
        return `"${text}"`;
      }).join(',')
    );
    
    const csv = [headers.join(','), ...rows].join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    
    const link = document.createElement('a');
    link.href = url;
    link.download = `table_export_${Date.now()}.csv`;
    link.click();
    
    URL.revokeObjectURL(url);
    
    window.toast?.success('CSV exported successfully');
  }
  
  exportExcel() {
    // Simple Excel export (CSV with .xls extension works in most cases)
    const headers = Array.from(this.thead.querySelectorAll('th'))
      .map(th => th.textContent.replace('⇅', '').replace('↑', '').replace('↓', '').trim());
    
    const rows = this.filteredRows.map(row => 
      Array.from(row.cells).map(cell => cell.textContent).join('\t')
    );
    
    const excel = [headers.join('\t'), ...rows].join('\n');
    const blob = new Blob([excel], { type: 'application/vnd.ms-excel' });
    const url = URL.createObjectURL(blob);
    
    const link = document.createElement('a');
    link.href = url;
    link.download = `table_export_${Date.now()}.xls`;
    link.click();
    
    URL.revokeObjectURL(url);
    
    window.toast?.success('Excel exported successfully');
  }
  
  exportPDF() {
    window.toast?.info('PDF export requires a print dialog. Use Print option instead.');
    this.print();
  }
  
  print() {
    window.print();
  }
  
  render() {
    // Hide all rows
    this.allRows.forEach(row => row.style.display = 'none');
    
    // Show paginated filtered rows
    const start = (this.currentPage - 1) * this.options.itemsPerPage;
    const end = start + this.options.itemsPerPage;
    
    this.filteredRows.slice(start, end).forEach(row => {
      row.style.display = '';
    });
    
    if (this.options.paginate) {
      this.renderPagination();
    }
  }
}

// Auto-initialize tables with data-enhance attribute
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('table[data-enhance]').forEach(table => {
    new TableEnhancer(table.id);
  });
});

// Global function for manual initialization
window.enhanceTable = (tableId, options) => {
  return new TableEnhancer(tableId, options);
};
