import { useEffect, useRef, useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { Message } from '../components/Formulaire.jsx'
import { useSession } from '../context/session.js'
import { api } from '../lib/api.js'
import useTitre from '../lib/useTitre.js'

/** EF-03 : confirmation de l'adresse e-mail par le lien reçu. */
export default function ConfirmerEmail() {
  useTitre("Confirmation de l'adresse e-mail")
  const [params] = useSearchParams()
  const { pret, rafraichir } = useSession()
  const [etat, setEtat] = useState({ type: 'info', texte: 'Vérification en cours…' })
  const dejaEnvoye = useRef(false)

  useEffect(() => {
    if (!pret || dejaEnvoye.current) return
    dejaEnvoye.current = true
    api('/confirmer-email', { method: 'POST', body: { token: params.get('token') ?? '' } })
      .then((r) => {
        setEtat({ type: 'succes', texte: r.message })
        rafraichir()
      })
      .catch((e) => setEtat({ type: 'erreur', texte: e.message }))
  }, [pret, params, rafraichir])

  return (
    <div className="page-etroite">
      <h1>Confirmation de votre adresse</h1>
      <div className="carte">
        <Message type={etat.type}>{etat.texte}</Message>
        <Link to="/connexion" className="bouton">
          Aller à la connexion
        </Link>
      </div>
    </div>
  )
}
