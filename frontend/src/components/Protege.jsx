import { Navigate, useLocation } from 'react-router-dom'
import { accueilPour, useSession } from '../context/session.js'

/**
 * Restreint l'accès à une page selon le rôle. Confort d'affichage seulement :
 * la vraie protection est faite par l'API (ES-03).
 */
export default function Protege({ roles, children }) {
  const { user, pret } = useSession()
  const location = useLocation()

  if (!pret) return <p className="chargement">Chargement…</p>
  if (!user) return <Navigate to="/connexion" replace state={{ depuis: location.pathname }} />
  if (roles && !roles.includes(user.role)) return <Navigate to={accueilPour(user)} replace />

  return children
}
