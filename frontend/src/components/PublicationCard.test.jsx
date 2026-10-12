import { fireEvent, render, screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import Logo from './Logo.jsx'
import PublicationCard from './PublicationCard.jsx'

const base = {
  id: 1,
  type: 'demande',
  typeLibelle: 'Je cherche',
  nature: 'aide',
  natureLibelle: 'Aide ponctuelle',
  categorie: { id: 3, libelle: 'Courses', parent: 'Courses et aide ponctuelle' },
  complement: 'le samedi matin',
  auteur: 'Marie D.',
  rue: 'Rue des Lilas',
  publieeParMairie: false,
  createdAt: new Date().toISOString(),
  estAuteur: false,
  dejaRepondu: false,
  dejaSignale: false,
}

describe('PublicationCard', () => {
  it('affiche uniquement prénom + initiale, rue, catégorie, complément et date (EF-16)', () => {
    render(<PublicationCard publication={base} peutAgir />)

    expect(screen.getByRole('heading', { name: 'Courses' })).toBeInTheDocument()
    expect(screen.getByText('Marie D.')).toBeInTheDocument()
    expect(screen.getByText('Rue des Lilas')).toBeInTheDocument()
    expect(screen.getByText('« le samedi matin »')).toBeInTheDocument()
    expect(screen.getByText("aujourd'hui")).toBeInTheDocument()
    expect(document.body.textContent).not.toMatch(/@/)
  })

  it('propose « Je peux aider » sur une demande et « Ça m’intéresse » sur une offre (EF-17)', () => {
    const { rerender } = render(<PublicationCard publication={base} peutAgir />)
    expect(screen.getByRole('button', { name: 'Je peux aider' })).toBeInTheDocument()

    rerender(<PublicationCard publication={{ ...base, type: 'offre', typeLibelle: 'Je propose' }} peutAgir />)
    expect(screen.getByRole('button', { name: "Ça m'intéresse" })).toBeInTheDocument()
  })

  it('demande une confirmation avant de partager son contact', () => {
    render(<PublicationCard publication={base} peutAgir />)
    fireEvent.click(screen.getByRole('button', { name: 'Je peux aider' }))

    expect(screen.getByText(/Marie recevra par e-mail votre prénom/)).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Confirmer' })).toBeInTheDocument()
  })

  it("n'affiche aucun bouton à un compte qui ne peut pas agir", () => {
    render(<PublicationCard publication={base} peutAgir={false} />)
    expect(screen.queryByRole('button')).not.toBeInTheDocument()
  })

  it('signale une annonce publiée par la mairie', () => {
    render(<PublicationCard publication={{ ...base, publieeParMairie: true, auteur: 'Germaine L.' }} peutAgir />)
    expect(screen.getByText('Publiée par la mairie')).toBeInTheDocument()
  })
})

describe('Logo', () => {
  it('est décoratif par défaut et porte un nom accessible quand on lui donne un titre', () => {
    const { container, rerender } = render(<Logo />)
    expect(container.firstChild).toHaveAttribute('aria-hidden', 'true')

    rerender(<Logo titre="Maison et deux voisins" />)
    expect(screen.getByRole('img', { name: 'Maison et deux voisins' })).toBeInTheDocument()
  })
})
