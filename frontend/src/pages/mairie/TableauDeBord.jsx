import { Link, useOutletContext } from 'react-router-dom'
import useTitre from '../../lib/useTitre.js'

/** EF-25 */
export default function TableauDeBord() {
  useTitre('Espace mairie')
  const { compteurs } = useOutletContext()

  if (!compteurs) return <p className="chargement">Chargement…</p>

  const aTraiter = compteurs.inscriptionsEnAttente + compteurs.signalementsEnAttente

  return (
    <>
      <h1>Tableau de bord</h1>
      <p className="intro">
        {aTraiter === 0
          ? 'Rien à traiter pour le moment. Merci !'
          : 'Quelques actions vous attendent. Comptez quelques minutes.'}
      </p>

      <div className="grille grille--4">
        <Link to="/mairie/inscriptions" className={`carte chiffre ${compteurs.inscriptionsEnAttente ? 'chiffre--alerte' : ''}`} style={{ textDecoration: 'none' }}>
          <span className="chiffre__valeur">{compteurs.inscriptionsEnAttente}</span>
          <span className="chiffre__libelle">inscription(s) à valider</span>
        </Link>
        <Link to="/mairie/signalements" className={`carte chiffre ${compteurs.signalementsEnAttente ? 'chiffre--alerte' : ''}`} style={{ textDecoration: 'none' }}>
          <span className="chiffre__valeur">{compteurs.signalementsEnAttente}</span>
          <span className="chiffre__libelle">annonce(s) signalée(s)</span>
        </Link>
        <div className="carte chiffre">
          <span className="chiffre__valeur">{compteurs.publicationsActives}</span>
          <span className="chiffre__libelle">annonces en ligne</span>
        </div>
        <div className="carte chiffre">
          <span className="chiffre__valeur">{compteurs.publicationsResoluesDuMois}</span>
          <span className="chiffre__libelle">résolue(s) ce mois-ci</span>
        </div>
      </div>

      <section className="carte" style={{ marginTop: '1.5rem' }}>
        <h2>Le rôle de la mairie</h2>
        <ul>
          <li>Valider les inscriptions des habitants de la commune.</li>
          <li>Traiter les annonces signalées : les rétablir ou les supprimer.</li>
          <li>Publier une annonce pour un habitant sans Internet : les réponses arrivent sur l'e-mail de la mairie.</li>
        </ul>
        <p>La mairie ne modifie jamais le texte d'une annonce et n'organise pas l'entraide.</p>
      </section>
    </>
  )
}
