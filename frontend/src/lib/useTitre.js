import { useEffect } from 'react'

/** Titre de l'onglet propre à chaque page (accessibilité). */
export default function useTitre(titre) {
  useEffect(() => {
    document.title = titre ? `${titre} — Côte à côte` : 'Côte à côte — entraide entre voisins'
  }, [titre])
}
