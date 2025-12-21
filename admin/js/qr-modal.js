/**
 * QR Code Zoom Modal
 * Click QR codes to view them in full size
 */

(function() {
  'use strict';
  
  // Create modal on page load
  function createQRModal() {
    const modal = document.createElement('div');
    modal.id = 'qrZoomModal';
    modal.innerHTML = `
      <div class="qr-modal-content">
        <button class="qr-modal-close" onclick="closeQRZoom()" aria-label="Close">&times;</button>
        <img id="qrZoomImage" class="qr-modal-image" src="" alt="QR Code">
        <div class="qr-modal-info">
          <p>Click outside or press ESC to close</p>
        </div>
      </div>
    `;
    document.body.appendChild(modal);
    
    // Close on outside click
    modal.addEventListener('click', function(e) {
      if (e.target === modal) {
        closeQRZoom();
      }
    });
    
    // Close on ESC key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && modal.classList.contains('active')) {
        closeQRZoom();
      }
    });
  }
  
  // Open QR zoom modal
  window.openQRZoom = function(src) {
    if (!src || src.includes('placeholder')) return;
    
    const modal = document.getElementById('qrZoomModal');
    const img = document.getElementById('qrZoomImage');
    
    if (modal && img) {
      img.src = src;
      modal.classList.add('active');
      document.body.style.overflow = 'hidden';
    }
  };
  
  // Close QR zoom modal
  window.closeQRZoom = function() {
    const modal = document.getElementById('qrZoomModal');
    if (modal) {
      modal.classList.remove('active');
      document.body.style.overflow = 'auto';
    }
  };
  
  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', createQRModal);
  } else {
    createQRModal();
  }
  
  // Make QR codes clickable
  document.addEventListener('click', function(e) {
    if (e.target.classList.contains('qr-clickable')) {
      openQRZoom(e.target.src);
    }
  });
})();
