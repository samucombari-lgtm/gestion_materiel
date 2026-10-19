{{--
    Gabarit commun à toutes les pages.
    Une page l'utilise avec @extends('layouts.principal'), puis remplit
    @section('titre') et @section('contenu').

    Le menu n'apparaît que pour un utilisateur connecté (@auth).
    @can cache les liens que le rôle n'a pas le droit d'ouvrir : c'est seulement
    pour le confort. La vraie protection est le middleware can: sur chaque route
    (RA-02) : taper l'adresse à la main donne quand même une erreur 403.
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titre') – Gestion du matériel</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    @auth
        <nav class="menu">
            <a href="{{ route('tableau-de-bord') }}">Tableau de bord</a>

            @can('materiel.voir')
                <a href="{{ route('materiels.index') }}">Matériel</a>
            @endcan

            @can('compte.gerer')
                <a href="{{ route('comptes') }}">Comptes</a>
            @endcan

            @can('journal.voir')
                <a href="{{ route('journal') }}">Journal</a>
            @endcan

            <a href="{{ route('mot-de-passe') }}">Mot de passe</a>

            <form method="POST" action="{{ route('deconnexion') }}" class="menu-deconnexion">
                @csrf
                <button type="submit">Se déconnecter</button>
            </form>
        </nav>
    @endauth

    <main class="conteneur">
        {{-- Message de confirmation envoyé par un contrôleur avec ->with('succes', '...') --}}
        @if (session('succes'))
            <p class="succes" role="status">{{ session('succes') }}</p>
        @endif

        @yield('contenu')
    </main>
</body>
</html>
