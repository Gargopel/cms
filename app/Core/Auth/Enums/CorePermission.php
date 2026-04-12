<?php

namespace App\Core\Auth\Enums;

enum CorePermission: string
{
    case AccessAdmin = 'access_admin';
    case ViewDashboard = 'view_dashboard';
    case ViewExtensions = 'view_extensions';
    case ManageExtensions = 'manage_extensions';
    case ViewThemes = 'view_themes';
    case ManageThemes = 'manage_themes';
    case ViewMaintenance = 'view_maintenance';
    case RunMaintenanceActions = 'run_maintenance_actions';
    case ManageUsers = 'manage_users';
    case ManageRoles = 'manage_roles';
    case ManagePermissions = 'manage_permissions';
    case ViewSettings = 'view_settings';
    case ManageSettings = 'manage_settings';
    case ViewAuditLogs = 'view_audit_logs';
    case ViewSystemHealth = 'view_system_health';

    public function label(): string
    {
        return match ($this) {
            self::AccessAdmin => 'Access Admin',
            self::ViewDashboard => 'View Dashboard',
            self::ViewExtensions => 'View Extensions',
            self::ManageExtensions => 'Manage Extensions',
            self::ViewThemes => 'View Themes',
            self::ManageThemes => 'Manage Themes',
            self::ViewMaintenance => 'View Maintenance',
            self::RunMaintenanceActions => 'Run Maintenance Actions',
            self::ManageUsers => 'Manage Users',
            self::ManageRoles => 'Manage Roles',
            self::ManagePermissions => 'Manage Permissions',
            self::ViewSettings => 'View Settings',
            self::ManageSettings => 'Manage Settings',
            self::ViewAuditLogs => 'View Audit Logs',
            self::ViewSystemHealth => 'View System Health',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::AccessAdmin => 'Permite entrar no painel administrativo do core.',
            self::ViewDashboard => 'Permite visualizar o dashboard operacional do core.',
            self::ViewExtensions => 'Permite visualizar o registro de extensoes e bootstrap.',
            self::ManageExtensions => 'Permite sincronizar e alterar o estado operacional das extensoes pelo admin do core.',
            self::ViewThemes => 'Permite visualizar a area administrativa de temas do core.',
            self::ManageThemes => 'Permite selecionar e trocar o tema ativo da instancia.',
            self::ViewMaintenance => 'Permite visualizar a area de manutencao do core.',
            self::RunMaintenanceActions => 'Permite executar acoes sensiveis de manutencao suportadas pelo core.',
            self::ManageUsers => 'Permite listar, criar e editar usuarios administrativos do core.',
            self::ManageRoles => 'Permite listar, criar e editar cargos e atribuicoes de cargos.',
            self::ManagePermissions => 'Permite visualizar permissoes e atribui-las aos cargos.',
            self::ViewSettings => 'Permite visualizar as configuracoes globais do core.',
            self::ManageSettings => 'Permite atualizar as configuracoes globais do core.',
            self::ViewAuditLogs => 'Permite consultar os logs de auditoria administrativa do core.',
            self::ViewSystemHealth => 'Permite consultar o diagnostico de saude operacional do sistema.',
        };
    }
}
