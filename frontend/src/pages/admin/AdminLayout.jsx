import { NavLink, Outlet } from 'react-router-dom'

export default function AdminLayout() {
  return (
    <>
      <nav aria-label="Administration">
        <ul className="sous-nav">
          <li>
            <NavLink to="/admin" end>
              Catégories
            </NavLink>
          </li>
          <li>
            <NavLink to="/admin/mots-interdits">Mots interdits</NavLink>
          </li>
          <li>
            <NavLink to="/admin/comptes-mairie">Comptes mairie</NavLink>
          </li>
        </ul>
      </nav>
      <Outlet />
    </>
  )
}
