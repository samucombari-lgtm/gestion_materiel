{{--
    Page de connexion (BF-01). Seule page accessible à un visiteur.

    Sécurité :
     - @csrf ajoute un jeton caché que Laravel vérifie (ES-07) ;
     - {{ }} échappe tout ce qui est affiché, donc pas de XSS (ES-06) ;
     - old('email') réaffiche l'e-mail saisi ; le mot de passe n'est jamais réaffiché.
--}}
@extends('layouts.principal')

@section('titre', 'Connexion')

@section('contenu')
    <h1>Connexion</h1>

    @if ($errors->any())
        <div class="erreur" role="alert">
            @foreach ($errors->all() as $message)
                <p>{{ $message }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('connexion') }}">
        @csrf

        <label for="email">Adresse e-mail</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}"
               autocomplete="username" required autofocus>

        <label for="mot_de_passe">Mot de passe</label>
        <input type="password" id="mot_de_passe" name="mot_de_passe"
               autocomplete="current-password" required>

        <button type="submit">Se connecter</button>
    </form>

    {{-- Mention d'information RGPD (cahier des charges, section 6). --}}
    <p class="mention-rgpd">
        Les informations de votre compte (nom, prénom, classe, e-mail, rôle, historique des prêts)
        sont utilisées uniquement pour gérer le prêt du matériel du lycée.
        Responsable du traitement : le lycée. Contact : à préciser.
    </p>
@endsection
