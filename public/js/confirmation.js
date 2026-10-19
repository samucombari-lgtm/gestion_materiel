/*
 * Demande une confirmation avant d'envoyer certains formulaires
 * (archiver un matériel, annuler un prêt, désactiver un compte, changer un rôle).
 *
 * Pourquoi un fichier à part ? La politique de sécurité du contenu (CSP, voir
 * app/Http/Middleware/EnTetesSecurite.php) interdit le JavaScript écrit dans le
 * HTML, comme onsubmit="return confirm(...)". Seuls les fichiers .js servis par
 * notre serveur sont autorisés.
 *
 * Utilisation dans une vue Blade : ajouter l'attribut data-confirmation au formulaire.
 *     <form method="POST" action="..." data-confirmation="Archiver ce matériel ?">
 *
 * Ce n'est PAS une protection de sécurité (le serveur revérifie tout) : c'est un
 * garde-fou contre un clic par erreur.
 */
document.addEventListener('submit', function (evenement) {
    var formulaire = evenement.target;
    var message = formulaire.getAttribute('data-confirmation');

    if (message !== null && !window.confirm(message)) {
        evenement.preventDefault();
    }
});
