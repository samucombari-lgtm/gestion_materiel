{{--
    Détail d'un prêt et actions possibles.

    On n'arrive ici que si PretPolicy::view() a dit oui (sinon 403, voir PretController).

    Chaque bouton est affiché seulement si la Policy l'autorise (@can('valider', $pret)...).
    C'est pour le confort : chaque action revérifie la même Policy sur le serveur (RA-02).
--}}
@extends('layouts.principal')

@section('titre', 'Prêt n° ' . $pret->id)

@section('contenu')
    <h1>Prêt n° {{ $pret->id }}</h1>

    @if ($errors->any())
        <div class="erreur" role="alert">
            @foreach ($errors->all() as $message)
                <p>{{ $message }}</p>
            @endforeach
        </div>
    @endif

    <table class="tableau tableau-detail">
        <tr><th>Statut</th><td><span class="statut statut-{{ $pret->statut }}">{{ $pret->libelleStatut() }}</span></td></tr>
        <tr><th>Matériel</th><td>{{ $pret->materiel->nom }} ({{ $pret->materiel->numero_inventaire }})</td></tr>
        <tr><th>Demandeur</th><td>{{ $pret->demandeur->prenom }} {{ $pret->demandeur->nom }}</td></tr>
        <tr><th>Demandé le</th><td>{{ $pret->created_at->format('d/m/Y à H:i') }}</td></tr>
        <tr><th>Du</th><td>{{ $pret->date_debut->format('d/m/Y') }}</td></tr>
        <tr><th>Au (retour prévu)</th><td>{{ $pret->date_fin_prevue->format('d/m/Y') }}</td></tr>
        @if ($pret->validateur)
            <tr><th>{{ $pret->statut === \App\Models\Pret::REFUSE ? 'Refusé par' : 'Validé par' }}</th>
                <td>{{ $pret->validateur->prenom }} {{ $pret->validateur->nom }}</td></tr>
        @endif
        @if ($pret->motif_refus)
            <tr><th>Motif du refus</th><td>{{ $pret->motif_refus }}</td></tr>
        @endif
        @if ($pret->date_retour)
            <tr><th>Rendu le</th><td>{{ $pret->date_retour->format('d/m/Y') }}</td></tr>
            <tr><th>État au retour</th><td>{{ \App\Models\Materiel::LIBELLES_ETATS[$pret->etat_retour] }}</td></tr>
        @endif
    </table>

    @can('valider', $pret)
        <h2>Décision</h2>
        <form method="POST" action="{{ route('prets.valider', $pret->id) }}">
            @csrf
            @method('PATCH')
            <button type="submit">Valider la demande</button>
        </form>

        <form method="POST" action="{{ route('prets.refuser', $pret->id) }}">
            @csrf
            @method('PATCH')
            <label for="motif_refus">Motif du refus (obligatoire pour refuser)</label>
            <input type="text" id="motif_refus" name="motif_refus" value="{{ old('motif_refus') }}" maxlength="255" required>
            <button type="submit" class="bouton-danger">Refuser la demande</button>
        </form>
    @endcan

    @can('sortir', $pret)
        <h2>Sortie</h2>
        <form method="POST" action="{{ route('prets.sortir', $pret->id) }}">
            @csrf
            @method('PATCH')
            <button type="submit">Enregistrer la sortie du matériel</button>
        </form>
    @endcan

    @can('rendre', $pret)
        <h2>Retour</h2>
        <form method="POST" action="{{ route('prets.rendre', $pret->id) }}">
            @csrf
            @method('PATCH')
            <label for="etat_retour">État du matériel au retour</label>
            <select id="etat_retour" name="etat_retour" required>
                @foreach (\App\Models\Pret::ETATS_RETOUR as $etat)
                    <option value="{{ $etat }}" @selected(old('etat_retour') === $etat)>{{ \App\Models\Materiel::LIBELLES_ETATS[$etat] }}</option>
                @endforeach
            </select>
            <button type="submit">Enregistrer le retour</button>
        </form>
    @endcan

    @can('annuler', $pret)
        <form method="POST" action="{{ route('prets.annuler', $pret->id) }}"
              onsubmit="return confirm('Annuler ce prêt ?');">
            @csrf
            @method('PATCH')
            <button type="submit" class="bouton-secondaire">Annuler le prêt</button>
        </form>
    @endcan

    <p><a href="{{ route('prets.index') }}">← Retour à la liste</a></p>
@endsection
