{{--
    Petit tableau de prêts, réutilisé par les blocs du tableau de bord.
    Le « _ » au début du nom indique un morceau de vue, inclus avec @include.

    Paramètres :
     - $prets : les prêts à afficher ;
     - $messageSiVide : texte affiché s'il n'y a aucun prêt ;
     - $avecDemandeur : true pour afficher la colonne « Demandeur ».
--}}
@if ($prets->isEmpty())
    <p class="vide">{{ $messageSiVide }}</p>
@else
    <table class="tableau">
        <thead>
            <tr>
                <th>N°</th>
                <th>Matériel</th>
                @if ($avecDemandeur)
                    <th>Demandeur</th>
                @endif
                <th>Du</th>
                <th>Au</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($prets as $pret)
                <tr>
                    <td><a href="{{ route('prets.show', $pret->id) }}">{{ $pret->id }}</a></td>
                    <td>{{ $pret->materiel->nom }}</td>
                    @if ($avecDemandeur)
                        <td>{{ $pret->demandeur->prenom }} {{ $pret->demandeur->nom }}</td>
                    @endif
                    <td>{{ $pret->date_debut->format('d/m/Y') }}</td>
                    <td>{{ $pret->date_fin_prevue->format('d/m/Y') }}</td>
                    <td><span class="statut statut-{{ $pret->statut }}">{{ $pret->libelleStatut() }}</span></td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
