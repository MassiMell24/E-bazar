# Système d'historique des achats et ventes

## Date: 26 décembre 2025

## Modifications apportées

### Fonctionnalités
1. **Historique des articles vendus**: Les articles vendus restent dans la liste du vendeur jusqu'à ce qu'il les retire manuellement
2. **Historique des articles achetés**: Les articles achetés restent dans la liste de l'acheteur jusqu'à suppression manuelle
3. **Confirmation de réception**: Les acheteurs peuvent confirmer avoir reçu l'article (affiche "✓ Produit reçu")
4. **Indépendance des historiques**: Quand un vendeur retire un article de sa liste, il reste visible pour l'acheteur
5. **Suppression manuelle**: Les acheteurs peuvent supprimer définitivement les articles de leur historique

### Changements techniques

#### Base de données
- Ajout de la colonne `buyer_confirmed_reception` (BOOLEAN) : indique si l'acheteur a confirmé la réception
- Ajout de la colonne `seller_archived` (BOOLEAN) : permet au vendeur de masquer l'article de sa liste sans le supprimer
- Migration automatique avec fallback pour compatibilité

#### Modèle (Ad.php)
- Nouvelle méthode `confirmReception($id, $buyerId)` pour marquer un article comme reçu
- Nouvelle méthode `archiveForSeller($id, $sellerId)` pour archiver côté vendeur
- Modification de `getUserSold()` pour exclure les articles archivés par le vendeur
- `getUserPurchased()` affiche tous les articles achetés (même ceux confirmés reçus)
- Méthodes helper pour gérer la migration progressive

#### Contrôleur (DashboardController.php)
- `confirmReceived()` : marque l'article comme reçu (ne supprime plus)
- `deletePurchasedAd()` : supprime définitivement un article côté acheteur
- `deleteSoldAd()` : archive pour le vendeur au lieu de supprimer complètement

#### Vues
- **purchased.php**: 
  - Affiche "✓ Produit reçu" en vert pour les articles confirmés
  - Bouton "Confirmer la réception" (vert) pour les articles non encore reçus
  - Bouton "Supprimer" (rouge) toujours visible pour supprimer de l'historique
- **sold.php**: 
  - Bouton "Retirer de ma liste" pour archiver l'article de la vue du vendeur

### Migration

Pour appliquer la migration sur une base de données existante:

```bash
php scripts/migrate_buyer_archived.php
```

**Note**: Le code inclut une migration automatique avec fallback, donc l'application continue de fonctionner même si les colonnes n'existent pas encore (elles seront créées automatiquement lors de la première utilisation).

### Compatibilité
- Rétrocompatible avec les bases de données existantes
- Migration progressive avec gestion d'erreurs
- Fallback automatique en cas de colonnes manquantes

## Flux utilisateur

### Pour les acheteurs
1. Achète un article → apparaît dans "Articles achetés"
2. Reçoit l'article → clique sur "Confirmer la réception"
3. L'article affiche maintenant "✓ Produit reçu" en vert
4. Peut garder l'article dans son historique indéfiniment
5. Peut cliquer sur "Supprimer" pour retirer l'article de son historique

### Pour les vendeurs
1. Vend un article → apparaît dans "Articles vendus"
2. L'article reste visible avec la date de vente et le mode de livraison
3. Peut cliquer sur "Retirer de ma liste" pour le masquer de sa vue
4. L'article reste visible pour l'acheteur même après retrait

### Indépendance totale
- Le vendeur qui retire un article de sa liste n'affecte PAS l'acheteur
- L'acheteur qui supprime un article ne l'enlève que de SA vue
- Chaque utilisateur gère son propre historique indépendamment

