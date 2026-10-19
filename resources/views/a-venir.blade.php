{{--
    Page PROVISOIRE pour les écrans pas encore construits.
    $titre est donné par la route (routes/web.php).
    Elle sert à vérifier que le middleware can: laisse passer les bons rôles.
--}}
@extends('layouts.principal')

@section('titre', $titre)

@section('contenu')
    <h1>{{ $titre }}</h1>
    <p>Cette page sera bientôt disponible.</p>
@endsection
