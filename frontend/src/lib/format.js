const formatDate = new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })
const formatDateHeure = new Intl.DateTimeFormat('fr-FR', {
  day: 'numeric',
  month: 'long',
  hour: '2-digit',
  minute: '2-digit',
})

export function date(iso) {
  return iso ? formatDate.format(new Date(iso)) : ''
}

export function dateHeure(iso) {
  return iso ? formatDateHeure.format(new Date(iso)) : ''
}

/** « aujourd'hui », « hier », « il y a 3 jours », sinon la date. */
export function depuis(iso, maintenant = new Date()) {
  if (!iso) return ''
  const jour = (d) => new Date(d.getFullYear(), d.getMonth(), d.getDate())
  const ecart = Math.round((jour(maintenant) - jour(new Date(iso))) / 86400000)
  if (ecart <= 0) return "aujourd'hui"
  if (ecart === 1) return 'hier'
  if (ecart < 7) return `il y a ${ecart} jours`
  return `le ${date(iso)}`
}

export function pluriel(n, singulier, plurielTexte = `${singulier}s`) {
  return `${n} ${n > 1 ? plurielTexte : singulier}`
}
