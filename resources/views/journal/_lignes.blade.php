{{--
    Tableau des lignes du journal, réutilisé par la page Journal et par le
    tableau de bord de l'administrateur.

    Paramètre : $lignes (lignes de la table journal, avec leur utilisateur chargé).
    Tout est affiché avec {{ }} : l'objet peut contenir un e-mail tapé par un
    inconnu sur la page de connexion, il ne doit jamais être interprété comme du HTML.
--}}
@if ($lignes->isEmpty())
    <p class="vide">Aucun événement.</p>
@else
    <table class="tableau tableau-journal">
        <thead>
            <tr>
                <th>Date et heure</th>
                <th>Utilisateur</th>
                <th>Action</th>
                <th>Objet</th>
                <th>Adresse IP</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lignes as $ligne)
                <tr class="action-{{ $ligne->action }}">
                    <td>{{ $ligne->date_heure->format('d/m/Y H:i:s') }}</td>
                    <td>{{ $ligne->utilisateur ? $ligne->utilisateur->email : '—' }}</td>
                    <td>{{ $ligne->libelleAction() }}</td>
                    <td>{{ $ligne->objet }}</td>
                    <td>{{ $ligne->ip }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
