// guard/js/main-camera-handler.js - Main Camera Page Handler

(function() {
  'use strict';
  // Use global logger provided by `logger.js`
  
  // Wait for DOM to be ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMainCamera);
  } else {
    initMainCamera();
  }
  
  function initMainCamera() {
    let cameraStream = null;
    let availableCameras = [];
    let currentCameraIndex = 0;
    
    const video = document.getElementById('liveCamera');
    const canvas = document.getElementById('cameraCanvas');
    const overlay = document.getElementById('cameraOverlay');
    const toggleBtn = document.getElementById('toggleCamera');
    const snapshotBtn = document.getElementById('snapshotBtn');
    const switchBtn = document.getElementById('switchCameraBtn');
    const cameraSelect = document.getElementById('cameraSelect');
    const fullscreenBtn = document.getElementById('fullscreenCamera');
    const status = document.getElementById('cameraStatus');
    const secondaryControls = document.getElementById('secondaryControls');
    const cameraTimestamp = document.getElementById('cameraTimestamp');
    const snapshotFlash = document.getElementById('snapshotFlash');
    
    if (!video || !toggleBtn) {
      console.warn('[MAIN-CAMERA] Camera elements not found');
      return;
    }
    
    async function enumerateCameras() {
      try {
        // Only enumerate if mediaDevices is available
        if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) {
          __vsLog('[MAIN-CAMERA] Camera enumeration not supported');
          return;
        }
        
        const devices = await navigator.mediaDevices.enumerateDevices();
        availableCameras = devices.filter(device => device.kind === 'videoinput');
        
        if (availableCameras.length > 1) {
          if (cameraSelect) {
            cameraSelect.classList.remove('hidden');
            cameraSelect.innerHTML = '<option value="">Select Camera...</option>';
            availableCameras.forEach((camera, index) => {
              const option = document.createElement('option');
              option.value = index;
              option.textContent = camera.label || `Camera ${index + 1}`;
              cameraSelect.appendChild(option);
            });
          }
          if (switchBtn) switchBtn.classList.remove('hidden');
        }
      } catch (error) {
        // Silently fail - camera enumeration is not critical on page load
        __vsLog('[MAIN-CAMERA] Camera enumeration skipped');
      }
    }
    
    async function startCamera(deviceId = null) {
      try {
        const constraints = {
          video: deviceId ? { deviceId: { exact: deviceId } } : { facingMode: 'environment' },
          audio: false
        };
        
        cameraStream = await navigator.mediaDevices.getUserMedia(constraints);
        video.srcObject = cameraStream;
        
        if (overlay) overlay.classList.add('hidden');
        
        if (status) {
          const statusDot = status.querySelector('span:first-child');
          const statusText = status.querySelector('span:last-child');
          if (statusDot) {
            statusDot.classList.remove('bg-gray-400');
            statusDot.classList.add('bg-green-500');
          }
          if (statusText) statusText.textContent = 'Live';
        }
        
        const btnText = toggleBtn.querySelector('#cameraBtnText');
        const powerIcon = toggleBtn.querySelector('#powerIcon');
        if (btnText) btnText.textContent = 'Stop Camera';
        if (powerIcon) {
          powerIcon.classList.remove('fa-power-off');
          powerIcon.classList.add('fa-stop');
        }
        
        if (secondaryControls) secondaryControls.classList.remove('hidden');
        if (fullscreenBtn) fullscreenBtn.classList.remove('hidden');
        if (cameraTimestamp) cameraTimestamp.classList.remove('hidden');
        
        updateTimestamp();
        await enumerateCameras();
        
        if (window.toast) {
          window.toast.success('📹 Camera started');
        }
      } catch (error) {
        // Only log non-permission errors to reduce console spam
        if (error.name !== 'NotAllowedError' && error.name !== 'PermissionDeniedError') {
          console.error('[MAIN-CAMERA] Camera error:', error);
        } else {
          __vsLog('[MAIN-CAMERA] Camera permission denied by user');
        }
        
        let errorMsg = '❌ Camera access denied. Please allow camera access in your browser.';
        if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
          errorMsg = '❌ No camera found. Please connect a camera device.';
        } else if (error.name === 'NotReadableError' || error.name === 'TrackStartError') {
          errorMsg = '❌ Camera is already in use by another application.';
        }
        
        if (window.toast) {
          window.toast.error(errorMsg);
        }
      }
    }
    
    function stopCamera() {
      if (cameraStream) {
        cameraStream.getTracks().forEach(track => track.stop());
        cameraStream = null;
        video.srcObject = null;
        
        if (overlay) overlay.classList.remove('hidden');
        
        if (status) {
          const statusDot = status.querySelector('span:first-child');
          const statusText = status.querySelector('span:last-child');
          if (statusDot) {
            statusDot.classList.remove('bg-green-500');
            statusDot.classList.add('bg-gray-400');
          }
          if (statusText) statusText.textContent = 'Offline';
        }
        
        const btnText = toggleBtn.querySelector('#cameraBtnText');
        const powerIcon = toggleBtn.querySelector('#powerIcon');
        if (btnText) btnText.textContent = 'Start Camera';
        if (powerIcon) {
          powerIcon.classList.remove('fa-stop');
          powerIcon.classList.add('fa-power-off');
        }
        
        if (secondaryControls) secondaryControls.classList.add('hidden');
        if (fullscreenBtn) fullscreenBtn.classList.add('hidden');
        if (cameraTimestamp) cameraTimestamp.classList.add('hidden');
        
        if (window.toast) {
          window.toast.info('📹 Camera stopped');
        }
      }
    }
    
    function updateTimestamp() {
      if (!cameraStream || !cameraTimestamp) return;
      const now = new Date();
      cameraTimestamp.textContent = now.toLocaleTimeString();
      setTimeout(updateTimestamp, 1000);
    }
    
    function takeSnapshot() {
      if (!cameraStream || !canvas) return;
      
      canvas.width = video.videoWidth;
      canvas.height = video.videoHeight;
      const ctx = canvas.getContext('2d');
      ctx.drawImage(video, 0, 0);
      
      // Flash effect
      if (snapshotFlash) {
        snapshotFlash.classList.remove('hidden');
        setTimeout(() => snapshotFlash.classList.add('hidden'), 150);
      }
      
      // Download snapshot
      canvas.toBlob(blob => {
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `camera_snapshot_${Date.now()}.png`;
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
      
      stopCamera();
      await startCamera(deviceId);
    }
    
    function toggleFullscreen() {
      if (!document.fullscreenElement) {
        video.requestFullscreen().catch(err => {
          console.error('[MAIN-CAMERA] Fullscreen error:', err);
        });
      } else {
        document.exitFullscreen();
      }
    }
    
    // Event listeners
    toggleBtn.addEventListener('click', async () => {
      if (cameraStream) {
        stopCamera();
      } else {
        await enumerateCameras();
        await startCamera();
      }
    });
    
    if (snapshotBtn) snapshotBtn.addEventListener('click', takeSnapshot);
    if (switchBtn) switchBtn.addEventListener('click', switchCamera);
    if (fullscreenBtn) fullscreenBtn.addEventListener('click', toggleFullscreen);
    
    if (cameraSelect) {
      cameraSelect.addEventListener('change', async (e) => {
        const index = parseInt(e.target.value);
        if (!isNaN(index) && availableCameras[index]) {
          currentCameraIndex = index;
          const deviceId = availableCameras[index].deviceId;
          stopCamera();
          await startCamera(deviceId);
        }
      });
    }
    
    // Don't enumerate cameras on page load - only when user clicks button
    __vsLog('[MAIN-CAMERA] Camera handler initialized (cameras will be enumerated when started)');

    // Expose start/stop APIs for other modules (guard_side.js may call these)
    window.startCamera = startCamera;
    window.stopCamera = stopCamera;
    window.takeCameraSnapshot = takeSnapshot;
  }
})();
