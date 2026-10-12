/**
 * Client de l'API Symfony.
 * - Authentification par cookie de session (même origine grâce au proxy Vite).
 * - Jeton CSRF envoyé dans l'en-tête X-CSRF-Token pour toute requête qui modifie des données (ES-05).
 */

let csrfToken = null

export function definirCsrf(token) {
  csrfToken = token
}

export class ApiError extends Error {
  constructor(status, message, errors = {}) {
    super(message)
    this.status = status
    this.errors = errors
  }
}

export async function api(chemin, { method = 'GET', body } = {}) {
  const headers = { Accept: 'application/json' }
  if (body !== undefined) headers['Content-Type'] = 'application/json'
  if (method !== 'GET' && csrfToken) headers['X-CSRF-Token'] = csrfToken

  let reponse
  try {
    reponse = await fetch(`/api${chemin}`, {
      method,
      headers,
      credentials: 'same-origin',
      body: body !== undefined ? JSON.stringify(body) : undefined,
    })
  } catch {
    throw new ApiError(0, 'Impossible de joindre le serveur. Vérifiez votre connexion.')
  }

  if (reponse.status === 204) return null

  const data = await reponse.json().catch(() => ({}))
  if (!reponse.ok) {
    throw new ApiError(reponse.status, data.message || 'Une erreur est survenue.', data.errors || {})
  }

  return data
}

/** Construit une chaîne de requête en ignorant les valeurs vides. */
export function query(params) {
  const q = new URLSearchParams()
  Object.entries(params).forEach(([cle, valeur]) => {
    if (valeur !== '' && valeur !== null && valeur !== undefined) q.set(cle, valeur)
  })
  const texte = q.toString()
  return texte ? `?${texte}` : ''
}
