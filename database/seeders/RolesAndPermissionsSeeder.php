<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (Permissions::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $admin = Role::findOrCreate('admin', 'web');
        $manager = Role::findOrCreate('manager', 'web');
        $sales = Role::findOrCreate('sales', 'web');
        $viewer = Role::findOrCreate('viewer', 'web');

        $admin->syncPermissions(Permissions::all());

        $manager->syncPermissions([
            Permissions::DASHBOARD_VIEW,
            Permissions::CONTACTS_VIEW,
            Permissions::CONTACTS_CREATE,
            Permissions::CONTACTS_UPDATE,
            Permissions::CONTACTS_DELETE,
            Permissions::CAMPAIGNS_VIEW,
            Permissions::CAMPAIGNS_CREATE,
            Permissions::CAMPAIGNS_UPDATE,
            Permissions::CAMPAIGNS_DELETE,
            Permissions::INTERACTIONS_VIEW,
            Permissions::INTERACTIONS_CREATE,
            Permissions::INTERACTIONS_UPDATE,
            Permissions::INTERACTIONS_DELETE,
            Permissions::FOLLOW_UPS_VIEW,
            Permissions::FOLLOW_UPS_CREATE,
            Permissions::FOLLOW_UPS_UPDATE,
            Permissions::FOLLOW_UPS_DELETE,
            Permissions::LEAD_SEARCHES_VIEW,
            Permissions::LEAD_SEARCHES_CREATE,
            Permissions::LEAD_SEARCHES_DELETE,
            Permissions::LEAD_SEARCH_PRESETS_VIEW,
            Permissions::LEAD_SEARCH_PRESETS_CREATE,
            Permissions::LEAD_SEARCH_PRESETS_UPDATE,
            Permissions::LEAD_SEARCH_PRESETS_DELETE,
            Permissions::EMAILS_SEND,
            Permissions::EMAILS_INBOX,
            Permissions::EMAIL_TEMPLATES_VIEW,
            Permissions::EMAIL_TEMPLATES_CREATE,
            Permissions::EMAIL_TEMPLATES_UPDATE,
            Permissions::EMAIL_TEMPLATES_DELETE,
            Permissions::REPORTS_VIEW,
            Permissions::REPORTS_EXPORT,
            Permissions::USERS_VIEW,
        ]);

        $sales->syncPermissions([
            Permissions::DASHBOARD_VIEW,
            Permissions::CONTACTS_VIEW,
            Permissions::CONTACTS_CREATE,
            Permissions::CONTACTS_UPDATE,
            Permissions::CAMPAIGNS_VIEW,
            Permissions::INTERACTIONS_VIEW,
            Permissions::INTERACTIONS_CREATE,
            Permissions::INTERACTIONS_UPDATE,
            Permissions::FOLLOW_UPS_VIEW,
            Permissions::FOLLOW_UPS_CREATE,
            Permissions::FOLLOW_UPS_UPDATE,
            Permissions::LEAD_SEARCHES_VIEW,
            Permissions::LEAD_SEARCHES_CREATE,
            Permissions::LEAD_SEARCH_PRESETS_VIEW,
            Permissions::LEAD_SEARCH_PRESETS_CREATE,
            Permissions::LEAD_SEARCH_PRESETS_UPDATE,
            Permissions::EMAILS_SEND,
            Permissions::EMAILS_INBOX,
            Permissions::EMAIL_TEMPLATES_VIEW,
            Permissions::EMAIL_TEMPLATES_CREATE,
            Permissions::EMAIL_TEMPLATES_UPDATE,
            Permissions::REPORTS_VIEW,
        ]);

        $viewer->syncPermissions([
            Permissions::DASHBOARD_VIEW,
            Permissions::CONTACTS_VIEW,
            Permissions::CAMPAIGNS_VIEW,
            Permissions::INTERACTIONS_VIEW,
            Permissions::FOLLOW_UPS_VIEW,
            Permissions::LEAD_SEARCHES_VIEW,
            Permissions::EMAILS_INBOX,
            Permissions::REPORTS_VIEW,
        ]);

        $adminUser = User::query()->updateOrCreate(
            ['email' => 'admin@salestracker.test'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        $adminUser->syncRoles(['admin']);

        $salesUser = User::query()->updateOrCreate(
            ['email' => 'sales@salestracker.test'],
            [
                'name' => 'Sales User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        $salesUser->syncRoles(['sales']);
    }
}
