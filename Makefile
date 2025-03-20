# Define variables
PROJECT_NAME="kirby"
PROJECT_HOME="~/projects/$(PROJECT_NAME)"
ARTIFACT_VERSION=$(shell date +%Y%m%d%H%M%S)

# Default target
.PHONY: all
all: help

# Help target
.PHONY: help
help:
	@echo "Usage:"
	@echo "  make deploy        Deploy the application"

# Copy MySQL data from old server
.PHONY: copy-mysql-data-to-new-server
copy-mysql-data-to-new-server:
	@echo "Copying MySQL data from old server to new server"
	
	# dump data on old server
	@ssh grapas "mkdir -p $(PROJECT_HOME)/mysql-data"
	@ssh grapas "mysqldump -u root -p'P4kByBK7#sMtvl\IAVv&1j1%' --default-character-set=utf8 --no-tablespaces kirbyprod > $(PROJECT_HOME)/mysql-data/$(ARTIFACT_VERSION).sql"
	@ssh grapas "ls -lah $(PROJECT_HOME)/mysql-data"
	
	# download dump
	@mkdir -p ~/Documents/mysql-backups/$(PROJECT_NAME)
	@scp grapas:$(PROJECT_HOME)/mysql-data/$(ARTIFACT_VERSION).sql ~/Documents/mysql-backups/$(PROJECT_NAME)/
	
	# upload dump to new server
	@ssh root@200.7.107.218 "mkdir -p $(PROJECT_HOME)/persistent/mysql-data"
	@scp ~/Documents/mysql-backups/$(PROJECT_NAME)/$(ARTIFACT_VERSION).sql root@200.7.107.218:$(PROJECT_HOME)/persistent/mysql-data
	@ssh root@200.7.107.218 "ls -lah $(PROJECT_HOME)/persistent/mysql-data/"

	# restore data on new server
	@ssh root@200.7.107.218 "mysql -u root kirby < $(PROJECT_HOME)/persistent/mysql-data/$(ARTIFACT_VERSION).sql"

# Deploy the application
.PHONY: deploy
deploy:
	@set -e
	@echo "Artifact version $(ARTIFACT_VERSION)"
	
	# generate artifact
	@zip -r -q artifact-$(ARTIFACT_VERSION).zip . \
		-x bootstrap/cache/\* public/resources/\* node_modules/\* vendor/\* \
		tests/\* wiki/\* storage/\* .git/\* \
		\*.zip \*.xml \*.md .env\* codeception\* .git\* .DS_Store \
		Makefile
	
	# create directories on remote server
	@ssh root@200.7.107.218 "mkdir -p $(PROJECT_HOME)/{releases,persistent,mysql-backups}"
	@ssh root@200.7.107.218 "mkdir -p $(PROJECT_HOME)/persistent/storage/{app,framework,logs}"
	@ssh root@200.7.107.218 "mkdir -p $(PROJECT_HOME)/persistent/storage/framework/{cache,sessions,views}"
	@ssh root@200.7.107.218 "mkdir -p /usr/share/nginx/html/projects"
	@ssh root@200.7.107.218 "mkdir -p ~/.cache/composer"
	
	# upload to remote server
	@scp artifact-$(ARTIFACT_VERSION).zip root@200.7.107.218:$(PROJECT_HOME)/

	# remove local artifact
	@rm artifact-*.zip

	# unzip artifact on remote server
	@ssh root@200.7.107.218 "unzip -q -o $(PROJECT_HOME)/artifact-$(ARTIFACT_VERSION).zip -d $(PROJECT_HOME)/releases/$(ARTIFACT_VERSION)"
	
	# create directories on remote server
	@ssh root@200.7.107.218 "mkdir -p $(PROJECT_HOME)/releases/$(ARTIFACT_VERSION)/bootstrap/cache"

	# copy .env file
	@ssh root@200.7.107.218 "cp ~/.$(PROJECT_NAME).env $(PROJECT_HOME)/releases/$(ARTIFACT_VERSION)/.env"

	# set nginx config
	@ssh root@200.7.107.218 "cd $(PROJECT_HOME)/releases/$(ARTIFACT_VERSION) && sed -i 's|##DOCUMENT_ROOT##|/usr/share/nginx/html/projects/$(PROJECT_NAME)/public|g' stubs/site-nginx.conf"

	# install dependencies
	@ssh root@200.7.107.218 "cd $(PROJECT_HOME)/releases/$(ARTIFACT_VERSION) && COMPOSE_BAKE=true docker compose up --build --remove-orphans --quiet-pull kirby-composer-dependencies"
	@ssh root@200.7.107.218 "cd $(PROJECT_HOME)/releases/$(ARTIFACT_VERSION) && COMPOSE_BAKE=true docker compose up --build --remove-orphans --quiet-pull kirby-npm-dependencies"

	# set files and folders permissions
	@ssh root@200.7.107.218 "chown -R nginx:nginx $(PROJECT_HOME)/releases/$(ARTIFACT_VERSION)"
	@ssh root@200.7.107.218 "chown -R nginx:nginx $(PROJECT_HOME)/persistent"
	@ssh root@200.7.107.218 "chmod +x $(PROJECT_HOME)/releases/*/scripts/backup_database.sh"

	# publish new release
	@ssh root@200.7.107.218 "ln -nfs $(PROJECT_HOME)/releases/$(ARTIFACT_VERSION) /usr/share/nginx/html/projects/$(PROJECT_NAME)"
	@ssh root@200.7.107.218 "ln -nfs ~/projects/kirby/persistent/storage/app/public/ /usr/share/nginx/html/projects/$(PROJECT_NAME)/public/storage"

	# start php-fpm container
	@ssh root@200.7.107.218 "cd $(PROJECT_HOME)/releases/$(ARTIFACT_VERSION) && COMPOSE_BAKE=true docker compose up --build --remove-orphans -d --quiet-pull --remove-orphans kirby-fpm"
	
	# start php-worker container
	@ssh root@200.7.107.218 "cd $(PROJECT_HOME)/releases/$(ARTIFACT_VERSION) && COMPOSE_BAKE=true docker compose up --build --remove-orphans -d --quiet-pull --remove-orphans kirby-worker"
	
	# clear cache
	@ssh root@200.7.107.218 "docker exec kirby-fpm sh -c 'php artisan optimize && chown -R nginx:nginx ./'"

	# run migrations
	@ssh root@200.7.107.218 "docker exec kirby-fpm sh -c 'php artisan migrate --force && php artisan authorization:refresh-admin-permissions'"

	# restart nginx
	@ssh root@200.7.107.218 "nginx -t"
	@ssh root@200.7.107.218 "systemctl restart nginx"

	# clean up old releases
	@ssh root@200.7.107.218 "cd $(PROJECT_HOME)/releases && ls -t | tail -n +10 | xargs rm -rf"

	# setup cron jobs
	@ssh root@200.7.107.218 "cp $(PROJECT_HOME)/releases/$(ARTIFACT_VERSION)/stubs/database-backup.cron /etc/cron.d/$(PROJECT_NAME)-db-backup"
	@ssh root@200.7.107.218 "chmod 644 /etc/cron.d/$(PROJECT_NAME)-db-backup"
