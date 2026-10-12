import { useCallback, useEffect, useState } from 'react'
import { NavLink, Outlet } from 'react-router-dom'
import { api } from '../../lib/api.js'

/** Sous-navigation de l'espace mairie, avec les compteurs d'actions en attente. */
export default function MairieLayout() {
  const [compteurs, setCompteurs] = useState(null)

  const rafraichirCompteurs = useCallback(() => {
    api('/mairie/tableau-de-bord').then(setCompteurs).catch(() => {})
  }, [])

  useEffect(() => {
    rafraichirCompteurs()
  }, [rafraichirCompteurs])

  const badge = (n) =>
    n > 0 ? (
      <span className="badge">
        {n}
        <span className="visually-hidden"> en attente</span>
      </span>
    ) : null

  return (
    <>
      <nav aria-label="Espace mairie">
        <ul className="sous-nav">
          <li>
            <NavLink to="/mairie" end>
              Tableau de bord
            </NavLink>
          </li>
          <li>
            <NavLink to="/mairie/inscriptions">Inscriptions{badge(compteurs?.inscriptionsEnAttente)}</NavLink>
          </li>
          <li>
            <NavLink to="/mairie/signalements">Signalements{badge(compteurs?.signalementsEnAttente)}</NavLink>
          </li>
          <li>
            <NavLink to="/mairie/habitants">Habitants</NavLink>
          </li>
          <li>
            <NavLink to="/mairie/annonces">Publier pour un habitant</NavLink>
          </li>
          <li>
            <NavLink to="/mairie/journal">Journal</NavLink>
          </li>
        </ul>
      </nav>
      <Outlet context={{ compteurs, rafraichirCompteurs }} />
    </>
  )
}
