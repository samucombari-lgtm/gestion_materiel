{{--
    Tableau de bord adapté au rôle (BF-30).

    Chaque variable vaut null si le rôle n'a pas la permission (voir
    TableauDeBordController) : @if (... !== null) n'affiche alors pas le bloc.

     - élève        : mes prêts en cours, mes retards ;
     - enseignant   : + demandes de ses élèves à valider ;
     - gestionnaire : + sorties à enregistrer, tous les retards ;
     - administrateur : sécurité des dernières 24 h.
--}}
@extends('layouts.principal')

@section('titre', 'Tableau de bord')

@section('contenu')
    <h1>Bonjour {{ auth()->user()->prenom }} {{ auth()->user()->nom }}</h1>

    @if ($retards !== null && $retards->isNotEmpty())
        <section class="bloc bloc-alerte">
            <h2>Retards ({{ $retards->count() }})</h2>
            @include('prets._liste-courte', ['prets' => $retards, 'messageSiVide' => '', 'avecDemandeur' => true])
        </section>
    @endif

    @if ($demandesAValider !== null)
        <section class="bloc">
            <h2>Demandes à valider</h2>
            @include('prets._liste-courte', [
                'prets' => $demandesAValider,
                'messageSiVide' => 'Aucune demande en attente.',
                'avecDemandeur' => true,
            ])
        </section>
    @endif

    @if ($sortiesAEnregistrer !== null)
        <section class="bloc">
            <h2>Sorties à enregistrer</h2>
            @include('prets._liste-courte', [
                'prets' => $sortiesAEnregistrer,
                'messageSiVide' => 'Aucun matériel à sortir aujourd\'hui.',
                'avecDemandeur' => true,
            ])
        </section>
    @endif

    @if ($mesPretsEnCours !== null)
        <section class="bloc">
            <h2>Mes prêts en cours</h2>
            @include('prets._liste-courte', [
                'prets' => $mesPretsEnCours,
                'messageSiVide' => 'Vous n\'avez aucun prêt en cours.',
                'avecDemandeur' => false,
            ])
            @can('pret.demander')
                <p><a href="{{ route('prets.create') }}" class="bouton">Demander un prêt</a></p>
            @endcan
        </section>
    @endif

    @if ($securite !== null)
        <section class="bloc">
            <h2>Sécurité – dernières 24 heures</h2>
            <div class="compteurs">
                <p class="compteur"><strong>{{ $securite['connexionsEchouees'] }}</strong> connexion(s) échouée(s)</p>
                <p class="compteur"><strong>{{ $securite['accesRefuses'] }}</strong> accès refusé(s)</p>
            </div>

            <h3>Derniers événements</h3>
            @include('journal._lignes', ['lignes' => $securite['derniersEvenements']])
            <p><a href="{{ route('journal') }}">Voir tout le journal →</a></p>
        </section>
    @endif
@endsection
