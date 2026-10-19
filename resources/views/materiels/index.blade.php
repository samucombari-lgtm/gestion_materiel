{{--
    Catalogue du matériel (BF-10). Ouvert à tous les rôles qui ont materiel.voir.

    Variables envoyées par MaterielController@index :
     - $materiels : la page de matériels à afficher (20 par page) ;
     - $recherche : le texte recherché ;
     - $afficherArchives : true si le gestionnaire a coché « Afficher les archivés ».

    Les boutons Ajouter / Modifier / Archiver sont cachés avec @can('materiel.gerer') ;
    leurs routes sont de toute façon protégées par le middleware can: (RA-02).
--}}
@extends('layouts.principal')

@section('titre', 'Catalogue du matériel')

@section('contenu')
    <h1>Catalogue du matériel</h1>

    @if ($errors->any())
        <div class="erreur" role="alert">
            @foreach ($errors->all() as $message)
                <p>{{ $message }}</p>
            @endforeach
        </div>
    @endif

    <form method="GET" action="{{ route('materiels.index') }}" class="barre-recherche">
        <label for="recherche">Rechercher (nom ou catégorie)</label>
        <input type="search" id="recherche" name="recherche" value="{{ $recherche }}" maxlength="100">

        @can('materiel.gerer')
            <label class="case-a-cocher">
                <input type="checkbox" name="archives" value="1" @checked($afficherArchives)>
                Afficher les archivés
            </label>
        @endcan

        <button type="submit">Rechercher</button>
    </form>

    @can('materiel.gerer')
        <p><a href="{{ route('materiels.create') }}" class="bouton">Ajouter un matériel</a></p>
    @endcan

    @if ($materiels->isEmpty())
        <p>Aucun matériel trouvé.</p>
    @else
        <table class="tableau">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Catégorie</th>
                    <th>N° d'inventaire</th>
                    <th>État</th>
                    <th>Disponibilité</th>
                    @can('pret.demander')
                        <th>Prêt</th>
                    @endcan
                    @can('materiel.gerer')
                        <th>Actions</th>
                    @endcan
                </tr>
            </thead>
            <tbody>
                @foreach ($materiels as $materiel)
                    <tr>
                        <td>{{ $materiel->nom }}</td>
                        <td>{{ $materiel->categorie }}</td>
                        <td>{{ $materiel->numero_inventaire }}</td>
                        <td><span class="etat etat-{{ $materiel->etat }}">{{ $materiel->libelleEtat() }}</span></td>
                        <td>{{ $materiel->estDisponible() ? 'Oui' : 'Non' }}</td>
                        @can('pret.demander')
                            <td>
                                {{-- Un matériel prêté peut être réservé pour des dates plus tard. --}}
                                @if ($materiel->estDisponible() || $materiel->estPrete())
                                    <a href="{{ route('prets.create', ['materiel' => $materiel->id]) }}">Emprunter</a>
                                @endif
                            </td>
                        @endcan
                        @can('materiel.gerer')
                            <td class="actions">
                                <a href="{{ route('materiels.edit', $materiel) }}">Modifier</a>

                                @if ($materiel->peutEtreArchive())
                                    <form method="POST" action="{{ route('materiels.archiver', $materiel) }}"
                                          onsubmit="return confirm('Archiver ce matériel ? Il disparaîtra du catalogue.');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="bouton-secondaire">Archiver</button>
                                    </form>
                                @endif
                            </td>
                        @endcan
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{ $materiels->links('pagination::default') }}
    @endif
@endsection
