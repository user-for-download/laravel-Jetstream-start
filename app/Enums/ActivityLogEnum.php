<?php

declare(strict_types=1);

namespace App\Enums;

enum ActivityLogEnum: string
{
    case TEAM_CREATED = 'team_created';
    case TEAM_UPDATED = 'updated';
    case MEMBER_ADDED = 'team_member_added';
    case MEMBER_REMOVED = 'team_member_removed';
    case ROLE_UPDATED = 'team_member_role_updated';
    case INVITATION_SENT = 'team_invitation_sent';
    case INVITATION_CANCELLED = 'team_invitation_cancelled';
    case OWNERSHIP_TRANSFERRED = 'team_ownership_transferred';
    case CREATED = 'created';
}
