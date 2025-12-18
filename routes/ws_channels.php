<?php

use WS;

// Like Laravel broadcasting channel authorization
WS::channel('private-chat.{chatId}', function ($user, $chatId) {
    return $user->can('viewChat', (int) $chatId);
});

WS::channel('presence-room.{roomId}', function ($user, $roomId) {
    return ['id' => $user->id, 'name' => $user->name];
});
