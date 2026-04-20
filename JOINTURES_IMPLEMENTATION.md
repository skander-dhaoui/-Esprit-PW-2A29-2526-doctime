# 📚 Implémentation des JOINTURES - Workshop Valorys

## ✅ Objectifs Réalisés

Cette implémentation respecte **tous les critères pédagogiques**:
- ✅ **Pas de contrôles HTML5** - Validation serveur uniquement (PHP)
- ✅ **Architecture MVC stricte** - Model / View / Controller
- ✅ **POO obligatoire** - Classes avec encapsulation
- ✅ **PDO obligatoire** - Prepared statements et paramètres liés

---

## 📁 Structure du Projet

```
valorys_Copie/
├── models/
│   ├── Categorie.php          # Modèle Catégorie
│   └── Produit.php            # Modèle Produit (+ méthodes JOINTURE)
├── controllers/
│   └── CategorieController.php # Contrôleur avec méthodes afficherProduits()
└── views/
    └── frontoffice/
        ├── searchProduitsByCategorie.php     # Logique du formulaire
        └── showProduitsByCategorie.html      # Affichage HTML
```

---

## 🔗 JOINTURES Implémentées

### 1️⃣ **INNER JOIN** - getProduitsByCategorie()

```php
// models/Produit.php
public function getProduitsByCategorie(int $categorieId): array {
    $sql = "SELECT p.id, p.nom, p.slug, p.description, p.prix, p.stock, 
                   p.image, p.prescription, p.status, p.created_at,
                   c.id AS categorie_id, c.nom AS categorie_nom
            FROM produits p
            INNER JOIN categories c ON p.categorie_id = c.id
            WHERE p.categorie_id = :categorie_id
            AND p.status = 'actif'
            ORDER BY p.nom ASC";
    
    return $this->db->query($sql, ['categorie_id' => $categorieId]);
}
```

**Principe:** Retourne les produits avec les informations jointes de leur catégorie.

---

### 2️⃣ **LEFT JOIN** - getAllCategories()

```php
// models/Produit.php
public function getAllCategories(): array {
    $sql = "SELECT c.id, c.nom, c.slug,
                   COUNT(p.id) AS nombre_produits
            FROM categories c
            LEFT JOIN produits p ON c.id = p.categorie_id AND p.status = 'actif'
            WHERE c.statut = 'actif' OR c.statut IS NULL
            GROUP BY c.id, c.nom, c.slug
            ORDER BY c.nom ASC";
    
    return $this->db->query($sql);
}
```

**Principe:** Retourne toutes les catégories même celles sans produits.

---

## 🎛️ Architecture MVC

### **1. Model (Modèles)**

#### `Categorie.php`
- `getById($id)` - Récupère une catégorie
- `getAll()` - Récupère toutes les catégories
- `create()`, `update()`, `delete()` - CRUD

#### `Produit.php`
- `getProduitsByCategorie($id)` ⭐ - **JOINTURE INNER JOIN**
- `getAllCategories()` ⭐ - **JOINTURE LEFT JOIN**
- `getById()` - JOIN avec catégorie
- `getAll()` - Récupère tous les produits

### **2. Controller (Contrôleurs)**

#### `CategorieController.php`
```php
class CategorieController {
    
    public function afficherProduits(int $idCategorie): array {
        // Validation serveur (pas HTML5)
        if ($idCategorie <= 0) return [];
        
        // Vérifier la catégorie existe
        $categorie = $this->categorieModel->getById($idCategorie);
        if (!$categorie) return [];
        
        // Retourner les produits via JOINTURE
        return $this->produitModel->getProduitsByCategorie($idCategorie);
    }
    
    public function afficherCategories(): array {
        // Retourner toutes les catégories avec comptage
        return $this->produitModel->getAllCategories();
    }
}
```

### **3. View (Vues)**

#### `showProduitsByCategorie.html`
- Formulaire de sélection de catégorie
- Affichage des produits filtrés
- Validation serveur complète
- Pas de HTML5 validation

---

## 🔐 Validation Serveur (PAS de HTML5)

### Exemple: Validation dans le Controller

```php
// CategorieController.php
private function validerCategorie(array $data): array {
    $errors = [];
    
    // ✅ Validation serveur stricte
    if (empty($data['nom']) || !is_string($data['nom'])) {
        $errors[] = 'Le nom de la catégorie est obligatoire.';
    } elseif (strlen(trim($data['nom'])) < 2) {
        $errors[] = 'Le nom doit contenir au moins 2 caractères.';
    }
    
    // Validation du slug
    if (!empty($data['slug'])) {
        if (!preg_match('/^[a-z0-9-]+$/', $data['slug'])) {
            $errors[] = 'Le slug doit contenir uniquement des lettres minuscules, chiffres et tirets.';
        }
    }
    
    return $errors;
}
```

---

## 🔒 Sécurité PDO

### Prepared Statements avec Paramètres Liés

```php
// ✅ SÉCURISÉ - Utilisation de paramètres
$sql = "SELECT * FROM produits WHERE categorie_id = :categorie_id";
$this->db->query($sql, ['categorie_id' => $categorieId]);

// ❌ DANGEREUX - Concaténation (injection SQL)
$sql = "SELECT * FROM produits WHERE categorie_id = " . $_GET['id'];
```

---

## 🌐 Routes Disponibles

### Accéder à la page de jointure:

```
http://localhost/valorys_Copie/index.php?page=search_products_category
http://localhost/valorys_Copie/index.php?page=jointure_produits
```

---

## 📊 Diagramme de la Jointure

```
┌─────────────────┐        ┌──────────────────┐
│    CATEGORIES   │        │    PRODUITS      │
├─────────────────┤        ├──────────────────┤
│ id (PK)         │◄───────│ id (PK)          │
│ nom             │ (FK)   │ nom              │
│ slug            │        │ slug             │
│ statut          │        │ categorie_id     │
└─────────────────┘        │ prix             │
                           │ stock           │
                           │ status          │
                           └──────────────────┘

JOINTURE:
  FROM produits p
  INNER JOIN categories c 
    ON p.categorie_id = c.id
```

---

## ✨ Fonctionnalités Implémentées

### ✅ Pour le Formulaire:
1. Récupère toutes les catégories avec COUNT des produits
2. Affiche un dropdown avec les catégories
3. Valide la sélection (côté serveur)

### ✅ Pour l'Affichage:
1. Récupère les produits de la catégorie sélectionnée (JOINTURE)
2. Affiche les informations du produit ET de la catégorie
3. Gère les cas d'erreur (catégorie inexistante, pas de produits)

---

## 🧪 Exemple de Résultat

Quand on sélectionne la catégorie "Hygiène" (id=1):

```
SELECT p.id, p.nom, p.prix, c.nom AS categorie_nom
FROM produits p
INNER JOIN categories c ON p.categorie_id = c.id
WHERE p.categorie_id = 1 AND p.status = 'actif'

Résultat:
┌────┬──────────────────┬────────┬──────────────┐
│ id │ nom              │ prix   │ categorie_nom│
├────┼──────────────────┼────────┼──────────────┤
│ 6  │ Gel douche Bodix │ 5.99   │ Hygiène      │
│ 7  │ Savon liquide    │ 2.99   │ Hygiène      │
│ 8  │ Déodorant spray  │ 3.50   │ Hygiène      │
└────┴──────────────────┴────────┴──────────────┘
```

---

## 📝 Résumé des Fichiers

| Fichier | Rôle | Type |
|---------|------|------|
| `Categorie.php` | Modèle catégories | Model |
| `Produit.php` | Modèle produits + JOINTURES | Model |
| `CategorieController.php` | Gestion de la logique | Controller |
| `searchProduitsByCategorie.php` | Traitement formulaire | View Logic |
| `showProduitsByCategorie.html` | Affichage HTML/CSS | View |

---

## 🎓 Points Clés Pédagogiques

✅ **MVC:** Séparation complète des responsabilités
✅ **POO:** Classes avec encapsulation et héritage
✅ **PDO:** Prepared statements obligatoires
✅ **Validation:** Serveur uniquement (pas HTML5)
✅ **JOINTURE:** INNER JOIN et LEFT JOIN
✅ **Sécurité:** Protection contre injections SQL

---

**Status:** ✅ Complètement implémenté et prêt pour les tests!
