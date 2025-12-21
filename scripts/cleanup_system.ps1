# System Cleanup Script
# Run this to remove unnecessary testing files and old backups
# BACKUP YOUR DATABASE BEFORE RUNNING!

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "VehiScan System Cleanup Script" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$baseDir = "C:\xampp\htdocs\Vehiscan-RFID"

# Files to delete (testing files with hardcoded IPs)
$filesToDelete = @(
    "_testing\regenerate_all_qr_codes.php",
    "_testing\test_visitor_pass_urls.php",
    "_testing\test_https_redirect.php",
    "visitor\qr_test.php",
    "visitor\view_pass.php.backup",
    "dev-tools\homeowner_registration_backup.php",
    "dev-tools\guard_panel_improved_backup.js"
)

# Folders to delete (old backups)
$foldersToDelete = @(
    "backups\archived_backups_2025-11-20_141135"
)

Write-Host "This script will delete the following files:" -ForegroundColor Yellow
Write-Host ""

Write-Host "FILES:" -ForegroundColor White
foreach ($file in $filesToDelete) {
    $fullPath = Join-Path $baseDir $file
    if (Test-Path $fullPath) {
        Write-Host "  [EXISTS] $file" -ForegroundColor Green
    } else {
        Write-Host "  [MISSING] $file" -ForegroundColor DarkGray
    }
}

Write-Host ""
Write-Host "FOLDERS:" -ForegroundColor White
foreach ($folder in $foldersToDelete) {
    $fullPath = Join-Path $baseDir $folder
    if (Test-Path $fullPath) {
        $size = (Get-ChildItem $fullPath -Recurse | Measure-Object -Property Length -Sum).Sum / 1MB
        Write-Host "  [EXISTS] $folder (Size: $([math]::Round($size, 2)) MB)" -ForegroundColor Green
    } else {
        Write-Host "  [MISSING] $folder" -ForegroundColor DarkGray
    }
}

Write-Host ""
$confirmation = Read-Host "Do you want to proceed with deletion? (yes/no)"

if ($confirmation -ne "yes") {
    Write-Host "Cleanup cancelled." -ForegroundColor Red
    exit
}

Write-Host ""
Write-Host "Cleaning up..." -ForegroundColor Yellow

# Delete files
$deletedCount = 0
foreach ($file in $filesToDelete) {
    $fullPath = Join-Path $baseDir $file
    if (Test-Path $fullPath) {
        try {
            Remove-Item $fullPath -Force
            Write-Host "  [DELETED] $file" -ForegroundColor Green
            $deletedCount++
        } catch {
            Write-Host "  [FAILED] $file - $_" -ForegroundColor Red
        }
    }
}

# Delete folders
foreach ($folder in $foldersToDelete) {
    $fullPath = Join-Path $baseDir $folder
    if (Test-Path $fullPath) {
        try {
            Remove-Item $fullPath -Recurse -Force
            Write-Host "  [DELETED] $folder" -ForegroundColor Green
            $deletedCount++
        } catch {
            Write-Host "  [FAILED] $folder - $_" -ForegroundColor Red
        }
    }
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Cleanup Complete!" -ForegroundColor Green
Write-Host "Items deleted: $deletedCount" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "1. Test the system at http://localhost/Vehiscan-RFID" -ForegroundColor White
Write-Host "2. Create a visitor pass to test QR codes" -ForegroundColor White
Write-Host "3. Review FINAL_SYSTEM_REPORT.md for deployment guide" -ForegroundColor White
