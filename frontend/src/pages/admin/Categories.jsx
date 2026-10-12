import { useCallback, useEffect, useState } from 'react'
import { Champ, Message } from '../../components/Formulaire.jsx'
import { useSession } from '../../context/session.js'
import { api } from '../../lib/api.js'
import useTitre from '../../lib/useTitre.js'

/** RG-08 : catégories gérées en base par l'administrateur association. */
export default function Categories() {
  useTitre('Catégories')
  const { rafraichir } = useSession()
  const [categories, setCategories] = useState(null)
  const [nouvelle, setNouvelle] = useState({ libelle: '', parentId: '' })
  const [message, setMessage] = useState(null)
  const [erreur, setErreur] = useState(null)

  const charger = useCallback(() => api('/admin/categories').then(setCategories).catch((e) => setErreur(e.message)), [])

  useEffect(() => {
    charger()
  }, [charger])

  async function enregistrer(chemin, method, body, texte) {
    setErreur(null)
    try {
      await api(chemin, { method, body })
      setMessage(texte)
      charger()
      rafraichir()
    } catch (e) {
      setErreur(e.errors?.libelle ?? e.message)
    }
  }

  function ajouter(e) {
    e.preventDefault()
    const parent = categories.find((c) => String(c.id) === nouvelle.parentId)
    const ordre = (parent ? parent.sousCategories.length : categories.length) + 1
    enregistrer(
      '/admin/categories',
      'POST',
      { libelle: nouvelle.libelle, parentId: nouvelle.parentId ? Number(nouvelle.parentId) : null, actif: true, ordre },
      'Catégorie ajoutée.',
    )
    setNouvelle({ libelle: '', parentId: '' })
  }

  const basculer = (c) =>
    enregistrer(`/admin/categories/${c.id}`, 'PUT', { ...c, actif: !c.actif }, c.actif ? 'Catégorie désactivée.' : 'Catégorie réactivée.')

  if (!categories) return <p className="chargement">{erreur ?? 'Chargement…'}</p>

  return (
    <>
      <h1>Catégories</h1>
      <p className="intro">
        Une catégorie désactivée n'apparaît plus dans les listes. Les catégories à risque (électricité, gaz, travaux en
        hauteur) ne doivent pas être créées.
      </p>
      <Message type="succes">{message}</Message>
      <Message type="erreur">{erreur}</Message>

      <form className="carte" onSubmit={ajouter} style={{ marginBottom: '1.5rem' }}>
        <h2>Ajouter une catégorie</h2>
        <div className="grille grille--2">
          <Champ label="Libellé">
            {(p) => (
              <input {...p} type="text" required maxLength={80} value={nouvelle.libelle} onChange={(e) => setNouvelle({ ...nouvelle, libelle: e.target.value })} />
            )}
          </Champ>
          <Champ label="Dans la catégorie">
            {(p) => (
              <select {...p} value={nouvelle.parentId} onChange={(e) => setNouvelle({ ...nouvelle, parentId: e.target.value })}>
                <option value="">— Catégorie principale —</option>
                {categories.map((c) => (
                  <option key={c.id} value={c.id}>
                    {c.libelle}
                  </option>
                ))}
              </select>
            )}
          </Champ>
        </div>
        <button type="submit" className="bouton">
          Ajouter
        </button>
      </form>

      {categories.map((c) => (
        <section className="carte" key={c.id} aria-labelledby={`cat-${c.id}`}>
          <div className="entete-page" style={{ marginBottom: '0.5rem' }}>
            <h2 id={`cat-${c.id}`} style={{ opacity: c.actif ? 1 : 0.6 }}>
              {c.libelle} {!c.actif && '(désactivée)'}
            </h2>
            <button type="button" className="bouton bouton--secondaire" onClick={() => basculer(c)}>
              {c.actif ? 'Désactiver' : 'Réactiver'}
            </button>
          </div>
          <ul>
            {c.sousCategories.map((s) => (
              <li key={s.id} style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '1rem' }}>
                <span style={{ opacity: s.actif ? 1 : 0.6 }}>
                  {s.libelle} {!s.actif && '(désactivée)'}
                </span>
                <button type="button" className="bouton bouton--discret" onClick={() => basculer(s)}>
                  {s.actif ? 'Désactiver' : 'Réactiver'}
                  <span className="visually-hidden"> {s.libelle}</span>
                </button>
              </li>
            ))}
          </ul>
        </section>
      ))}
    </>
  )
}
