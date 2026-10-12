import './Logo.css'

/** Logo en CSS pur : une maison avec deux personnes qui se tiennent la main. */
export default function Logo({ grand = false, titre }) {
  return (
    <span
      className={grand ? 'logo logo--grand' : 'logo'}
      role={titre ? 'img' : undefined}
      aria-label={titre}
      aria-hidden={titre ? undefined : true}
    >
      <span className="logo__toit" />
      <span className="logo__maison">
        <span className="logo__personne logo__personne--gauche" />
        <span className="logo__mains" />
        <span className="logo__personne logo__personne--droite" />
      </span>
    </span>
  )
}
