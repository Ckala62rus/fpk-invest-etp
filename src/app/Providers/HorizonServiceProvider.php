<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\HorizonApplicationServiceProvider;

/**
 * Доступ к UI Laravel Horizon (фаза 12.1): только super_admin вне local.
 */
class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * @return void
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Gate просмотра очереди Horizon.
     *
     * @return void
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function (?User $user = null): bool {
            return $user !== null && $user->hasRole('super_admin');
        });
    }
}
