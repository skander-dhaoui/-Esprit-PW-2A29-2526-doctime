# 📚 JOINTURES Articles ↔ Replies - Workshop Valorys

## ✅ Objectifs Réalisés

Implémentation complète des **jointures entre Articles et Replies** avec respectde tous les critères pédagogiques:
- ✅ **Pas de contrôles HTML5** - Validation serveur uniquement (PHP)
- ✅ **Architecture MVC stricte** - Model / View / Controller
- ✅ **POO obligatoire** - Classes avec encapsulation
- ✅ **PDO obligatoire** - Prepared statements et paramètres liés

---

## 📁 Structure du Projet

```
valorys_Copie/
├── models/
│   └── Article.php        # Modèle Article (+ méthodes JOINTURE)
├── controllers/
│   └── ArticleController.php  # Contrôleur avec jointures
└── views/
    └── frontoffice/
        ├── searchRepliesByArticle.php     # Logique du formulaire
        └── showRepliesByArticle.html      # Affichage HTML
```

---

## 🔗 JOINTURES Implémentées

### 1️⃣ **INNER JOIN** - getRepliesByArticle()

```php
// models/Article.php
public function getRepliesByArticle(int $articleId): array {
    $sql = "SELECT r.id, r.replay, r.status, r.created_at,
                   u.id AS user_id, u.nom AS user_nom, u.prenom AS user_prenom, u.email,
                   a.id AS article_id, a.titre AS article_titre
            FROM replies r
            INNER JOIN articles a ON r.article_id = a.id
            LEFT JOIN users u ON r.user_id = u.id
            WHERE r.article_id = :article_id AND r.status = 'approuvee'
            ORDER BY r.created_at DESC";
    
    return $this->db->query($sql, ['article_id' => $articleId]);
}
```

**Principe:** Retourne les commentaires avec les données jointes de l'article ET de l'utilisateur.

---

### 2️⃣ **LEFT JOIN** - getArticlesWithReplyCount()

```php
// models/Article.php
public function getArticlesWithReplyCount(): array {
    $sql = "SELECT a.id, a.titre, a.slug,
                   COUNT(r.id) AS nombre_replies
            FROM articles a
            LEFT JOIN replies r ON a.id = r.article_id AND r.status = 'approuvee'
            WHERE a.status = 'publié'
            GROUP BY a.id, a.titre, a.slug
            ORDER BY a.titre ASC";
    
    return $this->db->query($sql);
}
```

**Principe:** Retourne tous les articles même ceux sans commentaires.

---

### 3️⃣ **JOINTURE Complète** - getArticleWithReplies()

```php
// models/Article.php
public function getArticleWithReplies(int $id): array {
    // Récupère article + charges les replies dans un tableau
    $article = /* ... */;
    $article['replies'] = $this->getRepliesByArticle($id);
    return $article;
}
```

**Principe:** Retourne un article complet avec tous ses commentaires.

---

## 🎛️ Architecture MVC

### **1. Model (Article.php)**

#### Nouvelles Méthodes:
- `getRepliesByArticle($id)` ⭐ - **JOINTURE INNER JOIN + LEFT JOIN**
  - Récupère replies jointes avec articles ET utilisateurs
  
- `getArticlesWithReplyCount()` ⭐ - **JOINTURE LEFT JOIN + COUNT**
  - Affiche tous les articles avec comptage des replies
  
- `getArticleWithReplies($id)` ⭐ - **JOINTURE Complète**
  - Retourne article complet avec tableau de replies

---

### **2. Controller (ArticleController.php)**

#### Nouvelles Méthodes:
```php
public function afficherReplies(int $idArticle): array {
    // Validation serveur
    if ($idArticle <= 0) return [];
    
    // Récupérer les replies via JOINTURE
    return $this->articleModel->getRepliesByArticle($idArticle);
}

public function afficherArticles(): array {
    // Retourner tous les articles avec comptage
    return $this->articleModel->getArticlesWithReplyCount();
}

public function afficherArticleComplet(int $id): ?array {
    // Retourner article complet avec replies
    return $this->articleModel->getArticleWithReplies($id);
}
```

---

### **3. View (Vues)**

#### `showRepliesByArticle.html`
- Formulaire de sélection d'article
- Affichage des commentaires filtrés
- Validation serveur complète
- Pas de HTML5 validation

---

## 🔐 Validation Serveur (PAS de HTML5)

### Exemple: Validation dans le formulaire

```php
// searchRepliesByArticle.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedArticleId = isset($_POST['article']) ? (int)$_POST['article'] : null;
    
    // ✅ Validation serveur stricte
    if ($selectedArticleId === null || $selectedArticleId <= 0) {
        $error = 'Veuillez sélectionner un article valide.';
    } else {
        // Vérifier l'article existe
        $article = $articleCtrl->afficherArticleComplet($selectedArticleId);
        if (!$article) {
            $error = 'L\'article sélectionné n\'existe pas ou n\'est pas publié.';
        }
    }
}
```

---

## 🔒 Sécurité PDO

### Prepared Statements avec Paramètres Liés

```php
// ✅ SÉCURISÉ - Utilisation de paramètres
$sql = "SELECT * FROM replies WHERE article_id = :article_id";
$this->db->query($sql, ['article_id' => $articleId]);

// ❌ DANGEREUX - Concaténation (injection SQL)
$sql = "SELECT * FROM replies WHERE article_id = " . $_GET['id'];
```

---

## 🌐 Routes Disponibles

### Accéder à la page de jointure:

```
http://localhost/valorys_Copie/index.php?page=search_replies_article
http://localhost/valorys_Copie/index.php?page=jointure_articles
```

---

## 📊 Diagramme de la Jointure

```
┌──────────────────┐         ┌─────────────────┐
│    ARTICLES      │         │    REPLIES      │
├──────────────────┤         ├─────────────────┤
│ id (PK)          │◄────────│ id (PK)         │
│ titre            │ (FK)    │ article_id      │
│ contenu          │         │ user_id         │
│ auteur_id        │         │ replay (text)   │
│ status (enum)    │         │ status (enum)   │
└──────────────────┘         │ created_at      │
                             └─────────────────┘

JOINTURE:
  FROM replies r
  INNER JOIN articles a 
    ON r.article_id = a.id
  LEFT JOIN users u
    ON r.user_id = u.id
```

---

## ✨ Fonctionnalités Implémentées

### ✅ Pour le Formulaire:
1. Récupère tous les articles avec COUNT des replies
2. Affiche un dropdown avec les articles
3. Valide la sélection (côté serveur)

### ✅ Pour l'Affichage:
1. Récupère les replies de l'article sélectionné (JOINTURE)
2. Affiche l'auteur et la date de chaque reply
3. Gère les cas d'erreur (article inexistant, pas de commentaires)
4. Affiche le contenu du commentaire avec nl2br pour sauter les lignes

---

## 🧪 Exemple de Résultat

Quand on sélectionne l'article "Les bases de PHP" (id=3):

```
SELECT r.*, u.nom AS user_nom, a.titre AS article_titre
FROM replies r
INNER JOIN articles a ON r.article_id = a.id
LEFT JOIN users u ON r.user_id = u.id
WHERE r.article_id = 3 AND r.status = 'approuvee'

Résultat:
┌────┬───────────────────────┬───────────┬────────────┐
│ id │ replay                │ user_nom  │ created_at │
├────┼───────────────────────┼───────────┼────────────┤
│ 1  │ Très bon article!     │ Ali       │ 2026-04-18 │
│ 2  │ Merci pour l'explication│ Fatima  │ 2026-04-17 │
│ 3  │ Peut-on avoir plus...  │ Mohamed  │ 2026-04-16 │
└────┴───────────────────────┴───────────┴────────────┘
```

---

## 📝 Résumé des Fichiers

| Fichier | Rôle | Type |
|---------|------|------|
| `Article.php` | Modèles + JOINTURES | Model |
| `ArticleController.php` | Gestion de la logique | Controller |
| `searchRepliesByArticle.php` | Traitement formulaire | View Logic |
| `showRepliesByArticle.html` | Affichage HTML/CSS | View |

---

## 🎓 Points Clés Pédagogiques

✅ **Relation one-to-many:** 1 article → many replies
✅ **INNER JOIN:** Récupère replies jointes avec article
✅ **LEFT JOIN:** Affiche articles même sans replies
✅ **COUNT + GROUP BY:** Comptage des replies par article
✅ **Multiple JOINs:** Articles + Replies + Users dans une seule requête
✅ **MVC:** Séparation complète des responsabilités
✅ **POO:** Classes avec encapsulation et héritage
✅ **PDO:** Prepared statements obligatoires
✅ **Validation:** Serveur uniquement (pas HTML5)
✅ **Sécurité:** Protection contre injections SQL

---

## 📚 Différence avec Produits/Catégories

| Aspect | Produits/Catégories | Articles/Replies |
|--------|-------------------|-----------------|
| **Relation** | many-to-one | one-to-many |
| **Clé étrangère** | produits.categorie_id | replies.article_id |
| **Jointure principale** | INNER (produits ont catégories) | INNER (replies liées) |
| **Comptage** | LEFT JOIN | LEFT JOIN + GROUP BY |
| **Cas d'erreur** | Pas de catégorie → article orphelin | Pas de reply → OK |

---

**Status:** ✅ Complètement implémenté et prêt pour les tests!

**Accès:** `http://localhost/valorys_Copie/index.php?page=jointure_articles`
