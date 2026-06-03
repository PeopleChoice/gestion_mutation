# GUIDE UTILISATEUR
## Système de Gestion des Mutations de Parcelles
### Direction Générale des Impôts et des Domaines (DGID)

---

**Public visé :** utilisateurs finaux (administrateurs, gestionnaires, receveurs, opérateurs)
**Objectif :** apprendre à utiliser concrètement le logiciel, écran par écran.

---

## TABLE DES MATIÈRES

1. [Premiers pas](#1-premiers-pas)
2. [Connexion au logiciel](#2-connexion-au-logiciel)
3. [Le tableau de bord](#3-le-tableau-de-bord)
4. [Gérer les communes](#4-gérer-les-communes)
5. [Gérer les projets (lotissements)](#5-gérer-les-projets-lotissements)
6. [Gérer les parcelles](#6-gérer-les-parcelles)
7. [Importer un fichier Excel](#7-importer-un-fichier-excel)
8. [Traiter les mutations](#8-traiter-les-mutations)
9. [Créer une mutation manuellement](#9-créer-une-mutation-manuellement)
10. [Demander l'annulation d'une mutation](#10-demander-lannulation-dune-mutation)
11. [Générer et télécharger un PDF](#11-générer-et-télécharger-un-pdf)
12. [Vérifier un document par QR code](#12-vérifier-un-document-par-qr-code)
13. [Recherche globale](#13-recherche-globale)
14. [Consulter la carte](#14-consulter-la-carte)
15. [Générer des rapports](#15-générer-des-rapports)
16. [Administration (admin uniquement)](#16-administration-admin-uniquement)
17. [Foire aux questions](#17-foire-aux-questions)

---

## 1. PREMIERS PAS

### 1.1 Qu'est-ce que ce logiciel ?

Le **Système de Gestion des Mutations de Parcelles** permet de :
- enregistrer des **communes**, **projets de lotissement** et **parcelles** ;
- importer en masse des fichiers Excel d'**attributions** et de **mutations** ;
- valider ou refuser chaque mutation ;
- générer automatiquement les **PDF officiels** signés avec **QR code** ;
- vérifier l'authenticité d'un document depuis un téléphone.

### 1.2 Avant de commencer

Vous avez besoin de :
- un **identifiant** (email) et un **mot de passe** fournis par l'administrateur ;
- un **navigateur récent** (Chrome, Firefox, Edge, Safari) ;
- une **connexion Internet** ;
- pour le scan QR : un téléphone avec appareil photo (aucune application à installer).

### 1.3 Les 4 profils d'utilisateurs

| Rôle | Ce qu'il peut faire |
|---|---|
| **Admin** | Tout : utilisateurs, communes, templates, export/import BDD |
| **Gestionnaire** | Imports, parcelles, projets, validation mutations, annulations |
| **Receveur** | Valider/refuser les mutations, générer rapports et PDF |
| **Opérateur** | Importer des fichiers, consulter parcelles et imports |

Si une action est grisée ou refusée, c'est probablement que votre rôle ne le permet pas.

---

## 2. CONNEXION AU LOGICIEL

1. Ouvrez votre navigateur sur l'adresse du site (ex : `https://mutations.domaines.sn`).
2. Vous arrivez sur l'écran **Connexion**.
3. Saisissez votre **email** et votre **mot de passe**.
4. Cliquez sur **Se connecter**.

> ⚠️ Après **5 tentatives ratées en 1 minute**, votre IP est bloquée temporairement. Patientez quelques minutes ou contactez l'administrateur.

Pour vous déconnecter : cliquez sur votre nom en haut à droite, puis **Déconnexion**.

---

## 3. LE TABLEAU DE BORD

Une fois connecté, vous arrivez sur le **Tableau de bord** (`/dashboard`). Il affiche :
- le **nombre total** de parcelles, de mutations, d'imports ;
- les **mutations en attente** de validation ;
- les **dernières activités** ;
- des **raccourcis rapides** vers les modules principaux.

La barre de menu en haut donne accès à : **Projets · Parcelles · Imports · Mutations · Rapports · Recherche**.

---

## 4. GÉRER LES COMMUNES

**Menu :** *Communes*

- Tous les utilisateurs peuvent **voir** la liste des communes.
- Seul l'**admin** peut **ajouter**, **modifier** ou **supprimer**.

### Ajouter une commune (admin)

1. Cliquez sur **Nouvelle commune**.
2. Renseignez le **nom**, le **code** et la **région**.
3. Cliquez sur **Enregistrer**.

---

## 5. GÉRER LES PROJETS (LOTISSEMENTS)

Un **projet** = un lotissement (ex : « Lotissement Diamniadio Ext. 3 »).

**Menu :** *Projets*

### Créer un projet

1. **Projets > Nouveau projet**.
2. Renseignez : nom, **code projet** (unique), commune, promoteur, superficie totale, nombre prévu de parcelles.
3. **Enregistrer**.

### Initialiser les parcelles d'un projet

Après création, vous pouvez **importer en masse les parcelles** du lotissement :

1. Sur la fiche du projet, cliquez sur **Initialiser les parcelles**.
2. **Téléchargez le modèle Excel** proposé.
3. Remplissez une ligne par parcelle (numéro, superficie, coordonnées, etc.).
4. Cliquez sur **Vérifier le fichier** pour contrôler avant import.
5. Si OK, cliquez sur **Importer les parcelles**.

### Exporter les données d'un projet

Sur la fiche du projet : bouton **Exporter** → télécharge un Excel récapitulatif.

---

## 6. GÉRER LES PARCELLES

**Menu :** *Parcelles*

- **Tout le monde** peut **consulter** la liste et le détail.
- **Admin + Gestionnaire** peuvent **créer, modifier, attribuer**.

### Consulter une parcelle

Cliquez sur le **numéro** de la parcelle. Vous voyez :
- ses **caractéristiques** (superficie, statut, projet) ;
- son **historique des propriétaires** (chaîne des mutations) ;
- ses **coordonnées géographiques** ;
- les **documents PDF** associés.

### Créer une parcelle manuelle (admin / gestionnaire)

1. **Parcelles > Nouvelle parcelle**.
2. Choisissez le projet, saisissez le numéro et les infos.
3. **Enregistrer**.

### Attribuer une parcelle à un propriétaire

1. Ouvrez la parcelle.
2. Cliquez sur **Attribuer**.
3. Renseignez l'**identité du propriétaire** (NIN, nom, adresse…).
4. **Valider**.

---

## 7. IMPORTER UN FICHIER EXCEL

C'est le cœur du logiciel : recevoir les **listes d'attribution** et de **mutations** du promoteur, et les charger en masse.

### 7.1 Import lié à un projet

**Menu :** *Imports > Nouvel import*

1. Choisissez le **projet** concerné.
2. Téléchargez le **modèle Excel** correspondant.
3. Remplissez-le (une ligne = une attribution ou une mutation).
4. Joignez le **fichier Excel** (+ éventuellement le PDF source).
5. Cliquez sur **Vérifier** pour pré-contrôler les lignes.
6. Si tout est OK, **Importer**.

L'import est ensuite visible dans **Imports** avec un statut (`en_attente`, `valide`, `traite`).

### 7.2 Import global multi-projets

Si votre fichier contient des lignes de **plusieurs projets**, utilisez :

**Menu :** *Imports > Import global*

- Onglet **Attribution** : pour les **premières attributions**.
- Onglet **Mutation** : pour les **changements de propriétaire**.
- Chaque ligne référence son projet par son **code projet**.
- Modèles téléchargeables séparément pour chaque type.

Ensuite, allez dans **Validation globale** pour traiter ligne par ligne.

### 7.3 Envoyer le modèle par email (admin / gestionnaire)

Sur la page d'un projet, bouton **Envoyer le modèle au promoteur** → expédie le modèle Excel pré-rempli à l'email saisi.

> ⏱️ Limité à **10 envois par heure** pour éviter le spam.

---

## 8. TRAITER LES MUTATIONS

**Menu :** *Mutations* ou **Imports > [un import] > Traiter**

Vous arrivez sur un écran listant les **lignes de mutation** à valider.

### Pour chaque ligne, vous pouvez :

- ✅ **Valider** : la mutation est enregistrée, la parcelle change de propriétaire, le PDF officiel est généré.
- ❌ **Refuser** : la ligne est rejetée avec un motif.
- 👁️ **Aperçu** : voir le PDF tel qu'il sera généré, avant validation.

### Validation en masse

En haut de la liste :
- **Tout valider** : valide toutes les lignes en attente de cet import.
- **Tout refuser** : refuse toutes les lignes.
- **Cocher** plusieurs lignes puis **Valider la sélection** / **Refuser la sélection**.

> Seuls les rôles **admin** et **gestionnaire** peuvent valider/refuser.

### Validation directe d'une mutation existante

Sur le détail d'une mutation (`/mutations/{id}`), boutons **Valider** ou **Refuser** disponibles.

---

## 9. CRÉER UNE MUTATION MANUELLEMENT

Si vous n'avez pas de fichier Excel, vous pouvez saisir une mutation à la main.

1. **Mutations > Nouvelle mutation**.
2. **Rechercher la parcelle** (autocomplétion par numéro).
3. **Rechercher l'ancien propriétaire** (autocomplétion par nom / NIN).
4. Saisir les infos du **nouveau propriétaire**.
5. Choisir le **type de mutation** (vente, donation, succession…).
6. **Enregistrer**.

La mutation passe ensuite par le circuit de validation classique.

---

## 10. DEMANDER L'ANNULATION D'UNE MUTATION

Tout utilisateur connecté peut **demander** l'annulation d'une mutation déjà validée.

1. Ouvrez la fiche de la mutation.
2. Cliquez sur **Demander l'annulation**.
3. Indiquez le **motif** détaillé.
4. **Envoyer la demande**.

### Côté admin / gestionnaire

**Menu :** *Annulations*
- Liste des demandes **en attente**.
- Pour chaque demande : bouton **Accepter** ou **Refuser**.
- Si acceptée, la mutation est annulée et la parcelle revient à l'ancien propriétaire.

---

## 11. GÉNÉRER ET TÉLÉCHARGER UN PDF

Chaque mutation validée génère automatiquement un **PDF officiel** avec :
- l'en-tête de la DGID,
- les informations de la parcelle et du propriétaire,
- un **QR code** de vérification,
- l'emplacement de la signature.

### Depuis la fiche de la mutation

- **PDF** : ouvre le document dans le navigateur.
- **Télécharger** : enregistre le PDF en local.
- **Aperçu** : prévisualisation avant signature.

### Imports : PDFs fusionnés

Sur la fiche d'un import, le bouton **PDFs fusionnés** télécharge **un seul fichier** contenant tous les PDFs validés de cet import — pratique pour l'impression en lot.

---

## 12. VÉRIFIER UN DOCUMENT PAR QR CODE

Chaque PDF officiel porte un **QR code unique**. Toute personne (sans compte) peut vérifier son authenticité.

### Méthode 1 — Scanner le QR

1. Avec l'appareil photo d'un téléphone, scannez le QR du PDF.
2. Le lien `…/verification/{hash}` s'ouvre.
3. La page indique **✅ Document authentique** ou **❌ Document non valide**, avec les détails.

### Méthode 2 — Saisie manuelle

Si le QR est illisible :
1. Allez sur la page **Vérification** du site.
2. Saisissez le **code de vérification** imprimé sous le QR.
3. Cliquez sur **Vérifier**.

### Méthode 3 — Scanner intégré (connecté)

Une fois connecté : menu **Scanner** → utilise la caméra du PC/téléphone pour scanner directement depuis le logiciel.

> 🔒 Vérification limitée à **30 tentatives / minute** par IP.

---

## 13. RECHERCHE GLOBALE

**Menu :** *Recherche* (ou raccourci dans la barre du haut)

Tapez un terme — le logiciel cherche dans :
- les **parcelles** (numéro, projet),
- les **propriétaires** (nom, NIN),
- les **mutations** (référence),
- les **projets** (nom, code),
- les **imports** (nom de fichier).

Une **recherche rapide** (autocomplétion) est aussi disponible dans la barre du haut.

---

## 14. CONSULTER LA CARTE

**Menu :** *Carte* (icône globe)

Affiche les projets et parcelles sur une **carte interactive** :
- zoom et déplacement,
- clic sur une parcelle → mini-fiche avec lien vers le détail,
- code couleur selon le statut (libre / attribuée / mutée).

---

## 15. GÉNÉRER DES RAPPORTS

**Menu :** *Rapports*

1. Choisissez le **type** de rapport (mutations par période, par commune, par projet, par utilisateur…).
2. Définissez la **plage de dates** et les **filtres**.
3. Cliquez sur **Générer**.
4. Le rapport s'affiche à l'écran et peut être **téléchargé en PDF ou Excel**.

---

## 16. ADMINISTRATION (ADMIN UNIQUEMENT)

### 16.1 Templates de documents

**Menu :** *Templates*

Modifier l'apparence des PDF officiels (logo, en-tête, formules…).

1. Liste des templates existants.
2. **Modifier** un template : éditer le contenu (variables `{{ }}`).
3. **Aperçu** : prévisualiser avec des données fictives.
4. **Enregistrer**.

> ⚠️ Toute modification impacte **tous les PDF futurs**.

### 16.2 Export / Import base de données

**Menu :** *Admin > Export / Import*

- **Exporter en CSV** : un ZIP contenant un CSV par table.
- **Exporter en SQL** : un fichier `.sql` complet (sauvegarde).
- **Exporter tout** : CSV + SQL en un seul ZIP.
- **Télécharger** ou **Supprimer** les exports existants.
- **Importer un fichier SQL** : restauration d'une sauvegarde.

> 🛑 **L'import écrase les données existantes**. Faites toujours un export avant.

---

## 17. FOIRE AUX QUESTIONS

**Q : J'ai oublié mon mot de passe.**
R : Contactez l'administrateur — il peut le réinitialiser via la console.

**Q : L'écran de connexion me refuse — « Trop de tentatives ».**
R : Attendez 1 minute, puis réessayez. Vérifiez bien votre email et mot de passe.

**Q : Je vois « 403 » ou « Accès refusé ».**
R : Votre rôle n'a pas la permission. Demandez à l'admin de vous donner le rôle adéquat.

**Q : Mon import Excel échoue avec « ligne invalide ».**
R : Utilisez le bouton **Vérifier** avant **Importer**. Comparez votre fichier au **modèle téléchargé** — les colonnes doivent correspondre exactement.

**Q : Le QR code ne se lit pas.**
R : Améliorez l'éclairage, ou utilisez la **vérification manuelle** en tapant le code sous le QR.

**Q : Le PDF généré contient une erreur.**
R : Refusez la mutation, corrigez les données source, puis re-validez. Si c'est la mise en page : un admin doit modifier le **template**.

**Q : Puis-je annuler une validation ?**
R : Oui, via une **demande d'annulation** (voir §10). Elle doit être approuvée par un admin ou gestionnaire.

---

**Support :** en cas de bug ou d'évolution souhaitée, contactez votre administrateur.

*Fin du guide utilisateur.*