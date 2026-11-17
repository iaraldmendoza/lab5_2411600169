<?php
namespace App\Listeners;
use App\Events\InventoryChanged;
use App\Models\User;
use App\Notifications\LowStockNotification;
use Illuminate\Support\Facades\Notification;
class SendLowStockAlerts {
 public function send(InventoryChanged $event):void {
  if($event->shouldAlert)Notification::send(User::where('role','admin')->get(),(new LowStockNotification($event->stock))->afterCommit());
 }
}
