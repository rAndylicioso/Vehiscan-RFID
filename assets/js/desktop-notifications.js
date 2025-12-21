/**
 * Desktop Notification System for Guard Panel
 * Location: assets/js/desktop-notifications.js
 */

class DesktopNotificationManager {
  constructor() {
    this.permission = Notification.permission;
    this.enabled = false;
    this.soundEnabled = false; // Disabled until sound files are added
    this.sounds = {
      // Temporarily disabled - sound files not yet created
      // granted: new Audio('../assets/sounds/access-granted.mp3'),
      // denied: new Audio('../assets/sounds/access-denied.mp3'),
      // notification: new Audio('../assets/sounds/notification.mp3')
    };
    
    this.init();
  }
  
  async init() {
    // Check browser support
    if (!('Notification' in window)) {
      console.warn('Desktop notifications not supported');
      return;
    }
    
    // Load saved preferences
    this.enabled = localStorage.getItem('notifications_enabled') === 'true';
    this.soundEnabled = localStorage.getItem('sound_enabled') !== 'false';
    
    // Request permission if needed
    if (this.enabled && this.permission === 'default') {
      await this.requestPermission();
    }
    
    this.createControls();
  }
  
  async requestPermission() {
    try {
      this.permission = await Notification.requestPermission();
      return this.permission === 'granted';
    } catch (error) {
      console.error('Error requesting notification permission:', error);
      return false;
    }
  }
  
  createControls() {
    // Create notification toggle in UI
    const controls = document.querySelector('.controls') || document.querySelector('.topbar');
    if (!controls) return;
    
    const notifButton = document.createElement('button');
    notifButton.id = 'notificationToggle';
    notifButton.className = 'btn-icon';
    notifButton.title = 'Toggle desktop notifications';
    notifButton.innerHTML = this.enabled ? '🔔' : '🔕';
    notifButton.setAttribute('aria-label', 'Toggle notifications');
    
    notifButton.addEventListener('click', () => this.toggle());
    
    controls.insertBefore(notifButton, controls.firstChild);
  }
  
  async toggle() {
    if (!this.enabled) {
      // Enable notifications
      if (this.permission !== 'granted') {
        const granted = await this.requestPermission();
        if (!granted) {
          window.toast?.error('Notification permission denied');
          return;
        }
      }
      
      this.enabled = true;
      localStorage.setItem('notifications_enabled', 'true');
      document.getElementById('notificationToggle').innerHTML = '🔔';
      window.toast?.success('Desktop notifications enabled');
    } else {
      // Disable notifications
      this.enabled = false;
      localStorage.setItem('notifications_enabled', 'false');
      document.getElementById('notificationToggle').innerHTML = '🔕';
      window.toast?.info('Desktop notifications disabled');
    }
  }
  
  /**
   * Send a notification
   * @param {string} title - Notification title
   * @param {object} options - Notification options
   */
  send(title, options = {}) {
    if (!this.enabled || this.permission !== 'granted') {
      return null;
    }
    
    const defaultOptions = {
      icon: '../assets/icon.png',
      badge: '../assets/badge.png',
      requireInteraction: false,
      ...options
    };
    
    try {
      const notification = new Notification(title, defaultOptions);
      
      notification.onclick = () => {
        window.focus();
        notification.close();
        if (options.onClick) {
          options.onClick();
        }
      };
      
      // Auto-close after duration
      if (options.duration) {
        setTimeout(() => notification.close(), options.duration);
      }
      
      return notification;
    } catch (error) {
      console.error('Error showing notification:', error);
      return null;
    }
  }
  
  /**
   * Notify about new access log
   * @param {object} log - Access log data
   */
  notifyAccessLog(log) {
    const isGranted = log.status === 'Access Granted';
    const icon = isGranted ? '✅' : '❌';
    const sound = isGranted ? 'granted' : 'denied';
    
    const title = `${icon} New Vehicle Access`;
    const body = `Plate: ${log.plate_number}\nStatus: ${log.status}\nTime: ${log.log_time}`;
    
    this.send(title, {
      body: body,
      tag: 'access-log',
      requireInteraction: true,
      duration: 10000,
      onClick: () => {
        // Focus on the log entry
        const logEntry = document.querySelector(`[data-log-id="${log.log_id}"]`);
        if (logEntry) {
          logEntry.scrollIntoView({ behavior: 'smooth', block: 'center' });
          logEntry.classList.add('highlight');
          setTimeout(() => logEntry.classList.remove('highlight'), 2000);
        }
      }
    });
    
    this.playSound(sound);
  }
  
  /**
   * Notify about visitor pass
   * @param {object} pass - Visitor pass data
   */
  notifyVisitorPass(pass) {
    const title = '🎫 Visitor Pass Created';
    const body = `Visitor: ${pass.visitor_name}\nPlate: ${pass.visitor_plate}\nValid until: ${pass.valid_until}`;
    
    this.send(title, {
      body: body,
      tag: 'visitor-pass',
      duration: 8000
    });
    
    this.playSound('notification');
  }
  
  /**
   * Notify about expiring passes
   * @param {number} count - Number of expiring passes
   */
  notifyExpiringPasses(count) {
    const title = '⚠️ Expiring Visitor Passes';
    const body = `${count} visitor pass${count > 1 ? 'es' : ''} expiring soon`;
    
    this.send(title, {
      body: body,
      tag: 'expiring-passes',
      duration: 10000,
      requireInteraction: true
    });
    
    this.playSound('notification');
  }
  
  /**
   * Play notification sound
   * @param {string} type - Sound type: 'granted', 'denied', 'notification'
   */
  playSound(type) {
    if (!this.soundEnabled) return;
    
    const sound = this.sounds[type];
    if (sound) {
      sound.currentTime = 0;
      sound.volume = 0.5;
      sound.play().catch(err => {
        console.warn('Could not play notification sound:', err);
      });
    }
  }
  
  /**
   * Toggle sound on/off
   */
  toggleSound() {
    this.soundEnabled = !this.soundEnabled;
    localStorage.setItem('sound_enabled', this.soundEnabled.toString());
    window.toast?.info(`Notification sounds ${this.soundEnabled ? 'enabled' : 'disabled'}`);
  }
  
  /**
   * Test notification
   */
  test() {
    this.send('🧪 Test Notification', {
      body: 'If you can see this, notifications are working!',
      duration: 5000
    });
    this.playSound('notification');
  }
}

// Initialize globally
window.desktopNotifications = new DesktopNotificationManager();

// Helper function for backward compatibility
window.notifyNewAccess = (log) => {
  window.desktopNotifications?.notifyAccessLog(log);
};
