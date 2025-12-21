/**
 * Guard Configuration
 * Base URL and API endpoint configuration
 */

(function() {
  'use strict';
  
  // Get base URL from page
  const scriptPath = window.location.pathname;
  const baseUrl = scriptPath.substring(0, scriptPath.indexOf('/guard'));
  
  // Set global configuration
  window.baseUrl = baseUrl;
  window.vehiscanConfig = {
    baseUrl: baseUrl,
    apiEndpoints: {
      homeowners: `${baseUrl}/guard/pages/fetch_homeowners.php`,
      logs: `${baseUrl}/guard/pages/fetch_logs.php`
    },
    imagePaths: {
      homeowners: `${baseUrl}/uploads/homeowners`,
      vehicles: `${baseUrl}/uploads/vehicles`
    },
    debug: false // Set to true for debugging
  };
  
  // Log configuration if debug is enabled
  // Use global logger provided by `logger.js`
  __vsLog('[Guard Config]', window.vehiscanConfig);
})();
