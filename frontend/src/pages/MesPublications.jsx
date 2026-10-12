import { useCallback, useEffect, useState } from 'react'
import { Link, useLocation } from 'react-router-dom'
import { Message } from '../components/Formulaire.jsx'
import { api } from '../lib/api.js'
import { date, depuis, pluriel } from '../lib/format.js'
import useTitre from '../lib/useTitre.js'

/** EF-12, EF-13 : renouveler, marquer comme résolue, supprimer ses annonces. */
export function ListeMesPublications({ titreVide = "Vous n'avez pas encore publié d'annonce." }) {
  const location = useLocation()
  const [data, setData] = useState(null)
  const [message, setMessage] = useState(location.state?.message ?? null)
  const [erreur, setErreur] = useState(null)
  const [aSupprimer, setASupprimer] = useState(null)

  const charger = useCallback(() => api('/publications/mes').then(setData).catch((e) => setErreur(e.message)), [])

  useEffect(() => {
    charger()
  }, [charger])

  async function action(id, chemin, method = 'POST', texte) {
    setErreur(null)
    setMessage(null)
    try {
      await api(`/publications/${id}${chemin}`, { method })
      setMessage(texte)
      setASupprimer(null)
      charger()
    } catch (e) {
      setErreur(e.message)
    }
  }

  if (!data) return <p className="chargement">{erreur ?? 'Chargement…'}</p>

  return (
    <>
      <Message type="succes">{message}</Message>
      <Message type="erreur">{erreur}</Message>
      <p className="intro">
        {pluriel(data.actives, 'annonce en ligne', 'annonces en ligne')} sur {data.maxActives} possibles.
      </p>

      {data.items.length === 0 ? (
        <div className="carte vide">
          <p>{titreVide}</p>
        </div>
      ) : (
        <ul className="liste-publications">
          {data.items.map((p) => (
            <li key={p.id}>
              <article className={`publication publication--${p.type}`}>
                <div className="publication__haut">
                  <span className={`etiquette-pastille etiquette-pastille--${p.type}`}>{p.typeLibelle}</span>
                  <span className="etiquette-pastille">{p.natureLibelle}</span>
                  <span className="etiquette-pastille">{p.statutLibelle}</span>
                </div>
                <h2 className="publication__titre">{p.categorie.libelle}</h2>
                {p.beneficiaire && <p className="publication__sous-titre">Pour {p.beneficiaire} — {p.rue}</p>}
                {p.complement && <p className="publication__complement">« {p.complement} »</p>}
                <p className="publication__meta">
                  <span>Publiée {depuis(p.createdAt)}</span>
                  {p.statut === 'active' && <span>Expire le {date(p.expiresAt)}</span>}
                  <span>{pluriel(p.nbReponses, 'réponse')}</span>
                </p>

                {aSupprimer === p.id ? (
                  <div className="confirmation">
                    <p>Supprimer définitivement cette annonce ?</p>
                    <div className="actions">
                      <button type="button" className="bouton bouton--danger" onClick={() => action(p.id, '', 'DELETE', 'Annonce supprimée.')}>
                        Oui, supprimer
                      </button>
                      <button type="button" className="bouton bouton--secondaire" onClick={() => setASupprimer(null)}>
                        Annuler
                      </button>
                    </div>
                  </div>
                ) : (
                  <div className="actions">
                    {p.statut === 'active' && (
                      <button type="button" className="bouton" onClick={() => action(p.id, '/resoudre', 'POST', 'Bravo ! Annonce marquée comme résolue.')}>
                        C'est résolu
                      </button>
                    )}
                    {p.renouvelable && (
                      <button
                        type="button"
                        className="bouton bouton--secondaire"
                        onClick={() => action(p.id, '/renouveler', 'POST', 'Annonce prolongée de 30 jours.')}
                      >
                        Prolonger 30 jours
                      </button>
                    )}
                    {p.statut !== 'masquee' && (
                      <button type="button" className="bouton bouton--discret" onClick={() => setASupprimer(p.id)}>
                        Supprimer
                      </button>
                    )}
                  </div>
                )}
              </article>
            </li>
          ))}
        </ul>
      )}
    </>
  )
}

export default function MesPublications() {
  useTitre('Mes annonces')
  return (
    <>
      <div className="entete-page">
        <h1>Mes annonces</h1>
        <Link to="/publier" className="bouton">
          Publier une annonce
        </Link>
      </div>
      <ListeMesPublications />
    </>
  )
}
