import { useCallback, useEffect, useState } from 'react'
import { useOutletContext } from 'react-router-dom'
import { Message } from '../../components/Formulaire.jsx'
import { api } from '../../lib/api.js'
import { date } from '../../lib/format.js'
import useTitre from '../../lib/useTitre.js'

/** EF-22 à EF-24 : rétablir, supprimer, suspendre l'auteur. */
export default function Signalements() {
  useTitre('Annonces signalées')
  const { rafraichirCompteurs } = useOutletContext()
  const [liste, setListe] = useState(null)
  const [message, setMessage] = useState(null)
  const [erreur, setErreur] = useState(null)

  const charger = useCallback(() => {
    api('/mairie/signalements').then(setListe).catch((e) => setErreur(e.message))
    rafraichirCompteurs()
  }, [rafraichirCompteurs])

  useEffect(() => {
    charger()
  }, [charger])

  async function agir(chemin, method = 'POST') {
    setErreur(null)
    try {
      const r = await api(chemin, { method })
      setMessage(r.message)
      charger()
    } catch (e) {
      setErreur(e.message)
    }
  }

  return (
    <>
      <h1>Annonces signalées</h1>
      <p className="intro">Une annonce est masquée automatiquement dès 3 signalements, en attendant votre décision.</p>
      <Message type="succes">{message}</Message>
      <Message type="erreur">{erreur}</Message>

      {!liste ? (
        <p className="chargement">Chargement…</p>
      ) : liste.length === 0 ? (
        <div className="carte vide">
          <p>Aucun signalement en attente.</p>
        </div>
      ) : (
        liste.map(({ publication: p, motifs, nombre, auteurId, auteurStatut, premierSignalement }) => (
          <article className="carte" key={p.id} aria-labelledby={`sig-${p.id}`}>
            <div className="publication__haut">
              <span className={`etiquette-pastille etiquette-pastille--${p.type}`}>{p.typeLibelle}</span>
              <span className="etiquette-pastille">{p.natureLibelle}</span>
              {p.statut === 'masquee' && <span className="etiquette-pastille etiquette-pastille--demande">Masquée</span>}
            </div>
            <h2 id={`sig-${p.id}`}>
              {p.categorie.libelle} — {p.auteur}, {p.rue}
            </h2>
            {p.complement && <p className="publication__complement">« {p.complement} »</p>}
            <p>
              <strong>{nombre} signalement(s)</strong> depuis le {date(premierSignalement)} :{' '}
              {Object.entries(motifs)
                .map(([motif, n]) => `${motif} (${n})`)
                .join(', ')}
            </p>
            <div className="actions">
              <button type="button" className="bouton" onClick={() => agir(`/mairie/publications/${p.id}/retablir`)}>
                Rétablir l'annonce
              </button>
              <button type="button" className="bouton bouton--danger" onClick={() => agir(`/mairie/publications/${p.id}`, 'DELETE')}>
                Supprimer l'annonce
              </button>
              {auteurId && auteurStatut === 'valide' && (
                <button type="button" className="bouton bouton--secondaire" onClick={() => agir(`/mairie/habitants/${auteurId}/suspendre`)}>
                  Suspendre l'auteur
                </button>
              )}
            </div>
          </article>
        ))
      )}
    </>
  )
}
