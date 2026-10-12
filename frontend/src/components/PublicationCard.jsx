import { useId, useState } from 'react'
import { api } from '../lib/api.js'
import { depuis } from '../lib/format.js'

/**
 * Carte d'une publication (EF-16) : prénom + initiale, rue, catégorie, complément, date.
 * Boutons « Je peux aider » / « Ça m'intéresse » (EF-17) et « Signaler » (EF-21).
 */
export default function PublicationCard({ publication, motifs = [], peutAgir = false, onChange }) {
  const p = publication
  const [etape, setEtape] = useState(null) // null | 'repondre' | 'signaler'
  const [motif, setMotif] = useState('')
  const [message, setMessage] = useState(null)
  const [erreur, setErreur] = useState(null)
  const [envoi, setEnvoi] = useState(false)
  const idTitre = useId()

  const estDemande = p.type === 'demande'
  const libelleReponse = estDemande ? 'Je peux aider' : "Ça m'intéresse"
  const destinataire = p.publieeParMairie ? 'La mairie' : p.auteur.split(' ')[0]

  async function envoyer(chemin, body) {
    setEnvoi(true)
    setErreur(null)
    try {
      const reponse = await api(`/publications/${p.id}/${chemin}`, { method: 'POST', body })
      setMessage(reponse.message)
      setEtape(null)
      onChange?.(p.id, chemin)
    } catch (e) {
      setErreur(e.message)
    } finally {
      setEnvoi(false)
    }
  }

  return (
    <article className={`publication publication--${p.type}`} aria-labelledby={idTitre}>
      <div className="publication__haut">
        <span className={`etiquette-pastille etiquette-pastille--${p.type}`}>{p.typeLibelle}</span>
        <span className="etiquette-pastille">{p.natureLibelle}</span>
        {p.publieeParMairie && (
          <span className="etiquette-pastille etiquette-pastille--mairie">Publiée par la mairie</span>
        )}
      </div>

      <h3 className="publication__titre" id={idTitre}>
        {p.categorie.libelle}
      </h3>
      {p.categorie.parent && <p className="publication__sous-titre">{p.categorie.parent}</p>}
      {p.complement && <p className="publication__complement">« {p.complement} »</p>}

      <dl className="publication__meta">
        <dt>Auteur</dt>
        <dd>
          <strong>{p.auteur}</strong>
        </dd>
        <dt>Rue</dt>
        <dd>{p.rue}</dd>
        <dt>Date</dt>
        <dd>{depuis(p.createdAt)}</dd>
      </dl>

      {message && (
        <p className="message message--succes" role="status">
          {message}
        </p>
      )}
      {erreur && (
        <p className="message message--erreur" role="alert">
          {erreur}
        </p>
      )}

      {etape === 'repondre' && (
        <div className="confirmation">
          <p>
            {destinataire} recevra par e-mail votre prénom et le contact que vous avez choisi de partager dans votre
            profil. Vos coordonnées ne seront visibles par personne d'autre.
          </p>
          <div className="actions">
            <button type="button" className="bouton" disabled={envoi} onClick={() => envoyer('repondre')}>
              Confirmer
            </button>
            <button type="button" className="bouton bouton--secondaire" onClick={() => setEtape(null)}>
              Annuler
            </button>
          </div>
        </div>
      )}

      {etape === 'signaler' && (
        <form
          className="confirmation"
          onSubmit={(e) => {
            e.preventDefault()
            if (motif) envoyer('signaler', { motif })
          }}
        >
          <div className="champ">
            <label htmlFor={`motif-${p.id}`}>Pourquoi signalez-vous cette annonce ?</label>
            <select id={`motif-${p.id}`} value={motif} onChange={(e) => setMotif(e.target.value)} required>
              <option value="">— Choisir —</option>
              {motifs.map((m) => (
                <option key={m.value} value={m.value}>
                  {m.libelle}
                </option>
              ))}
            </select>
          </div>
          <div className="actions">
            <button type="submit" className="bouton bouton--danger" disabled={envoi || !motif}>
              Signaler à la mairie
            </button>
            <button type="button" className="bouton bouton--secondaire" onClick={() => setEtape(null)}>
              Annuler
            </button>
          </div>
        </form>
      )}

      {!p.estAuteur && peutAgir && etape === null && (
        <div className="actions">
          {p.dejaRepondu ? (
            <span className="etiquette-pastille">Vous avez répondu ✓</span>
          ) : (
            !message && (
              <button type="button" className="bouton" onClick={() => setEtape('repondre')}>
                {libelleReponse}
              </button>
            )
          )}
          {!p.dejaSignale && (
            <button
              type="button"
              className="bouton bouton--discret"
              onClick={() => setEtape('signaler')}
              aria-label={`Signaler l'annonce ${p.categorie.libelle} de ${p.auteur}`}
            >
              Signaler
            </button>
          )}
        </div>
      )}
      {p.estAuteur && <span className="etiquette-pastille">C'est votre annonce</span>}
    </article>
  )
}
