<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Private channel for Cashier & Managers (per DESIGN.md Section 5.2)
Broadcast::channel('cashier', function ($user) {
    return $user->hasRole(['cashier', 'manager', 'bumk_staff']);
});

// Private channel for Barista station
Broadcast::channel('bar', function ($user) {
    return $user->hasRole(['barista', 'manager', 'bumk_staff']);
});

// Private channel for Kitchen station
Broadcast::channel('kitchen', function ($user) {
    return $user->hasRole(['kitchen', 'manager', 'bumk_staff']);
});

// Private channel for Managers
Broadcast::channel('manager', function ($user) {
    return $user->hasRole(['manager', 'bumk_staff']);
});
