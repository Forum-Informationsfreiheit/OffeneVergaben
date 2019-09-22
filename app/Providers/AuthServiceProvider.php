<?php

namespace App\Providers;

use App\Role;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        $this->defineAbilities();
    }

    protected function defineAbilities() {

        Gate::before(function ($user, $ability) {
            if ($user->isSuperAdmin()) {
                return true;
            }
        });

        Gate::define('show-page',function($user, $page) {
            if ($user->role_id >= Role::EDITOR) {
                return true;
            }

            return false;
        });

        Gate::define('show-news',function($user, $post) {
            if ($user->role_id >= Role::EDITOR) {
                return true;
            }

            return false;
        });

        Gate::define('create-user',function($user) {
            return $user->isAdmin();
        });
        Gate::define('update-user',function($user, $userToEdit) {
            return $user->isAdmin() || $user->id === $userToEdit->id;
        });
        Gate::define('delete-user',function($user) {
            return $user->isAdmin();
        });

        Gate::define('update-tags',function($user) {
            return $user->role_id >= Role::EDITOR;
        });
        Gate::define('update-pages',function($user) {
            return $user->role_id >= Role::EDITOR;
        });
        Gate::define('update-posts',function($user) {
            return $user->role_id >= Role::EDITOR;
        });
    }
}
