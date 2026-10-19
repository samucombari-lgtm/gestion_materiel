<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Un visiteur non connecté qui ouvre l'accueil est renvoyé vers la connexion.
     * Les vrais tests d'autorisation (section 5.7) arrivent à l'étape 8.
     */
    public function test_un_visiteur_est_redirige_vers_la_connexion(): void
    {
        $this->get('/')->assertRedirect('/tableau-de-bord');
        $this->get('/tableau-de-bord')->assertRedirect('/connexion');
    }
}
