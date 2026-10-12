import { useState } from 'react'
import { Message } from '../../components/Formulaire.jsx'
import useTitre from '../../lib/useTitre.js'
import { ListeMesPublications } from '../MesPublications.jsx'
import Publier from '../Publier.jsx'

/** EF-26 : annonces saisies par la mairie pour des habitants sans Internet. */
export default function PublicationsMairie() {
  useTitre('Publier pour un habitant')
  const [version, setVersion] = useState(0)
  const [message, setMessage] = useState(null)

  return (
    <>
      <h1>Publier pour un habitant sans Internet</h1>
      <Message type="succes">{message}</Message>
      <Publier
        key={version}
        pourMairie
        onPublie={() => {
          setMessage("L'annonce est en ligne.")
          setVersion((v) => v + 1)
        }}
      />
      <h2 style={{ marginTop: '2rem' }}>Annonces publiées par la mairie</h2>
      <ListeMesPublications key={`liste-${version}`} titreVide="Aucune annonce publiée par la mairie." />
    </>
  )
}
