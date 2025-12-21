/**
 * Registration Form Functionality
 * Handles camera capture, file uploads, form validation, and submission
 * Enhanced with real-time validation, drag-and-drop, keyboard shortcuts
 */

// Loading overlay management
const loadingOverlay = document.getElementById('loadingOverlay');
const progressBar = document.getElementById('progressBar');
const loadingMessage = document.getElementById('loadingMessage');

function showLoading(message = 'Processing...') {
  loadingOverlay.classList.remove('hidden');
  loadingMessage.textContent = message;
  progressBar.style.width = '0%';
}

function updateProgress(percent) {
  progressBar.style.width = percent + '%';
}

function hideLoading() {
  loadingOverlay.classList.add('hidden');
}

// Keyboard shortcuts
function initializeKeyboardShortcuts() {
  document.addEventListener('keydown', (e) => {
    // Ctrl+Enter to submit
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
      e.preventDefault();
      const form = document.getElementById('registrationForm');
      if (form) {
        form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
      }
    }
  });
}

// Real-time validation
function initializeValidation() {
  const nameInput = document.getElementById('nameInput');
  const contactInput = document.getElementById('contactInput');
  const plateInput = document.getElementById('plateInput');
  const addressInput = document.getElementById('addressInput');

  if (nameInput) {
    nameInput.addEventListener('input', function() {
      const hint = document.getElementById('nameHint');
      if (this.value.length >= 3) {
        this.classList.add('valid');
        this.classList.remove('invalid');
        hint.textContent = '✓ Name looks good';
        hint.style.color = '#10b981';
      } else if (this.value.length > 0) {
        this.classList.add('invalid');
        this.classList.remove('valid');
        hint.textContent = '✗ Name too short (min 3 characters)';
        hint.style.color = '#ef4444';
      } else {
        this.classList.remove('valid', 'invalid');
        hint.textContent = 'Enter your complete legal name';
        hint.style.color = '';
      }
    });
  }

  if (contactInput) {
    contactInput.addEventListener('input', function() {
      const hint = document.getElementById('contactHint');
      // Auto-format phone number
      let value = this.value.replace(/[^0-9]/g, '');
      if (value.length > 11) value = value.slice(0, 11);
      
      if (value.length >= 4 && value.length <= 7) {
        this.value = value.slice(0, 4) + '-' + value.slice(4);
      } else if (value.length > 7) {
        this.value = value.slice(0, 4) + '-' + value.slice(4, 7) + '-' + value.slice(7);
      } else {
        this.value = value;
      }

      if (value.length === 11) {
        this.classList.add('valid');
        this.classList.remove('invalid');
        hint.textContent = '✓ Valid contact number';
        hint.style.color = '#10b981';
      } else if (value.length > 0) {
        this.classList.add('invalid');
        this.classList.remove('valid');
        hint.textContent = `✗ ${11 - value.length} more digit${11 - value.length !== 1 ? 's' : ''} needed`;
        hint.style.color = '#ef4444';
      } else {
        this.classList.remove('valid', 'invalid');
        hint.textContent = "We'll use this for important notifications";
        hint.style.color = '';
      }
    });
  }

  if (plateInput) {
    plateInput.addEventListener('input', function() {
      const hint = document.getElementById('plateHint');
      const value = this.value.toUpperCase();
      if (value.length >= 3) {
        this.classList.add('valid');
        this.classList.remove('invalid');
        hint.textContent = '✓ Plate number accepted';
        hint.style.color = '#10b981';
      } else if (value.length > 0) {
        this.classList.add('invalid');
        this.classList.remove('valid');
        hint.textContent = '✗ Too short (min 3 characters)';
        hint.style.color = '#ef4444';
      } else {
        this.classList.remove('valid', 'invalid');
        hint.textContent = 'Used for gate access verification (auto-uppercase)';
        hint.style.color = '';
      }
    });
  }

  if (addressInput) {
    addressInput.addEventListener('input', function() {
      const hint = document.getElementById('addressHint');
      const remaining = 200 - this.value.length;
      if (this.value.length >= 10) {
        this.classList.add('valid');
        this.classList.remove('invalid');
        hint.textContent = `✓ ${remaining} characters remaining`;
        hint.style.color = '#10b981';
      } else if (this.value.length > 0) {
        this.classList.add('invalid');
        this.classList.remove('valid');
        hint.textContent = '✗ Address too short';
        hint.style.color = '#ef4444';
      } else {
        this.classList.remove('valid', 'invalid');
        hint.textContent = 'Include complete address within subdivision';
        hint.style.color = '';
      }
    });
  }
}

// Initialize plate number auto-uppercase
function initializePlateInput() {
  const plateInput = document.getElementById('plateInput');
  if (plateInput) {
    plateInput.addEventListener('input', function(e) {
      e.target.value = e.target.value.toUpperCase();
    });
  }
}

// Initialize camera button functionality
function initializeCameraButtons() {
  document.querySelectorAll('.camera-btn').forEach(btn => {
    const inputName = btn.dataset.for;
    const mainFileInput = document.querySelector(`input[name="${inputName}"]`);
    
    if (!mainFileInput) {
      console.error('Main file input not found for:', inputName);
      return;
    }

    console.log('Initializing camera button for:', inputName);

    // Create a wrapper div for the button to contain the file input
    const wrapper = document.createElement('div');
    wrapper.style.cssText = 'position: relative; display: inline-flex;';
    
    // Create a dedicated file input positioned over the button
    const cameraInput = document.createElement('input');
    cameraInput.type = 'file';
    cameraInput.accept = 'image/*';
    cameraInput.capture = 'environment';
    cameraInput.id = inputName + '_cameraInput';
    cameraInput.className = 'hidden-file-input';
    cameraInput.style.cssText = `
      position: absolute;
      opacity: 0;
      width: 100%;
      height: 100%;
      top: 0;
      left: 0;
      cursor: pointer;
      z-index: 1;
    `;
    
    // Wrap the button and insert the input inside the wrapper
    btn.parentNode.insertBefore(wrapper, btn);
    wrapper.appendChild(btn);
    wrapper.appendChild(cameraInput);
    
    // When camera input changes, transfer the file to main input
    cameraInput.addEventListener('change', function() {
      if (cameraInput.files && cameraInput.files.length > 0) {
        // Transfer the file to the main input
        try {
          const dt = new DataTransfer();
          dt.items.add(cameraInput.files[0]);
          mainFileInput.files = dt.files;
          
          // Trigger change event on main input to show preview
          mainFileInput.dispatchEvent(new Event('change', { bubbles: true }));
        } catch (err) {
          console.error('Error transferring photo:', err);
        }
      }
    });
    
  });
}

// Initialize gallery button functionality
function initializeGalleryButtons() {
  document.querySelectorAll('.gallery-btn').forEach(btn => {
    const inputName = btn.dataset.for;
    const mainFileInput = document.querySelector(`input[name="${inputName}"]`);
    
    if (!mainFileInput) {
      console.error('Main file input not found for:', inputName);
      return;
    }

    console.log('Initializing gallery button for:', inputName);

    // Create a wrapper div for the button to contain the file input
    const wrapper = document.createElement('div');
    wrapper.style.cssText = 'position: relative; display: inline-flex;';
    
    // Create a dedicated file input positioned over the button
    const galleryInput = document.createElement('input');
    galleryInput.type = 'file';
    galleryInput.accept = 'image/*';
    galleryInput.id = inputName + '_galleryInput';
    galleryInput.className = 'hidden-file-input';
    galleryInput.style.cssText = `
      position: absolute;
      opacity: 0;
      width: 100%;
      height: 100%;
      top: 0;
      left: 0;
      cursor: pointer;
      z-index: 1;
    `;
    
    // Wrap the button and insert the input inside the wrapper
    btn.parentNode.insertBefore(wrapper, btn);
    wrapper.appendChild(btn);
    wrapper.appendChild(galleryInput);
    
    // When gallery input changes, transfer the file to main input
    galleryInput.addEventListener('change', function() {
      if (galleryInput.files && galleryInput.files.length > 0) {
        // Transfer the file to the main input
        try {
          const dt = new DataTransfer();
          for (let i = 0; i < galleryInput.files.length; i++) {
            dt.items.add(galleryInput.files[i]);
          }
          mainFileInput.files = dt.files;
          
          // Trigger change event on main input to show preview
          mainFileInput.dispatchEvent(new Event('change', { bubbles: true }));
        } catch (err) {
          console.error('Error transferring file:', err);
        }
      }
    });
    
  });
}

// Initialize file input with drag-and-drop and preview
function initializeFileInputLabels() {
  const ownerInput = document.getElementById('ownerImgInput');
  const ownerLabel = document.getElementById('ownerImgLabel');
  const ownerBox = document.getElementById('ownerUploadBox');
  const ownerPreview = document.getElementById('ownerPreview');
  const ownerPreviewImg = document.getElementById('ownerPreviewImg');
  
  const carInput = document.getElementById('carImgInput');
  const carLabel = document.getElementById('carImgLabel');
  const carBox = document.getElementById('carUploadBox');
  const carPreview = document.getElementById('carPreview');
  const carPreviewImg = document.getElementById('carPreviewImg');

  function setupFileInput(input, label, box, preview, previewImg) {
    if (!input || !box) return;

    // File selection handler with enhanced validation
    input.addEventListener('change', async () => {
      if (input.files && input.files.length > 0) {
        const file = input.files[0];
        const maxSize = 4 * 1024 * 1024;
        
        // Validate file type
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!validTypes.includes(file.type.toLowerCase())) {
          Swal.fire({
            icon: 'error',
            title: 'Invalid File Type',
            text: 'Please upload a JPG, PNG, or WEBP image file.',
            confirmButtonColor: '#ef4444'
          });
          input.value = '';
          return;
        }
        
        // Validate file size
        if (file.size > maxSize) {
          Swal.fire({
            icon: 'error',
            title: 'File Too Large',
            text: `Image is ${(file.size / 1024 / 1024).toFixed(2)}MB. Maximum size is 4MB.`,
            html: '<small>Tip: Try compressing the image or taking a new photo.</small>',
            confirmButtonColor: '#ef4444'
          });
          input.value = '';
          return;
        }

        // Show loading state
        if (label) {
          label.textContent = 'Processing image...';
          label.style.color = '#3b82f6';
        }

        // Load and validate image
        const reader = new FileReader();
        reader.onload = async (e) => {
          const img = new Image();
          img.onload = async () => {
            // Show preview with smooth animation
            if (previewImg && preview) {
              previewImg.src = e.target.result;
              preview.style.display = 'block';
              box.classList.add('has-file');
              
              // Add fade-in animation
              preview.style.opacity = '0';
              setTimeout(() => {
                preview.style.transition = 'opacity 0.3s ease';
                preview.style.opacity = '1';
              }, 10);
            }

            // Update label with file info
            if (label) {
              const sizeKB = (file.size / 1024).toFixed(0);
              const dimensions = `${img.width}x${img.height}`;
              label.innerHTML = `<span style="color: #10b981;">✓</span> ${file.name} <span style="color: #6b7280;">(${sizeKB}KB • ${dimensions}px)</span>`;
            }
          };
          
          img.onerror = () => {
            Swal.fire({
              icon: 'error',
              title: 'Invalid Image',
              text: 'Unable to load the image. Please try a different file.',
              confirmButtonColor: '#ef4444'
            });
            input.value = '';
            resetFileInput(input, label, box, preview);
          };
          
          img.src = e.target.result;
        };
        
        reader.onerror = () => {
          Swal.fire({
            icon: 'error',
            title: 'Read Error',
            text: 'Failed to read the image file. Please try again.',
            confirmButtonColor: '#ef4444'
          });
          input.value = '';
          resetFileInput(input, label, box, preview);
        };
        
        reader.readAsDataURL(file);
      } else {
        resetFileInput(input, label, box, preview);
      }
    });

    // Enhanced drag and drop with validation
    box.addEventListener('dragover', (e) => {
      e.preventDefault();
      e.stopPropagation();
      box.classList.add('drag-over');
      
      // Show visual feedback
      const uploadText = box.querySelector('.upload-title');
      if (uploadText) {
        uploadText.dataset.originalText = uploadText.dataset.originalText || uploadText.textContent;
        uploadText.textContent = 'Drop image here';
      }
    });

    box.addEventListener('dragleave', (e) => {
      e.preventDefault();
      e.stopPropagation();
      box.classList.remove('drag-over');
      
      // Restore original text
      const uploadText = box.querySelector('.upload-title');
      if (uploadText && uploadText.dataset.originalText) {
        uploadText.textContent = uploadText.dataset.originalText;
      }
    });

    box.addEventListener('drop', (e) => {
      e.preventDefault();
      e.stopPropagation();
      box.classList.remove('drag-over');
      
      // Restore original text
      const uploadText = box.querySelector('.upload-title');
      if (uploadText && uploadText.dataset.originalText) {
        uploadText.textContent = uploadText.dataset.originalText;
      }
      
      const files = e.dataTransfer.files;
      if (files.length === 0) return;
      
      // Validate file type before processing
      const file = files[0];
      const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
      if (!validTypes.includes(file.type.toLowerCase())) {
        Swal.fire({
          icon: 'error',
          title: 'Invalid File',
          text: 'Please drop a JPG, PNG, or WEBP image file.',
          confirmButtonColor: '#ef4444'
        });
        return;
      }
      
      if (files.length > 1) {
        Swal.fire({
          icon: 'info',
          title: 'Multiple Files',
          text: 'Only the first image will be used.',
          confirmButtonColor: '#3b82f6',
          timer: 2000
        });
      }
      
      const dt = new DataTransfer();
      dt.items.add(files[0]);
      input.files = dt.files;
      input.dispatchEvent(new Event('change', { bubbles: true }));
    });

    // Click to select
    box.addEventListener('click', (e) => {
      // Don't trigger if clicking buttons or preview remove
      if (e.target.closest('.camera-btn') || 
          e.target.closest('.gallery-btn') || 
          e.target.closest('.preview-remove') ||
          e.target.closest('.upload-actions')) {
        return;
      }
      input.click();
    });
  }

  function resetFileInput(input, label, box, preview) {
    if (preview) {
      preview.style.display = 'none';
      preview.style.opacity = '0';
      preview.style.transition = '';
    }
    if (box) box.classList.remove('has-file');
    if (label) {
      label.style.color = '';
      if (input.name === 'owner_img') {
        label.innerHTML = 'Owner photo is required for verification';
      } else {
        label.innerHTML = 'Helps guards identify your vehicle';
      }
    }
  }

  // Setup both inputs
  setupFileInput(ownerInput, ownerLabel, ownerBox, ownerPreview, ownerPreviewImg);
  setupFileInput(carInput, carLabel, carBox, carPreview, carPreviewImg);

  // Remove button handlers with confirmation
  document.querySelectorAll('.preview-remove').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.stopPropagation();
      const targetName = btn.dataset.for;
      const input = document.querySelector(`input[name="${targetName}"]`);
      const label = document.getElementById(targetName === 'owner_img' ? 'ownerImgLabel' : 'carImgLabel');
      const box = document.getElementById(targetName === 'owner_img' ? 'ownerUploadBox' : 'carUploadBox');
      const preview = document.getElementById(targetName === 'owner_img' ? 'ownerPreview' : 'carPreview');
      
      if (input) {
        // Fade out animation
        if (preview) {
          preview.style.transition = 'opacity 0.2s ease';
          preview.style.opacity = '0';
        }
        
        setTimeout(() => {
          input.value = '';
          resetFileInput(input, label, box, preview);
        }, 200);
      }
    });
  });
}

// Form submission handler with validation and progress tracking
function initializeFormSubmission() {
  const form = document.getElementById('registrationForm');
  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    // Show loading overlay
    showLoading('Validating data...');
    updateProgress(10);
    
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const btnText = submitBtn.querySelector('.btn-text');
    const btnLoading = submitBtn.querySelector('.btn-loading');
    
    submitBtn.disabled = true;
    if (btnText) btnText.style.display = 'none';
    if (btnLoading) btnLoading.style.display = 'flex';
    
    // Validate required fields using structured name fields
    const firstNameInput = e.target.querySelector('input[name="first_name"]');
    const lastNameInput = e.target.querySelector('input[name="last_name"]');
    const plateInput = e.target.querySelector('input[name="plate_number"]');
    
    if (!firstNameInput || !lastNameInput) {
      hideLoading();
      submitBtn.disabled = false;
      Swal.fire({
        icon: 'error',
        title: 'Form Error',
        text: 'Name fields not found. Please refresh the page.',
        confirmButtonColor: '#ef4444'
      });
      return;
    }
    
    const firstName = firstNameInput.value.trim();
    const lastName = lastNameInput.value.trim();
    const plate = plateInput ? plateInput.value.trim() : '';
    
    if (!firstName || firstName.length < 2) {
      hideLoading();
      submitBtn.disabled = false;
      Swal.fire({
        icon: 'error',
        title: 'Invalid First Name',
        text: 'Please enter a valid first name (minimum 2 characters)',
        confirmButtonColor: '#ef4444'
      });
      return;
    }
    
    if (!lastName || lastName.length < 2) {
      hideLoading();
      submitBtn.disabled = false;
      Swal.fire({
        icon: 'error',
        title: 'Invalid Last Name',
        text: 'Please enter a valid last name (minimum 2 characters)',
        confirmButtonColor: '#ef4444'
      });
      return;
    }
    
    updateProgress(20);
    
    const formData = new FormData(e.target);
    
    // Validate file size before upload
    const ownerImg = e.target.querySelector('input[name="owner_img"]').files[0];
    const vehicleImg = e.target.querySelector('input[name="car_img"]').files[0];
    
    const maxSize = 4 * 1024 * 1024; // 4MB
    
    if (!ownerImg) {
      hideLoading();
      submitBtn.disabled = false;
      Swal.fire({
        icon: 'error',
        title: 'Owner Photo Required',
        text: 'Please upload a photo of the homeowner',
        confirmButtonColor: '#ef4444'
      });
      return;
    }
    
    if (ownerImg && ownerImg.size > maxSize) {
      hideLoading();
      submitBtn.disabled = false;
      Swal.fire({
        icon: 'error',
        title: 'File Too Large',
        text: `Owner photo is ${(ownerImg.size / 1024 / 1024).toFixed(2)}MB. Maximum size is 4MB.`,
        confirmButtonColor: '#ef4444'
      });
      return;
    }
    
    if (vehicleImg && vehicleImg.size > maxSize) {
      hideLoading();
      submitBtn.disabled = false;
      Swal.fire({
        icon: 'error',
        title: 'File Too Large',
        text: `Vehicle photo is ${(vehicleImg.size / 1024 / 1024).toFixed(2)}MB. Maximum size is 4MB.`,
        confirmButtonColor: '#ef4444'
      });
      return;
    }
    
    updateProgress(40);
    loadingMessage.textContent = 'Uploading images...';

    try {
      const res = await fetch(window.location.href, { method: 'POST', body: formData, credentials: 'same-origin' });
      
      updateProgress(80);
      loadingMessage.textContent = 'Saving to database...';
      
      updateProgress(90);
      
      if (!res.ok) {
        throw new Error(`HTTP error! status: ${res.status}`);
      }
      
      const json = await res.json();
      console.log('Registration response:', json);
      
      updateProgress(100);

      hideLoading();
      
      if (json.success) {
        const fullName = `${formData.get('first_name')} ${formData.get('last_name')}`;
        const username = json.username || formData.get('username');
        const email = json.email || formData.get('email');
        const plateNum = json.plate_number || formData.get('plate_number');
        
        await Swal.fire({
          icon: 'warning',
          title: 'Registration Submitted',
          html: `
            <div style="text-align:left;padding:10px;font-size:14px;">
              <p><b>Name:</b> ${fullName}</p>
              <p><b>Username:</b> ${username}</p>
              <p><b>Email:</b> ${email}</p>
              <p><b>Plate:</b> ${plateNum}</p>
              <hr style="margin:15px 0;">
              <p style="background:#fef3c7;padding:12px;border-radius:4px;border-left:4px solid #f59e0b;">
                <strong style="color:#92400e;">⏳ Pending Admin Approval</strong><br>
                <span style="color:#78350f;font-size:13px;">Your account will be reviewed. You'll receive an email once approved.</span>
              </p>
              <p style="text-align:center;margin-top:10px;">
                <small style="color:#6b7280;">Status: <strong style="color:#d97706;">PENDING APPROVAL</strong></small>
              </p>
            </div>
          `,
          confirmButtonText: 'OK',
          confirmButtonColor: '#2563eb'
        });
        e.target.reset();
        
        // Reset file input labels
        const ownerLabel = document.getElementById('ownerImgLabel');
        const carLabel = document.getElementById('carImgLabel');
        if (ownerLabel) {
          ownerLabel.textContent = 'Required. Choose from gallery or use camera (JPG, PNG, WEBP, max 4 MB).';
        }
        if (carLabel) {
          carLabel.textContent = 'Optional. Choose from gallery or use camera (same limits as owner photo).';
        }
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Registration Failed',
          text: json.message || 'An error occurred during registration',
          confirmButtonColor: '#ef4444',
          footer: '<p class="text-xs text-gray-500">If this problem persists, please contact the administrator.</p>'
        });
      }
    } catch (err) {
      hideLoading();
      console.error('Registration error:', err);
      Swal.fire({
        icon: 'error',
        title: 'Connection Error',
        html: `
          <p>Unable to submit registration. Please check your connection and try again.</p>
          <p class="text-sm text-gray-500 mt-2">Error details: ${err.message}</p>
        `,
        confirmButtonColor: '#ef4444'
      });
    } finally {
      // Restore button state
      submitBtn.disabled = false;
      if (btnText) btnText.style.display = 'inline';
      if (btnLoading) btnLoading.style.display = 'none';
    }
  });
}

// Auto-focus first input
function initializeAutoFocus() {
  const firstInput = document.getElementById('nameInput');
  if (firstInput) {
    setTimeout(() => firstInput.focus(), 100);
  }
}

// Initialize all functionality when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
  console.log('🚀 Registration page initialized');
  initializeKeyboardShortcuts();
  initializeValidation();
  initializePlateInput();
  initializeCameraButtons();
  initializeGalleryButtons();
  initializeFileInputLabels();
  initializeFormSubmission();
  initializeAutoFocus();
  
  // Add visual feedback on form interaction
  const form = document.getElementById('registrationForm');
  if (form) {
    form.addEventListener('input', () => {
      form.classList.add('form-touched');
    });
  }
});
