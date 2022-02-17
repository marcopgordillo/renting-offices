<?php

namespace App\Policies;

use App\Models\Image;
use App\Models\Office;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;

class ImagePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Image  $image
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Image $image)
    {
        //
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Image  $image
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Image $image)
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Image  $image
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Image $image, Model $model)
    {
        return $user->id === $model->user->id
                && $image->imageable_type === 'office'
                && $image->imageable_id === $model->id
                && $user->tokenCan('images.delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Image  $image
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Image $image)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Image  $image
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Image $image)
    {
        //
    }
}
