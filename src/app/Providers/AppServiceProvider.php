<?php

namespace App\Providers;

use App\Contracts\ActivityLogRepositoryInterface;
use App\Contracts\AuthServiceInterface;
use App\Contracts\ClassifierCategoryRepositoryInterface;
use App\Contracts\CompanyGroupRepositoryInterface;
use App\Contracts\CompanyRepositoryInterface;
use App\Contracts\UserRepositoryInterface;
use App\Repositories\ActivityLogRepository;
use App\Repositories\ClassifierCategoryRepository;
use App\Repositories\CompanyGroupRepository;
use App\Repositories\CompanyRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AuthServiceInterface::class, AuthService::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(ActivityLogRepositoryInterface::class, ActivityLogRepository::class);
        $this->app->bind(CompanyGroupRepositoryInterface::class, CompanyGroupRepository::class);
        $this->app->bind(ClassifierCategoryRepositoryInterface::class, ClassifierCategoryRepository::class);
        $this->app->bind(CompanyRepositoryInterface::class, CompanyRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
