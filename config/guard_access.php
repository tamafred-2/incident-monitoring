<?php

/*
 * Restrictions applied to security guard accounts only.
 */
return [
    // Guards may sign in only from devices an admin has approved.
    'require_device' => env('GUARD_REQUIRE_DEVICE', true),

    // Guards must be within the premises radius of the subdivision's map pin.
    // The check is skipped while the subdivision has no pin set.
    'require_premises' => env('GUARD_REQUIRE_PREMISES', true),

    // Radius used when the subdivision has not set its own.
    'default_radius_meters' => 300,

    // GPS accuracy (meters) credited in the guard's favour, at most.
    'max_accuracy_allowance_meters' => 100,

    // A guard's location must be re-confirmed within this many minutes.
    'location_ttl_minutes' => 10,

    // How often an open page re-sends the guard's location.
    'heartbeat_seconds' => 120,
];
