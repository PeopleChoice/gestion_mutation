# Spécification technique — Gestion des Mutations Foncières (réécriture Rust + Vue.js)

> Extraite du projet Laravel d'origine. Source de vérité des colonnes : le schéma
> SQLite réel + les modèles Eloquent. Sert de référence pour le portage natif
> dans `versionrust/` (backend Rust/Axum + frontend Vue 3).

Application de gestion d'attributions et mutations de parcelles foncières
(lotissements de régularisation, contexte DGID/Bureau des Domaines, Sénégal).
Auth par session + rôles/permissions (spatie), génération PDF + QR code, et
**vérification publique par hash HMAC-SHA256**. Import Excel.

---

## Entités (tables) — colonnes exactes

### users
id (PK), name, email (unique), email_verified_at?, password (bcrypt), remember_token?, timestamps.

### communes
id, nom, departement?, region?, timestamps.

### projets
id, nom, code? (unique, format `AB1234` auto), type_lotissement?, commune_id (FK→communes cascade),
description?, latitude? decimal(10,7), longitude? decimal(10,7), fichier_dxf?, geojson? (json/text), timestamps.

### proprietaires
id, civilite? enum('Monsieur','Madame','Société'), prenom, nom, nin?, ninea?, cni_passport?,
type_piece?, telephone?, adresse?, timestamps.
- Calculés : nom_complet (Société → prenom seul), identifiant (cni_passport ?: nin ?: ninea), piece_formatee.

### parcelles
id, numero_lot, projet_id (FK cascade), proprietaire_id? (FK set null), ref_lettre?, date_attribution?,
superficie? decimal(10,2), usage?, observation?, geometrie? (json), centroid_lat? , centroid_lng?, timestamps.
- **UNIQUE(numero_lot, projet_id)** — règle métier centrale.

### mutations
id, parcelle_id (FK cascade), ancien_proprietaire_id? (FK set null), nouveau_proprietaire_id? (FK set null),
import_id? (FK set null), statut enum('en_attente','validee','refusee','annulee') défaut 'en_attente',
ref_lettre?, numero_notification? (7 chiffres), date_mutation?, motif_refus?, observation?,
validated_by? (FK users set null), validated_at?, code_verification? (unique, 64 hex HMAC),
code_paye?, type_piece?, demandeur_prenom?, demandeur_nom?, demandeur_telephone?, timestamps.
- ancien_proprietaire_id NULL ⇒ première **attribution** ; sinon **mutation** (transfert).

### imports
id, nom_fichier, fichier_path, projet_id (FK cascade), imported_by (FK users cascade),
statut enum('en_cours','termine','erreur') défaut 'en_cours', type(20) défaut 'mutation',
total_lignes int=0, lignes_traitees int=0, lignes_matchees int=0, timestamps.

### import_lignes
id, import_id (FK cascade), numero_ordre int, civilite?, prenom?, nom?, type_piece?, code_paye?,
cni_passport?, demandeur_prenom?, demandeur_nom?, demandeur_telephone?, nin?, ninea?, telephone?,
numero_lot, ref_lettre?, date_excel?, observation?, precedent_attributaire?, parcelle_id? (FK set null),
matched bool=false, statut enum('en_attente','validee','refusee') défaut 'en_attente', timestamps.

### document_templates
id, nom, type défaut 'notification_attribution', entete_html, corps_html, pied_html?,
centre_fiscal?, bureau?, actif bool=true, timestamps.

### mutation_annulations
id, mutation_id (FK cascade), demande_par (FK users cascade), approuve_par? (FK users set null),
statut enum('en_attente','approuvee','rejetee') défaut 'en_attente', motif, motif_rejet?, approuve_at?, timestamps.

### activity_logs
id, user_id? (FK set null), action, module, description, ip_address?, details? (json), timestamps.

---

## Rôles & permissions (spatie)

Permissions (13) : importer_fichier, voir_imports, gerer_parcelles, valider_mutation, refuser_mutation,
annuler_mutation, approuver_annulation, generer_rapport, generer_pdf, gerer_templates, gerer_utilisateurs,
gerer_projets, gerer_communes.

Rôles (4) :
- **admin** : toutes les permissions. Compte seedé : `admin@domaines.sn` (ADMIN_SEED_EMAIL/PASSWORD).
- **receveur** : voir_imports, valider/refuser_mutation, generer_rapport, generer_pdf, gerer_parcelles.
- **gestionnaire** : importer_fichier, voir_imports, gerer_parcelles, valider/refuser/annuler_mutation,
  generer_rapport, generer_pdf, gerer_projets, gerer_communes.
- **operateur** : importer_fichier, voir_imports, gerer_parcelles.

Gardes de routes par **rôle** uniquement (les permissions ne gardent pas le routage) :
- role:admin → écritures communes, suppression import, templates CRUD, export/import BDD.
- role:admin|gestionnaire → création/édition/attribution parcelles, validation/refus mutations (masse), annulations.
- auth → reste du back-office. Vérification publique → sans auth, throttle.

---

## Notes spéciales (logique à reproduire fidèlement)

### Numéro de notification (`Mutation::genererNumeroNotification`)
Transaction + verrou. Dernier numero_notification → extrait chiffres → +1 → **zero-pad 7 chiffres**.
Unicité globale garantie. En import de masse : compteur séquentiel depuis `num_notif_depart` saisi.

### Code projet (`Projet::genererCode`)
Format `AB1234` : 2 lettres (initiales) + 4 chiffres séquentiels par préfixe. Hook `creating`.

### Code de vérification & QR (`Mutation::genererCodeVerification`) — CRITIQUE
- Données signées : `id | numero_notification | parcelle.numero_lot | parcelle.projet_id |
  nouveau_proprietaire_id | nouveauProprietaire.cni_passport | date_mutation(Y-m-d)` jointes par `|`.
- Algo : `HMAC-SHA256(data, key)` où key = APP_KEY décodée (si `base64:` → bytes bruts). Hex 64 car.
- URL QR : `/verification/{code_verification}`.
- Vérification publique : `where code_verification = hash`. Masque les données perso si non connecté.

### Template PDF (`DocumentTemplate::renderPourMutation`)
Concatène entete+corps+pied, remplace placeholders. Template actif = type='notification_attribution' AND actif.
Placeholders : numero_notification, date_mutation (d F Y fr), numero_lot, nom_projet, commune,
civilite/prenom/nom/nom_complet/cni/type_piece/telephone/nin/ninea/adresse _nouveau et _ancien,
code_paye, piece_formatee, demandeur_*, ref_lettre, centre_fiscal, bureau, date_jour, chef_bureau,
entete_image, qr_code, code_verification.
piece_formatee : CNI → `CNI_{code_paye} n° : {num}` ; Passeport → `PP_{code_paye} n° : {num}`.

### Parsing import Excel
Détection en-tête = ligne la plus remplie parmi les 3 premières (templates à 2 lignes d'en-tête).
Normalisation lot : strip suffixe `.0`. Matching : `Parcelle WHERE numero_lot=? AND projet_id=?`.
Variantes de colonnes accentuées/non. Imports globaux : projet résolu par colonne « Code projet ».

### Règles transversales
- UNIQUE(numero_lot, projet_id).
- On ne mute jamais une parcelle vierge (sans propriétaire) : refus / skip en masse.
- Attribution forcée d'une parcelle attribuée : exige mot de passe (timing-safe).
- Suppression import : préserve mutations validées (import_id→NULL) et parcelles ; cascade sur import_lignes.
- Annulation approuvée : remet l'ancien propriétaire, mutation → annulee.
- Journalisation systématique (ActivityLog).

### Équivalents Rust prévus
- Auth/RBAC custom (JWT + 4 rôles). Excel → `calamine`/`rust_xlsxwriter`. PDF → moteur HTML→PDF.
- QR → `qrcode` + `image`. HMAC → `hmac` + `sha2`. DB → `sqlx` (Any : MySQL + SQLite au runtime).

---

## État d'avancement du portage (`versionrust/`)

| Module | Backend | Frontend |
|--------|---------|----------|
| Setup (choix MySQL/SQLite) | ✅ | ✅ |
| Auth + RBAC | ✅ | ✅ |
| Dashboard (stats) | ✅ | ✅ |
| Communes (CRUD) | ✅ | ✅ |
| Projets (CRUD) | ✅ | ✅ |
| Parcelles (CRUD + attribuer) | ✅ | ✅ |
| Mutations (CRUD + code vérif + valider/refuser) | ✅ | ✅ |
| Vérification publique (HMAC) | ✅ | ✅ |
| Recherche globale | ✅ | ✅ |
| Notification + QR (impression) | ✅ | ✅ |
| Document templates | ✅ | ✅ |
| Annulations (demande/traitement) | ✅ | ✅ |
| Imports Excel (parsing/matching) | ⏳ TODO | ⏳ TODO |
| Rapports / Export-Import BDD | ⏳ TODO | ⏳ TODO |

> La notification est rendue en **HTML + QR (data URI PNG)** côté backend, puis
> imprimée via le navigateur (Imprimer → Enregistrer en PDF). Le code QR encode
> l'URL publique de vérification (`GM_PUBLIC_URL`).

Les modules ⏳ sont à compléter en s'appuyant sur ce document.
