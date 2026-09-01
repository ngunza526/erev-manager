<?php

namespace App\Support;

/**
 * Referentiel canonique des roles et permissions eReve Church SaaS.
 *
 * Cinq roles metier remplacent l'ancienne liste heterogene, plus un role
 * technique "SuperAdmin plateforme" reserve a l'exploitant du SaaS et non
 * attribuable via l'interface (cree par commande console).
 *
 * La matrice role -> permissions est la seule source de verite : le seeder,
 * les middlewares de routes et les tests la consomment.
 */
final class Rbac
{
    // ---------------------------------------------------------------------
    // Roles
    // ---------------------------------------------------------------------
    public const ADMINISTRATEUR = 'Administrateur';

    public const ADMIN_FIN = 'AdminFin';

    public const CAISSIER = 'Caissier';

    public const AUDITEUR = 'Auditeur';

    public const SECRETAIRE = 'Secretaire';

    /** Role technique de l'editeur du SaaS. Jamais attribue via l'UI. */
    public const SUPERADMIN_PLATEFORME = 'SuperAdmin plateforme';

    // ---------------------------------------------------------------------
    // Permissions
    // ---------------------------------------------------------------------
    public const USERS_MANAGE = 'users.manage';

    public const ROLES_MANAGE = 'roles.manage';

    public const CHURCHES_MANAGE = 'churches.manage';

    public const AUDIT_VIEW = 'audit.view';

    public const DASHBOARD_VIEW = 'dashboard.view';

    public const REPORTS_VIEW = 'reports.view';

    public const REPORTS_PRODUCE = 'reports.produce';

    public const ACCOUNTING_POST = 'accounting.post';

    public const CASHBOX_OPERATE = 'cashbox.operate';

    public const CASHBOX_JOURNAL = 'cashbox.journal';

    public const REQUISITION_CREATE = 'requisition.create';

    public const REQUISITION_VALIDATE = 'requisition.validate';

    public const BUDGET_CONFIGURE = 'budget.configure';

    public const BUDGET_REVISE = 'budget.revise';

    public const BUDGET_APPROVE = 'budget.approve';

    public const BUDGET_MONITOR = 'budget.monitor';

    public const SOUBASSEMENT_VERIFY = 'soubassement.verify';

    public const MEMBERS_MANAGE = 'members.manage';

    public const SERVICES_MANAGE = 'services.manage';

    public const CONTRIBUTIONS_RECORD = 'contributions.record';

    public const TITHE_STATEMENTS = 'tithe.statements';

    public const BANK_RECONCILE = 'bank.reconcile';

    public const OFFLINE_SYNC = 'offline.sync';

    public const WORKSPACE_SWITCH = 'workspace.switch';

    /** Provisioning de locataires : reserve au role plateforme. */
    public const PLATFORM_TENANTS = 'platform.tenants.manage';

    /** Plan comptable de reference commun au SaaS : reserve au role plateforme. */
    public const PLATFORM_CHART_OF_ACCOUNTS = 'platform.chart_of_accounts.manage';

    /** Niveau (colonne roles.level) associe a chaque role. */
    public const LEVEL_PLATFORM = 'platform';

    public const LEVEL_COORDINATION = 'coordination';

    public const LEVEL_EGLISE = 'eglise';

    /**
     * @return list<string>
     */
    public static function permissions(): array
    {
        return [
            self::USERS_MANAGE,
            self::ROLES_MANAGE,
            self::CHURCHES_MANAGE,
            self::AUDIT_VIEW,
            self::DASHBOARD_VIEW,
            self::REPORTS_VIEW,
            self::REPORTS_PRODUCE,
            self::ACCOUNTING_POST,
            self::CASHBOX_OPERATE,
            self::CASHBOX_JOURNAL,
            self::REQUISITION_CREATE,
            self::REQUISITION_VALIDATE,
            self::BUDGET_CONFIGURE,
            self::BUDGET_REVISE,
            self::BUDGET_APPROVE,
            self::BUDGET_MONITOR,
            self::SOUBASSEMENT_VERIFY,
            self::MEMBERS_MANAGE,
            self::SERVICES_MANAGE,
            self::CONTRIBUTIONS_RECORD,
            self::TITHE_STATEMENTS,
            self::BANK_RECONCILE,
            self::OFFLINE_SYNC,
            self::WORKSPACE_SWITCH,
            self::PLATFORM_TENANTS,
            self::PLATFORM_CHART_OF_ACCOUNTS,
        ];
    }

    /**
     * @return list<string>
     */
    public static function roles(): array
    {
        return [
            self::ADMINISTRATEUR,
            self::ADMIN_FIN,
            self::CAISSIER,
            self::AUDITEUR,
            self::SECRETAIRE,
            self::SUPERADMIN_PLATEFORME,
        ];
    }

    /** Roles attribuables via l'interface de gestion des utilisateurs. */
    public static function assignableRoles(): array
    {
        return [
            self::ADMINISTRATEUR,
            self::ADMIN_FIN,
            self::CAISSIER,
            self::AUDITEUR,
            self::SECRETAIRE,
        ];
    }

    /**
     * Matrice role -> permissions. Source de verite unique.
     *
     * @return array<string, list<string>>
     */
    public static function matrix(): array
    {
        return [
            self::ADMINISTRATEUR => [
                self::USERS_MANAGE,
                self::ROLES_MANAGE,
                self::CHURCHES_MANAGE,
                self::AUDIT_VIEW,
                self::DASHBOARD_VIEW,
                self::REPORTS_VIEW,
                self::REPORTS_PRODUCE,
                self::REQUISITION_CREATE,
                self::REQUISITION_VALIDATE,
                self::BUDGET_APPROVE,
                self::BUDGET_MONITOR,
                self::WORKSPACE_SWITCH,
            ],
            self::ADMIN_FIN => [
                self::DASHBOARD_VIEW,
                self::REPORTS_VIEW,
                self::ACCOUNTING_POST,
                self::CASHBOX_OPERATE,
                self::CASHBOX_JOURNAL,
                self::REQUISITION_CREATE,
                self::REQUISITION_VALIDATE,
                self::BUDGET_CONFIGURE,
                self::BUDGET_REVISE,
                self::BUDGET_MONITOR,
                self::CONTRIBUTIONS_RECORD,
                self::BANK_RECONCILE,
                self::OFFLINE_SYNC,
            ],
            self::CAISSIER => [
                self::CASHBOX_OPERATE,
                self::CASHBOX_JOURNAL,
                self::REQUISITION_CREATE,
                self::CONTRIBUTIONS_RECORD,
                self::OFFLINE_SYNC,
            ],
            self::AUDITEUR => [
                self::DASHBOARD_VIEW,
                self::REPORTS_VIEW,
                self::SOUBASSEMENT_VERIFY,
                self::BUDGET_MONITOR,
            ],
            self::SECRETAIRE => [
                self::MEMBERS_MANAGE,
                self::SERVICES_MANAGE,
                self::TITHE_STATEMENTS,
                self::REQUISITION_CREATE,
                self::OFFLINE_SYNC,
            ],
            // Le role plateforme detient toutes les permissions, y compris
            // le provisioning de locataires et le plan comptable de reference.
            self::SUPERADMIN_PLATEFORME => self::permissions(),
        ];
    }

    /**
     * @return list<string>
     */
    public static function permissionsFor(string $role): array
    {
        return self::matrix()[$role] ?? [];
    }

    public static function levelFor(string $role): string
    {
        return match ($role) {
            self::SUPERADMIN_PLATEFORME => self::LEVEL_PLATFORM,
            self::ADMINISTRATEUR => self::LEVEL_COORDINATION,
            default => self::LEVEL_EGLISE,
        };
    }
}
