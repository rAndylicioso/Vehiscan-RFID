# Configuration Files

This folder contains configuration files for the VehiScan RFID system.

## Files

### tailwind.config.js
**Tailwind CSS Configuration**
- **Purpose**: Defines Tailwind CSS customization (colors, animations, content paths)
- **Used By**: Build scripts in `/scripts` and npm build commands
- **Location**: Moved here from root for better organization
- **Reference**: Build commands use `--config ./config/tailwind.config.js`

## Important Notes

- The **active** `.env` and `db.php` files are in the project root (not in this folder)
- `tailwind.config.js` is the active config file used by the build system
- Build scripts automatically reference `./config/tailwind.config.js`

## Updating Configuration

### Tailwind Config
Edit `config/tailwind.config.js` to:
- Add new content paths
- Customize colors and theme
- Add plugins
- Configure purge options

After editing, rebuild CSS with:
```bash
npm run build
```

### Database Config
Edit the **root** `db.php` file for database changes. The copy here is reference only.

### Environment Variables
Edit the **root** `.env` file for environment changes. The copy here is reference only.
