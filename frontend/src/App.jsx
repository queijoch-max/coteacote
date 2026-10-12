import { Route, Routes } from 'react-router-dom'
import Layout from './components/Layout.jsx'
import Protege from './components/Protege.jsx'
import { ROLES } from './context/session.js'
import Accueil from './pages/Accueil.jsx'
import ConfirmerEmail from './pages/ConfirmerEmail.jsx'
import Connexion from './pages/Connexion.jsx'
import Inscription from './pages/Inscription.jsx'
import { Cgu, Confidentialite, MentionsLegales } from './pages/Legal.jsx'
import MesPublications from './pages/MesPublications.jsx'
import Introuvable from './pages/Introuvable.jsx'
import Profil from './pages/Profil.jsx'
import Publications from './pages/Publications.jsx'
import Publier from './pages/Publier.jsx'
import AdminLayout from './pages/admin/AdminLayout.jsx'
import Categories from './pages/admin/Categories.jsx'
import ComptesMairie from './pages/admin/ComptesMairie.jsx'
import MotsInterdits from './pages/admin/MotsInterdits.jsx'
import Habitants from './pages/mairie/Habitants.jsx'
import Inscriptions from './pages/mairie/Inscriptions.jsx'
import Journal from './pages/mairie/Journal.jsx'
import MairieLayout from './pages/mairie/MairieLayout.jsx'
import PublicationsMairie from './pages/mairie/PublicationsMairie.jsx'
import Signalements from './pages/mairie/Signalements.jsx'
import TableauDeBord from './pages/mairie/TableauDeBord.jsx'

const { PENDING, HABITANT, MAIRIE, ADMIN } = ROLES

export default function App() {
  return (
    <Routes>
      <Route element={<Layout />}>
        <Route index element={<Accueil />} />
        <Route path="connexion" element={<Connexion />} />
        <Route path="inscription" element={<Inscription />} />
        <Route path="confirmer-email" element={<ConfirmerEmail />} />
        <Route path="mentions-legales" element={<MentionsLegales />} />
        <Route path="confidentialite" element={<Confidentialite />} />
        <Route path="cgu" element={<Cgu />} />

        <Route path="publications" element={<Protege roles={[HABITANT, MAIRIE]}><Publications /></Protege>} />
        <Route path="publier" element={<Protege roles={[HABITANT]}><Publier /></Protege>} />
        <Route path="mes-publications" element={<Protege roles={[HABITANT]}><MesPublications /></Protege>} />
        <Route path="profil" element={<Protege roles={[HABITANT, PENDING]}><Profil /></Protege>} />

        <Route path="mairie" element={<Protege roles={[MAIRIE]}><MairieLayout /></Protege>}>
          <Route index element={<TableauDeBord />} />
          <Route path="inscriptions" element={<Inscriptions />} />
          <Route path="signalements" element={<Signalements />} />
          <Route path="habitants" element={<Habitants />} />
          <Route path="annonces" element={<PublicationsMairie />} />
          <Route path="journal" element={<Journal />} />
        </Route>

        <Route path="admin" element={<Protege roles={[ADMIN]}><AdminLayout /></Protege>}>
          <Route index element={<Categories />} />
          <Route path="mots-interdits" element={<MotsInterdits />} />
          <Route path="comptes-mairie" element={<ComptesMairie />} />
        </Route>

        <Route path="*" element={<Introuvable />} />
      </Route>
    </Routes>
  )
}
