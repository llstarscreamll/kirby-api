@setup
require __DIR__.'/vendor/autoload.php';
\Dotenv\Dotenv::createImmutable(__DIR__, '.env')->load();

$site = env(strtoupper($target ?? 'lab').'_SITE');
$userAndServer = explode(';', env(strtoupper($target ?? 'lab').'_SERVERS'));
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

@servers(['local' => '127.0.0.1', 'remote' => $userAndServer])

@story('deploy')
startDeployment
cloneRepository
setPermissions
runComposer
updateSymlinks
optimizeInstallation
{{-- backup --}}
migrateDatabase
blessNewRelease
setPermissions
cleanOldReleases
finishDeploy
@endstory

@story('deploy-code')
deployOnlyCode
setPermissions
@endstory

@task('startDeployment', ['on' => 'local'])
{{ logMessage("🏃  Starting deployment...") }}
@endtask

@task('cloneRepository', ['on' => 'remote'])
{{ logMessage("🌀  Cloning repository...") }}
[ -d {{ $releasesDir }} ] || mkdir -p {{ $releasesDir }};
cd {{ $releasesDir }};

# Create the release dir
mkdir {{ $newReleaseDir }};

# Clone the repo
git clone --depth 1 --branch {{ $branch }} git@github.com:{{ $repository }} {{ $newReleaseName }}

# Configure sparse checkout
cd {{ $newReleaseDir }}
git checkout {{ $branch }}
git config core.sparsecheckout true
echo "*" > .git/info/sparse-checkout
echo "!storage" >> .git/info/sparse-checkout
echo "!public/build" >> .git/info/sparse-checkout
git read-tree -mu HEAD

# Mark release
cd {{ $newReleaseDir }}
echo "{{ $newReleaseName }}" > public/release-name.txt
@endtask

@task('runComposer', ['on' => 'remote'])
{{ logMessage("🚚  Running Composer...") }}
cd {{ $newReleaseDir }};
COMPOSER=$(which composer)
php7.4 $COMPOSER --prefer-dist --no-scripts --no-ansi --no-interaction --optimize-autoloader --no-progress --profile install
@endtask

@task('runYarn', ['on' => 'local'])
{{ logMessage("📦  Running Yarn...") }}
cd {{ $newReleaseDir }};
yarn config set ignore-engines true
yarn --frozen-lockfile --silent
@endtask

@task('generateAssets', ['on' => 'local'])
{{ logMessage("🌅  Generating assets...") }}
cd {{ $newReleaseDir }};
yarn run production --progress false --silent
@endtask

@task('updateSymlinks', ['on' => 'remote'])
{{ logMessage("🔗  Updating symlinks to persistent data...") }}
# Remove the storage directory and replace with persistent data
rm -rf {{ $newReleaseDir }}/storage;
cd {{ $newReleaseDir }};
ln -nfs {{ $baseDir }}/persistent/storage storage;

# Import the environment config
cd {{ $newReleaseDir }};
ln -nfs {{ $baseDir }}/.env .env;
@endtask

@task('optimizeInstallation', ['on' => 'remote'])
{{ logMessage("✨  Optimizing installation...") }}
cd {{ $newReleaseDir }};
php7.4 artisan clear-compiled;
@endtask

@task('backup', ['on' => 'remote'])
{{ logMessage("📀  Backing up database...") }}
cd {{ $newReleaseDir }}
php7.4 artisan backup:run
@endtask

@task('migrateDatabase', ['on' => 'remote'])
{{ logMessage("🙈  Migrating database...") }}
cd {{ $newReleaseDir }}
php7.4 artisan migrate --force
@endtask

@task('setPermissions', ['on' => 'remote'])
{{ logMessage("🔐  Set folders permissions...") }}
cd {{ $currentDir }};
sudo chown -R www-data:www-data storage/* bootstrap/cache/*
sudo chmod -R ug+rwx storage/* bootstrap/cache/*
@endtask

@task('blessNewRelease', ['on' => 'remote'])
{{ logMessage("🙏  Blessing new release...") }}
ln -nfs {{ $newReleaseDir }} {{ $currentDir }};
cd {{ $newReleaseDir }}
php7.4 artisan db:seed
php7.4 artisan authorization:refresh-admin-permissions
php7.4 artisan optimize
php7.4 artisan storage:link
php7.4 artisan queue:restart
sudo php7.4 artisan horizon:terminate

sudo service php7.4-fpm restart
@endtask

@task('cleanOldReleases', ['on' => 'remote'])
{{ logMessage("🚾  Cleaning up old releases...") }}
# Delete all but the 5 most recent.
cd {{ $releasesDir }}
ls -dt {{ $releasesDir }}/* | tail -n +6 | xargs -d "\n" sudo chown -R $USER .;
ls -dt {{ $releasesDir }}/* | tail -n +6 | xargs -d "\n" rm -rf;
@endtask

@task('finishDeploy', ['on' => 'local'])
{{ logMessage("🚀  Application deployed!") }}
@endtask

@task('deployOnlyCode',['on' => 'remote'])
{{ logMessage("💻  Deploying code changes form $branch to $currentDir") }}
cd {{ $currentDir }}
git checkout $branch
git pull origin $branch

COMPOSER=$(which composer)
php7.4 $COMPOSER --prefer-dist --no-scripts --no-ansi --no-interaction --optimize-autoloader --no-progress --profile install

sudo chown -R www-data:www-data storage/* bootstrap/cache/*
sudo chmod -R ug+rwx storage/* bootstrap/cache/*

php7.4 artisan optimize
php7.4 artisan storage:link
php7.4 artisan queue:restart
sudo php7.4 artisan horizon:terminate
sudo service php7.4-fpm restart
@endtask
