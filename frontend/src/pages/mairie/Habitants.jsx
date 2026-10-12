import { useEffect, useState } from 'react'
import { Message } from '../../components/Formulaire.jsx'
import { api, query } from '../../lib/api.js'
import { date } from '../../lib/format.js'
import useTitre from '../../lib/useTitre.js'

/** EF-24 : suspension et réactivation des comptes. */
export default function Habitants() {
  useTitre('Habitants inscrits')
  const [recherche, setRecherche] = useState('')
  const [liste, setListe] = useState(null)
  const [message, setMessage] = useState(null)
  const [erreur, setErreur] = useState(null)
  const [version, setVersion] = useState(0)

  useEffect(() => {
    const minuteur = setTimeout(() => {
      api(`/mairie/habitants${query({ q: recherche })}`).then(setListe).catch((e) => setErreur(e.message))
    }, 250)
    return () => clearTimeout(minuteur)
  }, [recherche, version])

  async function agir(id, action) {
    setErreur(null)
    try {
      const r = await api(`/mairie/habitants/${id}/${action}`, { method: 'POST' })
      setMessage(r.message)
      setVersion((v) => v + 1)
    } catch (e) {
      setErreur(e.message)
    }
  }

  return (
    <>
      <h1>Habitants inscrits</h1>
      <Message type="succes">{message}</Message>
      <Message type="erreur">{erreur}</Message>

      <div className="champ" style={{ maxWidth: 420 }}>
        <label htmlFor="recherche-habitant">Rechercher (nom, prénom ou rue)</label>
        <input id="recherche-habitant" type="search" value={recherche} onChange={(e) => setRecherche(e.target.value)} />
      </div>

      {!liste ? (
        <p className="chargement">Chargement…</p>
      ) : (
        <div className="tableau-conteneur">
          <table>
            <caption className="visually-hidden">Liste des habitants inscrits</caption>
            <thead>
              <tr>
                <th scope="col">Nom</th>
                <th scope="col">Rue</th>
                <th scope="col">Inscrit le</th>
                <th scope="col">État</th>
                <th scope="col">Action</th>
              </tr>
            </thead>
            <tbody>
              {liste.map((u) => (
                <tr key={u.id}>
                  <td>
                    {u.prenom} {u.nom}
                  </td>
                  <td>{u.rue}</td>
                  <td>{date(u.inscritLe)}</td>
                  <td>{u.statutLibelle}</td>
                  <td>
                    {u.statut === 'valide' ? (
                      <button type="button" className="bouton bouton--secondaire" onClick={() => agir(u.id, 'suspendre')}>
                        Suspendre<span className="visually-hidden"> {u.prenom} {u.nom}</span>
                      </button>
                    ) : (
                      <button type="button" className="bouton" onClick={() => agir(u.id, 'reactiver')}>
                        Réactiver<span className="visually-hidden"> {u.prenom} {u.nom}</span>
                      </button>
                    )}
                  </td>
                </tr>
              ))}
              {liste.length === 0 && (
                <tr>
                  <td colSpan={5} className="vide">
                    Aucun habitant trouvé.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      )}
    </>
  )
}
