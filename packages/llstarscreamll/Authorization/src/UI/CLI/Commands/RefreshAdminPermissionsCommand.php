<?php

namespace llstarscreamll\Authorization\UI\CLI\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use llstarscreamll\Authorization\Models\Role;
use llstarscreamll\Authorization\Models\Permission;

/**
 * Class RefreshAdminPermissionsCommand.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class RefreshAdminPermissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'authorization:refresh-admin-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh admin permissions';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $permissions = Permission::all();
        Role::whereName('admin')->first()->permissions()->sync($permissions);
        Cache::clear();
    }
}
