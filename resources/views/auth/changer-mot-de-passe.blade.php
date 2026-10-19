{{--
    Changement du mot de passe (section 5.6).
    $changementObligatoire (envoyé par MotDePasseController) vaut true à la
    première connexion : on affiche alors un message d'explication.
--}}
@extends('layouts.principal')

@section('titre', 'Changer le mot de passe')

@section('contenu')
    <h1>Changer le mot de passe</h1>

    @if ($changementObligatoire)
        <p class="information">
            Vous utilisez un mot de passe temporaire. Choisissez votre propre mot de passe pour continuer.
        </p>
    @endif

    @if ($errors->any())
        <div class="erreur" role="alert">
            @foreach ($errors->all() as $message)
                <p>{{ $message }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('mot-de-passe') }}">
        @csrf

        <label for="mot_de_passe_actuel">Mot de passe actuel</label>
        <input type="password" id="mot_de_passe_actuel" name="mot_de_passe_actuel"
               autocomplete="current-password" required autofocus>

        <label for="nouveau_mot_de_passe">Nouveau mot de passe (12 caractères minimum)</label>
        <input type="password" id="nouveau_mot_de_passe" name="nouveau_mot_de_passe"
               autocomplete="new-password" minlength="12" maxlength="72" required>

        <label for="nouveau_mot_de_passe_confirmation">Confirmer le nouveau mot de passe</label>
        <input type="password" id="nouveau_mot_de_passe_confirmation" name="nouveau_mot_de_passe_confirmation"
               autocomplete="new-password" minlength="12" maxlength="72" required>

        <button type="submit">Enregistrer</button>
    </form>

    <form method="POST" action="{{ route('deconnexion') }}">
        @csrf
        <button type="submit" class="bouton-secondaire">Se déconnecter</button>
    </form>
@endsection
