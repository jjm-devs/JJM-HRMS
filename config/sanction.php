<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sanction Order — fixed values
    |--------------------------------------------------------------------------
    |
    | Values that rarely change live here (dev-managed). The list of activities
    | is dynamic and managed by admins via Filament (sanction_activities table).
    |
    */

    // State Holding Account number printed in the subject and body.
    'account_no' => '475405000024',

    // Reference / Memo number prefix. The year and a (blank) serial are appended
    // at generation time, e.g. "JJMA-127/HRD/2026/____".
    'reference_prefix' => 'JJMA-127/HRD/',

    // Selectable signatory blocks. HR picks one when generating the letter.
    'signatories' => [
        'amd_nt' => [
            'Additional Mission Director (N/T), JJM',
            'Assam, Hengrabari, Guwahati-36',
        ],
        'ce_amd_t' => [
            'Chief Engineer (Water) cum',
            'Additional Mission Director (T), JJM',
            'Assam, Hengrabari, Guwahati-36',
        ],
    ],

    // Default "Copy to:" recipients. Pre-filled in the modal; HR can edit per letter.
    'copy_to_default' => [
        'The Mission Director, Jal Jeevan Mission for favour of information and as per approval.',
        'The Chief Engineer(T), PHE, (Water) for information.',
        'The EE, PHED(B), Jal Jeevan Mission Assam for kind information.',
    ],

    // Standard terms and conditions block.
    'terms' => [
        'The JJM Guidelines should be followed strictly.',
        'No expenditure is to be incurred for which IMIS entry cannot be done.',
        'Cash book and ledger should be maintained against each expenditure.',
    ],

];
