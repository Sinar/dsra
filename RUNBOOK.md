# Local Installation Runbook

Instructions for deploying the Digital Sovereignty Readiness Assessment without Docker, using PHP and a web server directly.

> For the preferred Docker-based setup, see the main [README](README.md).

## Prerequisites

- PHP 8.1 or higher
- Apache or Nginx web server
- Composer (for dependency management)

## Local Installation

1. **Clone the repository**:
   ```bash
   git clone https://github.com/Sinar/dsra.git
   cd dsra
   ```

2. **Install dependencies**:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

   **Note**: If you encounter a "composer.lock does not contain valid JSON" error, the lock file may have been corrupted during file transfer. Fix it by running:
   ```bash
   rm composer.lock
   composer install --no-dev --optimize-autoloader
   ```

3. **Set file permissions**:
   ```bash
   # Set ownership (adjust user/group for your system)
   sudo chown -R apache:apache /var/www/html/dsra

   # Set directory permissions
   sudo chmod 755 /var/www/html/dsra
   sudo chmod 775 /var/www/html/dsra/logs

   # Set file permissions
   find /var/www/html/dsra -type f -exec chmod 644 {} \;
   ```

4. **Configure web server**:
   See [Web Server Configuration](#web-server-configuration) in the main README for Apache and Nginx setup examples.

5. **Access the application**:
   ```
   http://your-server/dsra
   ```
