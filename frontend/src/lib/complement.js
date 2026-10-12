/**
 * Vérification du complément côté navigateur.
 * C'est un confort pour l'habitant (message immédiat) : la vraie vérification est faite
 * par le serveur (ES-04), qui applique aussi la liste des insultes.
 */

export const LONGUEUR_MAX = 30
export const MESSAGE = 'Ce texte contient des éléments non autorisés.'

const LOOK_ALIKE = { 0: 'o', 1: 'i', 3: 'e', 4: 'a', 5: 's' }
const VOIRIE = ['rue', 'avenue', 'av', 'bd', 'boulevard', 'chemin', 'allee', 'impasse', 'place', 'route', 'lieudit']
const VOIRIE_NORMALISE = ['avenue', 'boulevard', 'impasse', 'lieudit']
const EMAIL = ['arobase', 'gmail', 'hotmail', 'orange', 'free']
const WEB = ['http', 'www']
const ARGENT = ['euro', 'euros', 'prix', 'payer']

export function sansAccents(texte) {
  return texte.normalize('NFD').replace(/\p{Mn}/gu, '')
}

const convertir = (texte) => texte.replace(/[01345]/g, (c) => LOOK_ALIKE[c])

export function normaliser(texte) {
  return convertir(sansAccents(texte.toLowerCase()).replace(/[\s.\-_'’]+/gu, ''))
}

function mots(texte) {
  const liste = sansAccents(texte.toLowerCase()).split(/[^\p{L}\p{N}]+/u).filter(Boolean)
  return new Set([...liste, ...liste.map(convertir)])
}

/** Renvoie le code de la règle enfreinte (RG-xx) ou null si le texte est accepté. */
export function regleEnfreinte(texte) {
  if (!texte || !texte.trim()) return null
  if ([...texte].length > LONGUEUR_MAX) return 'RG-07'
  if ((texte.match(/\d/g) || []).length > 4) return 'RG-01'
  if (texte.includes('@')) return 'RG-03'
  if (/\.\s*(fr|com)\b/i.test(texte)) return 'RG-04'
  if (texte.includes('€')) return 'RG-09'
  if (!/^[\p{L}\p{N} '’-]*$/u.test(texte)) return 'RG-06'

  const m = mots(texte)
  const n = normaliser(texte)
  const contientMot = (liste) => liste.some((mot) => m.has(mot))
  const contient = (liste) => liste.some((mot) => n.includes(mot))

  if (contientMot(VOIRIE) || contient(VOIRIE_NORMALISE)) return 'RG-02'
  if (contientMot(EMAIL) || contient(EMAIL)) return 'RG-03'
  if (contientMot(WEB) || contient([...WEB, 'pointfr', 'pointcom', 'dotcom'])) return 'RG-04'
  if (contientMot(ARGENT) || contient(['prix', 'payer'])) return 'RG-09'

  return null
}

export function verifierComplement(texte) {
  return regleEnfreinte(texte) ? MESSAGE : null
}
