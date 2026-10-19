{{--
    Création ET modification d'un compte (BF-04).

    $compte->exists vaut false pour une création, true pour une modification.
     - Création : le rôle est choisi dans ce formulaire ; un mot de passe
       temporaire est généré par l'application (pas de champ mot de passe ici).
     - Modification : le rôle se change dans un formulaire SÉPARÉ plus bas, car
       il a ses propres règles (RA-05) et ferme les sessions de la personne (RA-06).

    $roles, $classes : listes pour les menus.
    $idsClassesDuCompte : classes déjà cochées.
--}}
@extends('layouts.principal')

@php
    $estUneCreation = ! $compte->exists;
    $idsClassesCochees = array_map('intval', old('classes', $idsClassesDuCompte));
@endphp

@section('titre', $estUneCreation ? 'Créer un compte' : 'Modifier un compte')

@section('contenu')
    <h1>{{ $estUneCreation ? 'Créer un compte' : 'Modifier le compte de ' . $compte->prenom . ' ' . $compte->nom }}</h1>

    @if ($errors->any())
        <div class="erreur" role="alert">
            @foreach ($errors->all() as $message)
                <p>{{ $message }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ $estUneCreation ? route('comptes.store') : route('comptes.update', $compte) }}">
        @csrf
        @unless ($estUneCreation)
            @method('PUT')
        @endunless

        <label for="prenom">Prénom</label>
        <input type="text" id="prenom" name="prenom" value="{{ old('prenom', $compte->prenom) }}" maxlength="100" required>

        <label for="nom">Nom</label>
        <input type="text" id="nom" name="nom" value="{{ old('nom', $compte->nom) }}" maxlength="100" required>

        <label for="email">Adresse e-mail (identifiant de connexion)</label>
        <input type="email" id="email" name="email" value="{{ old('email', $compte->email) }}" maxlength="255" required>

        @if ($estUneCreation)
            <label for="role_id">Rôle</label>
            <select id="role_id" name="role_id" required>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}" @selected((int) old('role_id', $compte->role_id) === $role->id)>
                        {{ $role->libelle() }}
                    </option>
                @endforeach
            </select>
        @endif

        <fieldset class="liste-classes">
            <legend>Classes (élèves et enseignants seulement)</legend>
            @forelse ($classes as $classe)
                <label class="case-a-cocher">
                    <input type="checkbox" name="classes[]" value="{{ $classe->id }}"
                           @checked(in_array($classe->id, $idsClassesCochees, true))>
                    {{ $classe->nom }}
                </label>
            @empty
                <p class="vide">Aucune classe n'existe encore.</p>
            @endforelse
        </fieldset>

        <button type="submit">{{ $estUneCreation ? 'Créer le compte' : 'Enregistrer' }}</button>
        <a href="{{ route('comptes.index') }}" class="lien-annuler">Annuler</a>
    </form>

    @unless ($estUneCreation)
        @can('changerRole', $compte)
            <section class="bloc">
                <h2>Rôle</h2>
                <p>Rôle actuel : <strong>{{ $compte->role->libelle() }}</strong>.
                   Changer le rôle déconnecte immédiatement la personne.</p>
                <form method="POST" action="{{ route('comptes.role', $compte) }}"
                      data-confirmation="Changer le rôle ? La personne sera déconnectée.">
                    @csrf
                    @method('PATCH')
                    <label for="nouveau_role">Nouveau rôle</label>
                    <select id="nouveau_role" name="role_id" required>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" @selected($compte->role_id === $role->id)>{{ $role->libelle() }}</option>
                        @endforeach
                    </select>
                    <button type="submit">Changer le rôle</button>
                </form>
            </section>
        @endcan
    @endunless
@endsection
