import { createContext, useContext } from 'react'

export const SessionContext = createContext(null)

export const ROLES = {
  PENDING: 'ROLE_PENDING',
  HABITANT: 'ROLE_HABITANT',
  MAIRIE: 'ROLE_MAIRIE',
  ADMIN: 'ROLE_ADMIN',
}

export function useSession() {
  return useContext(SessionContext)
}

/** Page d'arrivée selon le rôle. */
export function accueilPour(user) {
  if (!user) return '/'
  switch (user.role) {
    case ROLES.MAIRIE:
      return '/mairie'
    case ROLES.ADMIN:
      return '/admin'
    case ROLES.HABITANT:
      return user.statut === 'valide' ? '/publications' : '/profil'
    default:
      return '/profil'
  }
}
