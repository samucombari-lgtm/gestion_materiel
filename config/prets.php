<?php

/**
 * Réglages des prêts.
 *
 * Les valeurs viennent du fichier .env : on peut les changer sans toucher au code.
 * Dans le code, on les lit avec config('prets.max_prets_en_cours_eleve').
 *
 * Après une modification du .env en production, lancer « php artisan config:cache ».
 */

return [

    /*
     * RA-07 : nombre maximum de prêts en cours (en attente, validés ou sortis)
     * pour un élève. Valeur à préciser avec le lycée.
     */
    'max_prets_en_cours_eleve' => (int) env('MAX_PRETS_EN_COURS_ELEVE', 3),

];
