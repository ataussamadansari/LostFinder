<?php

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function (User $user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('conversation.{uuid}', function (User $user, string $uuid) {
    $conversation = Conversation::where('uuid', $uuid)->first();
    if (!$conversation) {
        return false;
    }

    if ($user->isAdmin() || $user->hasPermission('lost_items.view')) {
        return true;
    }

    return $conversation->participants()->where('users.id', $user->id)->exists();
});
