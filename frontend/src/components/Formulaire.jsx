import { useId } from 'react'

/** Champ de formulaire accessible : libellé, aide et erreur reliés au champ. */
export function Champ({ label, aide, erreur, children, id: idImpose }) {
  const idAuto = useId()
  const id = idImpose || idAuto
  const idAide = aide ? `${id}-aide` : undefined
  const idErreur = erreur ? `${id}-erreur` : undefined
  const decrit = [idAide, idErreur].filter(Boolean).join(' ') || undefined

  return (
    <div className="champ">
      <label htmlFor={id}>{label}</label>
      {aide && (
        <span className="champ__aide" id={idAide}>
          {aide}
        </span>
      )}
      {children({ id, 'aria-describedby': decrit, 'aria-invalid': erreur ? true : undefined })}
      {erreur && (
        <span className="champ__erreur" id={idErreur}>
          {erreur}
        </span>
      )}
    </div>
  )
}

/** Message de retour, annoncé aux lecteurs d'écran. */
export function Message({ type = 'info', children }) {
  if (!children) return null
  return (
    <div className={`message message--${type}`} role={type === 'erreur' ? 'alert' : 'status'}>
      {children}
    </div>
  )
}

/** Choix en gros boutons radio (type, nature). */
export function Choix({ legende, name, options, valeur, onChange, erreur }) {
  return (
    <fieldset className="champ" aria-describedby={erreur ? `${name}-erreur` : undefined}>
      <legend>{legende}</legend>
      <div className="choix">
        {options.map((option) => (
          <label key={option.value} className="choix__option">
            <input
              type="radio"
              name={name}
              value={option.value}
              checked={valeur === option.value}
              onChange={() => onChange(option.value)}
            />
            <span>
              {option.libelle}
              {option.aide && <small>{option.aide}</small>}
            </span>
          </label>
        ))}
      </div>
      {erreur && (
        <span className="champ__erreur" id={`${name}-erreur`}>
          {erreur}
        </span>
      )}
    </fieldset>
  )
}

/** Liste déroulante catégorie / sous-catégorie (groupes = catégories). */
export function SelectCategorie({ categories, value, onChange, toutes = false, ...props }) {
  return (
    <select value={value} onChange={(e) => onChange(e.target.value)} {...props}>
      <option value="">{toutes ? 'Toutes les catégories' : '— Choisir —'}</option>
      {categories.map((c) =>
        toutes ? (
          <optgroup key={c.id} label={c.libelle}>
            <option value={c.id}>{c.libelle} (tout)</option>
            {c.sousCategories.map((s) => (
              <option key={s.id} value={s.id}>
                {s.libelle}
              </option>
            ))}
          </optgroup>
        ) : (
          <optgroup key={c.id} label={c.libelle}>
            {c.sousCategories.map((s) => (
              <option key={s.id} value={s.id}>
                {s.libelle}
              </option>
            ))}
          </optgroup>
        ),
      )}
    </select>
  )
}
