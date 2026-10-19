{{--
    Tableau de bord PROVISOIRE : il sert seulement à vérifier la connexion et le
    rôle. Le vrai tableau de bord par rôle arrive à l'étape 7 (BF-30).
--}}
@extends('layouts.principal')

@section('titre', 'Tableau de bord')

@section('contenu')
    <h1>Bonjour {{ auth()->user()->prenom }} {{ auth()->user()->nom }}</h1>
    <p>Vous êtes connecté avec l'adresse {{ auth()->user()->email }}.</p>
    <p>Rôle : {{ auth()->user()->role->nom }}</p>
@endsection
