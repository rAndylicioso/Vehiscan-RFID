# Build Scripts

This folder contains build and deployment scripts for the VehiScan RFID system.

## Available Scripts

### build.bat
**Quick Windows Build Script**
- **Purpose**: Rebuild Tailwind CSS quickly on Windows
- **Usage**: Double-click the file or run `.\scripts\build.bat` from root
- **Output**: Generates minified `assets/css/tailwind.css`
- **Build Time**: ~5-6 seconds

### build-production.ps1
**PowerShell Production Build Script**
- **Purpose**: Advanced PowerShell build script with additional options
- **Usage**: `.\scripts\build-production.ps1` from root
- **Requirements**: PowerShell 5.1 or higher

## NPM Scripts (Recommended)

For most build operations, use npm scripts from the project root:

```bash
# Development mode with file watching
npm run dev

# Production build (minified)
npm run build

# Production build (alias)
npm run build:prod

# Update browser compatibility database
npm run update-db
```

## Build Configuration

The build process uses:
- **Config**: `config/tailwind.config.js`
- **Source**: `assets/css/tailwind-input.css`
- **Output**: `assets/css/tailwind.css`

## Notes

- All scripts must be run from the project root directory
- Build output is auto-generated and should not be edited manually
- The config file was moved to `/config` folder for better organization
