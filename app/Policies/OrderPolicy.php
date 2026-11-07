<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Customers may only see and act on their own orders.
     */
    public function view(User $user, Order $order): bool
    {
        return $order->user_id === $user->id;
    }
}
