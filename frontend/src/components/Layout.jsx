import { useEffect, useRef } from 'react'
import { Link, NavLink, Outlet, useLocation, useNavigate } from 'react-router-dom'
import { ROLES, useSession } from '../context/session.js'
import Logo from './Logo.jsx'

function liensPour(user) {
  if (!user) {
    return [
      { to: '/', label: 'Accueil', end: true },
      { to: '/connexion', label: 'Se connecter' },
      { to: '/inscription', label: "S'inscrire" },
    ]
  }
  switch (user.role) {
    case ROLES.HABITANT:
      return [
        { to: '/publications', label: 'Les annonces' },
        { to: '/publier', label: 'Publier' },
        { to: '/mes-publications', label: 'Mes annonces' },
        { to: '/profil', label: 'Mon profil' },
      ]
    case ROLES.MAIRIE:
      return [
        { to: '/mairie', label: 'Espace mairie' },
        { to: '/publications', label: 'Les annonces' },
      ]
    case ROLES.ADMIN:
      return [{ to: '/admin', label: 'Administration' }]
    default:
      return [{ to: '/profil', label: 'Mon compte' }]
  }
}

export default function Layout() {
  const { user, references, pret, deconnexion } = useSession()
  const navigate = useNavigate()
  const location = useLocation()
  const mainRef = useRef(null)
  const premierRendu = useRef(true)

  // Accessibilité : à chaque changement de page, le focus va au contenu principal.
  useEffect(() => {
    if (premierRendu.current) {
      premierRendu.current = false
      return
    }
    mainRef.current?.focus()
    window.scrollTo(0, 0)
  }, [location.pathname])

  async function seDeconnecter() {
    await deconnexion()
    navigate('/')
  }

  return (
    <>
      <a href="#contenu" className="lien-evitement">
        Aller au contenu
      </a>
      <header className="entete">
        <div className="conteneur entete__barre">
          <Link to="/" className="marque">
            <Logo />
            <span className="marque__nom">
              Côte à côte
              {references?.commune && <span className="marque__commune">Entraide à {references.commune.nom}</span>}
            </span>
          </Link>
          {user && (
            <div className="utilisateur">
              <span className="utilisateur__nom">
                Bonjour <strong>{user.prenom}</strong>
              </span>
              <button type="button" className="bouton bouton--secondaire" onClick={seDeconnecter}>
                Se déconnecter
              </button>
            </div>
          )}
        </div>
        <nav className="nav" aria-label="Navigation principale">
          <ul className="conteneur">
            {(pret ? liensPour(user) : []).map((lien) => (
              <li key={lien.to}>
                <NavLink to={lien.to} end={lien.end}>
                  {lien.label}
                </NavLink>
              </li>
            ))}
          </ul>
        </nav>
      </header>

      <main id="contenu" ref={mainRef} tabIndex={-1} style={{ outline: 'none' }}>
        <div className="conteneur">
          <Outlet />
        </div>
      </main>

      <footer className="pied">
        <div className="conteneur">
          <ul>
            <li>
              <Link to="/mentions-legales">Mentions légales</Link>
            </li>
            <li>
              <Link to="/confidentialite">Confidentialité</Link>
            </li>
            <li>
              <Link to="/cgu">Conditions d'utilisation</Link>
            </li>
          </ul>
          <p>Côte à côte — l'entraide entre voisins est gratuite. Aucun cookie publicitaire, aucun suivi.</p>
        </div>
      </footer>
    </>
  )
}
