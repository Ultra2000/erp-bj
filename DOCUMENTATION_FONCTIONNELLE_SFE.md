# DOCUMENTATION FONCTIONNELLE DU SYSTÈME DE FACTURATION ÉLECTRONIQUE (SFE)

## FRECORP

**Éditeur :** MDE Informatique & Réseau  
**Version :** 1.0  
**Date :** Février 2026  
**Objet :** Documentation fonctionnelle pour homologation e-MCeF — DGI Bénin  

---

## TABLE DES MATIÈRES

1. [Présentation générale du SFE](#1-présentation-générale-du-sfe)
2. [Processus de facturation](#2-processus-de-facturation)
3. [Certification e-MCeF](#3-certification-e-mcef)
4. [Gestion de la TVA et de l'AIB](#4-gestion-de-la-tva-et-de-laib)
5. [Annulation et avoirs](#5-annulation-et-avoirs)
6. [Sécurité et intégrité des factures](#6-sécurité-et-intégrité-des-factures)
7. [Cycle de vie d'une facture](#7-cycle-de-vie-dune-facture)
8. [Gestion des incidents](#8-gestion-des-incidents)
9. [Impression et archivage](#9-impression-et-archivage)
10. [Glossaire](#10-glossaire)

---

## 1. PRÉSENTATION GÉNÉRALE DU SFE

### 1.1 Objet

Le présent document décrit le fonctionnement du Système de Facturation Électronique (SFE) dénommé **FRECORP**, développé par la société **MDE Informatique & Réseau**, dans le cadre de la conformité aux exigences de la plateforme **e-MCeF** (Mécanisme de Certification Électronique de Facturation) de la Direction Générale des Impôts (DGI) du Bénin.

### 1.2 Périmètre fonctionnel

Le SFE FRECORP assure les fonctions suivantes :

* L'émission de factures normalisées conformes à la réglementation fiscale béninoise ;
* Le calcul automatique de la TVA selon les catégories e-MCeF et de l'AIB ;
* La transmission automatique de chaque facture à la plateforme e-MCeF de la DGI pour certification ;
* La réception et l'intégration du **Code MECeF** et du **QR Code fiscal** sur chaque facture ;
* La garantie de non-modification des factures certifiées ;
* La gestion des avoirs (notes de crédit) en conformité avec la réglementation ;
* L'archivage sécurisé de l'ensemble des factures émises et certifiées.

### 1.3 Identification de l'entreprise

L'entreprise utilisatrice du SFE est identifiée dans le système par :

* Sa **raison sociale** ;
* Son **Identifiant Fiscal Unique (IFU)** ;
* Son **Numéro d'Identification MECeF (NIM)**, attribué par la DGI ;
* Son adresse, téléphone, email et logo.

---

## 2. PROCESSUS DE FACTURATION

### 2.1 Création d'une facture

L'utilisateur crée une facture en renseignant les informations suivantes :

* **Client** : nom ou raison sociale, IFU (le cas échéant), adresse, téléphone ;
* **Lignes de facture** : pour chaque article ou service, la désignation, la quantité, le prix unitaire hors taxe et la catégorie de TVA applicable ;
* **Mode de paiement** : espèces, carte bancaire, mobile money ou paiement mixte.

Pour les ventes au comptoir (sans identification du client), un client générique est utilisé.

### 2.2 Numérotation séquentielle

Chaque facture reçoit un numéro unique, attribué automatiquement par le système, selon le format :

* **Factures de vente** : `FAC-YYYY-XXXXX` (exemple : FAC-2026-00042)
* **Avoirs** : `AVR-YYYY-XXXXX` (exemple : AVR-2026-00003)

La numérotation est **séquentielle**, **chronologique** et **sans interruption** pour chaque année civile, conformément aux exigences réglementaires.

### 2.3 Calcul automatique des montants

Lors de la validation d'une facture, le système calcule automatiquement :

* Le **montant Hors Taxe (HT)** de chaque ligne ;
* Le **montant de TVA** par ligne, selon la catégorie de TVA du produit ;
* Le **total HT** de la facture (somme des lignes) ;
* Le **total TVA** de la facture (somme des TVA par catégorie) ;
* Le **total TTC** (total HT + total TVA) ;
* Le **montant AIB** (Acompte sur Impôt sur les Bénéfices), calculé sur le total HT ;
* Le **montant net à payer** (total TTC + montant AIB).

---

## 3. CERTIFICATION e-MCeF

### 3.1 Principe

Chaque facture émise par le SFE est automatiquement transmise à la plateforme **e-MCeF** de la DGI pour certification. Cette transmission est déclenchée immédiatement après la validation de la facture, sans intervention manuelle de l'utilisateur.

### 3.2 Données transmises à la plateforme

Le SFE transmet à la plateforme e-MCeF les données suivantes pour chaque facture :

* Le type de facture (facture de vente ou avoir) ;
* Les informations du client (nom, IFU le cas échéant) ;
* Le détail des articles facturés (désignation, quantité, prix unitaire, montant HT) ;
* Les montants de TVA ventilés par catégorie (A, B, C, D, E, F) ;
* Le montant AIB et le groupe AIB applicable ;
* Le montant total de la facture ;
* Le mode de paiement ;
* La référence de la facture d'origine (dans le cas d'un avoir).

### 3.3 Réponse de la plateforme

En cas de certification réussie, la plateforme e-MCeF retourne :

* Le **Code MECeF** : code de certification unique attribué à la facture ;
* Le **QR Code fiscal** : code à scanner permettant la vérification de la facture ;
* Les **compteurs** de la plateforme.

Le SFE intègre automatiquement ces éléments dans la facture et enregistre la date et l'heure de certification.

### 3.4 Statut de certification

Chaque facture dispose d'un statut de certification :

| Statut | Description |
|--------|-------------|
| **En attente** | La facture a été créée mais n'a pas encore été transmise ou la réponse est en cours |
| **Certifiée** | La facture a été certifiée par la plateforme e-MCeF |
| **Erreur** | La transmission a échoué (voir section 8 — Gestion des incidents) |

### 3.5 Vérification d'IFU

Le SFE permet de vérifier la validité d'un numéro IFU (13 chiffres) auprès de la DGI. Cette vérification permet de s'assurer que l'IFU renseigné pour un client est valide avant l'émission de la facture.

---

## 4. GESTION DE LA TVA ET DE L'AIB

### 4.1 Catégories de TVA

Le SFE applique les catégories de TVA définies par la réglementation e-MCeF du Bénin :

| Catégorie | Taux | Description |
|-----------|------|-------------|
| **A** | 18 % | Taxable — taux normal |
| **B** | 0 % | Exonéré de TVA |
| **C** | 0 % | Exportation |
| **D** | 0 % | Régime particulier |
| **E** | 0 % | Taxe sur Prestations de Services (TPS) |
| **F** | 0 % | Hors champ / Autre |

Chaque article ou service est rattaché à une catégorie de TVA. Le montant de TVA est calculé automatiquement en fonction de cette catégorie.

Les montants de TVA sont ventilés par catégorie dans la facture et transmis de manière détaillée à la plateforme e-MCeF.

### 4.2 AIB — Acompte sur Impôt sur les Bénéfices

L'AIB est un prélèvement fiscal obligatoire au Bénin. Le SFE le calcule automatiquement selon les règles suivantes :

| Situation | Taux AIB applicable | Groupe |
|-----------|----------------------|--------|
| Le client dispose d'un IFU valide | **1 %** du montant HT | A |
| Le client ne dispose pas d'IFU | **5 %** du montant HT | B |
| Vente à l'exportation | Exempté | — |
| Vente au détail (comptoir, si option activée) | Exempté | — |

Le montant AIB est ajouté au total TTC pour former le **montant net à payer** par le client.

Le groupe AIB (A ou B) est transmis à la plateforme e-MCeF avec les données de la facture.

---

## 5. ANNULATION ET AVOIRS

### 5.1 Principe de non-modification

Conformément à la réglementation, une facture validée et certifiée par la plateforme e-MCeF **ne peut pas être modifiée**. Le numéro de facture, les montants, la date et le client sont définitivement figés dès la certification.

### 5.2 Annulation d'une facture

Lorsqu'une facture doit être annulée, le SFE procède comme suit :

1. La facture d'origine est marquée comme **annulée** ;
2. Un **avoir** (note de crédit) est automatiquement généré, reprenant les mêmes lignes que la facture d'origine avec des montants négatifs ;
3. L'avoir est numéroté selon le format `AVR-YYYY-XXXXX` ;
4. L'avoir est transmis à la plateforme e-MCeF pour certification, avec la référence de la facture d'origine ;
5. La plateforme certifie l'avoir et retourne un Code MECeF et un QR Code distincts.

### 5.3 Traçabilité

Le lien entre la facture d'origine et l'avoir est conservé dans le système, permettant de reconstituer l'historique complet de l'opération.

---

## 6. SÉCURITÉ ET INTÉGRITÉ DES FACTURES

### 6.1 Garantie d'intégrité

Le SFE garantit l'intégrité de chaque facture émise par un mécanisme de **chaînage cryptographique**. Chaque facture est liée à la précédente par une empreinte numérique unique, formant une chaîne inaltérable.

Ce mécanisme garantit que :

* Aucune facture ne peut être modifiée après certification sans rompre la chaîne ;
* Aucune facture ne peut être insérée, supprimée ou réordonnée dans la séquence ;
* Toute altération est détectable.

### 6.2 Non-altération

Les champs critiques d'une facture certifiée (numéro, montant total, date, client) sont rendus **immuables** par le système. Toute tentative de modification est bloquée.

### 6.3 Accès sécurisé

L'accès au SFE est protégé par une authentification par identifiant et mot de passe. Seuls les utilisateurs habilités peuvent émettre des factures.

### 6.4 Traçabilité des opérations

Le système enregistre un journal des opérations couvrant :

* La création de chaque facture ;
* Les certifications e-MCeF ;
* Les annulations et émissions d'avoirs ;
* Les tentatives de transmission en erreur.

---

## 7. CYCLE DE VIE D'UNE FACTURE

Le traitement d'une facture au sein du SFE FRECORP se déroule selon les étapes suivantes :

```
┌─────────────────────────────────────────────────────┐
│  1. CRÉATION DE LA FACTURE                          │
│  L'utilisateur saisit le client, les articles,      │
│  les quantités et le mode de paiement.              │
└──────────────────────┬──────────────────────────────┘
                       ▼
┌─────────────────────────────────────────────────────┐
│  2. CALCULS AUTOMATIQUES                            │
│  - Calcul des montants HT, TVA (par catégorie),    │
│    TTC et AIB                                       │
│  - Attribution du numéro de facture séquentiel      │
│  - Chaînage cryptographique avec la facture         │
│    précédente                                       │
└──────────────────────┬──────────────────────────────┘
                       ▼
┌─────────────────────────────────────────────────────┐
│  3. TRANSMISSION À LA PLATEFORME e-MCeF             │
│  - Envoi automatique des données à la DGI           │
│  - Réception du Code MECeF et du QR Code fiscal     │
│  - Enregistrement de la date de certification       │
└──────────────────────┬──────────────────────────────┘
                       ▼
┌─────────────────────────────────────────────────────┐
│  4. FACTURE CERTIFIÉE                               │
│  - Code MECeF et QR Code intégrés à la facture     │
│  - Facture imprimable conforme                      │
│  - Facture non modifiable                           │
│  - Archivage dans le système                        │
└─────────────────────────────────────────────────────┘
```

En cas de besoin d'annulation, un **avoir** est émis et certifié selon le même processus (voir section 5).

---

## 8. GESTION DES INCIDENTS

### 8.1 Indisponibilité de la plateforme e-MCeF

En cas d'indisponibilité temporaire de la plateforme e-MCeF :

1. La facture est enregistrée dans le système avec le statut **« En attente »** ;
2. Le motif de l'échec est conservé ;
3. L'utilisateur est informé que la certification n'a pas abouti ;
4. Une nouvelle tentative de transmission peut être déclenchée par l'utilisateur.

La facture reste dans le système et conserve son numéro séquentiel. Elle sera certifiée dès que la plateforme sera de nouveau accessible.

### 8.2 Erreur de transmission

En cas de rejet par la plateforme e-MCeF (données incorrectes, jeton expiré, etc.) :

* Le message d'erreur retourné par la plateforme est enregistré ;
* L'utilisateur est informé de la nature de l'erreur ;
* Après correction de la cause, une nouvelle tentative peut être effectuée.

### 8.3 Erreur de données

Le système valide les données saisies avant la transmission :

* Vérification de la présence des champs obligatoires (client, articles, montants) ;
* Vérification de la cohérence des montants ;
* Vérification du format de l'IFU (13 chiffres) le cas échéant.

En cas d'anomalie, la facture n'est pas émise et un message explicite informe l'utilisateur.

---

## 9. IMPRESSION ET ARCHIVAGE

### 9.1 Impression des factures normalisées

Chaque facture certifiée peut être imprimée. La facture imprimée comporte obligatoirement :

* Les informations de l'entreprise émettrice (raison sociale, IFU, NIM, adresse) ;
* Les informations du client (nom, IFU le cas échéant) ;
* Le numéro de facture ;
* La date d'émission ;
* Le détail des articles (désignation, quantité, prix unitaire, montant HT, catégorie TVA) ;
* Le total HT, le détail de la TVA par catégorie, le total TTC ;
* Le montant AIB et le groupe AIB ;
* Le montant net à payer ;
* Le mode de paiement ;
* Le **Code MECeF** ;
* Le **QR Code fiscal** ;
* Les mentions légales requises par la réglementation béninoise.

### 9.2 Archivage

L'ensemble des factures émises (factures de vente et avoirs) est archivé dans le système de manière pérenne. Chaque facture archivée conserve :

* Toutes les données de facturation (lignes, montants, taxes) ;
* Le statut de certification e-MCeF ;
* Le Code MECeF et le QR Code fiscal ;
* La date de certification ;
* Le lien avec l'avoir le cas échéant.

Les factures archivées sont consultables et réimprimables à tout moment.

---

## 10. GLOSSAIRE

| Terme | Définition |
|-------|------------|
| **AIB** | Acompte sur Impôt sur les Bénéfices — prélèvement fiscal applicable aux factures au Bénin |
| **Avoir** | Note de crédit émise en annulation d'une facture |
| **Code MECeF** | Code de certification unique attribué par la plateforme e-MCeF à chaque facture |
| **DGI** | Direction Générale des Impôts du Bénin |
| **e-MCeF** | Mécanisme de Certification Électronique de Facturation — plateforme de la DGI |
| **HT** | Hors Taxe — montant avant application de la TVA |
| **IFU** | Identifiant Fiscal Unique — numéro fiscal à 13 chiffres attribué aux contribuables béninois |
| **NIM** | Numéro d'Identification MECeF — identifiant attribué à l'entreprise par la DGI pour l'utilisation de la plateforme e-MCeF |
| **QR Code fiscal** | Code à réponse rapide apposé sur la facture, permettant la vérification de la certification |
| **SFE** | Système de Facturation Électronique |
| **TTC** | Toutes Taxes Comprises — montant incluant la TVA |
| **TVA** | Taxe sur la Valeur Ajoutée |

---

**Éditeur :** MDE Informatique & Réseau  
**Logiciel :** FRECORP — Système de Facturation Électronique  
**Conformité :** Plateforme e-MCeF — Direction Générale des Impôts (DGI) du Bénin  

**Fin du document**
