<?php
/**
 * Configuration for the Daily Timesheet Report.
 *
 * Keep real credentials OUT of version control – override these with
 * environment variables or a local (git-ignored) config in production.
 */

return [

    // ---- Database ----------------------------------------------------------
    'db' => [
        'host'    => getenv('DB_HOST')    ?: '127.0.0.1',
        'port'    => getenv('DB_PORT')    ?: '3306',
        'name'    => getenv('DB_NAME')    ?: 'workflow_test',
        'user'    => getenv('DB_USER')    ?: 'root',
        'pass'    => getenv('DB_PASS')    ?: 'root@123',
        'charset' => 'utf8mb4',
    ],

    // ---- Report filters (match the spec) -----------------------------------
    'filters' => [
        'stage' => 'FP',   // inw_transactions.stage
        'dept'  => 2,      // inw_transactions.dept (used by the daily timesheet report)

        // Standard working day length (hours). Only used by the daily
        // timesheet report to convert a percentage into work hours.
        'workday_hours' => 8,
    ],

    // ---- Mail --------------------------------------------------------------
    'mail' => [
        // Set to true to deliver over SMTP via PHPMailer (recommended).
        // Set to false to fall back to PHP's built-in mail().
        'use_smtp'   => true,

        'smtp_host'  => getenv('SMTP_HOST') ?: 'outlook.office365.com',
        'smtp_port'  => getenv('SMTP_PORT') ?: 587,
        'smtp_user'  => getenv('SMTP_USER') ?: 'techsupport@transforma.in',
        'smtp_pass'  => getenv('SMTP_PASS') ?: 'Tr@n$!t2018',
        'smtp_secure'=> 'tls',           // 'tls' or 'ssl'

        'from_email' => 'techsupport@transforma.in',
        'from_name'  => 'Daily Timesheet',

        // Recipients (comma separate or list more entries).
        'to' => [
            'jamal@transforma.in' => 'Operations Manager',
        ],
        'cc' => [
             'ananthi@transforma.in' => 'Ananthi Transforma',
			 'swamy@transforma.in' => 'Swamy Transforma',
        ],

        'subject_prefix' => 'Daily Timesheet Report',
    ],
];
