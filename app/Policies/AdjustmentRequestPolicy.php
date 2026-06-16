<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AdjustmentRequest;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdjustmentRequestPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AdjustmentRequest');
    }

    public function view(AuthUser $authUser, AdjustmentRequest $adjustmentRequest): bool
    {
        return $authUser->can('View:AdjustmentRequest');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AdjustmentRequest');
    }

    public function update(AuthUser $authUser, AdjustmentRequest $adjustmentRequest): bool
    {
        return $authUser->can('Update:AdjustmentRequest');
    }

    public function delete(AuthUser $authUser, AdjustmentRequest $adjustmentRequest): bool
    {
        return $authUser->can('Delete:AdjustmentRequest');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:AdjustmentRequest');
    }

    public function restore(AuthUser $authUser, AdjustmentRequest $adjustmentRequest): bool
    {
        return $authUser->can('Restore:AdjustmentRequest');
    }

    public function forceDelete(AuthUser $authUser, AdjustmentRequest $adjustmentRequest): bool
    {
        return $authUser->can('ForceDelete:AdjustmentRequest');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AdjustmentRequest');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AdjustmentRequest');
    }

    public function replicate(AuthUser $authUser, AdjustmentRequest $adjustmentRequest): bool
    {
        return $authUser->can('Replicate:AdjustmentRequest');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AdjustmentRequest');
    }

}