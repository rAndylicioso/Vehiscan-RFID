# Production Build Script for VehiScan RFID
Write-Host "========================================"
Write-Host "  VehiScan RFID - Production Build"
Write-Host "========================================"
Write-Host ""

# Clean previous build
Write-Host "[1/4] Cleaning previous build..."
Remove-Item .\assets\css\tailwind.css -ErrorAction SilentlyContinue
Write-Host "Done"
Write-Host ""

# Update dependencies
Write-Host "[2/4] Updating browserslist..."
npx update-browserslist-db@latest
Write-Host ""

# Build CSS
Write-Host "[3/4] Building production CSS..."
npm run build
Write-Host ""

# Verify output
Write-Host "[4/4] Verifying output..."
$cssFile = Get-Item .\assets\css\tailwind.css -ErrorAction Stop
$sizeKB = [math]::Round($cssFile.Length / 1KB, 2)
Write-Host "File: $($cssFile.Name)"
Write-Host "Size: $sizeKB KB"
Write-Host "Modified: $($cssFile.LastWriteTime)"
Write-Host ""

Write-Host "========================================"
Write-Host "BUILD SUCCESSFUL!"
Write-Host "========================================"
Write-Host ""
Write-Host "Next steps:"
Write-Host "1. Test all pages in your browser"
Write-Host "2. Check responsive design"
Write-Host "3. Deploy to production server"
Write-Host ""
