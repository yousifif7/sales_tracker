<?php

namespace App\Support;

final class Permissions
{
    public const DASHBOARD_VIEW = 'dashboard.view';

    public const CONTACTS_VIEW = 'contacts.view';
    public const CONTACTS_CREATE = 'contacts.create';
    public const CONTACTS_UPDATE = 'contacts.update';
    public const CONTACTS_DELETE = 'contacts.delete';

    public const CAMPAIGNS_VIEW = 'campaigns.view';
    public const CAMPAIGNS_CREATE = 'campaigns.create';
    public const CAMPAIGNS_UPDATE = 'campaigns.update';
    public const CAMPAIGNS_DELETE = 'campaigns.delete';

    public const INTERACTIONS_VIEW = 'interactions.view';
    public const INTERACTIONS_CREATE = 'interactions.create';
    public const INTERACTIONS_UPDATE = 'interactions.update';
    public const INTERACTIONS_DELETE = 'interactions.delete';

    public const FOLLOW_UPS_VIEW = 'follow-ups.view';
    public const FOLLOW_UPS_CREATE = 'follow-ups.create';
    public const FOLLOW_UPS_UPDATE = 'follow-ups.update';
    public const FOLLOW_UPS_DELETE = 'follow-ups.delete';

    public const LEAD_SEARCHES_VIEW = 'lead-searches.view';
    public const LEAD_SEARCHES_CREATE = 'lead-searches.create';
    public const LEAD_SEARCHES_DELETE = 'lead-searches.delete';

    public const LEAD_SEARCH_PRESETS_VIEW = 'lead-search-presets.view';
    public const LEAD_SEARCH_PRESETS_CREATE = 'lead-search-presets.create';
    public const LEAD_SEARCH_PRESETS_UPDATE = 'lead-search-presets.update';
    public const LEAD_SEARCH_PRESETS_DELETE = 'lead-search-presets.delete';

    public const EMAILS_SEND = 'emails.send';
    public const EMAILS_INBOX = 'emails.inbox';

    public const EMAIL_TEMPLATES_VIEW = 'email-templates.view';
    public const EMAIL_TEMPLATES_CREATE = 'email-templates.create';
    public const EMAIL_TEMPLATES_UPDATE = 'email-templates.update';
    public const EMAIL_TEMPLATES_DELETE = 'email-templates.delete';

    public const REPORTS_VIEW = 'reports.view';
    public const REPORTS_EXPORT = 'reports.export';

    public const USERS_VIEW = 'users.view';
    public const USERS_CREATE = 'users.create';
    public const USERS_UPDATE = 'users.update';
    public const USERS_DELETE = 'users.delete';

    public const ROLES_VIEW = 'roles.view';
    public const ROLES_CREATE = 'roles.create';
    public const ROLES_UPDATE = 'roles.update';
    public const ROLES_DELETE = 'roles.delete';

    /**
     * @return array<string, list<string>>
     */
    public static function grouped(): array
    {
        return [
            'Dashboard' => [
                self::DASHBOARD_VIEW,
            ],
            'Contacts' => [
                self::CONTACTS_VIEW,
                self::CONTACTS_CREATE,
                self::CONTACTS_UPDATE,
                self::CONTACTS_DELETE,
            ],
            'Campaigns' => [
                self::CAMPAIGNS_VIEW,
                self::CAMPAIGNS_CREATE,
                self::CAMPAIGNS_UPDATE,
                self::CAMPAIGNS_DELETE,
            ],
            'Interactions' => [
                self::INTERACTIONS_VIEW,
                self::INTERACTIONS_CREATE,
                self::INTERACTIONS_UPDATE,
                self::INTERACTIONS_DELETE,
            ],
            'Follow-ups' => [
                self::FOLLOW_UPS_VIEW,
                self::FOLLOW_UPS_CREATE,
                self::FOLLOW_UPS_UPDATE,
                self::FOLLOW_UPS_DELETE,
            ],
            'AI Lead Search' => [
                self::LEAD_SEARCHES_VIEW,
                self::LEAD_SEARCHES_CREATE,
                self::LEAD_SEARCHES_DELETE,
            ],
            'AI Prompts' => [
                self::LEAD_SEARCH_PRESETS_VIEW,
                self::LEAD_SEARCH_PRESETS_CREATE,
                self::LEAD_SEARCH_PRESETS_UPDATE,
                self::LEAD_SEARCH_PRESETS_DELETE,
            ],
            'Emails' => [
                self::EMAILS_SEND,
                self::EMAILS_INBOX,
            ],
            'Email Templates' => [
                self::EMAIL_TEMPLATES_VIEW,
                self::EMAIL_TEMPLATES_CREATE,
                self::EMAIL_TEMPLATES_UPDATE,
                self::EMAIL_TEMPLATES_DELETE,
            ],
            'Reports' => [
                self::REPORTS_VIEW,
                self::REPORTS_EXPORT,
            ],
            'Users' => [
                self::USERS_VIEW,
                self::USERS_CREATE,
                self::USERS_UPDATE,
                self::USERS_DELETE,
            ],
            'Roles & Permissions' => [
                self::ROLES_VIEW,
                self::ROLES_CREATE,
                self::ROLES_UPDATE,
                self::ROLES_DELETE,
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return collect(self::grouped())->flatten()->values()->all();
    }

    public static function label(string $permission): string
    {
        return str($permission)
            ->after('.')
            ->replace('-', ' ')
            ->title()
            ->toString();
    }
}
