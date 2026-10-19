{{--
    Liste des prêts (BF-24).

    $prets ne contient QUE les prêts que l'utilisateur a le droit de voir :
    le filtrage est fait en base par Pret::visiblesPar() (élève : les siens ;
    enseignant : + élèves de ses classes ; gestionnaire : tous).

    $statutChoisi : statut du filtre (vide = tous).
--}}
@extends('layouts.principal')

@section('titre', 'Prêts')

@section('contenu')
    <h1>Prêts</h1>

    <form method="GET" action="{{ route('prets.index') }}" class="barre-recherche">
        <label for="statut">Statut</label>
        <select id="statut" name="statut">
            <option value="">Tous</option>
            @foreach (\App\Models\Pret::LIBELLES_STATUTS as $statut => $libelle)
                <option value="{{ $statut }}" @selected($statutChoisi === $statut)>{{ $libelle }}</option>
            @endforeach
        </select>
        <button type="submit">Filtrer</button>
    </form>

    @can('pret.demander')
        <p><a href="{{ route('prets.create') }}" class="bouton">Demander un prêt</a></p>
    @endcan

    @if ($prets->isEmpty())
        <p>Aucun prêt.</p>
    @else
        <table class="tableau">
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Matériel</th>
                    <th>Demandeur</th>
                    <th>Du</th>
                    <th>Au</th>
                    <th>Statut</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($prets as $pret)
                    <tr>
                        <td>{{ $pret->id }}</td>
                        <td>{{ $pret->materiel->nom }}</td>
                        <td>{{ $pret->demandeur->prenom }} {{ $pret->demandeur->nom }}</td>
                        <td>{{ $pret->date_debut->format('d/m/Y') }}</td>
                        <td>{{ $pret->date_fin_prevue->format('d/m/Y') }}</td>
                        <td><span class="statut statut-{{ $pret->statut }}">{{ $pret->libelleStatut() }}</span></td>
                        <td><a href="{{ route('prets.show', $pret->id) }}">Voir</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{ $prets->links('pagination::default') }}
    @endif
@endsection
