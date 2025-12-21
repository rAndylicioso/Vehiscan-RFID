/**
 * VehiScan Login Page JavaScript
 * Handles role selection, password visibility, and form interactions
 */

(function() {
  'use strict';

  // Initialize when DOM is ready
  document.addEventListener('DOMContentLoaded', function() {
    // Role buttons removed - system auto-detects role
    initializePasswordToggle();
    initializeFormValidation();
    initializeKeyboardShortcuts();
    initializeInputFocusEffects();
    initializeCreateAccountLink();
  });

  /**
   * Keyboard shortcuts
   */
  function initializeKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
      // Escape to clear form
      if (e.key === 'Escape') {
        const username = document.getElementById('username');
        const password = document.getElementById('password');
        if (username && password) {
          username.value = '';
          password.value = '';
          username.focus();
        }
      }
    });
  }

  /**
   * Input focus effects and animations
   */
  function initializeInputFocusEffects() {
    const inputs = document.querySelectorAll('.form-group input');
    
    inputs.forEach(input => {
      // Add focus class to parent on focus
      input.addEventListener('focus', function() {
        this.parentElement.parentElement.classList.add('focused');
      });

      // Remove focus class on blur
      input.addEventListener('blur', function() {
        this.parentElement.parentElement.classList.remove('focused');
      });

      // Auto-select on focus for better UX
      input.addEventListener('focus', function() {
        setTimeout(() => {
          if (this.value.length > 0 && this.type !== 'password') {
            this.select();
          }
        }, 50);
      });
    });
  }

  /**
   * Password visibility toggle
   */
  function initializePasswordToggle() {
    const passwordInput = document.getElementById('password');
    const toggleButton = document.getElementById('togglePassword');

    if (!passwordInput || !toggleButton) {
      console.warn('Password toggle elements not found');
      return;
    }

    toggleButton.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      const isVisible = passwordInput.type === 'text';
      
      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleButton.textContent = '🙈';
        toggleButton.setAttribute('aria-label', 'Hide password');
      } else {
        passwordInput.type = 'password';
        toggleButton.textContent = '👁';
        toggleButton.setAttribute('aria-label', 'Show password');
      }

      // Animation feedback
      toggleButton.style.transform = 'translateY(-50%) scale(0.8)';
      setTimeout(() => {
        toggleButton.style.transform = '';
      }, 150);

      // Keep focus on password field
      passwordInput.focus();
      const len = passwordInput.value.length;
      passwordInput.setSelectionRange(len, len);
    });

    // Show/hide on Ctrl+Shift+P
    document.addEventListener('keydown', function(e) {
      if (e.ctrlKey && e.shiftKey && e.key === 'P') {
        e.preventDefault();
        toggleButton.click();
      }
    });

    // Reset to hidden when switching tabs/windows
    document.addEventListener('visibilitychange', function() {
      if (document.hidden && passwordInput.type === 'text') {
        passwordInput.type = 'password';
        toggleButton.textContent = '👁';
      }
    });
  }

  /**
   * Form validation and enhancement
   */
  function initializeFormValidation() {
    const loginForm = document.querySelector('.login-form');
    
    if (!loginForm) return;

    loginForm.addEventListener('submit', function(e) {
      const username = document.getElementById('username');
      const password = document.getElementById('password');

      // Basic validation
      if (!username.value.trim()) {
        e.preventDefault();
        showValidationError('Please enter your username or email');
        username.focus();
        return false;
      }

      if (!password.value) {
        e.preventDefault();
        showValidationError('Please enter your password');
        password.focus();
        return false;
      }

      // Form is valid, show loading state
      const submitButton = loginForm.querySelector('.btn-primary');
      const btnText = submitButton.querySelector('.btn-text');
      if (submitButton && btnText) {
        submitButton.disabled = true;
        btnText.textContent = 'Signing in...';
        
        // Add spinner
        const spinner = document.createElement('span');
        spinner.className = 'spinner';
        submitButton.insertBefore(spinner, btnText);
      }
    });

    // Real-time validation feedback
    const username = document.getElementById('username');
    const password = document.getElementById('password');

    if (username) {
      username.addEventListener('input', function() {
        this.setCustomValidity('');
        if (this.value.trim().length > 0) {
          this.classList.add('has-value');
        } else {
          this.classList.remove('has-value');
        }
      });
    }

    if (password) {
      password.addEventListener('input', function() {
        this.setCustomValidity('');
        if (this.value.length > 0) {
          this.classList.add('has-value');
        } else {
          this.classList.remove('has-value');
        }
      });
    }
  }

  /**
   * Show validation error using SweetAlert2
   */
  function showValidationError(message) {
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        icon: 'warning',
        title: 'Validation Error',
        text: message,
        confirmButtonText: 'OK'
      });
    } else {
      alert(message);
    }
  }

  /**
   * Create Account Link Handler
   */
  function initializeCreateAccountLink() {
    const createAccountLink = document.getElementById('createAccountLink');
    if (createAccountLink) {
      createAccountLink.addEventListener('click', function(e) {
        // Link already points to register.php, no need to prevent default
        console.log('Navigating to registration page');
      });
    }
  }

  /**
   * Handle Forgot Password
   */
  window.handleForgotPassword = function(e) {
    e.preventDefault();
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        icon: 'info',
        title: 'Reset Password',
        text: 'Please contact your system administrator to reset your password.',
        confirmButtonText: 'OK'
      });
    } else {
      alert('Please contact administrator for password reset');
    }
  };

})();
