{{--
    Page « Page expirée », affichée automatiquement par Laravel pour une erreur 419 :
    le jeton CSRF du formulaire est absent ou trop vieux (page restée ouverte
    après la fin de la session, ou formulaire forgé). L'action n'a PAS été faite (ES-07).
    Remplace la page anglaise de Laravel, dont le CSS écrit dans la page
    était bloqué par notre Content-Security-Policy.
--}}
@extends('layouts.principal')

@section('titre', 'Page expirée')

@section('contenu')
    <h1>Page expirée</h1>
    <p>Cette page est restée ouverte trop longtemps, ou le formulaire n'est plus valable.
       Votre action n'a pas été enregistrée.</p>
    <p>Rechargez la page, puis recommencez.</p>
    <p><a href="{{ url('/') }}">Retour à l'accueil</a></p>
@endsection
