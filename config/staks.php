<?php

// Single place for the attendance rules that used to be typed out in
// several files (870 minutes, 50 metres, ...). Change them here only;
// controllers and pages read them from this config.
return [
    'latitude' => env('STAKS_LATITUDE'),
    'longitude' => env('STAKS_LONGITUDE'),

    // Weekly work target, Monday-Saturday, in minutes (14.5 hours).
    'target_minutes' => (int) env('STAKS_TARGET_MINUTES', 870),

    // How close to the office (in metres) a phone's GPS must report.
    // Note: this is a check on client-supplied coordinates, not proof of
    // presence — see AttendanceController.
    'radius_meters' => (int) env('STAKS_RADIUS_METERS', 50),
];
