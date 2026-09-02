<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Contributions publiques (SEC-27)
    |--------------------------------------------------------------------------
    |
    | Les formulaires publics non authentifies (dons, inscriptions payantes)
    | ecrivent au grand livre. Pour limiter la pollution comptable :
    |
    | - le taux de change n'est jamais fourni par l'appelant : il est resolu
    |   cote serveur depuis la table exchange_rates ;
    | - le montant est plafonne par devise (au-dela, l'operation doit passer
    |   par un encaissement authentifie).
    |
    */

    'public_max_amount' => [
        'USD' => (float) env('PUBLIC_CONTRIBUTION_MAX_USD', 10000),
        'CDF' => (float) env('PUBLIC_CONTRIBUTION_MAX_CDF', 30000000),
    ],

];
