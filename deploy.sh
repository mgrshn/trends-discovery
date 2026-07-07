#!/bin/bash
# Запускать на VDS из ~/apps/discovery
set -e

echo ">>> git pull"
git pull origin master

echo ">>> build + restart"
docker compose -f docker-compose.prod.yml up -d --build

echo ">>> done"
echo "    Logs: docker compose -f docker-compose.prod.yml logs -f"
