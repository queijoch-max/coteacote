import { useCallback, useEffect, useState } from 'react'
import { Champ, Message } from '../../components/Formulaire.jsx'
import { api } from '../../lib/api.js'
import useTitre from '../../lib/useTitre.js'

/** RG-05 : liste des insultes gérée par l'administrateur association. */
export default function MotsInterdits() {
  useTitre('Mots interdits')
  const [mots, setMots] = useState(null)
  const [nouveau, setNouveau] = useState('')
  const [message, setMessage] = useState(null)
  const [erreur, setErreur] = useState(null)

  const charger = useCallback(() => api('/admin/mots-interdits').then(setMots).catch((e) => setErreur(e.message)), [])

  useEffect(() => {
    charger()
  }, [charger])

  async function appeler(chemin, method, body, texte) {
    setErreur(null)
    try {
      await api(chemin, { method, body })
      setMessage(texte)
      charger()
      return true
    } catch (e) {
      setErreur(e.errors?.mot ?? e.message)
      return false
    }
  }

  async function ajouter(e) {
    e.preventDefault()
    if (await appeler('/admin/mots-interdits', 'POST', { mot: nouveau, actif: true }, `« ${nouveau} » ajouté.`)) setNouveau('')
  }

  return (
    <>
      <h1>Mots interdits</h1>
      <p className="intro">
        Ces mots sont refusés dans le complément des annonces, même écrits avec des espaces, des tirets ou des chiffres à
        la place des lettres.
      </p>
      <Message type="succes">{message}</Message>
      <Message type="erreur">{erreur}</Message>

      <form className="carte actions" onSubmit={ajouter} style={{ marginBottom: '1.5rem', alignItems: 'flex-end' }}>
        <div style={{ flex: '1 1 240px' }}>
          <Champ label="Nouveau mot">
            {(p) => <input {...p} type="text" required value={nouveau} onChange={(e) => setNouveau(e.target.value)} />}
          </Champ>
        </div>
        <button type="submit" className="bouton" style={{ marginBottom: '1.15rem' }}>
          Ajouter
        </button>
      </form>

      {mots && (
        <div className="tableau-conteneur">
          <table>
            <caption className="visually-hidden">Liste des mots interdits</caption>
            <thead>
              <tr>
                <th scope="col">Mot</th>
                <th scope="col">État</th>
                <th scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
              {mots.map((m) => (
                <tr key={m.id}>
                  <td>{m.mot}</td>
                  <td>{m.actif ? 'Actif' : 'Inactif'}</td>
                  <td className="actions">
                    <button
                      type="button"
                      className="bouton bouton--secondaire"
                      onClick={() => appeler(`/admin/mots-interdits/${m.id}`, 'PUT', { mot: m.mot, actif: !m.actif }, 'Modification enregistrée.')}
                    >
                      {m.actif ? 'Désactiver' : 'Activer'}
                      <span className="visually-hidden"> {m.mot}</span>
                    </button>
                    <button
                      type="button"
                      className="bouton bouton--discret"
                      onClick={() => appeler(`/admin/mots-interdits/${m.id}`, 'DELETE', undefined, `« ${m.mot} » retiré.`)}
                    >
                      Retirer<span className="visually-hidden"> {m.mot}</span>
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </>
  )
}
