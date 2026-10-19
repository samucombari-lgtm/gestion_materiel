{{--
    Formulaire d'ajout ET de modification d'un matériel (BF-11, BF-12).

    $materiel->exists vaut false pour un nouveau matériel (ajout) et true pour un
    matériel déjà en base (modification). Cela choisit le titre, l'adresse et la
    méthode : POST pour ajouter, PUT pour modifier (@method('PUT'), car un
    formulaire HTML ne sait envoyer que GET ou POST).

    old('champ', valeur) : après une erreur de validation, réaffiche ce que
    l'utilisateur avait tapé ; sinon la valeur actuelle du matériel.

    $categories : catégories déjà utilisées, proposées par la liste <datalist>.
--}}
@extends('layouts.principal')

@php
    $estUnAjout = ! $materiel->exists;
@endphp

@section('titre', $estUnAjout ? 'Ajouter un matériel' : 'Modifier un matériel')

@section('contenu')
    <h1>{{ $estUnAjout ? 'Ajouter un matériel' : 'Modifier « ' . $materiel->nom . ' »' }}</h1>

    @if ($errors->any())
        <div class="erreur" role="alert">
            @foreach ($errors->all() as $message)
                <p>{{ $message }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ $estUnAjout ? route('materiels.store') : route('materiels.update', $materiel) }}">
        @csrf
        @unless ($estUnAjout)
            @method('PUT')
        @endunless

        <label for="nom">Nom</label>
        <input type="text" id="nom" name="nom" value="{{ old('nom', $materiel->nom) }}" maxlength="150" required>

        <label for="categorie">Catégorie</label>
        <input type="text" id="categorie" name="categorie" value="{{ old('categorie', $materiel->categorie) }}"
               maxlength="100" list="categories-existantes" required>
        <datalist id="categories-existantes">
            @foreach ($categories as $categorie)
                <option value="{{ $categorie }}">
            @endforeach
        </datalist>

        <label for="numero_inventaire">Numéro d'inventaire</label>
        <input type="text" id="numero_inventaire" name="numero_inventaire"
               value="{{ old('numero_inventaire', $materiel->numero_inventaire) }}" maxlength="50" required>

        <label for="etat">État</label>
        @if ($materiel->estPrete())
            {{-- Un matériel prêté change d'état seulement au retour du prêt (étape 6). --}}
            <p class="information">Prêté : l'état changera automatiquement au retour du matériel.</p>
        @else
            <select id="etat" name="etat" required>
                @foreach (\App\Models\Materiel::ETATS_CHOISISSABLES as $etat)
                    <option value="{{ $etat }}" @selected(old('etat', $materiel->etat) === $etat)>
                        {{ \App\Models\Materiel::LIBELLES_ETATS[$etat] }}
                    </option>
                @endforeach
            </select>
            @if ($materiel->estArchive())
                <p class="information">Ce matériel est archivé. Choisir un état et enregistrer le remet dans le catalogue.</p>
            @endif
        @endif

        <button type="submit">Enregistrer</button>
        <a href="{{ route('materiels.index') }}" class="lien-annuler">Annuler</a>
    </form>
@endsection
