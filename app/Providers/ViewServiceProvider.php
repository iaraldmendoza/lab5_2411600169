<?php

namespace App\Providers;

use App\Services\ProcurementWidgetService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        foreach (['pending-orders', 'recent-orders', 'supplier-performance', 'reorder-alerts'] as $name) {
            View::composer('widgets.'.$name, function ($view) use ($name) {
                $view->with('widget', app(ProcurementWidgetService::class)->data($name));
            });
        }
    }
}
