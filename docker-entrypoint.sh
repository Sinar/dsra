#!/bin/bash
# ==============================================================================
# Docker Entrypoint for Sinar Project DSRA
# ==============================================================================
# Generates a GPG key pair on first start for encrypting assessment results
# at rest, then starts the PHP development server.
#
# The GPG key is NOT committed to version control or the Docker image — it is
# generated at runtime and lives only in the container's ~/.gnupg directory.
# Mount a volume for /opt/app-root/src/.gnupg to persist across rebuilds.
# ==============================================================================

set -e

GPG_KEY_ID="${GPG_KEY_ID:-team@sinarproject.org}"
DATA_DIR="${DATA_DIR:-/opt/app-root/src/data}"

# Create data directory if it doesn't exist
mkdir -p "$DATA_DIR"

# Generate GPG key pair on first start
if [ ! -f "$DATA_DIR/.gpg-initialized" ] || ! gpg --list-keys "$GPG_KEY_ID" >/dev/null 2>&1; then
    echo "Generating GPG key pair for $GPG_KEY_ID..."

    # Ensure .gnupg directory exists with correct permissions for user 1001
    mkdir -p -m 700 /opt/app-root/src/.gnupg
    echo "allow-loopback-pinentry" > /opt/app-root/src/.gnupg/gpg-agent.conf

    # Create batch config for unattended key generation
    cat > /tmp/gpg-batch.conf <<EOF
Key-Type: RSA
Key-Length: 4096
Name-Real: Sinar Project DSRA
Name-Email: ${GPG_KEY_ID}
Expire-Date: 0
%no-protection
%transient-key
%commit
EOF

    # Generate the key
    if gpg --batch --gen-key /tmp/gpg-batch.conf 2>/tmp/gpg-error.log; then
        touch "${DATA_DIR}/.gpg-initialized"
        # Export the public key for verification (best-effort — may fail if bind mount permissions)
        if gpg --export --armor "${GPG_KEY_ID}" > "${DATA_DIR}/public-key.asc" 2>/dev/null; then
            echo "Public key exported to ${DATA_DIR}/public-key.asc"
        else
            echo "Warning: could not export public key to ${DATA_DIR}/public-key.asc (bind mount permissions?)"
        fi
        echo "GPG key pair generated successfully."
    else
        echo "Warning: GPG key generation failed. See /tmp/gpg-error.log for details."
        echo "Encrypted storage will not be available until a key is generated."
        echo "To generate manually: docker compose exec viewfinder gpg --batch --gen-key /tmp/gpg-batch.conf"
    fi

    rm -f /tmp/gpg-batch.conf
fi

# Start PHP development server
exec php -S 0.0.0.0:8080 -t /opt/app-root/src
