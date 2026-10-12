import { useState } from 'react'
import { Champ, Message } from '../components/Formulaire.jsx'
import { useSession } from '../context/session.js'
import { api } from '../lib/api.js'
import { date } from '../lib/format.js'
import useTitre from '../lib/useTitre.js'

function Statut({ user, onChange }) {
  const [message, setMessage] = useState(null)
  const [erreur, setErreur] = useState(null)

  async function appeler(chemin, method = 'POST') {
    setErreur(null)
    try {
      const r = await api(chemin, { method })
      if (r?.message) setMessage(r.message)
      else onChange(r)
    } catch (e) {
      setErreur(e.message)
    }
  }

  let contenu = null
  if (user.statut === 'en_attente' && !user.emailConfirme) {
    contenu = (
      <>
        <p>
          <strong>Confirmez votre adresse e-mail</strong> en cliquant sur le lien que nous vous avons envoyé. Ensuite, la
          mairie examinera votre inscription.
        </p>
        <button type="button" className="bouton bouton--secondaire" onClick={() => appeler('/moi/renvoyer-confirmation')}>
          Renvoyer l'e-mail de confirmation
        </button>
      </>
    )
  } else if (user.statut === 'en_attente') {
    contenu = (
      <p>
        <strong>Votre inscription est en cours de vérification par la mairie.</strong> Vous recevrez un e-mail dès qu'elle
        sera validée.
      </p>
    )
  } else if (user.statut === 'refuse') {
    contenu = (
      <>
        <p>
          <strong>La mairie n'a pas pu valider votre inscription.</strong>
        </p>
        {user.motifRefus && <p>{user.motifRefus}</p>}
      </>
    )
  } else if (user.statut === 'suspendu') {
    contenu = (
      <p>
        <strong>Votre compte est suspendu par la mairie.</strong> Vous ne pouvez plus publier ni répondre. Contactez la
        mairie pour en savoir plus.
      </p>
    )
  } else if (user.statut === 'supprime') {
    contenu = (
      <>
        <p>
          <strong>Vous avez demandé la suppression de votre compte.</strong> Il sera effacé définitivement le{' '}
          {date(user.suppressionDefinitiveLe)}. Vos annonces ont déjà été supprimées.
        </p>
        <button type="button" className="bouton" onClick={() => appeler('/moi/annuler-suppression')}>
          Annuler la suppression
        </button>
      </>
    )
  }

  if (!contenu) return null

  return (
    <section className="carte" aria-labelledby="titre-statut" style={{ marginBottom: '1.5rem' }}>
      <h2 id="titre-statut">État de votre compte</h2>
      <Message type="succes">{message}</Message>
      <Message type="erreur">{erreur}</Message>
      {contenu}
    </section>
  )
}

/** EF-06, EF-07, DP-04 */
export default function Profil() {
  useTitre('Mon profil')
  const { user, setUser, deconnexion } = useSession()
  const [form, setForm] = useState({
    prenom: user.prenom,
    nom: user.nom,
    rue: user.rue,
    telephone: user.telephone ?? '',
    partageTelephone: user.partageTelephone,
    partageEmail: user.partageEmail,
  })
  const [erreurs, setErreurs] = useState({})
  const [message, setMessage] = useState(null)
  const [erreur, setErreur] = useState(null)
  const [confirmerSuppression, setConfirmerSuppression] = useState(false)

  const maj = (champ) => (e) =>
    setForm({ ...form, [champ]: e.target.type === 'checkbox' ? e.target.checked : e.target.value })

  async function enregistrer(e) {
    e.preventDefault()
    setMessage(null)
    setErreur(null)
    setErreurs({})
    try {
      const moi = await api('/moi', { method: 'PUT', body: { ...form, telephone: form.telephone || null } })
      setUser(moi)
      setMessage('Vos informations sont enregistrées.')
    } catch (err) {
      setErreurs(err.errors)
      setErreur(err.message)
    }
  }

  async function supprimer() {
    try {
      const moi = await api('/moi', { method: 'DELETE' })
      setUser(moi)
      setConfirmerSuppression(false)
      window.scrollTo(0, 0)
    } catch (err) {
      setErreur(err.message)
    }
  }

  return (
    <>
      <h1>Mon profil</h1>
      <Statut user={user} onChange={setUser} />

      <div className="grille grille--2">
        <form className="carte" onSubmit={enregistrer} noValidate aria-labelledby="titre-infos">
          <h2 id="titre-infos">Mes informations</h2>
          <Message type="succes">{message}</Message>
          <Message type="erreur">{erreur}</Message>
          <Champ label="Prénom" erreur={erreurs.prenom}>
            {(p) => <input {...p} type="text" autoComplete="given-name" value={form.prenom} onChange={maj('prenom')} />}
          </Champ>
          <Champ label="Nom" erreur={erreurs.nom}>
            {(p) => <input {...p} type="text" autoComplete="family-name" value={form.nom} onChange={maj('nom')} />}
          </Champ>
          <Champ label="Rue (sans numéro)" erreur={erreurs.rue}>
            {(p) => <input {...p} type="text" value={form.rue} onChange={maj('rue')} />}
          </Champ>
          <Champ label="Téléphone (facultatif)" aide="Exemple : 06 12 34 56 78" erreur={erreurs.telephone}>
            {(p) => <input {...p} type="tel" autoComplete="tel" value={form.telephone} onChange={maj('telephone')} />}
          </Champ>

          <fieldset>
            <legend className="etiquette">Quand je réponds à une annonce, je partage :</legend>
            <p className="champ__aide">Seul l'auteur de l'annonce le recevra, par e-mail.</p>
            <label className="case">
              <input type="checkbox" checked={form.partageEmail} onChange={maj('partageEmail')} />
              <span>Mon adresse e-mail ({user.email})</span>
            </label>
            <label className="case">
              <input type="checkbox" checked={form.partageTelephone} onChange={maj('partageTelephone')} />
              <span>Mon numéro de téléphone</span>
            </label>
            {erreurs.partageEmail && <span className="champ__erreur">{erreurs.partageEmail}</span>}
          </fieldset>

          <button type="submit" className="bouton">
            Enregistrer
          </button>
        </form>

        <div>
          <section className="carte" aria-labelledby="titre-donnees">
            <h2 id="titre-donnees">Mes données</h2>
            <p>Téléchargez toutes les informations que l'application conserve sur vous (fichier JSON).</p>
            <a href="/api/moi/export" className="bouton bouton--secondaire" download>
              Télécharger mes données
            </a>
          </section>

          {user.statut !== 'supprime' && (
            <section className="carte" aria-labelledby="titre-suppression">
              <h2 id="titre-suppression">Supprimer mon compte</h2>
              <p>
                Vos annonces seront supprimées tout de suite. Votre compte sera effacé définitivement après 30 jours :
                d'ici là, vous pourrez changer d'avis.
              </p>
              {confirmerSuppression ? (
                <div className="confirmation">
                  <p>
                    <strong>Confirmez-vous la suppression de votre compte ?</strong>
                  </p>
                  <div className="actions">
                    <button type="button" className="bouton bouton--danger" onClick={supprimer}>
                      Oui, supprimer
                    </button>
                    <button type="button" className="bouton bouton--secondaire" onClick={() => setConfirmerSuppression(false)}>
                      Annuler
                    </button>
                  </div>
                </div>
              ) : (
                <button type="button" className="bouton bouton--danger" onClick={() => setConfirmerSuppression(true)}>
                  Supprimer mon compte
                </button>
              )}
            </section>
          )}

          <p style={{ marginTop: '1rem' }}>
            <button type="button" className="bouton bouton--discret" onClick={deconnexion}>
              Se déconnecter
            </button>
          </p>
        </div>
      </div>
    </>
  )
}
