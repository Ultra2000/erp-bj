# Guide d'utilisation de la Douchette DST X-9100

## Configuration Matérielle

### Branchement
1. Branchez votre douchette DST Wired Laser Barcode Scanner X-9100 sur un port USB libre
2. Windows devrait la reconnaître automatiquement comme un clavier HID
3. Aucun driver spécifique n'est nécessaire

### Test rapide
- Ouvrez le Bloc-notes Windows
- Scannez un code-barres
- Le code devrait apparaître automatiquement suivi d'un Enter

## Utilisation dans le POS

### 🎯 Scan automatique
Le système détecte automatiquement les scans de votre douchette :

1. **Ouvrez la page POS** (Point de Vente)
2. **Ne cliquez dans aucun champ de saisie**
3. **Scannez simplement un produit** avec votre douchette
4. Le produit sera automatiquement ajouté au panier !

### 📊 Indicateurs visuels
Lors du scan, vous verrez :
- 🔵 **Indicateur bleu en haut** : Affiche le code en cours de scan
- ✅ **Message vert** : Produit trouvé et ajouté au panier
- ❌ **Message rouge** : Produit non trouvé

### 🔊 Retour sonore
- **Bip aigu** : Produit ajouté avec succès
- **Bip grave** : Erreur (produit non trouvé)
- **Vibration** : Si vous utilisez une tablette/appareil mobile

### ⌨️ Raccourcis clavier
- **F2** : Focus sur la recherche manuelle
- **F3** : Focus sur le champ code-barres
- **F12** : Encaisser la vente
- **ESC** : Vider le panier

## Configuration des Codes-Barres Produits

### Vérifier/Modifier les codes-barres
1. Allez dans **Produits** > Liste des produits
2. Éditez un produit
3. Remplissez le champ **"Code-barres"** avec le code-barres physique du produit
   - Le champ **"Code interne"** est généré automatiquement par le système
   - Le champ **"Code-barres"** doit contenir le code-barres de l'étiquette physique
4. Types de codes-barres supportés :
   - EAN-13 (codes-barres européens standards)
   - EAN-8
   - Code 128
   - UPC-A / UPC-E
   - Code 39
   - et autres formats standards

> 💡 **Astuce** : Vous pouvez utiliser votre douchette pour scanner directement dans le champ "Code-barres" lors de l'édition d'un produit !

### Générer des codes-barres
Si vos produits n'ont pas de code-barres :
1. Le système peut générer automatiquement des codes internes
2. Imprimez des étiquettes avec ces codes
3. Utilisez votre douchette pour les scanner

## Dépannage

### La douchette ne fonctionne pas
- ✅ Vérifiez que la douchette est bien branchée (LED allumée)
- ✅ Testez dans le Bloc-notes : scannez un code → doit s'afficher
- ✅ Rafraîchissez la page POS (F5)
- ✅ Ne cliquez pas dans un champ de saisie avant de scanner

### Les produits ne sont pas trouvés
- ✅ Vérifiez que le champ **"Code-barres"** du produit correspond au code scanné
- ✅ Le champ "Code interne" (généré automatiquement) est différent du "Code-barres"
- ✅ Le code doit être exact (respecter les espaces et caractères)
- ✅ Consultez les logs : ouvrez la console navigateur (F12) pour voir le code scanné

### Le scan est trop lent
- ✅ Normal : la douchette peut prendre 100-200ms pour scanner
- ✅ L'indicateur bleu vous montre la progression
- ✅ Attendez le bip avant de scanner le produit suivant

### Scans multiples accidentels
- ✅ Le système évite les doublons automatiques
- ✅ Si un produit est déjà dans le panier, sa quantité augmente
- ✅ Vous pouvez ajuster les quantités manuellement dans le panier

## Conseils d'utilisation

### 🚀 Workflow optimal
1. Ouvrez une session de caisse
2. Laissez le curseur dans la zone principale (pas dans les champs)
3. Scannez tous les produits du client
4. Vérifiez le panier
5. Appuyez sur F12 ou cliquez "Encaisser"

### 💡 Astuces
- **Position de scan** : 5-15 cm du code-barres
- **Angle** : Légèrement incliné pour éviter les reflets
- **Vitesse** : Balayez le laser d'un mouvement fluide
- **Étiquettes abîmées** : Scannez plusieurs fois ou saisissez manuellement

### 📱 Alternative mobile
Si la douchette n'est pas disponible :
- Cliquez sur le bouton **"Scanner"** (icône caméra)
- Utilisez la caméra de votre appareil
- Placez le code-barres dans le cadre

## Support Technique

### Logs de débogage
Pour diagnostiquer un problème :
1. Ouvrez la console (F12 dans le navigateur)
2. Allez dans l'onglet "Console"
3. Scannez un produit
4. Vous verrez : `🔍 Recherche code-barres: [CODE]`

### Configuration avancée
Les paramètres de détection sont dans le fichier :
`resources/views/filament/caisse/pages/point-of-sale.blade.php`

Paramètres modifiables :
- `timeSinceLastKey > 100` : Seuil de détection saisie humaine vs scanner
- `scannerBuffer.length >= 3` : Longueur minimale d'un code-barres valide
- `setTimeout(..., 200)` : Délai de timeout du buffer

---

**Version** : 1.0  
**Modèle testé** : DST Wired Laser Barcode Scanner X-9100  
**Interface** : USB HID (Human Interface Device)
