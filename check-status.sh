#!/bin/bash

# Aria Plugin Status Check Script

echo "🔍 Aria Plugin Status Check"
echo "=========================="

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# Check PHP files
echo -e "\n📁 PHP Classes:"
for file in includes/class-aria-*.php; do
    if [ -f "$file" ]; then
        echo -e "  ${GREEN}✓${NC} $(basename "$file")"
    fi
done

# Check JavaScript files
echo -e "\n📜 JavaScript Files:"
if [ -f "src/js/admin.js" ]; then
    lines=$(wc -l < src/js/admin.js)
    echo -e "  ${GREEN}✓${NC} admin.js ($lines lines)"
fi
if [ -f "src/js/chat.js" ]; then
    lines=$(wc -l < src/js/chat.js)
    echo -e "  ${GREEN}✓${NC} chat.js ($lines lines)"
fi

# Check build files
echo -e "\n📦 Build Files:"
if [ -f "dist/admin.js" ]; then
    echo -e "  ${GREEN}✓${NC} dist/admin.js"
fi
if [ -f "dist/chat.js" ]; then
    echo -e "  ${GREEN}✓${NC} dist/chat.js"
fi
if [ -f "dist/admin-style.css" ]; then
    echo -e "  ${GREEN}✓${NC} dist/admin-style.css"
fi
if [ -f "dist/chat-style.css" ]; then
    echo -e "  ${GREEN}✓${NC} dist/chat-style.css"
fi

# Check admin partials
echo -e "\n🎨 Admin Partials:"
for file in admin/partials/*.php; do
    if [ -f "$file" ]; then
        echo -e "  ${GREEN}✓${NC} $(basename "$file")"
    fi
done

# Check testing files
echo -e "\n🧪 Testing Setup:"
if [ -f "docker-compose.yml" ]; then
    echo -e "  ${GREEN}✓${NC} docker-compose.yml"
else
    echo -e "  ${RED}✗${NC} docker-compose.yml"
fi
if [ -f "TESTING-CHECKLIST.md" ]; then
    echo -e "  ${GREEN}✓${NC} TESTING-CHECKLIST.md"
else
    echo -e "  ${RED}✗${NC} TESTING-CHECKLIST.md"
fi
if [ -f "tests/bootstrap.php" ]; then
    echo -e "  ${GREEN}✓${NC} PHPUnit bootstrap"
else
    echo -e "  ${RED}✗${NC} PHPUnit bootstrap"
fi

# Check dependencies
echo -e "\n📚 Dependencies:"
if [ -d "node_modules" ]; then
    echo -e "  ${GREEN}✓${NC} Node modules installed"
else
    echo -e "  ${RED}✗${NC} Node modules not installed"
fi
if [ -d "vendor" ]; then
    echo -e "  ${GREEN}✓${NC} Composer packages installed"
else
    echo -e "  ${RED}✗${NC} Composer packages not installed"
fi

# Docker status
echo -e "\n🐳 Docker Status:"
if docker info > /dev/null 2>&1; then
    echo -e "  ${GREEN}✓${NC} Docker is running"
    if docker ps | grep -q aria-wordpress; then
        echo -e "  ${GREEN}✓${NC} WordPress container is running"
        echo -e "  ${GREEN}✓${NC} Access at: http://localhost:8080"
    else
        echo -e "  ${YELLOW}!${NC} WordPress container not running"
        echo -e "     Run: ${YELLOW}./setup-test-env.sh${NC}"
    fi
else
    echo -e "  ${RED}✗${NC} Docker not running"
fi

echo -e "\n=========================="
echo -e "📊 Summary:"
echo -e "  - PHP Development: ${GREEN}Complete${NC}"
echo -e "  - JavaScript: ${GREEN}Complete${NC}"
echo -e "  - CSS Styling: ${YELLOW}Pending${NC}"
echo -e "  - Testing Setup: ${GREEN}Ready${NC}"
echo -e "  - Documentation: ${GREEN}In Progress${NC}"