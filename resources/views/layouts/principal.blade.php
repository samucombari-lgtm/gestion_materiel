{{--
    Gabarit commun à toutes les pages.
    Une page l'utilise avec @extends('layouts.principal'), puis remplit
    @section('titre') et @section('contenu').
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
    <main class="conteneur">
        @yield('contenu')
    </main>
</body>
</html>
