<?php

namespace App\Providers;

use App\Support\VisitorActivityFeed;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer(['layouts.app', 'layouts.navigation'], function ($view): void {
            $user = auth()->user();

            if (!$user || !$user->isAdmin() || !Schema::hasTable('visitors')) {
                $view->with('adminVisitorNotifications', collect());
                $view->with('adminVisitorUnreadCount', 0);

                return;
            }

            $view->with('adminVisitorNotifications', VisitorActivityFeed::recentForUser($user));
            $view->with('adminVisitorUnreadCount', VisitorActivityFeed::unreadCountForUser($user));
        });
    }
}
