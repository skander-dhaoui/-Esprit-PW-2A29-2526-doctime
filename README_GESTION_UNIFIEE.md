# 🎯 Gestion Unifiée : User + Event + Pharmacie

## Vue d'ensemble

Ce module intègre la gestion des **Utilisateurs**, **Événements** et **Pharmacie** dans un système unifié et cohérent.

## 📁 Fichiers Créés

### Contrôleurs
- **`controllers/GestionUnifieeController.php`** - Logique métier unifiée
  - `tableauBordUnifie()` - Tableau de bord global
  - `creerParticipationAvecPharmacie()` - Créer participation avec produits
  - `obtenirParticipationsUtilisateur()` - Récupérer participations
  - `listerEvenementsAvecParticipations()` - Lister événements
  - `gererProduitsParticipation()` - Gérer produits associés
  - `genererRapportIntegre()` - Génération de rapports

### Modèles
- **`models/GestionUnifiee.php`** - Modèle de données
  - Gestion des tables de liaison
  - Export/Import JSON
  - Récupération de données complètes

### Vues
- **`views/backoffice/gestion-unifiee.php`** - Interface HTML
  - Tableau de bord
  - Gestion des utilisateurs
  - Gestion des événements
  - Gestion de la pharmacie
  - Gestion des participations
  - Rapports intégrés

### API
- **`api/gestion-unifiee.php`** - Endpoints REST
  - `/dashboard` - Tableau de bord
  - `/utilisateurs` - Liste utilisateurs
  - `/evenements` - Liste événements
  - `/participations` - Gestion participations
  - `/produits` - Gestion produits
  - `/rapport` - Génération rapports
  - `/export` - Export données
  - `/import` - Import données

### Configuration
- **`config/routes-gestion-unifiee.php`** - Routeur et routes
- **`migrations/001_gestion_unifiee_init.sql`** - Schéma BD

### Styles
- **`assets/css/gestion-unifiee.css`** - Feuille de styles complète

## 🚀 Utilisation

### 1. Initialiser les tables
```php
$model = new GestionUnifiee();
$model->initialiserTables();
```

### 2. Créer une participation avec produits
```php
$controller = new GestionUnifieeController();
$result = $controller->creerParticipationAvecPharmacie(
    $userId,
    $eventId,
    [$produitId1, $produitId2]
);
```

### 3. Récupérer les participations d'un utilisateur
```php
$result = $controller->obtenirParticipationsUtilisateur($userId);
```

### 4. Lister les événements avec statistiques
```php
$result = $controller->listerEvenementsAvecParticipations([
    'statut' => 'planifie'
]);
```

### 5. Générer un rapport
```php
$result = $controller->genererRapportIntegre(
    '2026-01-01',
    '2026-05-10'
);
```

## 📊 Endpoints API

### GET /api/gestion-unifiee.php?action=dashboard
Récupère le tableau de bord global
```json
{
  "statut": "succes",
  "data": {
    "utilisateurs": {...},
    "evenements": {...},
    "pharmacie": {...},
    "participations": {...}
  }
}
```

### POST /api/gestion-unifiee.php?action=participations
Crée une participation avec produits
```json
{
  "user_id": 1,
  "event_id": 1,
  "produits": [1, 2, 3]
}
```

### GET /api/gestion-unifiee.php?action=rapport
Génère un rapport
```
?action=rapport&debut=2026-01-01&fin=2026-05-10
```

### GET /api/gestion-unifiee.php?action=export
Exporte les données
```
?action=export&format=json
```

## 📋 Structure BD

### Table: `participation_produits`
Lie les participations aux produits de pharmacie
- `id` - Identifiant unique
- `participation_id` - Clé étrangère vers participations
- `produit_id` - Clé étrangère vers produits
- `quantity` - Quantité distribuée
- `createdAt` - Date de création

### Vue: `vw_participations_complete`
Vue complète des participations avec détails utilisateur, événement et produits

### Vue: `vw_events_statistics`
Statistiques des événements (participants, produits distribués, etc.)

## 🎨 Interface Utilisateur

### Tableau de Bord
- 📊 Statistiques globales (utilisateurs, événements, pharmacie, participations)
- 📈 Graphiques visuels
- 🔔 Mises à jour en temps réel

### Onglets
1. **Tableau de Bord** - Vue globale
2. **Utilisateurs** - Gestion utilisateurs avec filtres
3. **Événements** - Gestion événements en grille
4. **Pharmacie** - Gestion produits avec stocks
5. **Participations** - Gestion participations
6. **Rapports** - Génération rapports et exports

## 🔄 Flux d'utilisation

1. **Créer un utilisateur** → Formulaire dans l'onglet "Utilisateurs"
2. **Créer un événement** → Formulaire dans l'onglet "Événements"
3. **Ajouter des produits** → Pharmacie
4. **Créer une participation** → Utilisateur + Événement + Produits
5. **Générer un rapport** → Télécharger PDF/JSON

## 💾 Export/Import

### Exporter
```php
$model->exporterEnJSON('/path/to/export.json');
```

### Importer
```php
$model->importerJSON('/path/to/export.json');
```

## 🔐 Sécurité

- ✅ Utilisation de PDO préparé pour prévenir les injections SQL
- ✅ Validation des données
- ✅ Gestion des transactions
- ✅ Gestion d'erreurs robuste

## 📝 Notes

- Tous les produits d'une participation sont optionnels
- Les participations peuvent être annulées
- Les statistiques sont calculées en temps réel
- Les rapports supportent les filtres par date

## 🆘 Support

Pour des questions ou problèmes, consulter la documentation technique ou les logs système.
