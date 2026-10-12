import { useState } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { Champ, Choix, Message, SelectCategorie } from '../components/Formulaire.jsx'
import { useSession } from '../context/session.js'
import { api } from '../lib/api.js'
import { LONGUEUR_MAX, verifierComplement } from '../lib/complement.js'
import useTitre from '../lib/useTitre.js'

const AIDES_NATURE = { don: 'je donne / on me donne', pret: 'à rendre ensuite', aide: 'un coup de main' }

/**
 * EF-08 à EF-11 : publication par listes uniquement, plus un complément de 30 caractères.
 * ENF-05 : depuis la liste, « Je cherche de l'aide » → catégorie → Publier (nature pré-remplie).
 */
export default function Publier({ pourMairie = false, onPublie }) {
  useTitre(pourMairie ? 'Publier pour un habitant' : 'Publier une annonce')
  const { references, user } = useSession()
  const [params] = useSearchParams()
  const navigate = useNavigate()

  const [form, setForm] = useState({
    type: params.get('type') === 'offre' ? 'offre' : 'demande',
    categorieId: '',
    nature: 'aide',
    complement: '',
    prenom: '',
    initiale: '',
    rue: '',
  })
  const [erreurs, setErreurs] = useState({})
  const [erreur, setErreur] = useState(null)
  const [envoi, setEnvoi] = useState(false)

  const maj = (champ, valeur) => {
    setForm((f) => ({ ...f, [champ]: valeur }))
    if (champ === 'complement') setErreurs((e) => ({ ...e, complement: verifierComplement(valeur) }))
  }

  if (!pourMairie && !user.peutAgir) {
    return <Message type="info">Votre compte ne permet pas de publier pour le moment.</Message>
  }

  async function soumettre(e) {
    e.preventDefault()
    const locales = {}
    if (!form.categorieId) locales.categorieId = 'Choisissez une catégorie.'
    const erreurComplement = verifierComplement(form.complement)
    if (erreurComplement) locales.complement = erreurComplement
    if (Object.keys(locales).length) {
      setErreurs(locales)
      return
    }

    setEnvoi(true)
    setErreur(null)
    try {
      const publication = {
        type: form.type,
        categorieId: Number(form.categorieId),
        nature: form.nature,
        complement: form.complement || null,
      }
      if (pourMairie) {
        const beneficiaire = { prenom: form.prenom, initiale: form.initiale, rue: form.rue }
        await api('/mairie/publications', { method: 'POST', body: { ...publication, ...beneficiaire } })
        onPublie?.()
      } else {
        await api('/publications', { method: 'POST', body: publication })
        navigate('/mes-publications', { state: { message: 'Votre annonce est en ligne !' } })
      }
    } catch (err) {
      setErreurs(err.errors)
      setErreur(err.message)
      setEnvoi(false)
    }
  }

  if (!references) return <p className="chargement">Chargement…</p>

  return (
    <div className={pourMairie ? undefined : 'page-etroite'}>
      {!pourMairie && <h1>Publier une annonce</h1>}
      <form className="carte" onSubmit={soumettre} noValidate>
        <Message type="erreur">{erreur}</Message>

        {pourMairie && (
          <fieldset>
            <legend className="etiquette">Habitant sans Internet</legend>
            <p className="champ__aide">
              L'annonce indiquera « publiée par la mairie ». Les réponses arriveront à l'adresse de la mairie.
            </p>
            <div className="grille grille--3">
              <Champ label="Prénom" erreur={erreurs.prenom}>
                {(p) => <input {...p} type="text" value={form.prenom} onChange={(e) => maj('prenom', e.target.value)} required />}
              </Champ>
              <Champ label="Initiale du nom" erreur={erreurs.initiale}>
                {(p) => (
                  <input {...p} type="text" maxLength={1} value={form.initiale} onChange={(e) => maj('initiale', e.target.value)} required />
                )}
              </Champ>
              <Champ label="Rue (sans numéro)" erreur={erreurs.rue}>
                {(p) => <input {...p} type="text" value={form.rue} onChange={(e) => maj('rue', e.target.value)} required />}
              </Champ>
            </div>
          </fieldset>
        )}

        <Choix
          legende="Vous…"
          name="type"
          valeur={form.type}
          onChange={(v) => maj('type', v)}
          options={references.types}
          erreur={erreurs.type}
        />

        <Champ label="Catégorie" erreur={erreurs.categorieId}>
          {(p) => (
            <SelectCategorie
              {...p}
              categories={references.categories}
              value={form.categorieId}
              onChange={(v) => maj('categorieId', v)}
              required
            />
          )}
        </Champ>

        <Choix
          legende="Nature"
          name="nature"
          valeur={form.nature}
          onChange={(v) => maj('nature', v)}
          options={references.natures.map((n) => ({ ...n, aide: AIDES_NATURE[n.value] }))}
          erreur={erreurs.nature}
        />

        <Champ
          label="Précision (facultatif)"
          aide="30 caractères maximum. Pas d'adresse, de téléphone, d'e-mail ni de prix. Exemple : le samedi matin"
          erreur={erreurs.complement}
        >
          {(p) => (
            <>
              <input
                {...p}
                type="text"
                maxLength={LONGUEUR_MAX}
                value={form.complement}
                onChange={(e) => maj('complement', e.target.value)}
              />
              <span className="compteur" aria-live="polite">
                {form.complement.length} / {LONGUEUR_MAX}
              </span>
            </>
          )}
        </Champ>

        <div className="actions">
          <button type="submit" className="bouton bouton--grand" disabled={envoi}>
            {envoi ? 'Publication…' : "Publier l'annonce"}
          </button>
          {!pourMairie && (
            <Link to="/publications" className="bouton bouton--discret">
              Annuler
            </Link>
          )}
        </div>
      </form>
    </div>
  )
}
