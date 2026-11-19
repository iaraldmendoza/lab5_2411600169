<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class InventoryChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    public function __construct(public array $stock, public bool $shouldAlert)
    {
    }
}
