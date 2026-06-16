<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Pajak;
use Illuminate\Auth\Access\HandlesAuthorization;

class PajakPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Pajak');
    }

    public function view(AuthUser $authUser, Pajak $pajak): bool
    {
        return $authUser->can('View:Pajak');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Pajak');
    }

    public function update(AuthUser $authUser, Pajak $pajak): bool
    {
        return $authUser->can('Update:Pajak');
    }

    public function delete(AuthUser $authUser, Pajak $pajak): bool
    {
        return $authUser->can('Delete:Pajak');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Pajak');
    }

    public function restore(AuthUser $authUser, Pajak $pajak): bool
    {
        return $authUser->can('Restore:Pajak');
    }

    public function forceDelete(AuthUser $authUser, Pajak $pajak): bool
    {
        return $authUser->can('ForceDelete:Pajak');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Pajak');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Pajak');
    }

    public function replicate(AuthUser $authUser, Pajak $pajak): bool
    {
        return $authUser->can('Replicate:Pajak');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Pajak');
    }

}