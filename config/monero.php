<?php

return [
    'rpc_host' => env('MONERO_RPC_HOST'),
    'rpc_port' => env('MONERO_RPC_PORT'),
    'rpc_user' => env('MONERO_RPC_USER'),
    'rpc_password' => env('MONERO_RPC_PASSWORD'),
    'account_index' => env('MONERO_ACCOUNT_INDEX', 0),
];

