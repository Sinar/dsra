# Digital Sovereignty Readiness Assessment

A streamlined Digital Sovereignty assessment tool focused on providing organizations with a quick and actionable readiness evaluation.

## Overview

This tool helps organizations evaluate their digital sovereignty posture across 7 critical domains in just 10-15 minutes. **Upon completion, you can download a [PDF report](#pdf-report) for sharing with internal teams or stakeholders.**

The name "viewfinder" reflects the original tool's purpose as a lens into your organization's sovereignty posture. This repository, `sinarproj-dsra`, is maintained by Sinar Project with enhancements for Malaysian civil society context.

> **Note**: The default `main` branch stores no data server-side. The `cso-formsubmission` branch stores assessment results encrypted at rest — see [Data Storage & Security](#data-storage--security-cso-formsubmission-branch) for details.

For more background:
- [Digital Sovereignty Is Illusory Without Open Source and a Trusted Supply Chain](https://www.redhat.com/en/blog/digital-sovereignty-illusory-without-open-source-and-trusted-supply-chain)
- [Introducing the Red Hat Sovereignty Readiness Assessment Tool](https://www.redhat.com/en/blog/how-sovereign-your-strategy-introducing-red-hat-sovereignty-readiness-assessment-tool)

For adding or updating custom assessment questions, see [Adding Custom Questions](#adding-custom-questions).

## Quick Start

**Prerequisite:** Requires [Docker & Docker Compose](https://docs.docker.com/engine/install/ubuntu/).

```bash
git clone https://github.com/Sinar/dsra.git
cd dsra
docker compose up -d --build
```

Then open http://localhost:8080

#### Deploying the Email Submission Branch

```bash
git clone -b cso-formsubmission https://github.com/Sinar/dsra.git
cd dsra
cp .env.example .env
```

Edit `.env` with your configuration:

| Variable | Description | Required |
|---|---|---|
| `MAILER_DSN` | Mailgun API key or SMTP credentials | Yes |
| `MAILER_TO` | Recipient email for assessment results | Yes |
| `MAILER_FROM` | Sender email address | Yes |
| `BASE_URL` | Public URL of this instance (e.g. `https://dsra.example.com`) | Yes (for download links) |
| `GPG_KEY_ID` | GPG key identifier (default: `team@sinarproject.org`) | No |

```bash
# Create data directory for encrypted storage
mkdir -p data

# Build and start
docker compose up -d --build
```

> **First start**: The container automatically generates a GPG key pair on initial startup. The public key is exported to `data/public-key.asc` for verification. No manual steps needed.

> **Persistence**: To preserve GPG keys and stored files across container rebuilds, add this volume to your `docker-compose.yml`:
> ```yaml
> volumes:
>   - ./data:/opt/app-root/src/data
>   - gpg-keys:/opt/app-root/src/.gnupg
> ```
> Without the GPG volume, removing the container loses the private key and existing encrypted files cannot be decrypted.

To update an existing deployment with the latest changes:
```bash
docker compose down
git pull origin cso-formsubmission
docker compose up -d --build
```

> For local development without Docker (composer, PHP built-in server), see the [Local Installation Runbook](RUNBOOK.md).

## Screenshots

### Landing Page
The landing page features the Digital Sovereignty Readiness Assessment.

![Landing Page - Balanced Profile](images/screenshots/landing-page-balanced.png)

### Assessment Page
The assessment questionnaire presents 21 questions across 7 domains with Yes/No/"Don't Know" response options. Progress is auto-saved to browser storage.

![Assessment Page](images/screenshots/assessment-page.png)

### Results Pages
Comprehensive results display showing scoring, maturity level, domain analysis, and actionable recommendations.

![Results Page - Overview](images/screenshots/results-page1.png)

![Results Page - Domain Analysis](images/screenshots/results-page2.png)

![Results Page - Recommendations](images/screenshots/results-page3.png)

### PDF Report
Professional PDF report with scores, domain breakdown, maturity level assessment, and tailored improvement actions.

![PDF Report Sample](images/screenshots/pdf-report-sample.png)

## Features

### Digital Sovereignty Readiness Assessment
- **Quick Assessment**: Complete evaluation in 10-15 minutes
- **7 Critical Domains**: Comprehensive coverage across:
  - Data Sovereignty
  - Technical Sovereignty
  - Operational Sovereignty
  - Assurance Sovereignty
  - Open Source Strategy
  - Executive Oversight
  - Managed Services
- **21 Key Questions**: 2-3 targeted questions per domain
- **9 Industry Profiles**: Balanced, Financial Services, Healthcare, Government, Technology/SaaS, Manufacturing, Telecom, Energy, and Custom with domain-specific weighting
- **Custom Profile Builder**: Adjustable domain weight sliders for tailored assessments
- **Multiple Response Options**: Yes/No/"Don't Know" format
- **Instant Scoring**: Real-time maturity level calculation
- **Maturity Levels**: Foundation, Developing, Strategic, Advanced
- **Actionable Recommendations**: Tailored guidance based on assessment results
- **Research Questions**: Track "Don't Know" responses for follow-up investigation
- **PDF Export**: Professional downloadable reports
- **Progress Auto-Save**: Browser-based session persistence
- **Keyboard Navigation**: Arrow keys for quick navigation, Ctrl+S to save
- **Privacy-First** (default branch): No data collected or stored server-side; all progress persisted in browser localStorage
- **Secure Storage** (cso-formsubmission branch): Assessment results encrypted with GPG at rest in the `data/` directory

## Docker Installation (Recommended)

1. **Clone the repository**:
   ```bash
   git clone https://github.com/Sinar/dsra.git
   cd dsra
   ```

2. **Build and run with Docker Compose**:
   ```bash
   docker compose up -d --build
   ```

3. **Access the application**:
   ```
   http://localhost:8080
   ```

   > **For the email submission feature**: Copy `.env.example` to `.env` and configure `MAILER_DSN` before building (see [Deploying the Email Submission Branch](#quick-start) for details).

   > **For the encrypted storage feature**: The `data/` directory stores GPG-encrypted assessment results. Ensure it is writable by the container (mounted as a volume). GPG keys are auto-generated on first start — mount a volume for `/opt/app-root/src/.gnupg` to retain them across rebuilds.

#### Alternative: Docker without Compose

```bash
docker build -t sinarproj-dsra:latest .
docker run -d -p 8080:8080 --name sinarproj-dsra sinarproj-dsra:latest
```

#### Multi-Architecture Builds

```bash
# Example: Building for both amd64 and arm64
docker buildx build -t sinarproj-dsra:latest . --platform linux/amd64,linux/arm64
```

#### Managing the Container

```bash
# Stop the container
docker stop sinarproj-dsra

# Remove the container
docker rm sinarproj-dsra

# View logs
docker compose logs -f
```

> For local deployment without Docker using PHP, Apache/Nginx, and Composer directly, see the [Local Installation Runbook](RUNBOOK.md).

## Web Server Configuration

### Apache Configuration

**VirtualHost Example** (`/etc/httpd/conf.d/dsra.conf`):
```apache
<VirtualHost *:80>
    ServerName dsra.example.com
    DocumentRoot /var/www/html/dsra

    <Directory /var/www/html/dsra>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted

        # Security headers
        Header always set X-Content-Type-Options "nosniff"
        Header always set X-Frame-Options "SAMEORIGIN"
        Header always set X-XSS-Protection "1; mode=block"
    </Directory>

    # Logging
    ErrorLog /var/log/httpd/dsra-error.log
    CustomLog /var/log/httpd/dsra-access.log combined
</VirtualHost>
```

### Nginx Configuration

**Server Block Example** (`/etc/nginx/conf.d/dsra.conf`):
```nginx
server {
    listen 80;
    server_name dsra.example.com;
    root /var/www/html/dsra;
    index index.php;

    # Security headers
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php-fpm/php-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }

    # Logging
    access_log /var/log/nginx/dsra-access.log;
    error_log /var/log/nginx/dsra-error.log;
}
```

## File Structure

```
dsra/
├── index.php                    # Landing page
├── composer.json                # PHP dependencies
├── docker-compose.yml           # Docker Compose configuration
├── Dockerfile                   # Container build configuration
├── README.md                    # This file
├── RUNBOOK.md                   # Local installation runbook
│
├── ds-qualifier/                # Digital Sovereignty Readiness Assessment
│   ├── index.php               # Assessment questionnaire interface
│   ├── results.php             # Results and recommendations page
│   ├── config.php              # Questions configuration
│   ├── profiles.php            # Industry weighting profiles
│   ├── generate-pdf.php        # PDF report generator
│   ├── css/
│   │   └── ds-qualifier.css    # Assessment-specific styles
│   └── js/
│       └── ds-qualifier.js     # Interactive features & auto-save
│
├── includes/                    # Core backend classes
│   ├── Config.php              # Application configuration
│   ├── Security.php            # Security utilities
│   ├── Logger.php              # Logging functionality
│   └── Exceptions/             # Custom exception classes
│       ├── ViewfinderException.php
│       ├── FileSystemException.php
│       ├── DataValidationException.php
│       ├── ConfigurationException.php
│       └── ViewfinderJsonException.php
│
├── css/                         # Shared stylesheets
│   ├── bootstrap.min.css       # Bootstrap framework
│   ├── brands.css              # Font Awesome brands
│   ├── style.css               # Main application styles
│   ├── tab-dark.css            # Dark theme tab styling
│   ├── patternfly.css          # Red Hat PatternFly design system
│   └── patternfly-addons.css   # PatternFly extensions
│
├── images/                      # Images and logos
│   └── screenshots/             # Documentation screenshots
│       ├── landing-page-balanced.png
│       ├── landing-page-financial.png
│       ├── landing-page-custom.png
│       ├── assessment-page.png
│       ├── results-page1.png
│       ├── results-page2.png
│       ├── results-page3.png
│       ├── pdf-report-sample.png
│       └── cmmi-levels.png
│
├── error-pages/                 # Error handling pages
│   ├── error-handler.php
│   └── templates/
│       ├── system-error.php
│       ├── validation-error.php
│       ├── file-not-found.php
│       └── json-error.php
│
├── .github/                     # GitHub configuration
│   ├── workflows/
│   │   └── build-image.yml     # CI/CD pipeline
│   └── dependabot.yml          # Dependency updates
│
├── logs/                        # Application logs (created at runtime)
│
└── vendor/                      # Composer dependencies (created by composer install)
```

## Usage

### Landing Page
Navigate to the root URL to access the landing page featuring the Digital Sovereignty Readiness Assessment card.

### Taking an Assessment

1. **Select Profile**: Choose from 9 industry profiles or create a custom weighting
2. **Start Assessment**: Click "Start Assessment" button to begin
3. **Answer Questions**: Progress through 7 domains
   - Use Next/Previous buttons to navigate
   - Answer Yes/No or select "Don't Know" for uncertain items
   - Questions are validated before proceeding
   - Progress auto-saves to browser storage
4. **Submit**: Click "Complete Assessment" on the final section
5. **View Results**: Review your maturity level and recommendations
6. **Download Report**: Generate PDF report for stakeholders
7. **Take New Assessment**: Start fresh assessment anytime

### Understanding Results

#### Maturity Levels

The assessment uses a 4-level maturity model based on the [Capability Maturity Model Integration (CMMI)](https://en.wikipedia.org/wiki/Capability_Maturity_Model_Integration) framework.

Based on your score (0-21 points):

- **Foundation (0-5 points)**: Early-stage maturity
  - Ad-hoc processes with minimal sovereignty controls
  - Significant dependencies on external providers
  - Focus: Establish executive awareness and basic policies

- **Developing (6-10 points)**: Growing maturity
  - Basic controls are in place but not yet standardized
  - Projects are planned but processes may not be repeatable organization-wide
  - Focus: Build repeatable practices and implement foundational controls

- **Strategic (11-16 points)**: Mature posture
  - Processes are well characterized, understood, documented, and standardized
  - Digital sovereignty practices are consistent and repeatable across the organization
  - Clear governance structures and policies are in place
  - Focus: Ensure organization-wide consistency and pursue certifications

- **Advanced (17-21 points)**: Leading maturity
  - Continuous improvement through quantitative feedback and innovation
  - Proactive identification and deployment of innovative sovereignty practices
  - Industry-leading posture with thought leadership contributions
  - Focus: Drive innovation and lead industry best practices

#### Results Components

- **Score Breakdown**: Percentage-based maturity indicator
- **Domain Analysis Table**: Shows score and maturity level per domain
  - Progress bars show percentage completion per domain
- **Improvement Actions**: Recommended next steps based on maturity level
- **Domain Insights**: Detailed view of strengths and improvement areas
- **Research Questions**: "Don't Know" responses flagged for further investigation

## Configuration

### Application Settings
Edit `includes/Config.php` to modify:
- Application name and version
- Base paths
- Error handling settings
- Security configuration

### Assessment Questions
Edit `ds-qualifier/config.php` to customize:
- Question text
- Domain definitions
- Tooltips and help text

### Industry Profiles
Edit `ds-qualifier/profiles.php` to customize:
- Domain weighting multipliers
- Profile names and descriptions

## Dependencies

### PHP Requirements
- **PHP**: ^8.1
- **Extensions**: ext-json

### Composer Packages
- **monolog/monolog** (^3.5): Logging framework
- **dompdf/dompdf** (^3.1): PDF report generation

### Frontend Libraries (CDN)
- jQuery 3.6.0
- jQuery UI 1.13.2
- Font Awesome 8.x
- Bootstrap (included locally)
- PatternFly (included locally)

## Security Features

- **Input Validation**: Comprehensive sanitization of all user inputs
- **CSRF Protection**: Session-based CSRF token validation
- **Secure Headers**: X-Content-Type-Options, X-Frame-Options, X-XSS-Protection
- **Path Traversal Prevention**: Secure file path handling
- **Error Logging**: Detailed logging without exposing sensitive data
- **Session Timeout**: Automatic session expiration (1 hour)
- **Secure File Operations**: Atomic file writes with rollback capability

## Comparison with Full Viewfinder

| Feature | Full Viewfinder | DSRA |
|---------|----------------|------|
| Profile Management | ✓ | ✗ |
| Full Maturity Assessments | ✓ | ✗ |
| Readiness Assessment | ✓ | ✓ |
| Digital Sovereignty Quiz | ✓ | ✗ |
| Operation Sovereign Shield | ✓ | ✗ |
| Compliance Framework Mapping | ✓ | ✗ |
| Line of Business Content | ✓ | ✗ |
| Approximate Size | ~100+ MB | ~60-65 MB |

## Data Storage & Security (cso-formsubmission branch)

### What is stored
Assessment results (respondent details, scores, domain breakdown, maturity level) are saved as JSON and CSV files in the `data/` directory after successful email submission.

### Encryption at rest
All files are encrypted with GPG (RSA 4096-bit) using a key pair generated automatically on the first container start. The private key never leaves the server and is **never committed** to version control or the Docker image.

### Access tokens
Each submission generates a unique token embedded in the email. The token:
- Expires **14 days** after creation
- Allows a maximum of **5 downloads**
- Decrypts the file server-side — recipients receive plaintext JSON/CSV

### GPG key management
- Keys are generated in the container's `/opt/app-root/src/.gnupg` on first start
- The public key is exported to `data/public-key.asc` for verification
- **Do not commit** the `data/` directory or GPG key material to version control
- Mount a volume for `/opt/app-root/src/.gnupg` to preserve keys across container rebuilds
- Without the GPG volume, removing the container loses the private key and previously saved files become undecryptable

### Privacy
This branch **collects and stores** respondent PII (position, organisation, size, state). Ensure your data handling complies with applicable privacy regulations and obtain necessary consent from respondents.

## Troubleshooting

**Issue**: Port 8080 already in use
```bash
sudo lsof -i :8080
```
Or change the host port in `docker-compose.yml` (e.g., `"8081:8080"`).

**Issue**: Container name already exists
```bash
docker stop sinarproj-dsra
docker rm sinarproj-dsra
docker compose up -d
```

**Issue**: Container exits immediately
```bash
docker compose logs -f
docker compose up -d --build
```

**Issue**: PDF generation fails
```bash
docker compose exec viewfinder composer show dompdf/dompdf
docker compose up -d --build
```

**Issue**: Viewing logs
```bash
docker compose logs -f
docker compose logs viewfinder
```

**Issue**: Download link shows "Link expired"
The 14-day validity period has passed. The token is automatically removed from the system. Contact the Sinar Project team to request a new download link.

**Issue**: Download link shows "Download limit reached"
The file has been downloaded 5 times. Contact the Sinar Project team for a new download link.

**Issue**: Container crash-loops with "chmod: Operation not permitted" on `.gnupg`
The `gpg-keys` named volume was mounted with root ownership, which prevents UID 1001 from writing. Fixed in the Dockerfile by pre-creating the `.gnupg` directory during the build (so Docker volume initialization copies it with correct ownership). If you still hit this:
```bash
docker compose down -v
docker compose up -d --build
```

**Issue**: Container crash-loops with "public-key.asc: Permission denied"
The `data/` bind mount on the host needs to be writable by the container's UID 1001:
```bash
chmod 777 data/
docker compose restart
```

**Issue**: Email sends but no encrypted files found in `data/`
Check the container logs for GPG errors:
```bash
docker compose logs viewfinder | grep -i gpg
```
Ensure `data/` is writable by UID 1001 (`chmod 777 data/`), `GPG_KEY_ID` is set correctly in `.env`, and `gpg` is installed (verify with `docker compose exec viewfinder gpg --version`).

**Issue**: "GPG key not found" or "encryption failed" in logs
The GPG key generation may have failed on first start. Run manually:
```bash
docker compose exec viewfinder /opt/app-root/src/docker-entrypoint.sh
```
Or check the key exists:
```bash
docker compose exec viewfinder gpg --list-keys
```

## Development

### Adding Custom Questions

1. Edit `ds-qualifier/config.php`
2. Add questions to the appropriate domain
3. Follow the existing format:
   ```php
   'questions' => [
       [
           'id' => 'unique-id',
           'text' => 'Your question text?',
           'tooltip' => 'Helpful explanation'
       ]
   ]
   ```

### Customizing Styling

- **Main application**: Edit `css/style.css`
- **Assessment interface**: Edit `ds-qualifier/css/ds-qualifier.css`
- **Dark theme**: Edit `css/tab-dark.css`

### Modifying Maturity Levels

Edit `ds-qualifier/results.php` to adjust:
- Score thresholds
- Maturity level names
- Recommendations per level

## Support

This is a community-supported open source project. For issues, questions, or feature requests:

- **GitHub Issues**: https://github.com/Sinar/dsra/issues
- **GitHub Discussions**: https://github.com/Sinar/dsra/discussions

## License

Apache-2.0 License

## Disclaimer

This application is provided for informational purposes only. The information is provided "as is" with no guarantee or warranty of accuracy, completeness, or fitness for a particular purpose. Users should conduct their own validation and testing before relying on assessment results for decision-making.

---

**Digital Sovereignty Readiness Assessment (DSRA)** - Streamlined Digital Sovereignty Readiness Assessment

Version: 1.0.0
