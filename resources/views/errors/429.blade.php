{{--
    Page « Trop de tentatives », affichée automatiquement par Laravel pour une
    erreur 429 : le middleware throttle: a bloqué trop de requêtes depuis la même
    adresse IP (par exemple plus de 10 essais de connexion en une minute).
--}}
@extends('layouts.principal')

@section('titre', 'Trop de tentatives')

@section('contenu')
    <h1>Trop de tentatives</h1>
    <p>Vous avez fait trop d'essais en peu de temps. Patientez une minute avant de réessayer.</p>
    <p><a href="{{ url('/') }}">Retour à l'accueil</a></p>
@endsection
