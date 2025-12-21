@echo off
REM Quick Build Script for VehiScan RFID
REM Double-click this file to rebuild Tailwind CSS

echo ========================================
echo   VehiScan RFID - Quick CSS Build
echo ========================================
echo.

echo Building CSS...
cd ..
node node_modules/tailwindcss/lib/cli.js -i ./assets/css/tailwind-input.css -o ./assets/css/tailwind.css --minify --config ./config/tailwind.config.js

echo.
echo ========================================
echo   Build Complete!
echo ========================================
echo.
echo Press any key to exit...
pause > nul
