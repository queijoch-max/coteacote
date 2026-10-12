import { useSession } from '../context/session.js'
import useTitre from '../lib/useTitre.js'

/** DP-02, DP-03 : pages légales — texte provisoire, à faire valider avant toute mise en ligne. */

function Provisoire() {
  return (
    <p className="message message--info">
      Texte provisoire rédigé pour le prototype. Il sera complété lors de la création de l'association.
    </p>
  )
}

export function MentionsLegales() {
  useTitre('Mentions légales')
  return (
    <article className="texte-legal">
      <h1>Mentions légales</h1>
      <Provisoire />
      <h2>Éditeur</h2>
      <p>
        L'application Côte à côte est éditée par une association à but non lucratif (loi de 1901), en cours de création.
        Ses coordonnées (siège, numéro RNA, responsable de la publication) seront indiquées ici.
      </p>
      <h2>Hébergement</h2>
      <p>Le prototype fonctionne uniquement en local, sur l'ordinateur de développement. Il n'est pas hébergé en ligne.</p>
      <h2>Rôle de la mairie</h2>
      <p>
        La mairie encadre le service : elle valide les inscriptions et traite les signalements. Elle n'organise pas
        l'entraide entre habitants.
      </p>
    </article>
  )
}

export function Confidentialite() {
  useTitre('Politique de confidentialité')
  return (
    <article className="texte-legal">
      <h1>Politique de confidentialité</h1>
      <Provisoire />
      <h2>Données collectées</h2>
      <p>
        Nous collectons uniquement : prénom, nom, adresse e-mail, mot de passe (chiffré), nom de rue (sans numéro) et,
        si vous le souhaitez, un numéro de téléphone. Pas de date de naissance, pas de numéro de rue, pas de
        géolocalisation.
      </p>
      <h2>Ce qui est visible</h2>
      <p>
        Sur une annonce apparaissent seulement votre prénom, l'initiale de votre nom et votre rue. Votre e-mail et votre
        téléphone ne sont jamais affichés : ils sont transmis par e-mail uniquement à l'auteur d'une annonce à laquelle
        vous répondez, selon vos choix dans votre profil.
      </p>
      <h2>Durées de conservation</h2>
      <ul>
        <li>Annonces expirées ou résolues : supprimées après 6 mois.</li>
        <li>Journal des actions de la mairie : conservé 1 an.</li>
        <li>Compte supprimé : effacé définitivement après 30 jours.</li>
        <li>Inscription refusée ou jamais confirmée : effacée après 30 jours.</li>
      </ul>
      <h2>Vos droits</h2>
      <p>
        Depuis votre profil, vous pouvez consulter, modifier, télécharger (format JSON) et supprimer vos données à tout
        moment.
      </p>
      <h2>Cookies</h2>
      <p>
        Un seul cookie technique est utilisé pour vous garder connecté. Aucun cookie publicitaire, aucun outil de mesure
        d'audience ni de suivi.
      </p>
    </article>
  )
}

export function Cgu() {
  useTitre("Conditions générales d'utilisation")
  const { references } = useSession()
  return (
    <article className="texte-legal">
      <h1>Conditions générales d'utilisation</h1>
      <Provisoire />
      <h2>Objet</h2>
      <p>
        Côte à côte est un outil de mise en relation entre habitants de {references?.commune?.nom ?? 'la commune'} qui
        souhaitent demander ou proposer de l'aide.
      </p>
      <h2>Responsabilité</h2>
      <p>
        L'entraide relève de la seule responsabilité des habitants qui se mettent en relation. L'association et la mairie
        ne sont pas parties aux échanges et ne garantissent pas les services rendus.
      </p>
      <h2>Gratuité</h2>
      <p>
        <strong>Aucune contrepartie financière n'est autorisée.</strong> Les annonces de vente, de location ou de
        prestation payante sont interdites.
      </p>
      <h2>Règles de publication</h2>
      <ul>
        <li>Pas de coordonnées, d'adresse précise, de lien ni de prix dans les annonces.</li>
        <li>Pas de propos injurieux ou discriminants.</li>
        <li>Pas de services dangereux (électricité, gaz, travaux en hauteur).</li>
      </ul>
      <p>
        Toute annonce peut être signalée. La mairie peut masquer ou supprimer une annonce et suspendre un compte qui ne
        respecte pas ces règles.
      </p>
    </article>
  )
}
