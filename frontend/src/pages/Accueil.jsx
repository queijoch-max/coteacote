import { Link, Navigate } from 'react-router-dom'
import Logo from '../components/Logo.jsx'
import { accueilPour, useSession } from '../context/session.js'
import useTitre from '../lib/useTitre.js'

export default function Accueil() {
  const { user, pret, references } = useSession()
  useTitre(null)

  if (pret && user) return <Navigate to={accueilPour(user)} replace />

  const commune = references?.commune?.nom ?? 'votre commune'

  return (
    <>
      <section className="hero">
        <div>
          <h1>Entre voisins, on s'entraide.</h1>
          <p className="intro">
            Besoin d'un coup de main pour des courses, d'une perceuse pour un week-end, ou envie de donner des vêtements
            d'enfant ? Côte à côte met en relation les habitants de {commune}, simplement et gratuitement.
          </p>
          <div className="actions">
            <Link to="/inscription" className="bouton bouton--grand">
              Je m'inscris
            </Link>
            <Link to="/connexion" className="bouton bouton--secondaire bouton--grand">
              J'ai déjà un compte
            </Link>
          </div>
        </div>
        <div className="hero__illustration">
          <Logo grand titre="Une maison et deux voisins qui se tiennent la main" />
        </div>
      </section>

      <section className="grille grille--2" aria-labelledby="comment">
        <div className="carte">
          <h2 id="comment">Comment ça marche ?</h2>
          <ol className="etapes">
            <li>Vous vous inscrivez. La mairie vérifie que vous habitez bien la commune.</li>
            <li>Vous publiez une demande ou une offre en quelques clics, sans rien écrire de long.</li>
            <li>Un voisin clique sur « Je peux aider » : vous recevez son contact par e-mail.</li>
          </ol>
        </div>
        <div className="carte">
          <h2>En toute confiance</h2>
          <ul>
            <li>Votre adresse, votre téléphone et votre e-mail ne sont jamais affichés.</li>
            <li>Seul le prénom, l'initiale du nom et la rue apparaissent sur une annonce.</li>
            <li>L'entraide est gratuite : aucune vente, aucun paiement.</li>
            <li>La mairie encadre les inscriptions et traite les signalements.</li>
          </ul>
          <p>
            Pas d'Internet chez vous ? <strong>La mairie peut publier une annonce pour vous.</strong>
          </p>
        </div>
      </section>
    </>
  )
}
