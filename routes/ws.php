<?php

use WS;

WS::middleware(['ws.auth']) // optional group
->group(function () {
    WS::route('/chat/private', 'send_msg', [\App\Ws\Controllers\SendMsgController::class, 'index'])
        ->name('chat.private.send')
        ->middleware(['throttle:20,1']);

    WS::route('/chat/private', 'typing', [\App\Ws\Controllers\TypingController::class, 'index']);
});

// system routes
WS::route('/system', 'ping', fn () => ['pong' => true]);
