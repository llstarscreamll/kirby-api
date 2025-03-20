#!/bin/bash

# Define variables
PROJECT_NAME="kirby"
PROJECT_HOME="${HOME}/projects/${PROJECT_NAME}"
TIMESTAMP=$(date +%Y%m%d%H%M%S)
BACKUP_DIR="${PROJECT_HOME}/persistent/mysql-backups"

# Ensure backup directory exists
mkdir -p ${BACKUP_DIR}

# Execute MySQL dump
mysqldump -u root --default-character-set=utf8 --no-tablespaces kirby > ${BACKUP_DIR}/${PROJECT_NAME}-${TIMESTAMP}.sql

# Remove backups older than 15 days to avoid disk space issues
find ${BACKUP_DIR} -type f -name "backup-*.sql" -mtime +15 -delete

echo "${PROJECT_NAME} database backup completed: ${BACKUP_DIR}/backup-${TIMESTAMP}.sql"
