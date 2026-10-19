{{--
    Page « introuvable », affichée automatiquement par Laravel pour une erreur 404 :
    adresse inconnue, ou objet inexistant (ex. /materiels/9999/modifier).
--}}
@extends('layouts.principal')

@section('titre', 'Page introuvable')

@section('contenu')
    <h1>Page introuvable</h1>
    <p>La page demandée n'existe pas.</p>
    <p><a href="{{ url('/') }}">Retour à l'accueil</a></p>
@endsection
