# AUDIT COMPLET — FRECORP / GestStock ERP

> **Date d'audit :** 19 février 2026
> **Stack :** Laravel 12 + Filament v3 + SQLite/MySQL/PostgreSQL
> **Nom de marque :** FRECORP (panel admin) / GestStock (projet)

---

## TABLE DES MATIÈRES

1. [Architecture Générale](#1-architecture-générale)
2. [Panels Filament](#2-panels-filament)
3. [Multi-Tenancy & Entreprises](#3-multi-tenancy--entreprises)
4. [Rôles, Permissions & Sécurité](#4-rôles-permissions--sécurité)
5. [Module Ventes](#5-module-ventes)
6. [Module Devis](#6-module-devis)
7. [Module Bons de Livraison](#7-module-bons-de-livraison)
8. [Module Commandes Récurrentes](#8-module-commandes-récurrentes)
9. [Module Produits](#9-module-produits)
10. [Module Achats (Fournisseurs)](#10-module-achats-fournisseurs)
11. [Module Fournisseurs](#11-module-fournisseurs)
12. [Module Clients](#12-module-clients)
13. [Module Entrepôts & Stock Multi-sites](#13-module-entrepôts--stock-multi-sites)
14. [Module Transferts de Stock](#14-module-transferts-de-stock)
15. [Module Inventaires](#15-module-inventaires)
16. [Module Point de Vente (POS / Caisse)](#16-module-point-de-vente-pos--caisse)
17. [Module RH — Employés](#17-module-rh--employés)
18. [Module RH — Pointage & Présences](#18-module-rh--pointage--présences)
19. [Module RH — Plannings & Horaires](#19-module-rh--plannings--horaires)
20. [Module RH — Congés](#20-module-rh--congés)
21. [Module RH — Commissions](#21-module-rh--commissions)
22. [Module Comptabilité](#22-module-comptabilité)
23. [Module TVA (France & Bénin)](#23-module-tva-france--bénin)
24. [Module AIB (Bénin)](#24-module-aib-bénin)
25. [Intégration e-MCeF (DGI Bénin)](#25-intégration-e-mcef-dgi-bénin)
26. [Intégration PPF / Factur-X (France)](#26-intégration-ppf--factur-x-france)
27. [Intégration URSSAF (France)](#27-intégration-urssaf-france)
28. [Module Paiements & Règlements](#28-module-paiements--règlements)
29. [Module Rapports & Export](#29-module-rapports--export)
30. [Widgets Dashboard](#30-widgets-dashboard)
31. [Panel Super Admin](#31-panel-super-admin)
32. [Observers & Business Logic](#32-observers--business-logic)
33. [Services Techniques](#33-services-techniques)
34. [Jobs (Async)](#34-jobs-async)
35. [Mails](#35-mails)
36. [Import / Export](#36-import--export)
37. [Policies (Autorisations)](#37-policies-autorisations)
38. [Routes Publiques & API](#38-routes-publiques--api)
39. [Résumé des Modèles & Relations](#39-résumé-des-modèles--relations)
40. [Modules Activables par Entreprise](#40-modules-activables-par-entreprise)

---

## 1. Architecture Générale

| Composant | Technologie |
|-----------|------------|
| Framework backend | Laravel 12 |
| Panel admin | Filament v3 |
| Base de données | SQLite (dev) — MySQL/PostgreSQL (prod) |
| PDF | `barryvdh/laravel-dompdf` |
| Factur-X | `horstoeko/zugferd` + `tiime/factur-x` |
| QR Codes | `simplesoftwareio/simple-qrcode` |
| Codes-barres | `milon/barcode` (Code128) |
| Calendrier | `saade/filament-full-calendar` |
| Logs activité | `spatie/laravel-activitylog` |
| Excel | `maatwebsite/excel` |
| Scan caméra | ZXing Browser (UMD) |
| Front assets | Vite + Tailwind CSS |
| Email | SMTP configurable |

**3 panels Filament distincts :**
- `/admin` — Panel principal (brand: FRECORP)
- `/caisse` — Panel POS dédié caissiers (brand: GestStock POS)
- `/system` — Panel Super Admin

---

## 2. Panels Filament

### 2.1 Panel Admin (`/admin`)
- **Tenant :** `Company` (slug)
- **Registration :** Custom (`Register.php`)
- **Tenant Profile :** `EditCompanyProfile`
- **Dark mode :** activé par défaut
- **Groupes navigation :** Ventes, Stocks & Achats, Point de Vente, RH, Comptabilité, Administration
- **Plugins :** FilamentFullCalendar (planning RH)
- **Middlewares custom :** `RedirectToTenant`, `RedirectCashierToPOS`
- **Auto-discover :** Resources, Pages, Widgets

### 2.2 Panel Caisse (`/caisse`)
- **Tenant :** `Company` (slug)
- **Light mode** uniquement
- **Pages :** PointOfSale, CashSessionPage, SalesHistory
- **Navigation top** (pas de sidebar)
- **Minimaliste** — dédié aux opérations caisse

### 2.3 Panel Super Admin (`/system`)
- **Pas de tenant** — accès cross-company
- **Auth middleware :** `VerifyIsSuperAdmin`
- **Groupes :** Tableau de Bord, Gestion, Système
- **Resources :** CompanyResource, UserResource, CompanyIntegrationResource, TutorialVideoResource
- **Pages :** FeaturesManagement
- **Widgets :** FeaturesOverview, StatsOverview

---

## 3. Multi-Tenancy & Entreprises

| Aspect | Détail |
|--------|--------|
| Modèle | `Company` |
| Stratégie | Filament Tenant via `company_id` sur chaque table |
| Attribute slug | URL path: `/admin/{slug}/...` |
| Trait modèles | `BelongsToCompany` (auto-scope company_id) |
| Relation User↔Company | `BelongsToMany` (table `company_user`) |
| Création | Via Super Admin uniquement (inscription tenant désactivée) |
| Observer | `CompanyObserver` → crée rôles par défaut + entrepôt par défaut |
| Cache | Settings et stats cachés par company (`company.{id}.settings`) |
| Devise | Auto-détectée par IP via `GeoLocationService` ou configurée |
| Pays | `country_code` (ex: `BJ` pour Bénin) |
| Modules activables | Config via `settings.modules` JSON |

**Champs Company :**
`name`, `slug`, `email`, `phone`, `address`, `city`, `zip_code`, `website`, `logo_path`, `tax_number`, `registration_number`, `siret`, `footer_text`, `settings` (JSON), `currency`, `country_code`, `is_active`, `emcef_nim`, `emcef_token`, `emcef_enabled`, `emcef_sandbox`, `aib_mode`, `aib_exempt_retail`

---

## 4. Rôles, Permissions & Sécurité

### Système de rôles
- **Table `roles`** : liée à `company_id` (rôles par entreprise)
- **Table `permissions`** : permissions globales
- **Pivot `role_has_permissions`** et **`model_has_roles`**
- **Trait `HasRoles`** sur le modèle `User`

### Rôles par défaut (créés par `CompanyObserver`)

| Slug | Nom | Permissions |
|------|-----|-------------|
| `admin` | Administrateur | Toutes (`*`) |
| `manager` | Gestionnaire | Produits, Ventes, Achats, Clients, Fournisseurs, Devis, BL, POS, Entrepôts, RH, Rapports |
| `cashier` | Caissier | Produits (vue), Ventes (vue/créer), Clients (vue/créer), POS |
| `vendeur` | Vendeur | Produits (vue), Ventes, Clients, Devis, BL (vue), POS |
| `magasinier` | Magasinier | Produits (vue/stock), Achats (vue), Entrepôts, Transferts, Inventaire |
| `user` | Utilisateur | Lecture seule (produits, ventes, clients) — rôle par défaut |

### Permissions granulaires
- Format : `module.action` (ex: `sales.create`, `products.edit`, `pos.access`)
- Modules : products, sales, purchases, customers, suppliers, quotes, deliveries, pos, warehouses, transfers, inventory, employees, schedule, leaves, attendance, reports
- Actions : view, create, edit, delete, stock, approve, manage, access, session, reports

### Restriction par entrepôt
- **Table `user_warehouse`** : assigne des entrepôts aux utilisateurs
- `hasWarehouseRestriction()` — true si l'utilisateur a des entrepôts spécifiques assignés
- Les admins voient tout, les vendeurs/caissiers voient uniquement les données de leur(s) entrepôt(s)
- Scope `HasWarehouseScope` appliqué sur Sale, Purchase, etc.

### Sécurité
- Hash SHA-256 chaîné sur les factures (`security_hash`, `previous_hash`)
- Vérification publique via URL signée (`/verify/{type}/{id}?signature=...`)
- `is_super_admin` flag sur User pour l'accès au panel `/system`
- Audit trail via `spatie/laravel-activitylog`

---

## 5. Module Ventes

**Resource :** `SaleResource` — Nav: "Ventes" → groupe "Ventes"
**Modèle :** `Sale` (traits: `BelongsToCompany`, `LogsActivity`, `HasWarehouseScope`)
**Label :** Vente / Ventes

### Champs principaux
| Champ | Description |
|-------|-------------|
| `invoice_number` | Numérotation séquentielle auto (FAC-XXXXXX) |
| `type` | `invoice` ou `credit_note` (avoir) |
| `parent_id` | Référence facture originale (pour avoirs) |
| `customer_id` | FK Client |
| `warehouse_id` | FK Entrepôt source |
| `bank_account_id` | FK Compte bancaire |
| `status` | pending / completed / cancelled |
| `payment_status` | pending / partial / paid |
| `payment_method` | cash / card / transfer / check / mobile_money / credit / other |
| `is_export` | Vente à l'exportation (TVA exonérée) |
| `discount_percent` | Remise globale % |
| `total_ht`, `total_vat`, `total` | Montants calculés |
| `aib_rate`, `aib_amount`, `aib_exempt` | AIB Bénin |
| `security_hash`, `previous_hash` | Intégrité fiscale |
| `amount_paid`, `paid_at` | Suivi paiement |
| `emcef_*` | Champs certification e-MCeF |
| `ppf_*` | Champs PPF France |
| `notes` | Notes libres |

### Relations
- `customer` → Customer (BelongsTo)
- `warehouse` → Warehouse (BelongsTo)
- `bankAccount` → BankAccount (BelongsTo)
- `items` → SaleItem (HasMany) — inclut: product_id, quantity, unit_price, total_price, vat_rate, vat_category, tax_specific_amount, is_wholesale
- `payments` → Payment (morphMany)
- `parent` → Sale (BelongsTo, self-ref pour avoirs)
- `creditNotes` → Sale (HasMany, type=credit_note)
- `cashSession` → CashSession (BelongsTo)

### Relation Managers
- `ItemsRelationManager` — gestion des lignes d'article
- `PaymentsRelationManager` — suivi des règlements

### Actions disponibles
| Action | Description |
|--------|-------------|
| Créer | Nouvelle vente avec articles |
| Modifier | Sauf si status = completed |
| Voir | Détail complet |
| Supprimer | Sauf si completed |
| Facture PDF | Téléchargement PDF avec QR code |
| Prévisualiser | HTML avant PDF |
| Envoyer email | Envoi facture PDF par email |
| Paiement | Ajout règlement partiel/total |
| Certifier e-MCeF | Soumission DGI Bénin |
| Détails e-MCeF | Voir détails certification |
| Réessayer e-MCeF | Retry en cas d'erreur |
| Confirmer e-MCeF | Confirmation dans les 2 min |
| Générer un avoir | Crée credit_note + réintègre stock |
| Suppression bulk | Avec vérification pas de completed |

### Filtres
- Entrepôt (boutique)
- Statut paiement
- Statut vente

### Logique métier spéciale
- **Prix de gros automatique** : si `quantity >= min_wholesale_qty`, applique `wholesale_price_ht`
- **TVA adaptative** : 18% par défaut si e-MCeF (Bénin), 20% sinon (France)
- **Ventes export** : TVA forcée à 0%, catégorie "C"
- **AIB** : calculé automatiquement en mode "auto"
- **Numérotation séquentielle** : via table `sequences`
- **Chaînage hash** : security_hash = SHA256 des données clés

---

## 6. Module Devis

**Resource :** `QuoteResource` — Nav: "Devis" → groupe "Ventes"
**Modèle :** `Quote` + `QuoteItem`

### Champs
| Champ | Description |
|-------|-------------|
| `quote_number` | Auto-généré |
| `quote_date` | Date du devis |
| `valid_until` | Date d'expiration (+30j par défaut) |
| `customer_id` | FK Client |
| `status` | draft / sent / accepted / rejected / expired / converted |
| `discount_percent`, `total_ht`, `total_vat`, `total` | Financiers |
| `public_token`, `public_url` | Lien de partage client |

### Articles (QuoteItem)
- product_id, description, quantity, unit_price, vat_rate, vat_category, discount_percent

### Actions
| Action | Description |
|--------|-------------|
| Créer/Modifier | Avec articles repeater |
| PDF | Téléchargement/Prévisualisation |
| Envoi email | Via QuoteMail |
| Partage public | Lien token pour acceptation client |
| Convertir en vente | Statut → converted |

### Partage public
- Routes `/view/quote/{token}` (show, accept, reject)
- Le client peut accepter ou refuser sans compte

---

## 7. Module Bons de Livraison

**Resource :** `DeliveryNoteResource` — Nav: "Bons de livraison" → groupe "Ventes"
**Modèle :** `DeliveryNote` + `DeliveryNoteItem`

### Champs
| Champ | Description |
|-------|-------------|
| `delivery_number` | Auto-généré |
| `delivery_date` | Date livraison |
| `sale_id` | Lien commande |
| `customer_id` | FK Client |
| `carrier` | Transporteur |
| `tracking_number` | N° suivi |
| `total_packages` | Nombre de colis |
| `delivery_address` | Adresse |
| `status` | pending / preparing / ready / shipped / delivered / cancelled |

### Articles (DeliveryNoteItem)
- product_id, description, quantity_ordered, quantity_delivered

### Actions
- Créer, Modifier, PDF téléchargement/prévisualisation

---

## 8. Module Commandes Récurrentes

**Resource :** `RecurringOrderResource` — Nav: "Commandes" → groupe "Ventes"
**Modèle :** `RecurringOrder` + `RecurringOrderItem`

### Champs
| Champ | Description |
|-------|-------------|
| `reference` | Auto (REC-YYYYMM-XXXX) |
| `name` | Nom abonnement |
| `customer_id` | FK Client |
| `frequency` | daily / weekly / biweekly / monthly / quarterly / yearly |
| `frequency_value` | Intervalle |
| `start_date`, `end_date` | Période |
| `next_execution` | Prochaine génération |
| `max_executions` | Limite |
| `status` | active / paused / cancelled / completed |
| `auto_generate` | Génération auto des ventes |
| `auto_send_invoice` | Envoi auto facture email |
| `executions_count`, `last_execution` | Stats |

---

## 9. Module Produits

**Resource :** `ProductResource` — Nav: "Produits" → groupe "Stocks & Achats"
**Modèle :** `Product` (traits: `BelongsToCompany`, `LogsActivity`)

### Champs principaux
| Champ | Description |
|-------|-------------|
| `name` | Nom produit |
| `code` | Code interne auto (PYYXXXXXX) |
| `barcode` | Code-barres scannable |
| `barcode_type` | code128 par défaut |
| `description` | Description libre |
| `purchase_price` / `purchase_price_ht` | Prix achat |
| `price` / `sale_price_ht` | Prix vente |
| `vat_rate_purchase` / `vat_rate_sale` | Taux TVA |
| `vat_category` | Catégorie TVA (A, B, C, S...) |
| `tax_specific_amount` / `tax_specific_label` | Taxe spécifique |
| `wholesale_price` / `wholesale_price_ht` | Prix de gros |
| `min_wholesale_qty` | Seuil prix gros |
| `stock` / `min_stock` | Stock global / seuil alerte |
| `unit` | Unité de mesure |
| `supplier_id` | FK Fournisseur |
| `prices_include_vat` | Toggle HT/TTC |

### Attributs calculés
- `total_stock` : stock agrégé multi-entrepôts
- `margin` : marge unitaire
- `margin_percent` : % de marge

### Relations
- `supplier` → Supplier (BelongsTo)
- `warehouses` → Warehouse (BelongsToMany, pivot: quantity, min_quantity, location)
- `saleItems` → SaleItem (HasMany)
- `purchaseItems` → PurchaseItem (HasMany)
- `accountingCategory` → AccountingCategory (BelongsTo)

### Actions spéciales
- **Génération auto code** : `P` + année 2 chiffres + séquence 6 chiffres
- **Régénération code** : Action admin
- **Impression étiquettes** : Bulk action + individuel (2/3/4 colonnes, option prix)
- **Import produits** : Via `ProductImport` (Excel)
- **Export template** : Via `ProductTemplateExport`
- **Assignation entrepôt** : Auto à la création (entrepôt par défaut)
- Prix HT/TTC : calcul bidirectionnel selon toggle
- TVA configurable par produit (achat et vente séparément)

---

## 10. Module Achats (Fournisseurs)

**Resource :** `PurchaseResource` — Nav: "Achats" → groupe "Stocks & Achats"
**Modèle :** `Purchase` + `PurchaseItem` (traits: `BelongsToCompany`, `HasWarehouseScope`)

### Champs
| Champ | Description |
|-------|-------------|
| `invoice_number` | Numérotation auto (ACH-XXXXXX) |
| `supplier_id` | FK Fournisseur |
| `warehouse_id` | Entrepôt de réception |
| `status` | pending / completed / cancelled |
| `payment_method` | cash / card / transfer / check / sepa_debit / paypal |
| `bank_account_id` | Compte de paiement |
| `discount_percent` | Remise globale |
| `total_ht`, `total_vat`, `total` | Montants |

### Articles (PurchaseItem)
- product_id, quantity, unit_price, vat_rate, total_price

### Actions
- Créer, Modifier, Facture PDF, Prévisualisation, Suppression

---

## 11. Module Fournisseurs

**Resource :** `SupplierResource` — Nav: groupe "Stocks & Achats"
**Modèle :** `Supplier` (trait: `BelongsToCompany`)

### Champs
`name`, `email`, `phone`, `address`, `city`, `country`, `notes`

### Actions
Créer, Modifier, Supprimer, Suppression bulk

---

## 12. Module Clients

**Resource :** `CustomerResource` — Nav: "Clients" → groupe "Ventes"
**Modèle :** `Customer` (trait: `BelongsToCompany`)

### Champs
| Champ | Description |
|-------|-------------|
| `name` | Nom / Raison sociale |
| `registration_number` | IFU (13 chiffres, Bénin) |
| `customer_type` | B2B (Professionnel) / B2C (Particulier) |
| `email`, `phone` | Contact |
| `address`, `city`, `zip_code`, `country`, `country_code` | Adresse |
| `tax_number` | Numéro fiscal |
| `siret`, `siren` | Identifiants France |
| `notes` | Notes |

### Action spéciale
- **Recherche IFU (e-MCeF)** : bouton loupe qui interroge la DGI Bénin pour auto-remplir nom, adresse, téléphone depuis l'IFU

---

## 13. Module Entrepôts & Stock Multi-sites

**Resource :** `WarehouseResource` — Nav: "Entrepôts" → groupe "Stocks & Achats"
**Modèle :** `Warehouse`

### Champs
| Champ | Description |
|-------|-------------|
| `code` | Code unique (WH001) |
| `name` | Nom |
| `type` | warehouse / store / supplier / customer |
| `manager_name` | Responsable |
| `address`, `city`, `postal_code`, `country` | Adresse |
| `phone`, `email` | Contact |
| `latitude`, `longitude`, `gps_radius` | Géolocalisation (pour pointage) |
| `requires_gps_check` | Vérification GPS requise |
| `requires_qr_check` | Scan QR requis |
| `is_default` | Entrepôt par défaut |
| `is_active` | Actif/Inactif |
| `is_pos_location` | Désigné pour POS |

### Relations
- `products` → Product (BelongsToMany, pivot: quantity, min_quantity, location)
- `locations` → WarehouseLocation (HasMany)
- `employees` → Employee (HasMany)
- `stockTransfersFrom/To` → StockTransfer (HasMany)
- `inventories` → Inventory (HasMany)

### Pages spécialisées
- **Stock Consolidé** (`ConsolidatedStock`) : vue agrégée multi-entrepôts avec statut stock, valeur, etc.
- **Comparatif Boutiques** (`WarehouseComparison`) : comparaison CA/ventes par boutique avec tendances mois précédent

### Restriction d'accès
- Seuls les admins voient la liste des entrepôts
- Les utilisateurs restreints ne voient que les données de leurs entrepôts assignés

---

## 14. Module Transferts de Stock

**Resource :** `StockTransferResource` — Nav: "Transferts" → groupe "Stocks & Achats"
**Modèle :** `StockTransfer` + `StockTransferItem`

### Champs
| Champ | Description |
|-------|-------------|
| `reference` | Auto-généré |
| `source_warehouse_id` | Entrepôt source |
| `destination_warehouse_id` | Entrepôt destination |
| `transfer_date` | Date transfert |
| `expected_date` | Date prévue d'arrivée |
| `status` | pending / approved / in_transit / completed / cancelled |

### Articles (StockTransferItem)
- product_id, quantity_requested, quantity_shipped, quantity_received, unit_cost

### Workflow
- Badge navigation avec compteur de transferts en cours
- Filtrage par entrepôts accessibles à l'utilisateur (source OU destination)
- Vérification stock disponible dans l'entrepôt source

---

## 15. Module Inventaires

**Resource :** `InventoryResource` — Nav: "Inventaires" → groupe "Stocks & Achats"
**Modèle :** `Inventory` + `InventoryItem`

### Champs
| Champ | Description |
|-------|-------------|
| `reference` | Auto-généré |
| `name` | Nom inventaire |
| `warehouse_id` | Entrepôt cible |
| `type` | full (complet) / partial (partiel) / cycle (cyclique ABC) |
| `inventory_date` | Date |
| `status` | draft / in_progress / pending_validation / validated / cancelled |
| `progress_percent` | % progression |
| `discrepancies_count` | Nombre d'écarts |
| `value_difference` | Différence valorisée |

### Actions
- Créer, Modifier, Valider, Annuler
- Suivi progression et détection écarts

---

## 16. Module Point de Vente (POS / Caisse)

### Architecture
Le POS est implémenté en **3 couches** :

1. **`PosService`** (service centralisé) — toute la logique métier
2. **`CashRegisterPage`** (page Filament dans panel admin) — pour admins/managers
3. **Panel Caisse** (`/caisse`) avec `PointOfSale`, `CashSessionPage`, `SalesHistory` — pour caissiers

### Fonctionnalités POS
| Fonction | Détail |
|----------|--------|
| Recherche produit | Par nom, code interne, code-barres |
| Scan douchette | Champ input avec détection retour chariot |
| Scan caméra | ZXing, sélection device, mode continu |
| Ajout panier | Quantité, prix, calcul automatique |
| Prix de gros | Application auto si seuil atteint |
| Remise | % sur sous-total |
| TVA | Calculée automatiquement par produit |
| Client | Client walk-in par défaut, sélection optionnelle |
| Rendu monnaie | Calcul automatique |
| Stock temps réel | Alerte stock bas, badge visuel |
| Restriction prix | Caissiers ne modifient pas les prix, admins oui |

### Sessions de caisse
| Étape | Détail |
|-------|--------|
| Ouverture | Montant d'ouverture, userId, companyId |
| En cours | Comptage ventes, total encaissé, panier moyen |
| Clôture | Montant de fermeture, écart calculé (théorique vs réel), notes |

### Entrepôt POS
Résolution 4 niveaux :
1. Entrepôt assigné à l'utilisateur
2. Entrepôt marqué `is_pos_location`
3. Entrepôt par défaut (`is_default`)
4. N'importe quel entrepôt actif

### API POS (routes web)
| Route | Description |
|-------|-------------|
| `GET /api/pos/session/check` | Vérifie session ouverte |
| `POST /api/pos/session/open` | Ouvre session |
| `POST /api/pos/session/close` | Ferme session |
| `GET /api/pos/products` | Liste produits |
| `GET /api/pos/products/search` | Recherche produits |
| `GET /api/pos/products/barcode/{code}` | Produit par code-barres |
| `POST /api/pos/sale` | Enregistre vente POS |
| `GET /api/pos/report` | Rapport session en cours |
| `GET /api/pos/report/history` | Historique sessions |
| `GET /api/pos/report/{id}/pdf` | Export PDF rapport |
| `GET /api/pos/report/{id}/excel` | Export Excel rapport |

### Certification e-MCeF POS
Les ventes POS sont automatiquement soumises à e-MCeF si activé sur l'entreprise.

---

## 17. Module RH — Employés

**Resource :** `EmployeeResource` — Nav: "Employés" → groupe "RH"
**Modèle :** `Employee`

### Champs
| Champ | Description |
|-------|-------------|
| `employee_number` | Matricule auto |
| `first_name`, `last_name` | Identité |
| `email`, `phone` | Contact |
| `birth_date` | Date de naissance |
| `address`, `city`, `postal_code`, `country` | Adresse |
| `social_security_number` | N° SS |
| `photo` | Photo (upload, cropper circulaire) |
| `position` | Poste |
| `department` | Service |
| `warehouse_id` | Entrepôt par défaut |
| `contract_type` | CDI / CDD / Intérim / Stage / Apprentissage / Freelance |
| `hire_date`, `contract_end_date` | Dates contrat |
| `weekly_hours` | Heures/semaine (défaut 35) |
| `hourly_rate` | Taux horaire |
| `monthly_salary` | Salaire mensuel |
| `commission_rate` | Taux commission sur ventes |
| `status` | active / on_leave / terminated |

### Création de compte utilisateur
- Toggle "Créer un compte utilisateur" lors de la création d'employé
- Attribution automatique d'un rôle et mot de passe

### Visibilité
- Conditionné à `hr` module activé
- Permissions : `employees.view`, `employees.*`

---

## 18. Module RH — Pointage & Présences

### Pages
- **`TimeTrackingPage`** — Suivi pointage admin (vue tableau de bord)
- **`EmployeeClockIn`** — Interface auto-service pour pointer

**Resource :** `AttendanceLogResource` — Logs de pointage (lecture seule, masqué)

### Modèles
- **`Attendance`** : enregistrement journalier (clock_in, clock_out, break_start, break_end, hours_worked, status)
- **`AttendanceLog`** : log technique de chaque tentative (succès/échec, distance GPS, QR valide, IP, user agent)
- **`AttendanceQrToken`** : tokens QR temporaires par entrepôt

### Service `AttendanceService`
| Méthode | Description |
|---------|-------------|
| `clockIn()` | Pointage entrée avec validations GPS + QR |
| `clockOut()` | Pointage sortie |
| `breakStart()` / `breakEnd()` | Gestion pauses |
| `validateGps()` | Vérifie distance vs coordonnées entrepôt |
| `validateQr()` | Vérifie token QR en temps réel |

### Workflow de pointage
1. Sélection entrepôt
2. Vérification GPS (si `requires_gps_check`)
3. Scan QR Code (si `requires_qr_check`)
4. Confirmation pointage
5. Log succès/échec avec raison

### Pages spécialisées
- **`AttendanceQrDisplay`** : Affiche QR code de l'entrepôt pour pointage
- **`ScheduleCalendar`** : Calendrier planning (FullCalendar)

---

## 19. Module RH — Plannings & Horaires

**Resource :** `ScheduleResource` — masqué par défaut
**Modèle :** `Schedule`

### Champs
| Champ | Description |
|-------|-------------|
| `employee_id` | FK Employé |
| `date` | Date (si ponctuel) |
| `day_of_week` | Jour (si récurrent) |
| `is_recurring` | Toggle récurrence |
| `start_time`, `end_time` | Horaires |
| `break_duration` | Durée pause |
| `shift_type` | morning / afternoon / night / full_day |
| `position` | Poste/station |
| `location` | Lieu |
| `color` | Couleur calendrier |
| `is_published` | Visible par l'employé |

### Page `SchedulePlanning`
- Vue calendrier FullCalendar
- Édition drag & drop

---

## 20. Module RH — Congés

**Resource :** `LeaveRequestResource` — Nav: "Congés" → groupe "RH"
**Modèle :** `LeaveRequest`

### Champs
| Champ | Description |
|-------|-------------|
| `employee_id` | FK Employé |
| `type` | paid / unpaid / sick / maternity / paternity / other |
| `start_date`, `end_date` | Période |
| `days_count` | Calcul auto (jours ouvrés) |
| `reason` | Motif |
| `status` | pending / approved / rejected / cancelled |
| `rejection_reason` | Motif refus |
| `approved_by` | FK User approbateur |

### Actions
- Approuver / Refuser (avec motif)

---

## 21. Module RH — Commissions

**Resource :** `CommissionResource` — masqué (`shouldRegisterNavigation = false`)
**Modèle :** `Commission`

### Champs
| Champ | Description |
|-------|-------------|
| `employee_id` | FK Employé |
| `sale_id` | FK Vente (optionnel) |
| `period_start`, `period_end` | Période |
| `sale_amount` | Montant ventes |
| `commission_rate` | Taux % |
| `commission_amount` | Montant calculé |
| `status` | pending / approved / paid / cancelled |
| `paid_at` | Date de paiement |

---

## 22. Module Comptabilité

### Resources (la plupart masquées pour simplification)

| Resource | Statut | Description |
|----------|--------|-------------|
| `AccountingEntryResource` | Masqué | Grand Livre (écritures comptables immutables) |
| `AccountingCategoryResource` | Masqué | Catégories comptables (recette/dépense) |
| `AccountingRuleResource` | Masqué | Règles auto de catégorisation |
| `AccountingSettingResource` | Masqué | Paramètres comptables par entreprise |
| `BankAccountResource` | Masqué | Comptes bancaires |
| `BankTransactionResource` | Masqué | Transactions bancaires |

### Service `AccountingEntryService` (1184 lignes)
Génère automatiquement les écritures comptables conformes FEC :

| Action | Écritures |
|--------|-----------|
| Vente validée | DÉBIT 411xxx (Client TTC) + CRÉDIT 707xxx (Ventes HT) + CRÉDIT 445xxx (TVA collectée) |
| Achat validé | DÉBIT 607xxx (Achats HT) + DÉBIT 445xxx (TVA déductible) + CRÉDIT 401xxx (Fournisseur TTC) |
| Paiement reçu | DÉBIT 512xxx (Banque) + CRÉDIT 411xxx (Client) |
| Paiement émis | DÉBIT 401xxx (Fournisseur) + CRÉDIT 512xxx (Banque) |

### Service `AccountingService`
- Application de règles de catégorisation automatique sur transactions bancaires
- Matching par libellé (contient, commence par, finit par, exact)

### Paramètres comptables (`AccountingSetting`)
| Param | Description |
|-------|-------------|
| `is_vat_franchise` | Exonération TVA |
| `vat_regime` | TVA sur débits (facturation) ou encaissements (paiement) |
| `journal_sales` / `journal_purchases` | Codes journaux (VTE, ACH) |
| `account_customers` / `account_suppliers` | Comptes auxiliaires |
| Comptes par défaut | 707xxx, 607xxx, 445xxx, 512xxx, etc. |

---

## 23. Module TVA (France & Bénin)

### Taux disponibles (configurable par produit)
| Pays | Taux par défaut | Options |
|------|----------------|---------|
| Bénin (BJ) | **18%** | 18%, 0% (exonéré) |
| France (FR) | **20%** | 20%, 10%, 5.5%, 2.1%, 0% |

### Catégories TVA e-MCeF (Bénin)
| Code | Description |
|------|-------------|
| A | Taxable (18%) |
| B | Exonéré |
| C | Exportation |
| D | TVA inversée |
| E | Livraison gratuite |
| F | Hors champ |

### Widget VatSummaryWidget
- TVA collectée (ventes du mois)
- TVA déductible (achats du mois)
- TVA à reverser (solde)
- Tendance vs mois précédent
- Mode franchise (affichage simplifié si exonéré)

---

## 24. Module AIB (Bénin)

**AIB = Acompte sur Impôt sur le revenu des personnes physiques et sur les Bénéfices des sociétés**

### Configuration (sur Company)
| Mode | Comportement |
|------|-------------|
| `auto` | Calculé automatiquement : 1% si client avec IFU (B2B), 5% si sans IFU (B2C) |
| `manual` | Sélection manuelle du taux par vente |
| `disabled` | AIB désactivé |

### Taux
| Code | Taux | Condition |
|------|------|-----------|
| A | 1% | Client avec IFU valide |
| B | 5% | Client sans IFU |

### Sur les ventes
- `aib_rate` : A ou B
- `aib_amount` : montant calculé sur le HT
- `aib_exempt` : toggle exonération par vente
- `total_with_aib` : net à payer (TTC + AIB)

### Observer
`SaleObserver::applyAibIfNeeded()` — applique automatiquement en mode auto lors de la création/mise à jour

---

## 25. Intégration e-MCeF (DGI Bénin)

### Description
Machine Électronique Certifiée de Facturation — système national de certification des factures au Bénin.

### Service `EmcefService` (829 lignes)
| Méthode | Description |
|---------|-------------|
| `isEnabled()` | Vérifie config active (token + NIM) |
| `getStatus()` | Status connexion API |
| `verifyIfu(string)` | Recherche contribuable par IFU 13 chiffres |
| `submitInvoice(Sale)` | Soumet facture + attend confirmation (2 min) |
| `confirmInvoice(Sale)` | Confirme facture soumise |
| `submitCreditNote(Sale)` | Soumet avoir avec ref facture originale |

### Flux de certification
1. Vente status → `completed`
2. `SaleObserver` → dispatch `CertifyInvoiceEmcef` job
3. Job appelle `EmcefService::submitInvoice()`
4. API e-MCeF retourne UID → attente 2 min → confirmation
5. Réponse : NIM, Code MECeF, QR code, Compteurs
6. Facture marquée `emcef_status = certified`

### Champs e-MCeF sur Sale
| Champ | Description |
|-------|-------------|
| `emcef_uid` | UID retourné par soumission |
| `emcef_submitted_at` | Timestamp soumission |
| `emcef_nim` | Numéro d'Identification Machine |
| `emcef_code_mecef` | Code MECeF DGI |
| `emcef_qr_code` | QR code à imprimer |
| `emcef_counters` | Compteurs DGI |
| `emcef_status` | pending / submitted / certified / error / cancelled |
| `emcef_certified_at` | Timestamp certification |
| `emcef_error` | Message d'erreur |

### Configuration (sur Company)
| Champ | Description |
|-------|-------------|
| `emcef_enabled` | Activation |
| `emcef_sandbox` | Mode test (URL développeur) |
| `emcef_nim` | NIM de l'entreprise |
| `emcef_token` | Token API |

### URLs API
- Production : `https://sygmef.impots.bj/sygmef-emcf`
- Sandbox : `https://developper.impots.bj/sygmef-emcf`

### Page `EmcefReport`
- Rapport mensuel e-MCeF
- Stats : factures certifiées, avoirs, ventilation par groupe de taxe
- Table des factures certifiées avec filtres

---

## 26. Intégration PPF / Factur-X (France)

### Service `FacturXService`
- Génère PDF hybride PDF/A-3 avec XML CII (Cross Industry Invoice)
- Utilise `horstoeko/zugferd` (ZUGFeRD) et `tiime/factur-x`
- Profil : BASIC
- Support factures (type 380) et avoirs (type 381)
- Mapping : vendeur (Company), acheteur (Customer), lignes d'articles

### Service `PpfService` (Integration/)
- Communication avec API PISTE (Chorus Pro)
- Auth OAuth2
- Endpoints qualification

### Champs PPF sur Sale
| Champ | Description |
|-------|-------------|
| `ppf_status` | Status PPF |
| `ppf_id` | ID PPF |
| `ppf_chorus_id` | ID Chorus Pro |
| `ppf_synced_at` | Timestamp sync |

---

## 27. Intégration URSSAF (France)

### Service `UrssafService` (Integration/)
- Récupération situation de compte
- Téléchargement attestation de vigilance
- Auth OAuth2

### Widget `UrssafOverviewWidget`
- Solde URSSAF
- Prochaine échéance
- Conformité (status attestations)

### Modèles
- `UrssafAccount` — comptes
- `UrssafContribution` — cotisations
- `UrssafPayment` — paiements

---

## 28. Module Paiements & Règlements

**Resource :** `PaymentResource` — masqué (les paiements se font depuis SaleResource)
**Modèle :** `Payment` (morphable: Sale ou Purchase)

### Champs
| Champ | Description |
|-------|-------------|
| `payable_type` / `payable_id` | Relation polymorphe (Sale ou Purchase) |
| `amount` | Montant |
| `payment_method` | cash / card / transfer / check / mobile_money |
| `payment_date` | Date |
| `reference` | Réf chèque/virement |
| `account_number` | Compte comptable (512000 Banque, 530000 Caisse...) |
| `notes` | Notes |
| `created_by` | FK User |

### Constantes
```php
METHODS: cash, card, transfer, check, mobile_money
ACCOUNTS: cash→530000, card→512100, transfer→512000, check→511200, mobile_money→512200
```

### Observer `PaymentObserver`
- Recalcule `amount_paid` et `payment_status` sur le parent (Sale/Purchase)
- Status : pending → partial → paid

### Route reçu de paiement
`GET /payments/{payment}/receipt` → PDF reçu

---

## 29. Module Rapports & Export

### Pages de rapports

| Page | Description |
|------|-------------|
| `AccountingReports` | Rapports TVA, résultats par période (mois/trimestre/année/custom) |
| `EmcefReport` | Rapport mensuel e-MCeF (factures certifiées, avoirs, ventilation) |
| `ReportsCenter` | Centre de rapports PDF : état stock, bilan comptable, journal ventes, journal achats, rapport TVA |
| `WarehouseComparison` | Comparatif ventes par boutique avec tendances |
| `ConsolidatedStock` | Vue stock consolidé multi-entrepôts |
| `BalanceGenerale` | Balance générale comptable |
| `AccountingCorrection` | Corrections comptables |
| `AccountingExport` | Export comptable |
| `JournalAudit` | Journal d'audit comptable |
| `UserGuide` | Guide utilisateur intégré |
| `ImportProducts` | Page d'import produits |

### Service `FecExportService` (515 lignes)
- Génère fichier FEC (Fichier des Écritures Comptables)
- 18 colonnes normalisées, séparées par pipe (|)
- Mode prioritaire : lecture depuis `accounting_entries` (immutable)
- Fallback : génération à la volée depuis ventes/achats (rétrocompatibilité)

### Contrôleurs de rapports

| Contrôleur | Routes |
|------------|--------|
| `AccountingReportController` | Rapports comptables PDF |
| `CashReportController` | Rapports caisse (PDF + Excel) |
| `StockReportController` | Rapport état des stocks |
| `SaleInvoiceController` | Facture vente PDF + prévisualisation |
| `PurchaseInvoiceController` | Facture achat PDF + prévisualisation |
| `QuotePdfController` | Devis PDF |
| `DeliveryNotePdfController` | Bon de livraison PDF |
| `ProductLabelController` | Étiquettes produits PDF (multi-colonnes) |

### Export ticket de caisse
`GET /sales/{saleId}/receipt` → ticket thermique 80mm

---

## 30. Widgets Dashboard

| Widget | Type | Description |
|--------|------|-------------|
| `StatsOverview` | Stat cards | CA total, produits en stock, alertes stock, clients |
| `SalesChart` | Chart | Graphique des ventes |
| `StockAlert` | List | Produits en alerte stock bas |
| `WarehouseOverview` | Table | Vue d'ensemble des entrepôts |
| `WarehouseStockSummary` | Stats | Résumé stock par entrepôt |
| `QuickActionsWidget` | Actions | Boutons d'accès rapide |
| `QuotesChartWidget` | Chart | Stats devis |
| `OrdersStatsWidget` | Stats | Stats commandes |
| `VatSummaryWidget` | Stat cards | Résumé TVA (collectée, déductible, solde) |
| `AttendanceChartWidget` | Chart | Stats présence RH |
| `HRStatsWidget` | Stats | Stats RH (employés, congés) |
| `UrssafOverviewWidget` | Stat cards | Solde URSSAF, échéances |

### Filtre entrepôt sur dashboard
- Les admins ont un select pour filtrer par entrepôt
- Les caissiers/vendeurs voient uniquement les données de leur(s) entrepôt(s)
- Event Livewire `warehouse-filter-changed` propagé aux widgets

---

## 31. Panel Super Admin

### Accessible via `/system`
| Élément | Description |
|---------|-------------|
| `CompanyResource` | Gestion de toutes les entreprises (création, activation, modules, e-MCeF) |
| `UserResource` | Gestion de tous les utilisateurs |
| `CompanyIntegrationResource` | Intégrations tierces (PPF, URSSAF) par entreprise |
| `TutorialVideoResource` | Vidéos tutorielles |
| `FeaturesManagement` | Activation/désactivation globale et par entreprise des 25+ fonctionnalités |

### Gestion des fonctionnalités
2 niveaux :
1. **Global** : activer/désactiver une fonctionnalité pour TOUTES les entreprises
2. **Par entreprise** : override spécifique via `Company.settings.modules`

---

## 32. Observers & Business Logic

| Observer | Événements |
|----------|-----------|
| `SaleObserver` | creating (AIB auto) / created (e-MCeF submit) / updating (AIB recalc) / updated (e-MCeF si completed) |
| `CompanyObserver` | created → crée 6 rôles par défaut + entrepôt principal |
| `PaymentObserver` | created/updated/deleted → recalcule amount_paid + payment_status |
| `ActivityObserver` | Enregistrement activités |
| `AuditObserver` | Journal d'audit |

---

## 33. Services Techniques

| Service | Lignes | Description |
|---------|--------|-------------|
| `EmcefService` | 829 | Intégration complète DGI Bénin e-MCeF |
| `PosService` | 533 | Logique POS centralisée (sessions, ventes, stock) |
| `AccountingEntryService` | 1184 | Génération écritures comptables auto |
| `FecExportService` | 515 | Export FEC conforme |
| `FacturXService` | 130 | Génération PDF Factur-X |
| `AttendanceService` | 338 | Pointage employés (GPS + QR) |
| `AccountingService` | ~55 | Règles auto catégorisation transactions |
| `CountryConfigService` | 159 | Configuration pays (TVA, devise, labels) |
| `GeoLocationService` | ? | Détection devise/pays par IP |
| `BarcodeGenerator` | ? | Génération codes-barres |
| `IntegrityCertificateService` | ? | Hash chaîné pour intégrité fiscale |
| `PpfService` (Integration/) | ? | Service PPF/Chorus Pro |
| `UrssafService` (Integration/) | ? | Service URSSAF |
| `FacturXGenerator` (Integration/) | ? | Générateur Factur-X (integration) |

---

## 34. Jobs (Async)

| Job | Description |
|-----|-------------|
| `CertifyInvoiceEmcef` | Certification async e-MCeF d'une facture |
| `GeneratePdfReport` | Génération PDF rapport en arrière-plan |

---

## 35. Mails

| Mail | Description |
|------|-------------|
| `InvoiceMail` | Envoi facture PDF (sale ou purchase) |
| `QuoteMail` | Envoi devis PDF |
| `InvitationMail` | Invitation à rejoindre une entreprise |

---

## 36. Import / Export

| Classe | Description |
|--------|-------------|
| `ProductImport` | Import produits depuis Excel |
| `ProductTemplateExport` | Export template Excel pour import |

---

## 37. Policies (Autorisations)

24 policies basées sur le pattern `BasePolicy` :

| Policy | Modèle |
|--------|--------|
| `AccountingCategoryPolicy` | AccountingCategory |
| `AccountingRulePolicy` | AccountingRule |
| `AttendanceLogPolicy` | AttendanceLog |
| `BankAccountPolicy` | BankAccount |
| `BankTransactionPolicy` | BankTransaction |
| `CommissionPolicy` | Commission |
| `CustomerPolicy` | Customer |
| `DeliveryNotePolicy` | DeliveryNote |
| `EmployeePolicy` | Employee |
| `InventoryPolicy` | Inventory |
| `InvitationPolicy` | Invitation |
| `LeaveRequestPolicy` | LeaveRequest |
| `ProductPolicy` | Product |
| `PurchasePolicy` | Purchase |
| `QuotePolicy` | Quote |
| `RecurringOrderPolicy` | RecurringOrder |
| `RolePolicy` | Role |
| `SalePolicy` | Sale |
| `SchedulePolicy` | Schedule |
| `StockTransferPolicy` | StockTransfer |
| `SupplierPolicy` | Supplier |
| `UserPolicy` | User |
| `WarehousePolicy` | Warehouse |

---

## 38. Routes Publiques & API

### Routes publiques (sans auth)
| Route | Description |
|-------|-------------|
| `/` | Welcome page |
| `/view/quote/{token}` | Affichage/acceptation/refus devis client |
| `/invitation/{token}` | Acceptation invitation |
| `/register`, `/login` | Auth standard |

### Routes authentifiées
| Route | Description |
|-------|-------------|
| `/sales/{sale}/invoice` | PDF facture vente |
| `/sales/{sale}/invoice/preview` | Prévisualisation HTML |
| `/sales/{saleId}/receipt` | Ticket de caisse 80mm |
| `/purchases/{purchase}/invoice` | PDF facture achat |
| `/purchases/{purchase}/invoice/preview` | Prévisualisation |
| `/quotes/{quote}/pdf` | PDF devis |
| `/delivery-notes/{deliveryNote}/pdf` | PDF bon de livraison |
| `/payments/{payment}/receipt` | Reçu de paiement PDF |
| `/admin/products/labels/print` | Étiquettes produits PDF |
| `/api/pos/*` | API Point de Vente (voir section POS) |
| `/admin/api/products?q=` | Recherche produits (legacy) |
| `/admin/api/product-code/{code}` | Scan code-barres (legacy) |
| `/admin/api/cash-sale` | Vente rapide caisse (legacy) |

---

## 39. Résumé des Modèles & Relations

```
Company (tenant)
├── users (M2M via company_user)
├── roles → permissions (M2M)
├── products → supplier, warehouses (M2M pivot qty), accountingCategory
├── customers
├── suppliers
├── sales → customer, warehouse, bankAccount, items → product, payments, creditNotes, cashSession
├── purchases → supplier, warehouse, bankAccount, items → product
├── quotes → customer, items
├── deliveryNotes → sale, customer, items
├── recurringOrders → customer, items
├── employees → warehouse, user, commissions, attendances, schedules, leaveRequests
├── warehouses → products (M2M), locations, inventories, stockTransfers
├── stockTransfers → sourceWarehouse, destWarehouse, items
├── inventories → warehouse, items
├── cashSessions → user, sales
├── bankAccounts → transactions
├── bankTransactions → bankAccount, category
├── accountingCategories
├── accountingRules → category
├── accountingEntries (immutables)
├── accountingSettings
├── payments (morph: sale|purchase)
├── invitations
├── companyIntegrations
├── urssafAccounts, urssafContributions, urssafPayments
├── stockMovements
└── auditLogs
```

**Modèle User :**
```
User
├── companies (M2M) — tenant
├── roles (M2M via model_has_roles, scoped by company_id)
├── warehouses (M2M via user_warehouse) — restriction d'accès
├── employee (HasOne via user_id)
├── cashSessions
└── activityLogs
```

---

## 40. Modules Activables par Entreprise

La page `FeaturesManagement` (Super Admin) gère 25+ fonctionnalités réparties en catégories :

### Core (Ventes)
| Module | Clé | Description |
|--------|-----|-------------|
| Ventes | `sales` | Gestion ventes et factures |
| Devis | `quotes` | Création et gestion devis |
| Bons de Livraison | `delivery_notes` | BL |
| Commandes Récurrentes | `recurring_orders` | Abonnements |

### Stock
| Module | Clé | Description |
|--------|-----|-------------|
| Produits | `products` | Catalogue produits |
| Achats | `purchases` | Achats fournisseurs |
| Fournisseurs | `suppliers` | Gestion fournisseurs |
| Entrepôts | `warehouses` | Multi-entrepôts |
| Transferts | `stock_transfers` | Transferts inter-sites |
| Inventaire | `inventory` | Gestion inventaires |

### POS
| Module | Clé | Description |
|--------|-----|-------------|
| Caisse (POS) | `pos` | Point de vente |
| Sessions de Caisse | `cash_sessions` | Ouverture/fermeture |

### RH
| Module | Clé | Description |
|--------|-----|-------------|
| Module RH | `hr` | Activation globale RH |
| Employés | `employees` | Gestion employés |
| Présences | `attendance` | Suivi présences |
| Planning | `schedules` | Planification horaires |
| Congés | `leave_requests` | Demandes congés |
| Commissions | `commissions` | Calcul commissions |

### Comptabilité
| Module | Clé | Description |
|--------|-----|-------------|
| Module Comptabilité | `accounting` | Activation globale compta |
| Comptes Bancaires | `bank_accounts` | Gestion comptes |
| Transactions | `bank_transactions` | Suivi transactions |
| Écritures Comptables | `accounting_entries` | Saisie écritures |
| Rapports Comptables | `accounting_reports` | Bilans et rapports |

### Intégrations & Système
| Module | Clé | Description |
|--------|-----|-------------|
| Journaux d'activité | `activity_logs` | Historique actions |
| Invitations | `invitations` | Gestion invitations |

---

## Statistiques du projet

| Métrique | Valeur |
|----------|--------|
| Modèles Eloquent | **48** |
| Filament Resources (admin) | **28** |
| Filament Resources (superadmin) | **4** |
| Filament Pages (admin) | **20+** |
| Filament Widgets | **12** |
| Services métier | **14** |
| Observers | **5** |
| Policies | **24** |
| Controllers | **14** |
| Jobs async | **2** |
| Mails | **3** |
| Migrations | **80+** |
| Lignes de code (services) | **4,000+** |

---

> **Ce document constitue l'audit complet de l'application FRECORP/GestStock au 19/02/2026.**
> Tous les modules, resources, services, relations et fonctionnalités ont été identifiés par lecture directe du code source.
