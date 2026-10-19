{{--
    Page « Action impossible », affichée automatiquement par Laravel pour une
    erreur 405 : l'adresse existe, mais pas avec cette méthode (par exemple
    /deconnexion ouverte en GET alors qu'elle n'accepte que POST).
    Remplace la page anglaise de Laravel, dont le CSS écrit dans la page
    était bloqué par notre Content-Security-Policy.
--}}
@extends('layouts.principal')

@section('titre', 'Action impossible')

@section('contenu')
    <h1>Action impossible</h1>
    <p>Cette action ne peut pas être faite de cette manière.</p>
    <p><a href="{{ url('/') }}">Retour à l'accueil</a></p>
@endsection
