{{--
    Tableau de bord PROVISOIRE : il sert seulement à vérifier que la connexion
    marche. Le vrai tableau de bord par rôle arrive à l'étape 7 (BF-30).
--}}
@extends('layouts.principal')

@section('titre', 'Tableau de bord')

@section('contenu')
    <h1>Bonjour {{ auth()->user()->prenom }} {{ auth()->user()->nom }}</h1>
    <p>Vous êtes connecté avec l'adresse {{ auth()->user()->email }}.</p>

    <form method="POST" action="{{ route('deconnexion') }}">
        @csrf
        <button type="submit">Se déconnecter</button>
    </form>
@endsection
