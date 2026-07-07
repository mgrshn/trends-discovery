#!/bin/bash
# Скрипт деплоя — запускать на VDS из папки проекта
set -e

echo ">>> git pull"
git pull origin master

echo ">>> build + restart"
docker compose -f docker-compose.prod.yml up -d --build

echo ">>> done. Logs: docker compose -f docker-compose.prod.yml logs -f"
