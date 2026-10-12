import { useCallback, useEffect, useState } from 'react'
import { useOutletContext } from 'react-router-dom'
import { Message } from '../../components/Formulaire.jsx'
import { useSession } from '../../context/session.js'
import { api } from '../../lib/api.js'
import { date } from '../../lib/format.js'
import useTitre from '../../lib/useTitre.js'

/** EF-04, EF-05 */
export default function Inscriptions() {
  useTitre('Inscriptions à valider')
  const { references } = useSession()
  const { rafraichirCompteurs } = useOutletContext()
  const [liste, setListe] = useState(null)
  const [refus, setRefus] = useState({}) // id → motif choisi
  const [enRefus, setEnRefus] = useState(null)
  const [message, setMessage] = useState(null)
  const [erreur, setErreur] = useState(null)

  const charger = useCallback(() => {
    api('/mairie/inscriptions').then(setListe).catch((e) => setErreur(e.message))
    rafraichirCompteurs()
  }, [rafraichirCompteurs])

  useEffect(() => {
    charger()
  }, [charger])

  async function decider(id, decision) {
    setErreur(null)
    try {
      const body = decision === 'refuser' ? { motif: refus[id] || null } : undefined
      const r = await api(`/mairie/inscriptions/${id}/${decision}`, { method: 'POST', body })
      setMessage(r.message)
      setEnRefus(null)
      charger()
    } catch (e) {
      setErreur(e.message)
    }
  }

  return (
    <>
      <h1>Inscriptions à valider</h1>
      <p className="intro">Vérifiez que la personne habite bien la commune. Elle recevra un e-mail avec votre décision.</p>
      <Message type="succes">{message}</Message>
      <Message type="erreur">{erreur}</Message>

      {!liste ? (
        <p className="chargement">Chargement…</p>
      ) : liste.length === 0 ? (
        <div className="carte vide">
          <p>Aucune inscription en attente.</p>
        </div>
      ) : (
        liste.map((u) => (
          <article className="carte" key={u.id} aria-labelledby={`insc-${u.id}`}>
            <h2 id={`insc-${u.id}`}>
              {u.prenom} {u.nom}
            </h2>
            <p>
              {u.rue} · {u.email} · demande du {date(u.createdAt)}
            </p>
            {enRefus === u.id ? (
              <div className="confirmation">
                <div className="champ">
                  <label htmlFor={`motif-${u.id}`}>Motif du refus</label>
                  <select id={`motif-${u.id}`} value={refus[u.id] ?? ''} onChange={(e) => setRefus({ ...refus, [u.id]: e.target.value })}>
                    <option value="">— Sans motif —</option>
                    {references?.motifsRefus.map((m) => (
                      <option key={m.value} value={m.value}>
                        {m.libelle}
                      </option>
                    ))}
                  </select>
                </div>
                <div className="actions">
                  <button type="button" className="bouton bouton--danger" onClick={() => decider(u.id, 'refuser')}>
                    Confirmer le refus
                  </button>
                  <button type="button" className="bouton bouton--secondaire" onClick={() => setEnRefus(null)}>
                    Annuler
                  </button>
                </div>
              </div>
            ) : (
              <div className="actions">
                <button type="button" className="bouton" onClick={() => decider(u.id, 'valider')}>
                  Valider
                </button>
                <button type="button" className="bouton bouton--secondaire" onClick={() => setEnRefus(u.id)}>
                  Refuser…
                </button>
              </div>
            )}
          </article>
        ))
      )}
    </>
  )
}
