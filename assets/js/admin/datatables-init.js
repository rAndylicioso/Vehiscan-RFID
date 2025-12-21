/**
 * DataTables Initialization Script
 * Initializes DataTables for all admin panel tables
 */

(function() {
  'use strict';

  // Wait for both DOM and jQuery to be ready
  document.addEventListener('DOMContentLoaded', function() {
    // Wait for jQuery to be loaded
    if (typeof jQuery === 'undefined') {
      console.error('[DataTables] jQuery not loaded yet, waiting...');
      setTimeout(arguments.callee, 100);
      return;
    }
    console.log('[DataTables] Initializing...');

    // Initialize DataTables when employee table is loaded
    const initializeEmployeeTable = function() {
      const table = $('#employeesTable');
      if (table.length && !$.fn.DataTable.isDataTable('#employeesTable')) {
        table.DataTable({
          pageLength: 10,
          lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
          order: [[3, 'desc']], // Sort by Created date descending
          columnDefs: [
            { orderable: false, targets: 4 } // Disable sorting on Actions column
          ],
          language: {
            search: "Search employees:",
            lengthMenu: "Show _MENU_ employees",
            info: "Showing _START_ to _END_ of _TOTAL_ employees",
            infoEmpty: "No employees found",
            zeroRecords: "No matching employees found",
            paginate: {
              first: "First",
              last: "Last",
              next: "Next",
              previous: "Previous"
            }
          },
          dom: '<"flex items-center justify-between mb-4"lf>rt<"flex items-center justify-between mt-4"ip>'
        });
        console.log('[DataTables] Employee table initialized');
      }
    };

    // MutationObserver to detect when content is dynamically loaded
    const observer = new MutationObserver(function(mutations) {
      mutations.forEach(function(mutation) {
        if (mutation.addedNodes.length) {
          mutation.addedNodes.forEach(function(node) {
            if (node.nodeType === 1) { // Element node
              // Check if the added node contains the employeesTable
              if (node.querySelector && node.querySelector('#employeesTable')) {
                initializeEmployeeTable();
              }
              // Or if the added node itself is the table
              if (node.id === 'employeesTable') {
                initializeEmployeeTable();
              }
            }
          });
        }
      });
    });

    // Observe the main content area for changes
    const contentArea = document.querySelector('#content-area');
    if (contentArea) {
      observer.observe(contentArea, {
        childList: true,
        subtree: true
      });
    }

    // Try immediate initialization if table already exists
    initializeEmployeeTable();
  });

})();
