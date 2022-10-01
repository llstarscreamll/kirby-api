@setup
    require __DIR__.'/vendor/autoload.php';
    \Dotenv\Dotenv::createImmutable(__DIR__, '.env')->load();

    $site = env(strtoupper($target ?? 'lab').'_SITE');
    $servers = explode(';', env(strtoupper($target ?? 'lab').'_SERVERS'));
    $repository = "llstarscreamll/laravel.git";
    $baseDir = "~/{$site}";
    $releasesDir = "{$baseDir}/releases";
    $currentDir = "{$baseDir}/current";
    $newReleaseName = date('Y_m_d-H_i_s');
    $newReleaseDir = "{$releasesDir}/{$newReleaseName}";
    $branch = $branch ?? env('DEFAULT_BRANCH', 'develop');
    $user = get_current_user();

    function logMessage($message) {
        return "echo '\033[32m" .$message. "\033[0m';\n";
    }
@endsetup

@servers(['local' => '127.0.0.1', 'remote' => $servers])

@story('deploy')
    startDeployment
    generateAndUpdaloadArtifact
    unzipArtifact
    runComposer
    updateSymlinks
    backup
    migrateDatabase
    blessNewRelease
    cleanOldReleases
    finishDeploy
@endstory

@story('setup-server')
    startMessage
    updateDistro
    installUnzip
    installNginx
    installMySQL
    installRedis
    installPHP
    setupNginxServerBlock
    checkPortsConfig
@endstory

@task('startDeployment', ['on' => 'local'])
    {{ logMessage("🏃  Starting {$site} deployment") }}
@endtask

@task('generateAndUpdaloadArtifact', ['on' => 'local'])
    {{ logMessage("📸  Generate and upload artifact") }}
    zip -r -q \
    {{ $newReleaseName }}.zip ./ \
    -x vendor/\* node_modules/\* .git/\* tests/\* storage/\* bootstrap/cache/\* \
    \*.md \*.xml .\* \*.zip \*.yml LICENSE package.json yarn.lock Envoy* \
    packages/kirby/\*/tests/\* packages/kirby/\*.md packages/kirby/\*.xml packages/kirby/\*.yml

    @foreach ($servers as $server)
        ssh {{ $server }} 'mkdir -p {{ $releasesDir }}'
        scp {{ $newReleaseName }}.zip {{ $server }}:{{ $releasesDir }}
    @endforeach
@endtask

@task('unzipArtifact', ['on' => 'remote'])
    {{ logMessage("🌀  Unzip artifact") }}
    cd {{ $releasesDir }};
    unzip -q {{ $newReleaseName }}.zip -d {{ $newReleaseName }}
    cd {{ $newReleaseName }}
    mkdir -p bootstrap/cache
@endtask

@task('runComposer', ['on' => 'remote'])
    {{ logMessage("🚚  Running Composer") }}
    cd {{ $newReleaseDir }};
    COMPOSER=$(which composer)
    php74 $COMPOSER install --prefer-dist --no-scripts --no-ansi --no-interaction --optimize-autoloader --no-progress --profile -q
@endtask

@task('updateSymlinks', ['on' => 'remote'])
    {{ logMessage("🔗  Updating symlinks to persistent data") }}
    # remove the storage directory and replace with persistent data
    cd {{ $newReleaseDir }}
    rm -rf storage

    # create peristent storage folder if it does not exist yet
    if [ ! -d "/var/www/{{ $site }}/persistent/storage" ]; then
        {{ logMessage("Creating persistent folder for storage") }}
        mkdir -p /var/www/{{ $site }}/persistent/storage/{app,framework,logs}
        mkdir -p /var/www/{{ $site }}/persistent/storage/framework/{cache,sessions,views}
    fi

    # link the persistent storage folder
    ln -nfs /var/www/{{ $site }}/persistent/storage storage;
    # setup env file
    cd {{ $newReleaseDir }};
    ln -nfs /var/www/{{ $site }}/.env .env;
@endtask

@task('backup', ['on' => 'remote'])
    {{ logMessage("📀  Backing up database") }}
    cd /var/www/{{ $site }}/current
    php74 artisan backup:run
@endtask

@task('migrateDatabase', ['on' => 'remote'])
    {{ logMessage("🙈  Migrating database") }}
    cd {{ $newReleaseDir }}
    php74 artisan migrate --force
    php74 artisan authorization:refresh-admin-permissions
@endtask

@task('blessNewRelease', ['on' => 'remote'])
    {{ logMessage("🙏  Blessing new release") }}

    sed -i 's/:site:/{{ $site }}/' {{ $newReleaseDir }}/stubs/horizon.service
    sudo cp -rf {{ $newReleaseDir }}/stubs/horizon.service /lib/systemd/system/{{ $site}}-horizon.service

    # copy project from home to final path
    sudo cp -R {{ $newReleaseDir }} /var/www/{{ $site }}/releases/
    cd /var/www/{{ $site }}/releases/{{ $newReleaseName }}

    sudo php74 artisan storage:link
    sudo php74 artisan optimize

    sudo chown -R $USER:nginx /var/www/{{ $site }}
    sudo chcon -R -t httpd_sys_content_t /var/www/{{ $site }}/releases/{{ $newReleaseName }}
    sudo chcon -R -t httpd_sys_rw_content_t /var/www/{{ $site }}/persistent/storage

    sudo ln -nfs /var/www/{{ $site }}/releases/{{ $newReleaseName }} /var/www/{{ $site }}/current;

    sudo php74 artisan horizon:terminate
    sudo systemctl enable {{ $site}}-horizon.service
    sudo systemctl restart {{ $site}}-horizon.service
    sudo systemctl restart nginx
@endtask

@task('cleanOldReleases', ['on' => 'remote'])
    {{ logMessage("🚾  Cleaning up old releases") }}
    # Delete all but the 5 most recent.
    cd {{ $releasesDir }}
    # ls -dt {{ $releasesDir }}/* | tail -n +6 | xargs -d "\n" sudo chown -R $USER .;
    ls -dt {{ $releasesDir }}/* | tail -n +6 | xargs -d "\n" rm -rf;
@endtask

@task('finishDeploy', ['on' => 'local'])
    {{ logMessage("🚀  Application deployed!") }}
@endtask

{{-- Setup Server Tasks --}}

@task('startMessage', ['on' => 'local'])
    {{ logMessage("Setting up {$target} server at ".json_encode($servers)) }}
@endtask

@task('updateDistro', ['on' => 'remote'])
    {{ logMessage("Updating distro") }}
    sudo dnf update --assumeyes
@endtask

@task('installUnzip', ['on' => 'remote'])
    {{ logMessage("Install Unzip") }}
    sudo dnf install unzip --assumeyes
@endtask

@task('installNginx', ['on' => 'remote'])
    {{ logMessage("Install Nginx") }}
    sudo dnf install --assumeyes nginx
    sudo systemctl enable nginx
@endtask

@task('installPHP', ['on' => 'remote'])
    {{ logMessage("Install PHP and Composer") }}
    sudo dnf install --assumeyes \
        http://rpms.remirepo.net/enterprise/remi-release-8.rpm \
        composer \
        php74 php74-php-fpm php74-php-pdo php74-php-mysql php74-php-zip php74-php-mbstring php74-php-xml php74-php-bcmath php74-php-redis php74-php-gd php74-php-posix

    sudo sed -i 's/user = apache/user = nginx/' /etc/opt/remi/php74/php-fpm.d/www.conf
    sudo sed -i 's/group = apache/group = nginx/' /etc/opt/remi/php74/php-fpm.d/www.conf
    sudo sed -i 's/;listen.owner = nobody/listen.owner = nginx/' /etc/opt/remi/php74/php-fpm.d/www.conf
    sudo sed -i 's/;listen.group = nobody/listen.group = nginx/' /etc/opt/remi/php74/php-fpm.d/www.conf
    sudo sed -i 's/;listen.mode = 0660/listen.mode = 0660/' /etc/opt/remi/php74/php-fpm.d/www.conf
    sudo sed -i 's/^listen.acl_users/;listen.acl_users/' /etc/opt/remi/php74/php-fpm.d/www.conf

    sudo systemctl enable php74-php-fpm.service --now
@endtask

@task('installMySQL', ['on' => 'remote'])
    {{ logMessage("Install MySQL") }}
    sudo dnf install --assumeyes mysql-server mysql
    sudo systemctl enable mysqld --now
@endtask

@task('installRedis', ['on' => 'remote'])
    {{ logMessage("Install Redis") }}
    sudo dnf install --assumeyes redis
    sudo systemctl enable redis --now
@endtask

@task('setupNginxServerBlock', ['on' => 'remote'])
    {{ logMessage("Setup Nginx server block") }}
    > {{ $site }}.conf
    > server-block-template.conf
    echo "{{ "c2VydmVyIHsKCWxpc3RlbiA6cG9ydDo7CglsaXN0ZW4gWzo6XTo6cG9ydDo7CgoJc2VydmVyX3Rva2VucyBvZmY7CglhZGRfaGVhZGVyIFgtRnJhbWUtT3B0aW9ucyBTQU1FT1JJR0lOOwoJYWRkX2hlYWRlciBYLUNvbnRlbnQtVHlwZS1PcHRpb25zIG5vc25pZmY7CglhZGRfaGVhZGVyIFgtWFNTLVByb3RlY3Rpb24gIjE7IG1vZGU9YmxvY2siOwoKCXJvb3QgOmRvY3VtZW50X3Jvb3Q6OwoKCWluZGV4IGluZGV4LnBocDsKCglzZXJ2ZXJfbmFtZSBfOwoKCWxhcmdlX2NsaWVudF9oZWFkZXJfYnVmZmVycyA4IDE2azsKCglsb2NhdGlvbiAvIHsKCQl0cnlfZmlsZXMgJHVyaSAkdXJpLyAvaW5kZXgucGhwPyRxdWVyeV9zdHJpbmc7Cgl9CgoJbG9jYXRpb24gfiBcLnBocCQgewoJCWluY2x1ZGUgL2V0Yy9uZ2lueC9mYXN0Y2dpX3BhcmFtczsKCiAgICAgICAgZmFzdGNnaV9pbmRleCBpbmRleC5waHA7CgkJZmFzdGNnaV9wYXJhbSBET0NVTUVOVF9ST09UICRyZWFscGF0aF9yb290OwoJCWZhc3RjZ2lfcGFzcyB1bml4Oi92YXIvb3B0L3JlbWkvcGhwNzQvcnVuL3BocC1mcG0vd3d3LnNvY2s7CgkJZmFzdGNnaV9wYXJhbSBTQ1JJUFRfRklMRU5BTUUgJHJlYWxwYXRoX3Jvb3QkZmFzdGNnaV9zY3JpcHRfbmFtZTsKCX0KCglsb2NhdGlvbiB+IC9cLmh0IHsKCQlkZW55IGFsbDsKCX0KfQo=" }}" >> ~/server-block-template.conf
    cat ~/server-block-template.conf | base64 --decode >> {{ $site }}.conf
    sed -i 's|:port:|8000|' {{ $site }}.conf
    sed -i "s|:document_root:|/varwww//{{ $site }}/current/public|" {{ $site }}.conf
    sudo cp {{ $site }}.conf /etc/nginx/conf.d/
    rm {{ $site }}.conf
    rm server-block-template.conf

    sudo nginx -t

    sudo semanage port -a -t http_port_t -p tcp 8000 || true
    sudo semanage port -m -t http_port_t -p tcp 8000

    sudo systemctl restart nginx
@endtask

@task('checkPortsConfig', ['on' => 'remote'])
    {{ logMessage("Get ports configuration") }}
    sudo ss -tulpn | grep LISTEN
@endtask
