{{--
    Liste des comptes (BF-04), réservée à l'administrateur (compte.gerer).

    Variables envoyées par CompteController@index :
     - $comptes : 20 comptes par page, avec leur rôle et leurs classes ;
     - $filtres : filtres validés (role, statut, recherche).

    Après une création, la session contient UNE fois motDePasseTemporaire :
    on l'affiche pour que l'admin le transmette ; il ne sera plus jamais visible.

    Les boutons suivent UtilisateurPolicy (@can) : l'admin n'a aucun bouton sur
    sa propre ligne (RA-05). Le serveur revérifie à chaque action (RA-02).
--}}
@extends('layouts.principal')

@section('titre', 'Gestion des comptes')

@section('contenu')
    <h1>Gestion des comptes</h1>

    @if (session('motDePasseTemporaire'))
        <div class="information" role="status">
            <p>Mot de passe temporaire de <strong>{{ session('emailDuCompteCree') }}</strong> :</p>
            <p class="mot-de-passe-temporaire">{{ session('motDePasseTemporaire') }}</p>
            <p>Transmettez-le à la personne. <strong>Il ne sera plus affiché.</strong>
               Elle devra le changer à sa première connexion.</p>
        </div>
    @endif

    @if ($errors->any())
        <div class="erreur" role="alert">
            @foreach ($errors->all() as $message)
                <p>{{ $message }}</p>
            @endforeach
        </div>
    @endif

    <form method="GET" action="{{ route('comptes.index') }}" class="barre-recherche">
        <div>
            <label for="recherche">Nom, prénom ou e-mail</label>
            <input type="search" id="recherche" name="recherche" value="{{ $filtres['recherche'] ?? '' }}" maxlength="100">
        </div>
        <div>
            <label for="role">Rôle</label>
            <select id="role" name="role">
                <option value="">Tous</option>
                @foreach (\App\Models\Role::LIBELLES as $nomRole => $libelle)
                    <option value="{{ $nomRole }}" @selected(($filtres['role'] ?? '') === $nomRole)>{{ $libelle }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="statut">Statut</label>
            <select id="statut" name="statut">
                <option value="">Tous</option>
                <option value="actifs" @selected(($filtres['statut'] ?? '') === 'actifs')>Actifs</option>
                <option value="desactives" @selected(($filtres['statut'] ?? '') === 'desactives')>Désactivés</option>
            </select>
        </div>
        <button type="submit">Filtrer</button>
    </form>

    @can('role.attribuer')
        <p><a href="{{ route('comptes.create') }}" class="bouton">Créer un compte</a></p>
    @endcan

    @if ($comptes->isEmpty())
        <p>Aucun compte trouvé.</p>
    @else
        <table class="tableau">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>E-mail</th>
                    <th>Rôle</th>
                    <th>Classes</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($comptes as $compte)
                    <tr>
                        <td>{{ $compte->nom }} {{ $compte->prenom }}</td>
                        <td>{{ $compte->email }}</td>
                        <td>{{ $compte->role->libelle() }}</td>
                        <td>{{ $compte->classes->pluck('nom')->join(', ') }}</td>
                        <td>
                            <span class="etat {{ $compte->actif ? 'etat-disponible' : 'etat-archive' }}">
                                {{ $compte->actif ? 'Actif' : 'Désactivé' }}
                            </span>
                        </td>
                        <td class="actions">
                            @if ($compte->id === auth()->id())
                                <em>Votre compte</em>
                            @endif

                            @can('update', $compte)
                                <a href="{{ route('comptes.edit', $compte) }}">Modifier</a>
                            @endcan

                            @can('desactiver', $compte)
                                <form method="POST" action="{{ route('comptes.desactiver', $compte) }}"
                                      onsubmit="return confirm('Désactiver ce compte ? La personne sera déconnectée et ses demandes en cours annulées.');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="bouton-secondaire">Désactiver</button>
                                </form>
                            @endcan

                            @can('reactiver', $compte)
                                <form method="POST" action="{{ route('comptes.reactiver', $compte) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="bouton-secondaire">Réactiver</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{ $comptes->links('pagination::default') }}
    @endif
@endsection
