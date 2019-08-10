@setup
require __DIR__.'/vendor/autoload.php';
\Dotenv\Dotenv::create(__DIR__, '.env')->load();

$site = env('SITE');
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
runComposer
updateSymlinks
optimizeInstallation
{{-- backup --}}
migrateDatabase
setPermissions
blessNewRelease
cleanOldReleases
finishDeploy
@endstory

@story('deploy-code')
deployOnlyCode
setPermissions
@endstory

@task('startDeployment', ['on' => 'local'])
{{ logMessage("🏃  Starting deployment...") }}
git checkout {{ $branch }}
git pull origin {{ $branch }}
@endtask

@task('cloneRepository', ['on' => 'remote'])
{{ logMessage("🌀  Cloning repository...") }}
[ -d {{ $releasesDir }} ] || mkdir {{ $releasesDir }};
cd {{ $releasesDir }};

# Create the release dir
mkdir {{ $newReleaseDir }};

# Clone the repo
git clone --depth 1 git@github.com:{{ $repository }} {{ $newReleaseName }}

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
composer install --prefer-dist --no-scripts -q -o;
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
php artisan clear-compiled;
@endtask

@task('backup', ['on' => 'remote'])
{{ logMessage("📀  Backing up database...") }}
cd {{ $newReleaseDir }}
php artisan backup:run
@endtask

@task('migrateDatabase', ['on' => 'remote'])
{{ logMessage("🙈  Migrating database...") }}
cd {{ $newReleaseDir }};
php artisan migrate --force;
@endtask

@task('setPermissions', ['on' => 'remote'])
{{ logMessage("🔐  Set folders permissions...") }}
cd {{ $currentDir }};
sudo chown -R $USER:www-data storage/* bootstrap/cache/*
sudo chmod -R ug+rwx storage/* bootstrap/cache/*
@endtask

@task('blessNewRelease', ['on' => 'remote'])
{{ logMessage("🙏  Blessing new release...") }}
ln -nfs {{ $newReleaseDir }} {{ $currentDir }};
cd {{ $newReleaseDir }}
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
php artisan horizon:purge
sudo php artisan horizon:terminate

sudo service php7.3-fpm restart
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
{{ logMessage("💻  Deploying code changes...") }}
cd {{ $currentDir }}
git pull origin {{ $branch }}
composer install
php artisan optimize
php artisan db:seed --class=llstarscreamll\\Novelties\\Seeds\\NoveltiesPermissionsSeeder
php artisan db:seed --class=EmployeesPackageSeed
php artisan authorization:refresh-admin-permissions
php artisan queue:restart
php artisan horizon:purge
sudo php artisan horizon:terminate
sudo service php7.3-fpm restart
@endtask
