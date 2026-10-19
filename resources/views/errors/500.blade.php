{{--
    Page « Erreur du serveur », affichée par Laravel pour une erreur 500 quand
    APP_DEBUG=false (production). Aucun détail technique (ES-11).

    Volontairement AUTONOME (pas de @extends('layouts.principal')) : le gabarit
    interroge la base (@auth, @can) ; si l'erreur vient justement de la base
    (MariaDB arrêtée…), afficher le gabarit provoquerait une 2e erreur.
    Pas de CSS écrit dans la page : la Content-Security-Policy l'interdit.
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Erreur du serveur – Gestion du matériel</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <main class="conteneur">
        <h1>Erreur du serveur</h1>
        <p>Un problème technique empêche d'afficher cette page. Réessayez dans quelques instants.</p>
        <p>Si le problème continue, prévenez le service informatique du lycée.</p>
        <p><a href="{{ url('/') }}">Retour à l'accueil</a></p>
    </main>
</body>
</html>
