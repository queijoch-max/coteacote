import { useState } from 'react'
import { Link, Navigate, useLocation, useNavigate } from 'react-router-dom'
import { Champ, Message } from '../components/Formulaire.jsx'
import { accueilPour, useSession } from '../context/session.js'
import useTitre from '../lib/useTitre.js'

export default function Connexion() {
  useTitre('Se connecter')
  const { user, connexion } = useSession()
  const navigate = useNavigate()
  const location = useLocation()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [erreur, setErreur] = useState(null)
  const [envoi, setEnvoi] = useState(false)

  if (user && !envoi) return <Navigate to={accueilPour(user)} replace />

  async function soumettre(e) {
    e.preventDefault()
    setEnvoi(true)
    setErreur(null)
    try {
      const connecte = await connexion(email, password)
      navigate(location.state?.depuis || accueilPour(connecte), { replace: true })
    } catch (err) {
      setErreur(err.message)
      setEnvoi(false)
    }
  }

  return (
    <div className="page-etroite">
      <h1>Se connecter</h1>
      <form className="carte" onSubmit={soumettre} noValidate>
        <Message type="erreur">{erreur}</Message>
        <Champ label="Adresse e-mail">
          {(props) => (
            <input
              {...props}
              type="email"
              autoComplete="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
            />
          )}
        </Champ>
        <Champ label="Mot de passe">
          {(props) => (
            <input
              {...props}
              type="password"
              autoComplete="current-password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
            />
          )}
        </Champ>
        <button type="submit" className="bouton bouton--large" disabled={envoi}>
          {envoi ? 'Connexion…' : 'Se connecter'}
        </button>
      </form>
      <p style={{ marginTop: '1.25rem' }}>
        Pas encore de compte ? <Link to="/inscription">Demander une inscription</Link>
      </p>
    </div>
  )
}
