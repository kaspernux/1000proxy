<?php
return [
    // Minutes to keep a dedicated inbound without active clients before cleanup
    'dedicated_inbound_cleanup_grace' => env('DEDICATED_INBOUND_CLEANUP_GRACE', 30),

    // Safe dynamic port allocation range for dedicated inbounds
    'dedicated_inbound_port_min' => env('DEDICATED_INBOUND_PORT_MIN', 20000),
    'dedicated_inbound_port_max' => env('DEDICATED_INBOUND_PORT_MAX', 60000),
    'dedicated_inbound_port_lock_seconds' => env('DEDICATED_INBOUND_PORT_LOCK_SECONDS', 30),
];
