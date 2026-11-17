<?php
namespace App\Providers;
use App\Events\InventoryChanged;
use App\Listeners\SendLowStockAlerts;
use App\Models\{InventoryTransaction,User};
use App\Observers\InventoryTransactionObserver;
use Illuminate\Support\Facades\{Event,Gate,View};
use Illuminate\Support\ServiceProvider;
class AppServiceProvider extends ServiceProvider {
 public function register():void{}
 public function boot():void{
  InventoryTransaction::observe(InventoryTransactionObserver::class);
  Event::listen(InventoryChanged::class,[SendLowStockAlerts::class,'send']);
  Gate::define('manage-inventory',fn(User $user)=>$user->isAdmin());
  View::composer('layouts.inventory',function($view){
   $u=auth()->user();$view->with('unreadCount',$u?->unreadNotifications()->count()??0);
   $view->with('recentNotifications',$u?->notifications()->latest()->take(5)->get()??collect());
  });
 }
}
