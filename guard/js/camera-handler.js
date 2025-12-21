// guard/js/camera-handler.js - Floating Camera Window Handler

(function() {
  'use strict';
  
  // Wait for DOM to be ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFloatingCamera);
  } else {
    initFloatingCamera();
  }
  
  function initFloatingCamera() {
    const floatingToggle = document.getElementById('floatingCameraToggle');
    const floatingWindow = document.getElementById('floatingCameraWindow');
    const closeBtn = document.getElementById('closeCameraBtn');
    const minimizeBtn = document.getElementById('minimizeCameraBtn');
    const header = document.getElementById('cameraWindowHeader');
    
    if (!floatingToggle || !floatingWindow || !closeBtn || !minimizeBtn || !header) {
      console.warn('[CAMERA] Missing floating camera elements');
      return;
    }
    
    let cameraStream = null;
    let isDragging = false;

    // Toggle floating window
    floatingToggle.addEventListener('click', () => {
      floatingWindow.classList.toggle('hidden');
      if (!floatingWindow.classList.contains('hidden') && !cameraStream) {
        // Auto-start camera when opening
        setTimeout(() => {
          const toggleBtn = document.getElementById('floatingToggleCamera');
          if (toggleBtn) toggleBtn.click();
        }, 100);
      }
    });

    // Close window
    closeBtn.addEventListener('click', () => {
      floatingWindow.classList.add('hidden');
      stopFloatingCamera();
    });

    // Minimize window
    minimizeBtn.addEventListener('click', () => {
      floatingWindow.classList.add('hidden');
    });

    // Dragging functionality
    let startX, startY, initialLeft, initialTop;
    
    header.addEventListener('mousedown', (e) => {
      if (e.target.closest('button')) return; // Don't drag when clicking buttons
      isDragging = true;
      
      // Get current position
      const rect = floatingWindow.getBoundingClientRect();
      initialLeft = rect.left;
      initialTop = rect.top;
      startX = e.clientX;
      startY = e.clientY;
      
      header.style.cursor = 'grabbing';
      floatingWindow.style.transition = 'none';
    });

    document.addEventListener('mousemove', (e) => {
      if (isDragging) {
        const deltaX = e.clientX - startX;
        const deltaY = e.clientY - startY;
        const left = initialLeft + deltaX;
        const top = initialTop + deltaY;
        
        // Keep window within viewport
        const maxLeft = window.innerWidth - floatingWindow.offsetWidth;
        const maxTop = window.innerHeight - floatingWindow.offsetHeight;
        
        floatingWindow.style.left = Math.max(0, Math.min(left, maxLeft)) + 'px';
        floatingWindow.style.top = Math.max(0, Math.min(top, maxTop)) + 'px';
        floatingWindow.style.bottom = 'auto';
        floatingWindow.style.right = 'auto';
      }
    });

    document.addEventListener('mouseup', () => {
      if (isDragging) {
        isDragging = false;
        header.style.cursor = 'move';
        floatingWindow.style.transition = '';
      }
    });

    // Camera functionality
    const video = document.getElementById('floatingCamera');
    const canvas = document.getElementById('floatingCameraCanvas');
    const overlay = document.getElementById('floatingCameraOverlay');
    const toggleBtn = document.getElementById('floatingToggleCamera');
    const snapshotBtn = document.getElementById('floatingSnapshotBtn');
    const switchBtn = document.getElementById('floatingSwitchCameraBtn');
    const status = document.getElementById('floatCameraStatus');
    const btnText = document.getElementById('floatingCameraBtnText');
    const timestamp = document.getElementById('floatingTimestamp');
    const flash = document.getElementById('floatingSnapshotFlash');

    if (!video || !toggleBtn) {
      console.warn('[CAMERA] Missing camera video or toggle button');
      return;
    }

    let availableCameras = [];
    let currentCameraIndex = 0;

    async function enumerateCameras() {
      try {
        const devices = await navigator.mediaDevices.enumerateDevices();
        availableCameras = devices.filter(d => d.kind === 'videoinput');
        if (availableCameras.length > 1 && switchBtn) {
          switchBtn.classList.remove('hidden');
        }
      } catch (error) {
        __vsLog('[CAMERA] Error enumerating cameras:', error);
      }
    }

    async function startFloatingCamera(deviceId = null) {
      try {
        const constraints = {
          video: deviceId ? { deviceId: { exact: deviceId } } : { facingMode: 'environment' },
          audio: false
        };

        cameraStream = await navigator.mediaDevices.getUserMedia(constraints);
        video.srcObject = cameraStream;

        if (overlay) overlay.classList.add('hidden');
        if (timestamp) timestamp.classList.remove('hidden');
        if (snapshotBtn) snapshotBtn.classList.remove('hidden');
        
        if (status) {
          const statusDot = status.querySelector('span:first-child');
          const statusText = status.querySelector('span:last-child');
          if (statusDot) {
            statusDot.classList.remove('bg-gray-300');
            statusDot.classList.add('bg-green-500');
          }
          if (statusText) statusText.textContent = 'Live';
        }
        
        if (btnText) btnText.textContent = 'Stop';
        
        updateTimestamp();
        
        if (window.toast) {
          window.toast.success('📹 Camera started');
        }
      } catch (error) {
        __vsLog('[CAMERA] Camera error:', error);
        if (window.toast) {
          window.toast.error('❌ Camera access denied');
        }
      }
    }

    function stopFloatingCamera() {
      if (cameraStream) {
        cameraStream.getTracks().forEach(track => track.stop());
        cameraStream = null;
        video.srcObject = null;

        if (overlay) overlay.classList.remove('hidden');
        if (timestamp) timestamp.classList.add('hidden');
        if (snapshotBtn) snapshotBtn.classList.add('hidden');
        
        if (status) {
          const statusDot = status.querySelector('span:first-child');
          const statusText = status.querySelector('span:last-child');
          if (statusDot) {
            statusDot.classList.remove('bg-green-500');
            statusDot.classList.add('bg-gray-300');
          }
          if (statusText) statusText.textContent = 'Offline';
        }
        
        if (btnText) btnText.textContent = 'Start';
        
        if (window.toast) {
          window.toast.info('📹 Camera stopped');
        }
      }
    }

    // Expose floating camera APIs for other modules
    window.stopFloatingCamera = stopFloatingCamera;
    window.startFloatingCamera = startFloatingCamera;

    function updateTimestamp() {
      if (!cameraStream || !timestamp) return;
      const now = new Date();
      timestamp.textContent = now.toLocaleTimeString();
      setTimeout(updateTimestamp, 1000);
    }

    function takeSnapshot() {
      if (!cameraStream || !canvas) return;

      canvas.width = video.videoWidth;
      canvas.height = video.videoHeight;
      const ctx = canvas.getContext('2d');
      ctx.drawImage(video, 0, 0);

      // Flash effect
      if (flash) {
        flash.classList.remove('hidden');
        setTimeout(() => flash.classList.add('hidden'), 150);
      }

      // Download snapshot
      canvas.toBlob(blob => {
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `snapshot_${Date.now()}.png`;
        a.click();
        URL.revokeObjectURL(url);
      });

      if (window.toast) {
        window.toast.success('📸 Snapshot saved');
      }
    }

    async function switchCamera() {
      if (availableCameras.length <= 1) return;
      
      currentCameraIndex = (currentCameraIndex + 1) % availableCameras.length;
      const deviceId = availableCameras[currentCameraIndex].deviceId;
      
      stopFloatingCamera();
      await startFloatingCamera(deviceId);
    }

    // Event listeners
    toggleBtn.addEventListener('click', async () => {
      if (cameraStream) {
        stopFloatingCamera();
      } else {
        await enumerateCameras();
        await startFloatingCamera();
      }
    });

    if (snapshotBtn) snapshotBtn.addEventListener('click', takeSnapshot);
    if (switchBtn) switchBtn.addEventListener('click', switchCamera);

    // Initialize
    enumerateCameras();
  }
})();
