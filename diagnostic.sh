#!/bin/bash
# ======================================================
# SCRIPT DE DIAGNOSTIC - Saas-schooling Deployment
# ======================================================
# Utilisation : ./diagnostic.sh

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "🔍 =========================================="
echo "🔍 DIAGNOSTIC SAAS-SCHOOLING DEPLOYMENT"
echo "🔍 =========================================="
echo ""

# ============ CHECK 1: Docker ============
echo -e "${YELLOW}1️⃣ Docker & Containers Status${NC}"
if ! command -v docker &> /dev/null; then
    echo -e "${RED}✗ Docker not installed${NC}"
else
    echo -e "${GREEN}✓ Docker installed${NC}"
    docker --version
fi

if ! command -v docker-compose &> /dev/null; then
    echo -e "${RED}✗ Docker Compose not installed${NC}"
else
    echo -e "${GREEN}✓ Docker Compose installed${NC}"
    docker-compose --version
fi

echo ""
echo -e "${YELLOW}Running containers:${NC}"
docker-compose ps || echo -e "${RED}✗ docker-compose ps failed (not in project dir?)${NC}"

echo ""

# ============ CHECK 2: Ports ============
echo -e "${YELLOW}2️⃣ Port Status${NC}"
if netstat -tlnp 2>/dev/null | grep -q ":80 "; then
    echo -e "${GREEN}✓ Port 80 (HTTP) is listening${NC}"
else
    echo -e "${RED}✗ Port 80 (HTTP) not listening${NC}"
fi

if netstat -tlnp 2>/dev/null | grep -q ":443 "; then
    echo -e "${GREEN}✓ Port 443 (HTTPS) is listening${NC}"
else
    echo -e "${RED}✗ Port 443 (HTTPS) not listening${NC}"
fi

if netstat -tlnp 2>/dev/null | grep -q ":8000 "; then
    echo -e "${GREEN}✓ Port 8000 (Nginx Docker) is listening${NC}"
else
    echo -e "${RED}✗ Port 8000 (Nginx Docker) not listening${NC}"
fi

if netstat -tlnp 2>/dev/null | grep -q ":3306 "; then
    echo -e "${YELLOW}⚠ Port 3306 (MySQL) exposed on host (verify this is intended)${NC}"
fi

echo ""

# ============ CHECK 3: Apache ============
echo -e "${YELLOW}3️⃣ Apache Status${NC}"
if ! command -v apache2 &> /dev/null; then
    echo -e "${RED}✗ Apache2 not installed${NC}"
else
    echo -e "${GREEN}✓ Apache2 installed${NC}"

    if apache2ctl configtest 2>&1 | grep -q "Syntax OK"; then
        echo -e "${GREEN}✓ Apache syntax OK${NC}"
    else
        echo -e "${RED}✗ Apache syntax ERROR:${NC}"
        apache2ctl configtest
    fi
fi

echo ""

# ============ CHECK 4: SSL/TLS ============
echo -e "${YELLOW}4️⃣ SSL Certificate Status${NC}"
CERT_PATH="/etc/letsencrypt/live/splitpay.payplus.africa/fullchain.pem"
if [ -f "$CERT_PATH" ]; then
    echo -e "${GREEN}✓ Certificate found at $CERT_PATH${NC}"
    openssl x509 -in "$CERT_PATH" -text -noout 2>/dev/null | grep -A 2 "Not Before\|Not After" | head -4
else
    echo -e "${RED}✗ Certificate not found at $CERT_PATH${NC}"
    echo -e "${YELLOW}   Run: sudo certbot certonly --apache -d splitpay.payplus.africa${NC}"
fi

echo ""

# ============ CHECK 5: Database ============
echo -e "${YELLOW}5️⃣ Database Status${NC}"
if docker exec saas-db-prod mysqladmin ping -h127.0.0.1 &>/dev/null; then
    echo -e "${GREEN}✓ MySQL container is responding${NC}"
else
    echo -e "${RED}✗ MySQL container not responding${NC}"
fi

echo ""

# ============ CHECK 6: Redis ============
echo -e "${YELLOW}6️⃣ Redis Status${NC}"
if docker exec saas-redis-prod redis-cli ping &>/dev/null; then
    echo -e "${GREEN}✓ Redis container is responding${NC}"
else
    echo -e "${RED}✗ Redis container not responding${NC}"
fi

echo ""

# ============ CHECK 7: App Logs ============
echo -e "${YELLOW}7️⃣ Laravel App Logs (recent errors)${NC}"
echo -e "${YELLOW}   Last 10 lines from storage/logs:${NC}"
docker exec saas-app-prod tail -10 storage/logs/laravel.log 2>/dev/null || echo "   (no logs found)"

echo ""

# ============ CHECK 8: Nginx Config ============
echo -e "${YELLOW}8️⃣ Nginx Configuration${NC}"
if docker exec saas-nginx-prod nginx -t 2>&1 | grep -q "successful"; then
    echo -e "${GREEN}✓ Nginx configuration OK${NC}"
else
    echo -e "${RED}✗ Nginx configuration ERROR${NC}"
    docker exec saas-nginx-prod nginx -t
fi

echo ""

# ============ CHECK 9: Test Connectivity ============
echo -e "${YELLOW}9️⃣ Connectivity Test${NC}"
echo "   Testing Docker Nginx (port 8000)..."
if curl -s -H "Host: splitpay.payplus.africa" http://localhost:8000/ -o /dev/null -w "%{http_code}\n" 2>/dev/null | grep -qE "200|301|302|500"; then
    HTTP_CODE=$(curl -s -H "Host: splitpay.payplus.africa" http://localhost:8000/ -o /dev/null -w "%{http_code}\n" 2>/dev/null)
    echo -e "   ${YELLOW}HTTP Response Code: $HTTP_CODE${NC}"
    if [ "$HTTP_CODE" = "500" ]; then
        echo -e "   ${RED}⚠ 500 Error - Check logs above${NC}"
    fi
else
    echo -e "   ${RED}✗ Cannot reach localhost:8000${NC}"
fi

echo ""
echo -e "${GREEN}=========================================="
echo "✓ Diagnostic completed"
echo "==========================================${NC}"

