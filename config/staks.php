<?php

// Single place for the attendance rules that used to be typed out in
// several files (870 minutes, 50 metres, ...). Change them here only;
// controllers and pages read them from this config.
return [
    'latitude' => env('STAKS_LATITUDE'),
    'longitude' => env('STAKS_LONGITUDE'),

    // Weekly work target, Monday-Saturday, in minutes (14.5 hours).
    'target_minutes' => (int) env('STAKS_TARGET_MINUTES', 870),

    // How long a QR code lives before the cleanup job deletes it.
    'qr_cleanup_days' => (int) env('STAKS_QR_CLEANUP_DAYS', 3),

    // How long an approved/rejected penalty proof file is kept.
    'proof_retention_months' => (int) env('STAKS_PROOF_RETENTION_MONTHS', 1),

    // Largest penalty proof upload, in kilobytes (1 MB).
    'proof_upload_max_kb' => (int) env('STAKS_PROOF_UPLOAD_MAX_KB', 1024),

    // How long exact GPS coordinates are kept on an attendance row.
    // After this, latitude/longitude are deleted and only the
    // distance from the office remains.
    'gps_retention_days' => (int) env('STAKS_GPS_RETENTION_DAYS', 30),

    // How close to the office (in metres) a phone's GPS must report.
    // Note: this is a check on client-supplied coordinates, not proof of
    // presence — see AttendanceController.
    'radius_meters' => (int) env('STAKS_RADIUS_METERS', 50),
];
