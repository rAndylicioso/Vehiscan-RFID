(function(){
  'use strict';

  function isDebug() {
    return !!(window.vehiscanConfig && window.vehiscanConfig.debug);
  }

  const vsLogger = {
    log: (...args) => { if (isDebug()) console.log(...args); },
    info: (...args) => { if (isDebug()) console.info(...args); },
    warn: (...args) => { console.warn(...args); },
    error: (...args) => { console.error(...args); }
  };

  // Expose global helpers used across guard scripts
  window.vsLogger = vsLogger;
  window.__vsLog = (...args) => vsLogger.log(...args);
  window.vsLog = window.__vsLog;
})();
