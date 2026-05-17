#!/usr/bin/env bash
set -e

# NCDownloader Setup Script
# Installs all required tools: aria2, yt-dlp, ffmpeg, gdown, composer deps, frontend build
# Safe to run multiple times (idempotent)

NC_USER="${NC_USER:-www-data}"
BIN_DIR="$(cd "$(dirname "$0")" && pwd)/bin"
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo "========================================"
echo " NCDownloader Setup"
echo "========================================"

# Detect OS
if [ -f /etc/debian_version ]; then
    PKG_MGR="apt"
elif [ -f /etc/redhat-release ]; then
    PKG_MGR="yum"
else
    echo -e "${YELLOW}Unknown OS, skipping system package installs${NC}"
    PKG_MGR=""
fi

# ─── System packages ──────────────────────
install_system_packages() {
    echo ""
    echo "── Installing system packages ──"

    if [ "$PKG_MGR" = "apt" ]; then
        apt-get update -qq 2>/dev/null || true
        apt-get install -y -qq python3-pip python3 aria2 ffmpeg 2>/dev/null || {
            echo -e "${YELLOW}Some packages may have failed. Trying individually...${NC}"
            for pkg in python3-pip python3 aria2 ffmpeg; do
                apt-get install -y -qq "$pkg" 2>/dev/null && echo "  ✓ $pkg" || echo -e "${YELLOW}  ✗ $pkg (manual install needed)${NC}"
            done
        }
    elif [ "$PKG_MGR" = "yum" ]; then
        yum install -y python3-pip python3 aria2 ffmpeg 2>/dev/null || true
    fi
}

# ─── Python packages ──────────────────────
install_python_packages() {
    echo ""
    echo "── Installing Python packages ──"
    if command -v pip3 &>/dev/null; then
        pip3 install --break-system-packages gdown 2>/dev/null && echo -e "  ${GREEN}✓ gdown${NC}" || pip3 install gdown 2>/dev/null && echo -e "  ${GREEN}✓ gdown${NC}" || echo -e "${YELLOW}  ✗ gdown (pip install failed)${NC}"
    else
        echo -e "${YELLOW}  pip3 not found, skipping gdown${NC}"
    fi
}

# ─── Bundled binaries (aria2, yt-dlp) ─────
install_bundled_binaries() {
    echo ""
    echo "── Checking bundled binaries ──"
    mkdir -p "$BIN_DIR"

    # aria2c
    if command -v aria2c &>/dev/null; then
        echo -e "  ${GREEN}✓ aria2c (system)${NC}"
    elif [ -f "$BIN_DIR/aria2c" ] && [ -x "$BIN_DIR/aria2c" ]; then
        echo -e "  ${GREEN}✓ aria2c (bundled)${NC}"
    else
        echo "  Downloading aria2c..."
        curl -sSL "https://github.com/shiningw/ncdownloader-bin/raw/master/aria2c" -o "$BIN_DIR/aria2c" && chmod +x "$BIN_DIR/aria2c" && echo -e "  ${GREEN}✓ aria2c (downloaded)${NC}" || echo -e "${RED}  ✗ aria2c download failed${NC}"
    fi

    # yt-dlp
    if command -v yt-dlp &>/dev/null; then
        echo -e "  ${GREEN}✓ yt-dlp (system)${NC}"
    elif [ -f "$BIN_DIR/yt-dlp" ] && [ -x "$BIN_DIR/yt-dlp" ]; then
        echo -e "  ${GREEN}✓ yt-dlp (bundled)${NC}"
    else
        echo "  Downloading yt-dlp..."
        curl -sSL "https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp" -o "$BIN_DIR/yt-dlp" && chmod +x "$BIN_DIR/yt-dlp" && echo -e "  ${GREEN}✓ yt-dlp (downloaded)${NC}" || echo -e "${RED}  ✗ yt-dlp download failed${NC}"
    fi
}

# ─── Composer ─────────────────────────────
install_composer_deps() {
    echo ""
    echo "── Installing PHP dependencies ──"
    if command -v composer &>/dev/null; then
        composer install --prefer-dist --no-interaction 2>/dev/null && echo -e "  ${GREEN}✓ composer install${NC}" || echo -e "${YELLOW}  ✗ composer install failed${NC}"
    elif [ -f composer.phar ]; then
        php composer.phar install --prefer-dist --no-interaction 2>/dev/null && echo -e "  ${GREEN}✓ composer install${NC}" || echo -e "${YELLOW}  ✗ composer install failed${NC}"
    else
        echo -e "${YELLOW}  composer not found, skipping${NC}"
    fi
}

# ─── Frontend build ───────────────────────
install_frontend() {
    echo ""
    echo "── Building frontend ──"
    if command -v npm &>/dev/null; then
        npm install --silent 2>/dev/null || true
        npm run build 2>/dev/null && echo -e "  ${GREEN}✓ frontend build${NC}" || echo -e "${YELLOW}  ✗ frontend build failed${NC}"
    else
        echo -e "${YELLOW}  npm not found, skipping frontend build${NC}"
    fi
}

# ─── Permissions ──────────────────────────
fix_permissions() {
    echo ""
    echo "── Fixing permissions ──"
    if [ -d "$BIN_DIR" ]; then
        chmod -R 755 "$BIN_DIR"/* 2>/dev/null || true
        echo "  ✓ bin/ permissions set"
    fi
    if id "$NC_USER" &>/dev/null; then
        chown -R "$NC_USER:$NC_USER" "$BIN_DIR" 2>/dev/null || true
        echo -e "  ${GREEN}✓ ownership set to $NC_USER${NC}"
    fi
}

# ─── Status check ─────────────────────────
check_status() {
    echo ""
    echo "── Status ──"
    echo "  aria2c:  $(command -v aria2c &>/dev/null && echo -e "${GREEN}OK${NC}" || ([ -f "$BIN_DIR/aria2c" ] && echo -e "${GREEN}bundled${NC}") || echo -e "${RED}MISSING${NC}")"
    echo "  yt-dlp:  $(command -v yt-dlp &>/dev/null && echo -e "${GREEN}OK${NC}" || ([ -f "$BIN_DIR/yt-dlp" ] && echo -e "${GREEN}bundled${NC}") || echo -e "${RED}MISSING${NC}")"
    echo "  ffmpeg:  $(command -v ffmpeg &>/dev/null && echo -e "${GREEN}OK${NC}" || echo -e "${YELLOW}MISSING${NC}")"
    echo "  gdown:   $(command -v gdown &>/dev/null && echo -e "${GREEN}OK${NC}" || echo -e "${YELLOW}MISSING${NC}")"
    echo "  php:     $(command -v php &>/dev/null && echo -e "${GREEN}OK${NC}" || echo -e "${RED}MISSING${NC}")"
    echo "  composer:$(command -v composer &>/dev/null && echo -e "${GREEN}OK${NC}" || echo -e "${YELLOW}MISSING${NC}")"
    echo "  npm:     $(command -v npm &>/dev/null && echo -e "${GREEN}OK${NC}" || echo -e "${YELLOW}MISSING${NC}")"
    echo ""
    echo "Setup complete."
}

# ─── Run ──────────────────────────────────
install_system_packages
install_python_packages
install_bundled_binaries
install_composer_deps
install_frontend
fix_permissions
check_status
