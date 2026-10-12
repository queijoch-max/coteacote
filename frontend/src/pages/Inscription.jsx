import { useState } from 'react'
import { Link } from 'react-router-dom'
import { Champ, Message } from '../components/Formulaire.jsx'
import { useSession } from '../context/session.js'
import { api } from '../lib/api.js'
import useTitre from '../lib/useTitre.js'

const VIDE = { prenom: '', nom: '', email: '', password: '', rue: '', cgu: false }

/** EF-01 : prénom, nom, e-mail, mot de passe, rue (sans numéro), conditions d'utilisation. */
export default function Inscription() {
  useTitre('Demander une inscription')
  const { references } = useSession()
  const [form, setForm] = useState(VIDE)
  const [erreurs, setErreurs] = useState({})
  const [message, setMessage] = useState(null)
  const [erreur, setErreur] = useState(null)
  const [envoi, setEnvoi] = useState(false)

  const maj = (champ) => (e) =>
    setForm({ ...form, [champ]: e.target.type === 'checkbox' ? e.target.checked : e.target.value })

  async function soumettre(e) {
    e.preventDefault()
    const locales = {}
    if (form.password.length < 12) locales.password = 'Le mot de passe doit contenir au moins 12 caractères.'
    if (/\d/.test(form.rue)) locales.rue = 'Indiquez seulement le nom de la rue, sans numéro.'
    if (!form.cgu) locales.cgu = "Vous devez accepter les conditions d'utilisation."
    if (Object.keys(locales).length) {
      setErreurs(locales)
      return
    }

    setEnvoi(true)
    setErreur(null)
    setErreurs({})
    try {
      const reponse = await api('/inscription', { method: 'POST', body: form })
      setMessage(reponse.message)
    } catch (err) {
      setErreurs(err.errors)
      setErreur(err.message)
    } finally {
      setEnvoi(false)
    }
  }

  if (message) {
    return (
      <div className="page-etroite">
        <h1>Merci !</h1>
        <div className="carte">
          <Message type="succes">{message}</Message>
          <p>
            Ouvrez l'e-mail et cliquez sur le lien pour confirmer votre adresse (le lien est valable 24 heures). La
            mairie examinera ensuite votre demande et vous recevrez un e-mail dès qu'elle sera validée.
          </p>
          <Link to="/connexion" className="bouton">
            Aller à la connexion
          </Link>
        </div>
      </div>
    )
  }

  return (
    <div className="page-etroite">
      <h1>Demander une inscription</h1>
      <p className="intro">
        L'application est réservée aux habitants de {references?.commune?.nom ?? 'la commune'}. La mairie validera votre
        inscription.
      </p>
      <form className="carte" onSubmit={soumettre} noValidate>
        <Message type="erreur">{erreur}</Message>
        <div className="grille grille--2">
          <Champ label="Prénom" erreur={erreurs.prenom}>
            {(p) => <input {...p} type="text" autoComplete="given-name" value={form.prenom} onChange={maj('prenom')} required />}
          </Champ>
          <Champ label="Nom" aide="Seule l'initiale sera affichée." erreur={erreurs.nom}>
            {(p) => <input {...p} type="text" autoComplete="family-name" value={form.nom} onChange={maj('nom')} required />}
          </Champ>
        </div>
        <Champ label="Nom de votre rue" aide="Sans le numéro. Exemple : Rue des Lilas" erreur={erreurs.rue}>
          {(p) => <input {...p} type="text" value={form.rue} onChange={maj('rue')} required />}
        </Champ>
        <Champ label="Adresse e-mail" aide="Elle n'est jamais affichée aux autres habitants." erreur={erreurs.email}>
          {(p) => <input {...p} type="email" autoComplete="email" value={form.email} onChange={maj('email')} required />}
        </Champ>
        <Champ label="Mot de passe" aide="Au moins 12 caractères. Une phrase facile à retenir fonctionne bien." erreur={erreurs.password}>
          {(p) => (
            <input {...p} type="password" autoComplete="new-password" value={form.password} onChange={maj('password')} required minLength={12} />
          )}
        </Champ>
        <div className="champ">
          <label className="case">
            <input type="checkbox" checked={form.cgu} onChange={maj('cgu')} aria-invalid={erreurs.cgu ? true : undefined} />
            <span>
              J'accepte les{' '}
              <Link to="/cgu" target="_blank">
                conditions d'utilisation
              </Link>{' '}
              et la{' '}
              <Link to="/confidentialite" target="_blank">
                politique de confidentialité
              </Link>
              .
            </span>
          </label>
          {erreurs.cgu && <span className="champ__erreur">{erreurs.cgu}</span>}
        </div>
        <button type="submit" className="bouton bouton--large" disabled={envoi}>
          {envoi ? 'Envoi…' : 'Envoyer ma demande'}
        </button>
      </form>
    </div>
  )
}
