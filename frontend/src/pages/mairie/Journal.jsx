import { useEffect, useState } from 'react'
import { Message } from '../../components/Formulaire.jsx'
import { api } from '../../lib/api.js'
import { dateHeure } from '../../lib/format.js'
import useTitre from '../../lib/useTitre.js'

/** EF-28 : journal des actions (conservé 1 an). */
export default function Journal() {
  useTitre('Journal des actions')
  const [liste, setListe] = useState(null)
  const [erreur, setErreur] = useState(null)

  useEffect(() => {
    api('/mairie/journal').then(setListe).catch((e) => setErreur(e.message))
  }, [])

  return (
    <>
      <h1>Journal des actions</h1>
      <p className="intro">Les 100 dernières actions. Le journal est conservé un an.</p>
      <Message type="erreur">{erreur}</Message>
      {liste && (
        <div className="tableau-conteneur">
          <table>
            <caption className="visually-hidden">Journal des actions de la mairie</caption>
            <thead>
              <tr>
                <th scope="col">Date</th>
                <th scope="col">Action</th>
                <th scope="col">Élément</th>
                <th scope="col">Par</th>
              </tr>
            </thead>
            <tbody>
              {liste.map((j) => (
                <tr key={j.id}>
                  <td>{dateHeure(j.createdAt)}</td>
                  <td>{j.actionLibelle}</td>
                  <td>
                    {j.cible === 'user' ? 'Compte' : 'Annonce'} n° {j.cibleId}
                  </td>
                  <td>{j.par}</td>
                </tr>
              ))}
              {liste.length === 0 && (
                <tr>
                  <td colSpan={4} className="vide">
                    Aucune action enregistrée.
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
