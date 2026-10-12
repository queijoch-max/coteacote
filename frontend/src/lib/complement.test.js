import { describe, expect, it } from 'vitest'
import { MESSAGE, normaliser, regleEnfreinte, verifierComplement } from './complement.js'

describe('vérification du complément (confort côté navigateur)', () => {
  it.each([
    ['06 12 34', 'RG-01'],
    ['12 rue des lilas', 'RG-02'],
    ['marie@', 'RG-03'],
    ['www.site.fr', 'RG-04'],
    ['<script>', 'RG-06'],
    ['a'.repeat(31), 'RG-07'],
    ['10 €', 'RG-09'],
  ])('refuse « %s » (%s)', (texte, regle) => {
    expect(regleEnfreinte(texte)).toBe(regle)
  })

  it.each([
    ['g m a i l', 'RG-03'],
    ['0range', 'RG-03'],
    ['b o u l e v a r d', 'RG-02'],
    ['marie point com', 'RG-04'],
  ])('déjoue le contournement « %s »', (texte, regle) => {
    expect(regleEnfreinte(texte)).toBe(regle)
  })

  it.each(['', 'le samedi matin', 'lave-linge', 'remplacer un joint', 'taille 4 ans', "L'après-midi"])(
    'accepte « %s »',
    (texte) => {
      expect(verifierComplement(texte)).toBeNull()
    },
  )

  it('renvoie un message générique', () => {
    expect(verifierComplement('12 rue des lilas')).toBe(MESSAGE)
  })

  it('normalise comme le serveur', () => {
    expect(normaliser('0rângé - E_5.4 I')).toBe('orangeesai')
  })
})
