import { useCallback, useEffect, useMemo, useState } from 'react'
import { api, definirCsrf } from '../lib/api.js'
import { SessionContext } from './session.js'

/** Utilisateur connecté, jeton CSRF et listes de référence (catégories, natures…). */
export function SessionProvider({ children }) {
  const [user, setUser] = useState(null)
  const [references, setReferences] = useState(null)
  const [pret, setPret] = useState(false)

  const rafraichir = useCallback(async () => {
    const session = await api('/session')
    definirCsrf(session.csrfToken)
    setUser(session.user)
    return session.user
  }, [])

  useEffect(() => {
    Promise.all([rafraichir(), api('/references').then(setReferences)])
      .catch(() => {})
      .finally(() => setPret(true))
  }, [rafraichir])

  const connexion = useCallback(
    async (email, password) => {
      await api('/connexion', { method: 'POST', body: { email, password } })
      // La connexion change la session : on récupère un nouveau jeton CSRF.
      return rafraichir()
    },
    [rafraichir],
  )

  const deconnexion = useCallback(async () => {
    await api('/deconnexion', { method: 'POST' }).catch(() => {})
    return rafraichir()
  }, [rafraichir])

  const valeur = useMemo(
    () => ({ user, setUser, references, pret, rafraichir, connexion, deconnexion }),
    [user, references, pret, rafraichir, connexion, deconnexion],
  )

  return <SessionContext.Provider value={valeur}>{children}</SessionContext.Provider>
}

