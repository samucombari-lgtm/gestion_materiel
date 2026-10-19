{{--
    Page « Accès refusé » (RA-08), affichée automatiquement par Laravel pour
    toute erreur 403 : middleware can:, $this->authorize() refusé, abort(403).

    Refus discret : aucun détail technique, et on n'affiche jamais le message de
    l'exception, qui pourrait révéler pourquoi l'accès est refusé.
    L'enregistrement de la tentative dans le journal arrive à l'étape 7.
--}}
@extends('layouts.principal')

@section('titre', 'Accès refusé')

@section('contenu')
    <h1>Accès refusé</h1>
    <p>Vous n'avez pas le droit d'accéder à cette page.</p>
    <p><a href="{{ url('/') }}">Retour à l'accueil</a></p>
@endsection
