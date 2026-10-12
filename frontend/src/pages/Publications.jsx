import { useCallback, useEffect, useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { Message, SelectCategorie } from '../components/Formulaire.jsx'
import PublicationCard from '../components/PublicationCard.jsx'
import { ROLES, useSession } from '../context/session.js'
import { api, query } from '../lib/api.js'
import { pluriel } from '../lib/format.js'
import useTitre from '../lib/useTitre.js'

/** EF-15 : liste des annonces filtrable par type, catégorie et nature. */
export default function Publications() {
  useTitre('Les annonces')
  const { user, references } = useSession()
  const [params, setParams] = useSearchParams()
  const [items, setItems] = useState([])
  const [total, setTotal] = useState(0)
  const [page, setPage] = useState(1)
  const [chargement, setChargement] = useState(true)
  const [erreur, setErreur] = useState(null)

  const filtres = {
    type: params.get('type') ?? '',
    categorie: params.get('categorie') ?? '',
    nature: params.get('nature') ?? '',
  }

  const charger = useCallback(
    async (numero) => {
      setChargement(true)
      setErreur(null)
      try {
        const data = await api(`/publications${query({ ...filtres, page: numero })}`)
        setItems((precedents) => (numero === 1 ? data.items : [...precedents, ...data.items]))
        setTotal(data.total)
        setPage(numero)
      } catch (e) {
        setErreur(e.message)
      } finally {
        setChargement(false)
      }
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [params.toString()],
  )

  useEffect(() => {
    charger(1)
  }, [charger])

  function filtrer(cle, valeur) {
    const suivants = new URLSearchParams(params)
    if (valeur) suivants.set(cle, valeur)
    else suivants.delete(cle)
    setParams(suivants, { replace: true })
  }

  function apresAction(id, action) {
    setItems((liste) =>
      liste.map((p) =>
        p.id === id ? { ...p, dejaRepondu: p.dejaRepondu || action === 'repondre', dejaSignale: p.dejaSignale || action === 'signaler' } : p,
      ),
    )
  }

  const habitant = user.role === ROLES.HABITANT
  const filtresActifs = filtres.type || filtres.categorie || filtres.nature

  return (
    <>
      <div className="entete-page">
        <h1>Les annonces</h1>
      </div>

      {habitant && !user.peutAgir && (
        <Message type="info">
          Votre compte est {user.statutLibelle.toLowerCase()} : vous pouvez consulter les annonces mais pas publier ni
          répondre.
        </Message>
      )}

      {habitant && user.peutAgir && (
        <div className="grille grille--2" style={{ marginBottom: '1.5rem' }}>
          <Link to="/publier?type=demande" className="bouton bouton--grand">
            Je cherche de l'aide
          </Link>
          <Link to="/publier?type=offre" className="bouton bouton--secondaire bouton--grand">
            Je propose de l'aide
          </Link>
        </div>
      )}

      <form className="carte filtres" role="search" aria-label="Filtrer les annonces" onSubmit={(e) => e.preventDefault()}>
        <div className="champ">
          <label htmlFor="filtre-type">Type</label>
          <select id="filtre-type" value={filtres.type} onChange={(e) => filtrer('type', e.target.value)}>
            <option value="">Tout</option>
            {references?.types.map((t) => (
              <option key={t.value} value={t.value}>
                {t.libelle}
              </option>
            ))}
          </select>
        </div>
        <div className="champ">
          <label htmlFor="filtre-categorie">Catégorie</label>
          <SelectCategorie
            id="filtre-categorie"
            toutes
            categories={references?.categories ?? []}
            value={filtres.categorie}
            onChange={(v) => filtrer('categorie', v)}
          />
        </div>
        <div className="champ">
          <label htmlFor="filtre-nature">Nature</label>
          <select id="filtre-nature" value={filtres.nature} onChange={(e) => filtrer('nature', e.target.value)}>
            <option value="">Tout</option>
            {references?.natures.map((n) => (
              <option key={n.value} value={n.value}>
                {n.libelle}
              </option>
            ))}
          </select>
        </div>
        <button
          type="button"
          className="bouton bouton--secondaire"
          disabled={!filtresActifs}
          onClick={() => setParams({}, { replace: true })}
        >
          Effacer
        </button>
      </form>

      <Message type="erreur">{erreur}</Message>

      <p aria-live="polite" className="intro">
        {!chargement || page > 1 ? pluriel(total, 'annonce en ligne', 'annonces en ligne') : 'Chargement des annonces…'}
      </p>

      {items.length > 0 ? (
        <ul className="liste-publications">
          {items.map((p) => (
            <li key={p.id}>
              <PublicationCard
                publication={p}
                motifs={references?.motifsSignalement}
                peutAgir={user.peutAgir}
                onChange={apresAction}
              />
            </li>
          ))}
        </ul>
      ) : (
        !chargement && (
          <div className="carte vide">
            <p>Aucune annonce ne correspond à votre recherche.</p>
            {filtresActifs && (
              <button type="button" className="bouton bouton--secondaire" onClick={() => setParams({}, { replace: true })}>
                Voir toutes les annonces
              </button>
            )}
          </div>
        )
      )}

      {items.length < total && (
        <div className="actions" style={{ justifyContent: 'center', marginTop: '1.5rem' }}>
          <button type="button" className="bouton bouton--secondaire" disabled={chargement} onClick={() => charger(page + 1)}>
            {chargement ? 'Chargement…' : 'Voir plus d’annonces'}
          </button>
        </div>
      )}
    </>
  )
}
