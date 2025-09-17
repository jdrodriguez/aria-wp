#!/bin/bash

# Aria WordPress Plugin Testing Environment Setup Script

echo "🚀 Setting up Aria WordPress testing environment..."

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo -e "${RED}❌ Docker is not running. Please start Docker and try again.${NC}"
    exit 1
fi

# Stop and remove existing containers if they exist
echo -e "${YELLOW}🔄 Cleaning up existing containers...${NC}"
docker-compose down -v 2>/dev/null

# Start the containers
echo -e "${YELLOW}🐳 Starting Docker containers...${NC}"
docker-compose up -d

# Wait for WordPress to be ready
echo -e "${YELLOW}⏳ Waiting for WordPress to be ready...${NC}"
sleep 30

# Check if containers are running
if docker ps | grep -q aria-wordpress; then
    echo -e "${GREEN}✅ WordPress container is running${NC}"
else
    echo -e "${RED}❌ WordPress container failed to start${NC}"
    exit 1
fi

# Display access information
echo -e "\n${GREEN}✅ Testing environment is ready!${NC}"
echo -e "\n📋 Access Information:"
echo -e "  - WordPress: ${GREEN}http://localhost:8080${NC}"
echo -e "  - phpMyAdmin: ${GREEN}http://localhost:8081${NC}"
echo -e "  - Admin User: ${YELLOW}admin${NC}"
echo -e "  - Admin Password: ${YELLOW}password${NC}"
echo -e "  - Database User: ${YELLOW}wordpress${NC}"
echo -e "  - Database Password: ${YELLOW}wordpress${NC}"

echo -e "\n📁 Plugin Location:"
echo -e "  The Aria plugin is mounted at: ${GREEN}/wp-content/plugins/aria${NC}"

echo -e "\n🛠️  Useful Commands:"
echo -e "  - Stop environment: ${YELLOW}docker-compose down${NC}"
echo -e "  - View logs: ${YELLOW}docker-compose logs -f wordpress${NC}"
echo -e "  - Access WordPress shell: ${YELLOW}docker exec -it aria-wordpress bash${NC}"
echo -e "  - Access MySQL: ${YELLOW}docker exec -it aria-mysql mysql -u wordpress -pwordpress wordpress${NC}"

echo -e "\n⚡ Next Steps:"
echo -e "  1. Visit ${GREEN}http://localhost:8080${NC} to complete WordPress setup"
echo -e "  2. Activate the Aria plugin from the WordPress admin"
echo -e "  3. Configure the plugin with your AI API credentials"
echo -e "  4. Test the chat widget on the frontend"