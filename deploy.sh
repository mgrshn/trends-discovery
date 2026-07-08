#!/bin/bash
# Запускать на VDS из ~/apps/discovery
set -e

echo ">>> git pull"
git pull origin master

echo ">>> build + restart"
docker compose -f docker-compose.prod.yml up -d --build

echo ">>> waiting for backend..."
sleep 5

echo ">>> status"
docker compose -f docker-compose.prod.yml ps

echo ""
echo ">>> done. Logs: docker compose -f docker-compose.prod.yml logs -f"
