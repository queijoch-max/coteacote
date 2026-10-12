import { Link } from 'react-router-dom'
import useTitre from '../lib/useTitre.js'

export default function Introuvable() {
  useTitre('Page introuvable')
  return (
    <div className="page-etroite carte vide">
      <h1>Page introuvable</h1>
      <p>Cette page n'existe pas ou a été déplacée.</p>
      <Link to="/" className="bouton">
        Retour à l'accueil
      </Link>
    </div>
  )
}
