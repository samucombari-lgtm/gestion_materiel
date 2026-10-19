{{--
    Formulaire de demande de prêt (BF-20).

    Variables envoyées par PretController@create :
     - $materiels : matériels disponibles ou prêtés (on peut réserver pour plus tard) ;
     - $materielChoisi : id présélectionné par le bouton « Emprunter » du catalogue ;
     - $estEleve, $nombrePretsEnCours, $maxPretsEnCours : pour afficher la limite RA-07.

    Les contrôles min="..." du navigateur sont pour le confort : le serveur
    revérifie tout (dates, matériel, limite, réservations).
--}}
@extends('layouts.principal')

@section('titre', 'Demander un prêt')

@section('contenu')
    <h1>Demander un prêt</h1>

    @if ($estEleve)
        <p class="information">
            Vous avez {{ $nombrePretsEnCours }} prêt(s) en cours sur {{ $maxPretsEnCours }} autorisés.
        </p>
    @endif

    @if ($errors->any())
        <div class="erreur" role="alert">
            @foreach ($errors->all() as $message)
                <p>{{ $message }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('prets.store') }}">
        @csrf

        <label for="materiel_id">Matériel</label>
        <select id="materiel_id" name="materiel_id" required>
            <option value="">— Choisir —</option>
            @foreach ($materiels as $materiel)
                <option value="{{ $materiel->id }}" @selected((int) old('materiel_id', $materielChoisi) === $materiel->id)>
                    {{ $materiel->nom }} ({{ $materiel->numero_inventaire }}) – {{ $materiel->libelleEtat() }}
                </option>
            @endforeach
        </select>

        <label for="date_debut">Date de début</label>
        <input type="date" id="date_debut" name="date_debut" value="{{ old('date_debut') }}"
               min="{{ now()->toDateString() }}" required>

        <label for="date_fin_prevue">Date de retour prévue</label>
        <input type="date" id="date_fin_prevue" name="date_fin_prevue" value="{{ old('date_fin_prevue') }}"
               min="{{ now()->toDateString() }}" required>

        <button type="submit">Envoyer la demande</button>
        <a href="{{ route('prets.index') }}" class="lien-annuler">Annuler</a>
    </form>
@endsection
