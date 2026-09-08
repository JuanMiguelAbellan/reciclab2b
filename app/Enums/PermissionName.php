<?php

namespace App\Enums;

enum PermissionName: string
{
    case ManageUsers = 'manage-users';
    case ManageCompanies = 'manage-companies';
    case ApproveUsers = 'approve-users';
    case ApproveCompanies = 'approve-companies';
}
