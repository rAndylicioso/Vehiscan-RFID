/**
 * Modal Component for Visitor Pass Management
 * Location: assets/js/admin/visitor-pass-modal.js
 */

class VisitorPassModal {
  constructor() {
    this.overlay = null;
    this.homeowners = [];
    this.csrf = window.__ADMIN_CSRF__;
    this.init();
  }

  init() {
    console.log('[Modal] Initializing visitor pass modal...');
    // Create modal HTML
    this.createModalHTML();
    // Bind events
    this.bindEvents();
    console.log('[Modal] Visitor pass modal ready');
  }

  createModalHTML() {
    // Check if modal already exists
    const existing = document.getElementById('visitorPassModal');
    if (existing) {
      console.log('[Modal] Modal already exists, reusing');
      this.overlay = existing;
      return;
    }

    console.log('[Modal] Creating modal HTML...');
    const modalHTML = `
      <div class="modal-overlay" id="visitorPassModal">
        <div class="modal-container">
          <div class="modal-header">
            <h2 class="modal-title">
              <span>🎫</span>
              <span id="modalTitle">Create Visitor Pass</span>
            </h2>
            <button class="modal-close" id="closeModal" aria-label="Close modal">
              ×
            </button>
          </div>
          
          <div class="modal-body">
            <form id="visitorPassForm">
              <!-- Homeowner Selection -->
              <div class="form-group">
                <label class="form-label" for="homeowner_id">
                  Linked Homeowner
                  <span class="form-hint">(Optional - Leave blank for unlinked visitor)</span>
                </label>
                <select class="form-select" id="homeowner_id" name="homeowner_id">
                  <option value="">Select Homeowner (Optional)</option>
                </select>
              </div>

              <!-- Visitor Information -->
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label" for="visitor_name">
                    Visitor Name <span class="required">*</span>
                  </label>
                  <input 
                    type="text" 
                    class="form-input" 
                    id="visitor_name" 
                    name="visitor_name"
                    placeholder="John Doe"
                    required
                  >
                  <div class="form-error">Visitor name is required</div>
                </div>

                <div class="form-group">
                  <label class="form-label" for="visitor_plate">
                    Vehicle Plate Number <span class="required">*</span>
                  </label>
                  <input 
                    type="text" 
                    class="form-input" 
                    id="visitor_plate" 
                    name="visitor_plate"
                    placeholder="ABC-1234"
                    required
                    style="text-transform: uppercase;"
                  >
                  <div class="form-error">Vehicle plate is required</div>
                </div>
              </div>

              <!-- Purpose -->
              <div class="form-group">
                <label class="form-label" for="purpose">
                  Purpose of Visit <span class="required">*</span>
                </label>
                <input 
                  type="text" 
                  class="form-input" 
                  id="purpose" 
                  name="purpose"
                  placeholder="Delivery, Guest, Maintenance, etc."
                  list="purposeSuggestions"
                  required
                >
                <datalist id="purposeSuggestions">
                  <option value="Guest Visit">
                  <option value="Delivery">
                  <option value="Maintenance">
                  <option value="Contractor">
                  <option value="Family Visit">
                  <option value="Service Provider">
                </datalist>
                <div class="form-error">Purpose is required</div>
              </div>

              <!-- Validity Period -->
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label" for="valid_from">
                    Valid From <span class="required">*</span>
                  </label>
                  <input 
                    type="datetime-local" 
                    class="form-input" 
                    id="valid_from" 
                    name="valid_from"
                    required
                  >
                  <div class="form-error">Start date is required</div>
                </div>

                <div class="form-group">
                  <label class="form-label" for="valid_until">
                    Valid Until <span class="required">*</span>
                  </label>
                  <input 
                    type="datetime-local" 
                    class="form-input" 
                    id="valid_until" 
                    name="valid_until"
                    required
                  >
                  <div class="form-error">End date is required</div>
                </div>
              </div>

              <!-- Quick Duration Buttons -->
              <div class="form-group">
                <div class="form-label" style="margin-bottom: 8px;">Quick Duration Select:</div>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                  <button type="button" class="btn btn-secondary btn-sm" data-hours="2">2 Hours</button>
                  <button type="button" class="btn btn-secondary btn-sm" data-hours="4">4 Hours</button>
                  <button type="button" class="btn btn-secondary btn-sm" data-hours="8">8 Hours</button>
                  <button type="button" class="btn btn-secondary btn-sm" data-days="1">1 Day</button>
                  <button type="button" class="btn btn-secondary btn-sm" data-days="3">3 Days</button>
                  <button type="button" class="btn btn-secondary btn-sm" data-days="7">1 Week</button>
                </div>
              </div>

              <!-- Recurring Pass -->
              <div class="form-group">
                <div class="form-checkbox-group">
                  <input 
                    type="checkbox" 
                    class="form-checkbox" 
                    id="is_recurring" 
                    name="is_recurring"
                    value="1"
                  >
                  <label class="form-checkbox-label" for="is_recurring">
                    Recurring Pass (Auto-renew daily/weekly)
                  </label>
                </div>
                <p class="form-hint">
                  Enable for contractors or regular visitors who need repeated access
                </p>
              </div>

              <!-- Status (only shown when editing) -->
              <div class="form-group" id="statusGroup" style="display: none;">
                <label class="form-label" for="status">Status</label>
                <select class="form-select" id="status" name="status">
                  <option value="active">Active</option>
                  <option value="expired">Expired</option>
                  <option value="cancelled">Cancelled</option>
                </select>
              </div>

              <!-- Pass ID (hidden, for editing) -->
              <input type="hidden" id="pass_id" name="id">
            </form>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="cancelBtn">
              Cancel
            </button>
            <button type="button" class="btn btn-success" id="submitBtn">
              <span id="btnText">Create Pass</span>
            </button>
          </div>
        </div>
      </div>
    `;

    // Append to body
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    this.overlay = document.getElementById('visitorPassModal');
    console.log('[Modal] Modal HTML created and appended to body');
  }

  bindEvents() {
    console.log('[Modal] Binding events...');
    if (!this.overlay) {
      console.error('[Modal] Cannot bind events - overlay not found!');
      return;
    }

    // Close button
    const closeBtn = document.getElementById('closeModal');
    const cancelBtn = document.getElementById('cancelBtn');
    
    if (closeBtn) {
      closeBtn.addEventListener('click', () => this.close());
    }
    if (cancelBtn) {
      cancelBtn.addEventListener('click', () => this.close());
    }
    
    // Close on overlay click
    this.overlay.addEventListener('click', (e) => {
      if (e.target === this.overlay) {
        this.close();
      }
    });

    // Close on ESC key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && this.overlay.classList.contains('active')) {
        this.close();
      }
    });

    // Submit button
    document.getElementById('submitBtn').addEventListener('click', () => this.submit());

    // Quick duration buttons
    document.querySelectorAll('[data-hours], [data-days]').forEach(btn => {
      btn.addEventListener('click', (e) => this.setQuickDuration(e.target));
    });

    // Auto-uppercase plate number
    document.getElementById('visitor_plate').addEventListener('input', (e) => {
      e.target.value = e.target.value.toUpperCase();
    });

    // Validate end date is after start date
    document.getElementById('valid_from').addEventListener('change', () => this.validateDates());
    document.getElementById('valid_until').addEventListener('change', () => this.validateDates());
  }

  setQuickDuration(btn) {
    const now = new Date();
    const validFrom = document.getElementById('valid_from');
    const validUntil = document.getElementById('valid_until');

    // Set valid_from to now if empty
    if (!validFrom.value) {
      validFrom.value = this.formatDateTime(now);
    }

    // Calculate end time
    const endTime = new Date(validFrom.value || now);
    
    if (btn.dataset.hours) {
      endTime.setHours(endTime.getHours() + parseInt(btn.dataset.hours));
    } else if (btn.dataset.days) {
      endTime.setDate(endTime.getDate() + parseInt(btn.dataset.days));
    }

    validUntil.value = this.formatDateTime(endTime);
    this.validateDates();
  }

  formatDateTime(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day}T${hours}:${minutes}`;
  }

  validateDates() {
    const validFrom = document.getElementById('valid_from').value;
    const validUntil = document.getElementById('valid_until').value;

    if (validFrom && validUntil) {
      const start = new Date(validFrom);
      const end = new Date(validUntil);

      if (end <= start) {
        document.getElementById('valid_until').classList.add('error');
        Swal.fire({
          icon: 'error',
          title: 'Invalid Date',
          text: 'End date must be after start date',
          heightAuto: false
        });
        return false;
      } else {
        document.getElementById('valid_until').classList.remove('error');
      }
    }
    return true;
  }

  async loadHomeowners() {
    try {
      const res = await fetch('fetch/fetch_manage.php');
      const data = await res.json();
      
      if (data.success && data.homeowners) {
        this.homeowners = data.homeowners;
        this.populateHomeownerSelect();
      }
    } catch (err) {
      console.error('Error loading homeowners:', err);
    }
  }

  populateHomeownerSelect() {
    const select = document.getElementById('homeowner_id');
    select.innerHTML = '<option value="">Select Homeowner (Optional)</option>';
    
    this.homeowners.forEach(h => {
      const option = document.createElement('option');
      option.value = h.id;
      option.textContent = `${h.name} - ${h.plate_number}`;
      select.appendChild(option);
    });
  }

  open(mode = 'create', data = null) {
    console.log('[Modal] Opening modal in mode:', mode);
    
    if (!this.overlay) {
      console.error('[Modal] Cannot open - overlay not initialized!');
      alert('Modal not initialized. Please refresh the page.');
      return;
    }

    // Load homeowners if not loaded
    if (this.homeowners.length === 0) {
      this.loadHomeowners();
    }

    // Set mode
    if (mode === 'edit' && data) {
      document.getElementById('modalTitle').textContent = 'Edit Visitor Pass';
      document.getElementById('btnText').textContent = 'Update Pass';
      document.getElementById('statusGroup').style.display = 'block';
      this.populateForm(data);
    } else {
      document.getElementById('modalTitle').textContent = 'Create Visitor Pass';
      document.getElementById('btnText').textContent = 'Create Pass';
      document.getElementById('statusGroup').style.display = 'none';
      this.resetForm();
      
      // Set default dates (now to +4 hours)
      const now = new Date();
      const later = new Date(now.getTime() + 4 * 60 * 60 * 1000);
      document.getElementById('valid_from').value = this.formatDateTime(now);
      document.getElementById('valid_until').value = this.formatDateTime(later);
    }

    // Show modal
    console.log('[Modal] Showing modal...');
    this.overlay.classList.add('active');
    document.body.style.overflow = 'hidden';

    // Focus first input
    setTimeout(() => {
      const firstInput = document.getElementById('visitor_name');
      if (firstInput) {
        firstInput.focus();
      }
    }, 300);
  }

  close() {
    console.log('[Modal] Closing modal...');
    if (this.overlay) {
      this.overlay.classList.remove('active');
    }
    document.body.style.overflow = '';
    setTimeout(() => {
      this.resetForm();
    }, 300);
  }

  resetForm() {
    document.getElementById('visitorPassForm').reset();
    document.querySelectorAll('.form-input.error, .form-select.error').forEach(el => {
      el.classList.remove('error');
    });
  }

  populateForm(data) {
    document.getElementById('pass_id').value = data.id || '';
    document.getElementById('homeowner_id').value = data.homeowner_id || '';
    document.getElementById('visitor_name').value = data.visitor_name || '';
    document.getElementById('visitor_plate').value = data.visitor_plate || '';
    document.getElementById('purpose').value = data.purpose || '';
    document.getElementById('valid_from').value = data.valid_from || '';
    document.getElementById('valid_until').value = data.valid_until || '';
    document.getElementById('is_recurring').checked = data.is_recurring == 1;
    document.getElementById('status').value = data.status || 'active';
  }

  validateForm() {
    const form = document.getElementById('visitorPassForm');
    const inputs = form.querySelectorAll('[required]');
    let isValid = true;

    inputs.forEach(input => {
      if (!input.value.trim()) {
        input.classList.add('error');
        isValid = false;
      } else {
        input.classList.remove('error');
      }
    });

    if (!this.validateDates()) {
      isValid = false;
    }

    return isValid;
  }

  async submit() {
    if (!this.validateForm()) {
      Swal.fire({
        icon: 'error',
        title: 'Validation Error',
        text: 'Please fill in all required fields',
        heightAuto: false
      });
      return;
    }

    const submitBtn = document.getElementById('submitBtn');
    submitBtn.classList.add('btn-loading');
    submitBtn.disabled = true;

    try {
      const form = document.getElementById('visitorPassForm');
      const formData = new FormData(form);
      formData.append('csrf', this.csrf);

      const passId = document.getElementById('pass_id').value;
      const endpoint = passId ? '../api/update_visitor_pass.php' : '../api/create_visitor_pass.php';

      const res = await fetch(endpoint, {
        method: 'POST',
        body: formData
      });

      const json = await res.json();

      if (json.success) {
        await Swal.fire({
          icon: 'success',
          title: 'Success',
          text: json.message || 'Pass saved successfully',
          heightAuto: false
        });
        this.close();
        
        // Reload visitor passes
        if (typeof loadPage === 'function') {
          loadPage('visitors');
        } else {
          location.reload();
        }
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: json.message || 'Failed to save pass',
          heightAuto: false
        });
      }
    } catch (err) {
      console.error('Error:', err);
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Failed to save pass',
        heightAuto: false
      });
    } finally {
      submitBtn.classList.remove('btn-loading');
      submitBtn.disabled = false;
    }
  }
}

// Initialize modal when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  console.log('[Visitor Pass Modal] Initializing...');
  window.visitorPassModal = new VisitorPassModal();
  console.log('[Visitor Pass Modal] Initialized successfully');
});

// Also initialize immediately if DOM is already loaded
if (document.readyState === 'loading') {
  // DOM still loading, wait for event
} else {
  // DOM already loaded, initialize now
  console.log('[Visitor Pass Modal] DOM already loaded, initializing immediately...');
  window.visitorPassModal = new VisitorPassModal();
  console.log('[Visitor Pass Modal] Initialized successfully');
}
