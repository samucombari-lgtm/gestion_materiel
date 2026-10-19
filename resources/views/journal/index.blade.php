{{--
    Journal de sécurité (BF-31), réservé à l'administrateur (journal.voir).

    Lecture seule : aucun bouton de modification ou de suppression, et aucune
    route pour le faire (voir JournalController).

    $lignes : 50 lignes par page, les plus récentes d'abord.
    $filtres : filtres validés (action, recherche, du, au).
--}}
@extends('layouts.principal')

@section('titre', 'Journal de sécurité')

@section('contenu')
    <h1>Journal de sécurité</h1>

    @if ($errors->any())
        <div class="erreur" role="alert">
            @foreach ($errors->all() as $message)
                <p>{{ $message }}</p>
            @endforeach
        </div>
    @endif

    <form method="GET" action="{{ route('journal') }}" class="barre-recherche">
        <div>
            <label for="action">Action</label>
            <select id="action" name="action">
                <option value="">Toutes</option>
                @foreach (\App\Models\Journal::LIBELLES_ACTIONS as $action => $libelle)
                    <option value="{{ $action }}" @selected(($filtres['action'] ?? '') === $action)>{{ $libelle }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="recherche">E-mail ou objet</label>
            <input type="search" id="recherche" name="recherche" value="{{ $filtres['recherche'] ?? '' }}" maxlength="100">
        </div>
        <div>
            <label for="du">Du</label>
            <input type="date" id="du" name="du" value="{{ $filtres['du'] ?? '' }}">
        </div>
        <div>
            <label for="au">Au</label>
            <input type="date" id="au" name="au" value="{{ $filtres['au'] ?? '' }}">
        </div>
        <button type="submit">Filtrer</button>
    </form>

    @include('journal._lignes', ['lignes' => $lignes])

    {{ $lignes->links('pagination::default') }}
@endsection
