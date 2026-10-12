import { useCallback, useEffect, useState } from 'react'
import { Champ, Message } from '../../components/Formulaire.jsx'
import { api } from '../../lib/api.js'
import { date } from '../../lib/format.js'
import useTitre from '../../lib/useTitre.js'

const VIDE = { prenom: '', nom: '', email: '', password: '' }

export default function ComptesMairie() {
  useTitre('Comptes mairie')
  const [comptes, setComptes] = useState(null)
  const [form, setForm] = useState(VIDE)
  const [erreurs, setErreurs] = useState({})
  const [message, setMessage] = useState(null)
  const [erreur, setErreur] = useState(null)

  const charger = useCallback(() => api('/admin/comptes-mairie').then(setComptes).catch((e) => setErreur(e.message)), [])

  useEffect(() => {
    charger()
  }, [charger])

  async function creer(e) {
    e.preventDefault()
    setErreur(null)
    setErreurs({})
    try {
      const r = await api('/admin/comptes-mairie', { method: 'POST', body: form })
      setMessage(`Compte créé pour ${r.email}.`)
      setForm(VIDE)
      charger()
    } catch (err) {
      setErreurs(err.errors)
      setErreur(err.message)
    }
  }

  const maj = (champ) => (e) => setForm({ ...form, [champ]: e.target.value })

  return (
    <>
      <h1>Comptes mairie</h1>
      <Message type="succes">{message}</Message>
      <Message type="erreur">{erreur}</Message>

      <div className="grille grille--2">
        <form className="carte" onSubmit={creer} noValidate>
          <h2>Créer un compte</h2>
          <Champ label="Prénom" erreur={erreurs.prenom}>
            {(p) => <input {...p} type="text" value={form.prenom} onChange={maj('prenom')} />}
          </Champ>
          <Champ label="Nom" erreur={erreurs.nom}>
            {(p) => <input {...p} type="text" value={form.nom} onChange={maj('nom')} />}
          </Champ>
          <Champ label="Adresse e-mail" erreur={erreurs.email}>
            {(p) => <input {...p} type="email" value={form.email} onChange={maj('email')} />}
          </Champ>
          <Champ label="Mot de passe provisoire" aide="12 caractères minimum." erreur={erreurs.password}>
            {(p) => <input {...p} type="password" autoComplete="new-password" value={form.password} onChange={maj('password')} />}
          </Champ>
          <button type="submit" className="bouton">
            Créer le compte
          </button>
        </form>

        <section className="carte">
          <h2>Comptes existants</h2>
          {comptes?.length ? (
            <ul>
              {comptes.map((c) => (
                <li key={c.id}>
                  <strong>
                    {c.prenom} {c.nom}
                  </strong>{' '}
                  — {c.email} (créé le {date(c.createdAt)})
                </li>
              ))}
            </ul>
          ) : (
            <p>Aucun compte.</p>
          )}
        </section>
      </div>
    </>
  )
}
