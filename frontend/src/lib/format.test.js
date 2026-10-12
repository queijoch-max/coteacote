import { describe, expect, it } from 'vitest'
import { depuis, pluriel } from './format.js'

describe('format', () => {
  const maintenant = new Date('2026-10-10T15:00:00')

  it('affiche des dates relatives lisibles', () => {
    expect(depuis('2026-10-10T08:00:00', maintenant)).toBe("aujourd'hui")
    expect(depuis('2026-10-09T23:00:00', maintenant)).toBe('hier')
    expect(depuis('2026-10-07T10:00:00', maintenant)).toBe('il y a 3 jours')
    expect(depuis('2026-09-01T10:00:00', maintenant)).toBe('le 1 septembre 2026')
  })

  it('accorde au pluriel', () => {
    expect(pluriel(0, 'réponse')).toBe('0 réponse')
    expect(pluriel(1, 'réponse')).toBe('1 réponse')
    expect(pluriel(2, 'réponse')).toBe('2 réponses')
  })
})
