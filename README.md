<div align="center">

# 🏪 GestStock — ERP & Point de Vente

**Solution de gestion tout‑en‑un pour PME & commerces, pensée pour le Bénin et la zone OHADA.**
Stock · Facturation · Caisse (POS) · Comptabilité SYSCOHADA · Fiscalisation e‑MCeF (DGI) · Tontine/Épargne · RH

<br>

![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)
![Filament](https://img.shields.io/badge/Filament-v3-FDB515?logo=laravel&logoColor=white)
![Alpine.js](https://img.shields.io/badge/Alpine.js-3-8BC0D0?logo=alpinedotjs&logoColor=white)
![Tailwind](https://img.shields.io/badge/Tailwind-3-06B6D4?logo=tailwindcss&logoColor=white)
![e-MCeF](https://img.shields.io/badge/e--MCeF-DGI%20B%C3%A9nin-009639)
![OHADA](https://img.shields.io/badge/Comptabilit%C3%A9-SYSCOHADA-1D4ED8)
![Multi-tenant](https://img.shields.io/badge/Multi--entreprise-oui-6D28D9)

</div>

---

## 📋 Sommaire

- [Présentation](#-présentation)
- [Fonctionnalités par module](#-fonctionnalités-par-module)
- [Stack technique](#-stack-technique)
- [Installation (développement)](#-installation-développement)
- [Déploiement (production)](#-déploiement-production)
- [Rôles & permissions](#-rôles--permissions)
- [Spécificités Bénin / OHADA](#-spécificités-bénin--ohada)
- [Conventions importantes](#-conventions-importantes)
- [Licence](#-licence)

---

## 🎯 Présentation

**GestStock** est un ERP web **multi‑entreprise** (multi‑tenant) qui couvre l'ensemble du cycle
commercial et comptable d'une PME : des achats au point de vente, de la caisse à la
déclaration fiscale, en passant par la gestion des stocks multi‑entrepôts, les ressources
humaines et une caisse d'épargne/tontine intégrée.

Il est conçu pour le contexte **béninois** : fiscalisation des factures via **e‑MCeF (DGI)**,
comptabilité au plan **SYSCOHADA**, montants en **FCFA (XOF)**, et heures affichées en
**Africa/Porto‑Novo**.

---

## ✨ Fonctionnalités par module

### 🧾 Ventes & Achats
- Factures de **vente** et **d'achat** : remises (%), TVA, AIB, numérotation séquentielle
- **Avoirs** (notes de crédit) et annulation avec restauration du stock
- **Devis** et **bons de livraison** PDF, **commandes récurrentes**
- Suivi des **paiements** (partiels, multi‑versements) et des **créances clients** (relances, ancienneté)
- Envoi de factures **PDF par e‑mail**, QR code de vérification + lien signé anti‑fraude

### 🛒 Caisse / Point de vente (POS)
- Recherche produit, **scan douchette** et **scan caméra** (ZXing), mode continu, beep & vibration
- **Sessions de caisse** (ouverture/clôture, fond de caisse, rapprochement, écart)
- **Encaissement** de factures impayées, multi‑modes (espèces / carte / mobile money / virement)
- Sélection client, **paiement depuis l'épargne** du client, rendu monnaie automatique
- **Ticket de caisse** thermique 80 mm imprimable

### 📦 Stock & Entrepôts
- Produits (prix achat/vente, stock min., unité, catégories, TVA par produit)
- **Codes produits** auto (`PYYXXXXXX`) + **codes‑barres** Code 128 et **étiquettes PDF**
- **Multi‑entrepôts**, **transferts** inter‑entrepôts, **inventaires**, alertes de stock bas
- Import de produits (Excel/CSV)

### 💰 Comptabilité (SYSCOHADA)
- Plan comptable OHADA, **écritures**, **règles** d'imputation automatiques
- **Journaux** (ventes/achats), **balance générale**, **grand‑livre**, audit du journal
- Export **FEC** et rapports comptables PDF
- Suivi **TVA**, **banque** (comptes & transactions)

### 🏛️ Fiscalisation e‑MCeF (DGI Bénin)
- Certification des factures via l'API **e‑MCeF**, récupération **NIM / code MECeF / QR**
- Recherche **IFU** d'un contribuable, assistant de configuration (PPF)

### 💸 Tontine / Épargne
- **Porte‑monnaie d'épargne** par client (dépôts, retraits, utilisation en caisse)
- **Vue d'ensemble** tontine, **relevé de compte** PDF par client, **reçus** imprimables
- Mouvements rattachés à la **session de caisse** (traçabilité des espèces)

### 👥 RH
- Employés, **pointage**, **congés**, **planning** (calendrier), **commissions**

### 📊 Tableau de bord & Rapports
- Statistiques, graphique des ventes, **top produits / vendeurs / clients ambassadeurs**
- **Classements** clients & vendeurs sur période personnalisable (page + PDF)
- Centre de rapports, exports PDF/Excel

---

## 🧱 Stack technique

| Domaine | Technologie |
|---|---|
| Backend | **Laravel 11** (PHP 8.2+) |
| Admin / UI | **Filament v3** |
| Front interactif | **Alpine.js** + **Livewire**, **Vite** + **Tailwind CSS** |
| Base de données | **MySQL** (production) · **SQLite** (développement) |
| PDF | `barryvdh/laravel-dompdf`, `spatie/browsershot` |
| Excel | `maatwebsite/excel` |
| Codes‑barres / QR | `milon/barcode`, `simplesoftwareio/simple-qrcode`, ZXing (scan caméra) |
| E‑invoicing | `horstoeko/zugferd`, `tiime/factur-x` (Factur‑X / ZUGFeRD) |
| Journal d'audit | `spatie/laravel-activitylog` |
| Calendrier | `saade/filament-fullcalendar` |
| API | `laravel/sanctum` |

---

## 🚀 Installation (développement)

> Prérequis : PHP 8.2+, Composer, Node.js 18+, et l'extension PHP `sqlite3`.

```bash
git clone <url-du-repo> geststock
cd geststock

composer install
cp .env.example .env
php artisan key:generate

# Base SQLite locale
touch database/database.sqlite        # (sous Windows : New-Item database/database.sqlite)
php artisan migrate --seed

npm install
npm run build          # ou `npm run dev` pour le hot‑reload

php artisan serve
```

Accès : **http://localhost:8000** — panneau d'administration sur **`/admin`**.
Créez un premier utilisateur puis attribuez‑lui le rôle **admin** (seeder `RolesAndPermissionsSeeder`).

---

## 📦 Déploiement (production)

L'hébergement cible est **cPanel / MySQL**. Le serveur **ne construit pas** les assets :
le build Vite est fait **en local** et le dossier `public/build` est **versionné**.

**En local — avant de pousser un changement de front (CSS/JS) :**

```bash
npm run build
git add -A public/build
git commit -m "build assets"
git push
```

**Sur le serveur :**

```bash
git pull origin main
php artisan migrate --force          # uniquement si de nouvelles migrations
php artisan optimize:clear
php artisan filament:optimize-clear
```

> Après un changement de CSS, le hash des fichiers change : pensez au **Ctrl+F5** côté navigateur.

---

## 🔐 Rôles & permissions

Système de rôles/permissions granulaire (`RolesAndPermissionsSeeder`). Rôles par défaut :

| Rôle | Portée |
|---|---|
| **admin** | Accès complet à tous les modules |
| **manager** | Opérations quotidiennes (ventes, achats, stock, caisse, rapports, tontine) |
| **cashier** (Caissier) | Caisse : encaissement, sessions, tontine (pas de création de vente) |
| **vendeur** | Ventes et relation client |

Permissions notables : `pos.sell`, `pos.collect`, `pos.session`, `tontine.manage`,
`reports.view`, `customers.view`… Un **super‑admin** gère les entreprises (multi‑tenant).

---

## 🇧🇯 Spécificités Bénin / OHADA

- **Monnaie** : FCFA (XOF), montants **entiers** (sans décimales).
- **Fuseau horaire** : stockage en **UTC**, affichage en **Africa/Porto‑Novo** (UTC+1).
- **Fiscalité** : certification des factures **e‑MCeF (DGI)**, IFU, AIB, TVA.
- **Comptabilité** : plan **SYSCOHADA**, export **FEC**.

---

## 📌 Conventions importantes

- **Dates** : afficher avec la directive Blade **`@dt(...)`** (conversion UTC → fuseau local).
  Ne jamais afficher une date UTC brute.
- **PDF (DomPDF)** : pas de flexbox ni de dégradés CSS ; privilégier des tableaux et des
  couleurs pleines. Gabarit A4 : `@page { margin: 0 }` + `body { padding: 15mm 16mm }`.
- **Assets** : toujours **rebuild + commit de `public/build`** après un changement de style.
- **Tests** : base **Pest** initialisée — `php artisan test`.

---

## 📄 Licence

Logiciel **propriétaire** — tous droits réservés. Toute utilisation, reproduction ou
distribution sans autorisation écrite est interdite.

<div align="center">
<sub>Construit avec ❤️ sur Laravel & Filament.</sub>
</div>
