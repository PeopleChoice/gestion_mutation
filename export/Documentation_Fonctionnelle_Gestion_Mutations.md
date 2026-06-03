# DOCUMENTATION FONCTIONNELLE
## Système de Gestion des Mutations de Parcelles
### Direction Générale des Impôts et des Domaines (DGID)

---

**Version :** 1.0
**Date :** 16 Avril 2026
**Auteur :** Cheikh Tidiane DIOP

---

## TABLE DES MATIÈRES

1. [Présentation générale](#1-présentation-générale)
2. [Architecture technique](#2-architecture-technique)
3. [Gestion des utilisateurs et rôles](#3-gestion-des-utilisateurs-et-rôles)
4. [Module Communes](#4-module-communes)
5. [Module Projets / Lotissements](#5-module-projets--lotissements)
6. [Module Parcelles](#6-module-parcelles)
7. [Module Import Excel](#7-module-import-excel)
8. [Module Mutations](#8-module-mutations)
9. [Module Annulations](#9-module-annulations)
10. [Génération de documents PDF](#10-génération-de-documents-pdf)
11. [Système de vérification QR Code](#11-système-de-vérification-qr-code)
12. [Module Recherche globale](#12-module-recherche-globale)
13. [Module Rapports](#13-module-rapports)
14. [Module Templates de documents](#14-module-templates-de-documents)
15. [Intégration AutoCAD / DXF](#15-intégration-autocad--dxf)
16. [Carte géographique](#16-carte-géographique)
17. [Export / Import de la base de données](#17-export--import-de-la-base-de-données)
18. [Système d'emails](#18-système-demails)
19. [Base de données](#19-base-de-données)
20. [Sécurité](#20-sécurité)

---

## 1. PRÉSENTATION GÉNÉRALE

### 1.1 Contexte

Le système de **Gestion des Mutations de Parcelles** est une application web développée pour la Direction Générale des Impôts et des Domaines (DGID) du Sénégal. Elle permet de gérer le processus complet de mutation (transfert de propriété) des parcelles de terrain au niveau des lotissements communaux.

### 1.2 Objectifs

- Dématérialiser le processus de mutation des parcelles
- Assurer la traçabilité complète des transferts de propriété
- Générer automatiquement les documents officiels de notification d'attribution
- Permettre la vérification de l'authenticité des documents via QR code
- Faciliter l'import des données des promoteurs via fichiers Excel
- Offrir une vue géographique des projets et parcelles sur une carte interactive

### 1.3 Workflow principal

```
Promoteur soumet un fichier Excel
        ↓
Admin importe le fichier dans le système
        ↓
Le système cherche les correspondances (lot + projet)
        ↓
Le receveur vérifie l'appartenance de chaque parcelle
        ↓
Si conforme : valide la mutation (saisie du nouveau propriétaire)
Si non conforme : refuse la mutation (avec motif)
        ↓
Génération du PDF de notification avec QR code
        ↓
Rapport final des mutations traitées
```

---

## 2. ARCHITECTURE TECHNIQUE

### 2.1 Stack technologique

| Composant | Technologie |
|-----------|------------|
| Backend | Laravel 12.x (PHP 8.2+) |
| Base de données | SQLite (dev) / MySQL (prod) |
| Frontend | Blade Templates + Bootstrap 5.3 |
| PDF | DomPDF (barryvdh/laravel-dompdf) |
| Excel | Maatwebsite/Excel (PhpSpreadsheet) |
| QR Code | BaconQrCode + SimpleSoftwareIO |
| Permissions | Spatie Laravel Permission |
| Carte | Leaflet.js + OpenStreetMap |
| Éditeur code | CodeMirror 5 |
| Parsing DXF | Python 3 + ezdxf |

### 2.2 Structure des dossiers

```
gestion-mutations/
├── app/
│   ├── Console/Commands/     # Commandes artisan (export, import, parse DXF)
│   ├── Exports/              # Classes d'export Excel
│   ├── Http/Controllers/     # 13 contrôleurs
│   ├── Imports/              # Classes d'import Excel
│   ├── Mail/                 # Classes d'email
│   └── Models/               # 10 modèles Eloquent
├── database/
│   ├── migrations/           # 19 fichiers de migration
│   └── seeders/              # Seeders (rôles, données test, templates)
├── export/                   # Fichiers d'export générés
├── import/                   # Fichiers à importer (cron)
│   ├── traites/              # Fichiers importés avec succès
│   └── erreurs/              # Fichiers en erreur
├── public/images/            # Logo DGID, en-tête, QR previews
├── resources/views/          # 33 vues Blade
│   ├── admin/                # Export/Import
│   ├── auth/                 # Login
│   ├── communes/             # Gestion communes
│   ├── emails/               # Templates email
│   ├── imports/              # Import Excel
│   ├── layouts/              # Layout principal (sidebar, topbar)
│   ├── mutations/            # Traitement mutations
│   ├── parcelles/            # Gestion parcelles
│   ├── projets/              # Projets + carte
│   ├── rapports/             # Rapports
│   ├── recherche/            # Recherche globale
│   ├── templates/            # Éditeur de templates
│   └── verification/         # Scanner QR + résultats
└── scripts/
    └── parse_dxf.py          # Script Python pour parsing DXF
```

---

## 3. GESTION DES UTILISATEURS ET RÔLES

### 3.1 Rôles

Le système utilise 4 rôles hiérarchiques :

| Rôle | Description |
|------|------------|
| **Admin** | Accès complet au système, gestion des utilisateurs, approbation des annulations, export/import base de données |
| **Gestionnaire** | Import de fichiers, validation/refus de mutations, gestion des projets et communes, rapports |
| **Receveur** | Validation/refus des mutations, consultation des imports, génération PDF et rapports |
| **Opérateur** | Import de fichiers Excel, consultation, gestion des parcelles |

### 3.2 Permissions détaillées (13)

| Permission | Admin | Gestionnaire | Receveur | Opérateur |
|-----------|:-----:|:------------:|:--------:|:---------:|
| importer_fichier | ✓ | ✓ | | ✓ |
| voir_imports | ✓ | ✓ | ✓ | ✓ |
| gerer_parcelles | ✓ | ✓ | ✓ | ✓ |
| valider_mutation | ✓ | ✓ | ✓ | |
| refuser_mutation | ✓ | ✓ | ✓ | |
| annuler_mutation | ✓ | ✓ | | |
| approuver_annulation | ✓ | | | |
| generer_rapport | ✓ | ✓ | ✓ | |
| generer_pdf | ✓ | ✓ | ✓ | |
| gerer_templates | ✓ | ✓ | | |
| gerer_utilisateurs | ✓ | | | |
| gerer_projets | ✓ | ✓ | | |
| gerer_communes | ✓ | ✓ | | |

### 3.3 Comptes par défaut

| Email | Mot de passe | Rôle |
|-------|-------------|------|
| admin@domaines.sn | password | Admin |
| gestionnaire@domaines.sn | password | Gestionnaire |
| receveur@domaines.sn | password | Receveur |
| operateur@domaines.sn | password | Opérateur |

---

## 4. MODULE COMMUNES

### 4.1 Description

Gestion des communes (collectivités territoriales) auxquelles sont rattachés les projets de lotissement.

### 4.2 Fonctionnalités

- Créer une commune (nom, département, région)
- Modifier une commune
- Supprimer une commune (uniquement si aucun projet n'y est rattaché)
- Afficher le nombre de projets par commune

### 4.3 Données

| Champ | Type | Obligatoire |
|-------|------|:-----------:|
| Nom | Texte | ✓ |
| Département | Texte | |
| Région | Texte | |

---

## 5. MODULE PROJETS / LOTISSEMENTS

### 5.1 Description

Un projet représente un lotissement (ex: RAVIN, CITE RELIGIEUSE) rattaché à une commune. Il contient les parcelles et leurs propriétaires.

### 5.2 Fonctionnalités

- Créer un projet avec coordonnées GPS (optionnel)
- Importer les parcelles initiales via fichier Excel à la création (optionnel)
- Visualiser un projet avec la liste de toutes ses parcelles ordonnées par numéro de lot
- Filtrer les parcelles (attribuées / non attribuées)
- Rechercher dans les parcelles du projet
- Importer des parcelles supplémentaires via Excel
- Uploader un plan cadastral (DXF / GeoJSON)
- Exporter les parcelles du projet en Excel
- Voir le projet sur la carte
- Bouton "Ma position" pour auto-remplir les coordonnées GPS

### 5.3 Données

| Champ | Type | Obligatoire |
|-------|------|:-----------:|
| Nom | Texte | ✓ |
| Commune | Référence | ✓ |
| Type de lotissement | Sélection (Régularisation, Extension, Nouveau) | |
| Description | Texte long | |
| Latitude | Décimal (10,7) | |
| Longitude | Décimal (10,7) | |
| Fichier DXF | Fichier | |
| GeoJSON | JSON | |

### 5.4 Page de détail du projet

La page `/projets/{id}` affiche :
- Informations du projet (nom, commune, type, coordonnées)
- 4 statistiques : total parcelles, attribuées, non attribuées, mutations
- Barre de recherche instantanée
- Filtres : Tous / Attribués / Non attribués
- Tableau des parcelles trié par numéro de lot (croissant) :
  - Lot, Propriétaire, CNI, NIN, Téléphone, Statut
  - Les parcelles sans propriétaire affichent **"Non attribué"** en orange

---

## 6. MODULE PARCELLES

### 6.1 Description

Une parcelle représente un lot de terrain au sein d'un projet. Elle peut avoir un propriétaire actuel et un historique complet de mutations.

### 6.2 Fonctionnalités

- Créer une parcelle manuellement avec son propriétaire
- Modifier les informations d'une parcelle
- Consulter le détail d'une parcelle avec :
  - Informations (lot, projet, commune, superficie, usage)
  - Propriétaire actuel (nom, CNI, NIN, NINEA, téléphone)
  - **Chaîne de propriété** : résumé visuel (A → B → C → D)
  - **Timeline complète** des mutations avec :
    - Propriétaire actuel en haut (étoile dorée)
    - Chaque mutation avec ancien → nouveau propriétaire
    - Date, N° notification, validateur
    - Badges colorés selon le statut
    - Liens vers les détails et le PDF

### 6.3 Données

| Champ | Type | Obligatoire |
|-------|------|:-----------:|
| Numéro de lot | Texte | ✓ |
| Projet | Référence | ✓ |
| Propriétaire | Référence | |
| Réf. Lettre | Texte | |
| Date d'attribution | Date | |
| Superficie (m²) | Décimal | |
| Usage | Sélection (habitation, commercial, mixte) | |
| Observation | Texte | |
| Géométrie | JSON (GeoJSON) | |
| Centroïde (lat/lng) | Décimaux | |

### 6.4 Contrainte d'unicité

Le couple (numéro_lot, projet_id) est unique. Un même numéro de lot ne peut pas exister deux fois dans le même projet.

---

## 7. MODULE IMPORT EXCEL

### 7.1 Description

Permet à l'opérateur ou à l'admin d'importer un fichier Excel contenant les données de mutation soumises par un promoteur.

### 7.2 Format du fichier Excel

| Colonne | Description | Obligatoire |
|---------|------------|:-----------:|
| N° O | Numéro d'ordre | |
| Civilité | Monsieur / Madame / Société | |
| Prénom | Prénom du demandeur | |
| NOM | Nom du demandeur | |
| NIN | Numéro d'identification nationale | |
| NINEA | Pour les sociétés | |
| TEL | Téléphone | |
| **LOT** | **Numéro de lot** | **✓** |
| Réf. Lettre | Référence de la lettre | |
| Date | Date | |
| Observation | Remarques | |
| Précédent attributaire | Ancien propriétaire | |

### 7.3 Processus d'import

1. **Sélection du projet** et upload du fichier Excel
2. **Pré-vérification automatique** (AJAX) :
   - Le projet existe-t-il ?
   - Le fichier est-il lisible ?
   - La colonne LOT est-elle présente ?
   - Combien de lots correspondent dans le projet ?
   - Y a-t-il des doublons dans le fichier ?
   - Y a-t-il des lignes sans nom/prénom ?
3. **Résultat** :
   - **Valide** (badge vert) : bouton "Importer" activé
   - **Invalide** (badge rouge) : bouton "Importer" bloqué
   - **Avertissements** (orange) : import possible avec remarques
4. **Import** : création des lignes avec statut de correspondance

### 7.4 Template Excel téléchargeable

L'admin peut générer un template Excel pour un projet :
- **Template vide** : 40 lignes numérotées avec les en-têtes
- **Template pré-rempli** : lots du projet avec l'ancien propriétaire dans "Précédent attributaire"

### 7.5 Envoi par email

Le template peut être envoyé directement par email à un promoteur avec :
- Nom du promoteur
- Email
- Message personnalisé
- Pièce jointe : le fichier Excel

### 7.6 Liste des imports

- Vue par défaut : **"A traiter"** (imports avec des lignes en attente)
- Vue historique : tous les imports
- Les imports entièrement traités disparaissent de la vue par défaut

---

## 8. MODULE MUTATIONS

### 8.1 Description

Une mutation représente le transfert de propriété d'une parcelle d'un ancien propriétaire vers un nouveau propriétaire.

### 8.2 Statuts

| Statut | Description | Badge |
|--------|------------|-------|
| en_attente | Mutation importée, pas encore traitée | Orange |
| validee | Mutation acceptée par le receveur | Or |
| refusee | Mutation rejetée par le receveur | Rouge |
| annulee | Mutation annulée après validation | Gris |

### 8.3 Traitement des mutations

Depuis la page d'un import (`/imports/{id}`), le receveur peut traiter les mutations :

#### Traitement unitaire (ligne par ligne)
- **Valider** : popup avec saisie du nouveau propriétaire
  - Civilité, Prénom, Nom (obligatoires)
  - Type de pièce (CNI, Passeport, Carte consulaire, etc.) - optionnel
  - N° Pièce (obligatoire)
  - NIN, NINEA, Téléphone, Adresse - optionnels
  - Code payé - optionnel
  - Réf. Lettre - optionnel
- **Refuser** : popup avec motif de refus (obligatoire)

#### Traitement par sélection
- Cases à cocher sur chaque ligne
- "Tout sélectionner" en haut
- Boutons "Valider la sélection" / "Refuser la sélection"
- Popup de confirmation avec le nombre de lignes

#### Traitement global
- **"Valider tout (N)"** : valide toutes les lignes en attente avec les données du fichier Excel
- **"Refuser tout (N)"** : refuse toutes les lignes avec un motif commun
- Chaque action demande une **confirmation**

### 8.4 Retour visuel en temps réel

- Barre de progression (%) mise à jour après chaque action
- Compteurs dynamiques (en attente / validées / refusées)
- Les lignes traitées deviennent grisées
- Les boutons globaux disparaissent quand tout est traité
- Lien vers le PDF ou "Refusée" après traitement

### 8.5 Données de la mutation

| Champ | Type | Obligatoire |
|-------|------|:-----------:|
| Parcelle | Référence | ✓ |
| Ancien propriétaire | Référence | |
| Nouveau propriétaire | Référence | |
| Import | Référence | |
| Statut | Enum | ✓ |
| Réf. Lettre | Texte | |
| Code payé | Texte | |
| Type de pièce | Texte | |
| N° Notification | Texte (auto-généré) | |
| Code de vérification | SHA-256 (auto-généré) | |
| Date de mutation | Date | |
| Motif de refus | Texte | |
| Observation | Texte | |
| Validé par | Référence utilisateur | |

---

## 9. MODULE ANNULATIONS

### 9.1 Description

Permet de revenir en arrière sur une mutation validée. L'annulation nécessite obligatoirement la validation de l'administrateur.

### 9.2 Processus

1. Le receveur ou gestionnaire **demande l'annulation** depuis la page de la mutation (avec motif obligatoire)
2. La demande apparaît dans la section **"Annulations"** de l'admin
3. L'admin peut :
   - **Approuver** : le terrain est remis à l'ancien propriétaire, la mutation passe en statut "annulée"
   - **Rejeter** : la demande est rejetée avec un motif

### 9.3 Données

| Champ | Type | Obligatoire |
|-------|------|:-----------:|
| Mutation | Référence | ✓ |
| Demandé par | Référence utilisateur | ✓ |
| Approuvé par | Référence utilisateur | |
| Statut | Enum (en_attente, approuvee, rejetee) | ✓ |
| Motif | Texte | ✓ |
| Motif de rejet | Texte | |

---

## 10. GÉNÉRATION DE DOCUMENTS PDF

### 10.1 Description

Le système génère automatiquement les notifications d'attribution conformes au modèle officiel de la DGID.

### 10.2 Structure du document

Le PDF reprend fidèlement le format officiel :

1. **En-tête gauche** : image officielle DGID (République du Sénégal, Ministère des Finances, Direction Générale, Centre des Services Fiscaux, Bureau des Domaines)
2. **En-tête droite** : "Tivaouane, le [DATE]" (date en rouge, format "15 AVR 2026") + "Le Chef du Bureau" + QR code de vérification
3. **Numéro** : N°0000001 (en rouge, auto-incrémenté)
4. **Objet** : "Notification d'attribution du lot n° AT [LOT] du plan de lotissement dit « [PROJET] », Commune de [COMMUNE]"
5. **Corps** : texte juridique standard avec les variables remplacées
6. **Signature** : nom du receveur/validateur (à droite)
7. **Destinataire** : civilité, nom, CNI, téléphone du nouveau propriétaire (à gauche, en bas)

### 10.3 Fond du document

Le document utilise un fond turquoise clair (#E6FAF8) sur toute la page.

### 10.4 Modes d'affichage

- **Visualiser** (`/mutations/{id}/apercu`) : page avec iframe intégrée, sidebar d'infos, boutons Imprimer / Télécharger / Nouvel onglet
- **Stream** (`/mutations/{id}/pdf`) : PDF affiché directement dans le navigateur
- **Télécharger** (`/mutations/{id}/telecharger`) : téléchargement direct du fichier

### 10.5 Variables disponibles (placeholders)

Le contenu du PDF est entièrement configurable via des variables :

**Mutation :**
`numero_notification`, `date_mutation`, `ref_lettre`, `code_paye`, `type_piece`, `date_jour`, `chef_bureau`

**Parcelle / Projet :**
`numero_lot`, `nom_projet`, `commune`

**Nouveau propriétaire :**
`civilite_nouveau`, `prenom_nouveau`, `nom_nouveau`, `nom_complet_nouveau`, `cni_nouveau`, `type_piece_nouveau`, `telephone_nouveau`, `nin_nouveau`, `ninea_nouveau`, `adresse_nouveau`

**Ancien propriétaire :**
`civilite_ancien`, `nom_complet_ancien`, `cni_ancien`, `type_piece_ancien`, `telephone_ancien`

**Système :**
`centre_fiscal`, `bureau`, `entete_image`, `qr_code`, `code_verification`

---

## 11. SYSTÈME DE VÉRIFICATION QR CODE

### 11.1 Génération du code

Chaque mutation validée reçoit un code de vérification unique :

```
Hash SHA-256 de :
  mutation_id | numero_notification | numero_lot | projet_id |
  nouveau_proprietaire_id | cni_passport | date_mutation | APP_KEY
```

Ce hash est encodé dans un QR code intégré au PDF.

### 11.2 Scanner QR (`/scanner`)

La page de scan propose 2 modes :

**Mode caméra :**
- Activation de la webcam / caméra du téléphone
- Cadre animé pour guider le positionnement
- Détection automatique du QR code (librairie jsQR)
- Arrêt automatique de la caméra dès détection
- Choix de caméra (avant/arrière sur mobile)

**Mode saisie manuelle :**
- Champ texte pour coller le code de vérification
- Bouton "Vérifier"

### 11.3 Résultat de vérification

**Document authentique :** panneau avec toutes les informations (N° notification, date, lot, projet, commune, propriétaire, CNI, téléphone, validateur, statut)

**Document annulé :** panneau orange avertissant que la mutation a été annulée

**Document non authentique :** panneau rouge avec alerte de falsification

### 11.4 Page publique

La vérification (`/verification/{hash}`) est accessible **sans connexion**. N'importe qui peut scanner un QR code pour vérifier l'authenticité d'un document.

### 11.5 Historique des scans

Un tableau en bas de la page garde l'historique des vérifications effectuées pendant la session.

---

## 12. MODULE RECHERCHE GLOBALE

### 12.1 Barre de recherche rapide (topbar)

Présente sur toutes les pages :
- Autocomplétion en temps réel (debounce 250ms)
- Recherche dans les mutations, parcelles, propriétaires, projets
- Navigation clavier (flèches + Entrée)
- Raccourci `Ctrl+K` pour focus rapide
- `Echap` pour fermer

### 12.2 Page de recherche avancée (`/recherche`)

**Champs recherchés :**
- Numéro de lot, nom/prénom du propriétaire
- CNI, NIN, NINEA, téléphone
- Nom de projet, commune
- N° notification, réf. lettre, motif de refus

**Filtres avancés combinables :**
- Par type (Tout, Mutations, Parcelles, Propriétaires, Projets)
- Par statut (validée, refusée, en attente, annulée)
- Par projet
- Par commune
- Par période (date début / date fin)

**Affichage des résultats :**
- Résultats groupés par type avec compteurs
- Mutations : ancien → nouveau propriétaire, CNI, téléphone, motif de refus
- Parcelles : propriétaire actuel + timeline des mutations
- Propriétaires : toutes les parcelles associées
- Projets : commune, type, nombre de parcelles

---

## 13. MODULE RAPPORTS

### 13.1 Description

Génération de rapports filtrés sur les mutations.

### 13.2 Filtres

- Par import spécifique
- Par statut (validée, refusée, annulée)
- Par période (date début / date fin)

### 13.3 Formats de sortie

- **HTML** : tableau avec statistiques (total, validées, refusées, annulées)
- **PDF** : rapport formaté A4 paysage avec en-tête DGID

### 13.4 Colonnes du rapport

N° notification, Date, Lot, Projet, Commune, Ancien propriétaire, Nouveau propriétaire, Statut, Motif de refus

---

## 14. MODULE TEMPLATES DE DOCUMENTS

### 14.1 Description

Permet à l'admin ou au gestionnaire de personnaliser le contenu des documents PDF générés.

### 14.2 Structure d'un template

| Section | Description |
|---------|------------|
| **En-tête HTML** | Styles CSS, image DGID, date, QR code |
| **Corps HTML** | Texte de la notification avec variables |
| **Pied HTML** | Destinataire (nom, CNI, téléphone) |
| **Centre fiscal** | Ex: Centre des Services Fiscaux de TIVAOUANE |
| **Bureau** | Ex: Bureau des Domaines |

### 14.3 Éditeur

L'éditeur de template propose :
- **CodeMirror** avec coloration syntaxique HTML (pas de modification du code)
- **Placeholders cliquables** : clic sur une variable pour l'insérer au curseur
- **Aperçu en direct** : iframe à droite avec données fictives
- **Bouton "Aperçu"** : sauvegarde + rafraîchit l'aperçu
- **Format par défaut** : pré-rempli à la création

### 14.4 Création de template

La page de création charge automatiquement le format par défaut (conforme au modèle DGID). L'utilisateur peut modifier ou créer un template entièrement personnalisé.

---

## 15. INTÉGRATION AUTOCAD / DXF

### 15.1 Description

Le système permet d'importer un plan cadastral AutoCAD pour visualiser les contours des parcelles sur la carte.

### 15.2 Formats supportés

| Format | Support |
|--------|---------|
| **DXF** | Parsing automatique via Python (ezdxf) |
| **GeoJSON / JSON** | Import direct |
| **DWG** | Conversion requise vers DXF (via AutoCAD) |

### 15.3 Processus d'import DXF

1. L'admin ouvre la page de modification du projet
2. Section "Plan cadastral (DXF)" : upload du fichier + coordonnées d'origine
3. Le script Python `parse_dxf.py` :
   - Extrait les entités géométriques (LWPOLYLINE, POLYLINE, LINE, CIRCLE, TEXT)
   - Convertit les coordonnées locales (mètres) en GPS (lat/lng) avec un point d'origine et une échelle
   - Calcule les centroïdes des polygones
   - Matche les textes (numéros de lot) aux polygones les plus proches
   - Génère un GeoJSON complet
4. Le système lie chaque polygone à la parcelle correspondante dans la base

### 15.4 Paramètres de conversion

| Paramètre | Description | Défaut |
|-----------|------------|--------|
| Latitude d'origine | Point 0,0 du plan en GPS | 14.95 |
| Longitude d'origine | Point 0,0 du plan en GPS | -16.82 |
| Échelle | Facteur de conversion mètres → degrés | 0.00001 |

### 15.5 Commande artisan

```bash
php artisan dxf:parse {projet_id} {fichier.dxf} --lat=14.95 --lng=-16.82 --scale=0.00001
```

---

## 16. CARTE GÉOGRAPHIQUE

### 16.1 Description

Carte interactive affichant tous les projets géolocalisés avec leurs parcelles.

### 16.2 Technologie

- **Leaflet.js** (gratuit, open source)
- **OpenStreetMap** (fond de carte)

### 16.3 Fonctionnalités

- Marqueurs dorés DGID pour chaque projet
- Popup au clic : nom, commune, type, nombre de parcelles, liens
- **Polygones des parcelles** si un plan DXF/GeoJSON a été importé :
  - Contours dorés semi-transparents
  - Tooltip au survol avec le numéro de lot
  - Popup au clic avec lien vers la parcelle
- Zoom automatique pour afficher tous les projets
- Accès via la sidebar ("Carte")

---

## 17. EXPORT / IMPORT DE LA BASE DE DONNÉES

### 17.1 Export

Accessible depuis `/admin/export-import` (admin uniquement).

**3 formats :**
- **CSV (zip)** : un fichier CSV par table, zippé ensemble
- **SQL** : fichier avec les requêtes INSERT pour toutes les tables
- **Complet** : les deux formats

**15 tables exportées :** communes, projets, proprietaires, parcelles, imports, import_lignes, mutations, mutation_annulations, document_templates, users, roles, permissions, model_has_roles, model_has_permissions, role_has_permissions

### 17.2 Import

**Deux manières de déposer un fichier :**
1. Upload depuis l'interface web
2. Copier un fichier `.zip` ou `.sql` dans le dossier `/import`

**Deux manières de lancer l'import :**
1. Bouton "Importer maintenant" dans l'interface
2. Cron automatique (toutes les 5 minutes)

**Gestion des fichiers :**
- Fichier traité → déplacé dans `/import/traites/`
- Fichier en erreur → déplacé dans `/import/erreurs/`

### 17.3 Configuration du cron

```
* * * * * cd /chemin/vers/gestion-mutations && php artisan schedule:run >> /dev/null 2>&1
```

### 17.4 Commandes artisan

```bash
# Export
php artisan db:export --format=csv
php artisan db:export --format=sql
php artisan db:export --format=both

# Import
php artisan db:import
php artisan db:import --auto  # Mode cron (silencieux si rien à importer)
```

---

## 18. SYSTÈME D'EMAILS

### 18.1 Description

Envoi de templates Excel d'import par email aux promoteurs.

### 18.2 Contenu de l'email

- En-tête DGID (turquoise)
- Nom du promoteur
- Informations du projet (nom, commune, type)
- Message personnalisé (optionnel)
- Instructions de remplissage
- **Pièce jointe** : fichier Excel du template

### 18.3 Configuration

Le mailer est configurable dans le `.env` :

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=votre@email.com
MAIL_PASSWORD=mot_de_passe
MAIL_FROM_ADDRESS=domaines@dgid.sn
MAIL_FROM_NAME="Direction des Domaines"
```

---

## 19. BASE DE DONNÉES

### 19.1 Schéma relationnel

```
communes (1) ──── (N) projets (1) ──── (N) parcelles (1) ──── (N) mutations
                         │                      │                    │
                         │                      │                    ├── ancien_proprietaire → proprietaires
                         │                      │                    ├── nouveau_proprietaire → proprietaires
                         │                      │                    ├── validated_by → users
                         │                      │                    └── import → imports
                         │                      │
                         │                      └── proprietaire → proprietaires
                         │
                         └──── (N) imports (1) ──── (N) import_lignes
                                     │
                                     └── imported_by → users

mutations (1) ──── (1) mutation_annulations
                            ├── demande_par → users
                            └── approuve_par → users
```

### 19.2 Tables principales

| Table | Lignes (test) | Description |
|-------|:------------:|-------------|
| communes | 7 | Collectivités territoriales |
| projets | 5 | Lotissements |
| proprietaires | ~50 | Propriétaires de parcelles |
| parcelles | 85 | Lots de terrain |
| imports | 3 | Fichiers Excel importés |
| import_lignes | 25 | Lignes des fichiers importés |
| mutations | 13 | Transferts de propriété |
| mutation_annulations | 2 | Demandes d'annulation |
| document_templates | 1 | Modèles de documents |
| users | 4 | Utilisateurs du système |

---

## 20. SÉCURITÉ

### 20.1 Authentification

- Connexion par email / mot de passe
- Sessions avec token CSRF
- Option "Se souvenir de moi"
- Déconnexion avec invalidation de session

### 20.2 Autorisation

- Contrôle d'accès basé sur les rôles (RBAC) via Spatie Permission
- 4 rôles avec 13 permissions granulaires
- Protection des routes par middleware `auth`
- Vérification des rôles dans les vues (sidebar, boutons)

### 20.3 Intégrité des documents

- Code de vérification SHA-256 unique par mutation
- QR code intégré au PDF pour vérification instantanée
- Page de vérification publique (accessible sans connexion)
- Détection des documents falsifiés (hash invalide)
- Détection des mutations annulées

### 20.4 Protection des données

- Mots de passe hashés (bcrypt)
- Tokens CSRF sur tous les formulaires
- Validation côté serveur sur toutes les entrées
- Clés étrangères avec contraintes d'intégrité
- Contraintes d'unicité (lot + projet)

---

## ANNEXES

### A. Interface utilisateur

L'interface utilise un thème aux couleurs de la DGID :
- **Sidebar** : dégradé marron (#5D4E37 → #3A2F1E) avec logo DGID
- **Couleur primaire** : or (#C8A951)
- **Couleur secondaire** : marron (#5D4E37)
- **Sidebar rétractable** : peut être réduite aux icônes (état mémorisé)
- **Recherche globale** dans la barre supérieure avec raccourci Ctrl+K

### B. Prérequis d'installation

- PHP 8.2+
- Composer
- Python 3 (pour le parsing DXF)
- pip3 install ezdxf (bibliothèque Python)
- Extension PHP : GD, zip, mbstring
- Node.js (optionnel, pour compilation assets)

### C. Installation

```bash
git clone <repository>
cd gestion-mutations
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

---

**Document rédigé par :**

**Cheikh Tidiane DIOP**

*Développeur Full Stack*

*Dakar, Sénégal - Avril 2026*
