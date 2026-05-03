# 🎯 Architecture OOP DOCTIME Intelligent

## Approche 100% OOP sans Fonctions Globales

### ✅ Modèles (Classes d'Entités)

Tous les modèles utilisent une architecture OOP complète :

#### Structure Standard :
```php
class Sponsor {
    // 1️⃣ Propriétés Privées
    private ?int $id;
    private string $nom;
    
    // 2️⃣ Constructeur
    public function __construct(...$params) { ... }
    
    // 3️⃣ Destructeur
    public function __destruct() { ... }
    
    // 4️⃣ Getters
    public function getId(): ?int { return $this->id; }
    
    // 5️⃣ Setters (avec Fluent Interface)
    public function setNom(string $nom): self {
        $this->nom = trim($nom);
        return $this;
    }
    
    // 6️⃣ Méthodes Utilitaires
    public function toArray(): array { ... }
    public static function fromArray(array $data): self { ... }
}
```

### Modèles Disponibles :
- ✅ **Sponsor.php** — Entité sponsor (constructeur, destructeur, getters/setters)
- ✅ **Evenement.php** — Entité événement (constructeur, destructeur, getters/setters)
- ✅ **Participation.php** — Entité inscription (constructeur, destructeur, getters/setters)
- ✅ **EvenementAvance.php** — Fonctionnalités avancées (query builders, stats)

---

### ✅ Repositories (Gestion Base de Données)

Les repositories gèrent les opérations BD de manière OOP :

```php
class SponsorRepository {
    private PDO $pdo;
    
    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }
    
    public function findAll(): array { ... }
    public function findById(int $id): ?Sponsor { ... }
    public function save(Sponsor $sponsor): bool { ... }
    public function delete(int $id): bool { ... }
}
```

### Repositories Disponibles :
- ✅ **SponsorRepository.php** — CRUD Sponsor
- ✅ **EvenementRepository.php** — CRUD Événement  
- ✅ **ParticipationRepository.php** — CRUD Participation

---

### ✅ Helper (Utilitaires OOP)

Remplace les fonctions globales par des méthodes statiques :

#### Ancien Code (❌ À éviter) :
```php
$temps = temps_ecoule_fr($date);
$jours = duree_evenement_jours($debut, $fin);
```

#### Nouveau Code (✅ À utiliser) :
```php
$temps = Helper::tempsEcouleFr($date);
$jours = Helper::dureeEvenementJours($debut, $fin);
```

#### Méthodes Helper Disponibles :
```php
// Formatage & Conversion
Helper::tempsEcouleFr($date)          // "il y a 3 jours"
Helper::dureeEvenementJours($d1, $d2) // 5
Helper::formateDateFr($date)          // "26 avril 2026"
Helper::formatMontant(100.50)         // "100,50 €"

// Validation
Helper::validateEmail($email)         // true/false
Helper::validatePhoneFr($phone)       // true/false
Helper::validateUrl($url)             // true/false

// Nettoyage
Helper::sanitizeString($str)          // Trim + HTML escape
Helper::generateSlug($str)            // "mon-slug"
Helper::isEmpty($str)                 // true/false

// Variables d'Environnement
Helper::getEnv($key, $default)        // Lecture .env

// HTTP & Réponses
Helper::getParam($key, $default)      // Lecture GET sécurisée
Helper::postParam($key, $default)     // Lecture POST sécurisée
Helper::redirect($url)                // Redirection + exit
Helper::jsonResponse($array, 200)     // JSON + HTTP code
```

---

## 📊 Exemples d'Utilisation

### Créer un Sponsor
```php
$sponsor = new Sponsor(
    null,
    'ACME Corp',
    'contact@acme.fr',
    '0123456789',
    'https://acme.fr',
    'or',
    5000.00
);

$sponsor
    ->setNom('ACME Corp')
    ->setNiveau('platine')
    ->setMontant(10000.00);
```

### Récupérer un Sponsor
```php
$repo = new SponsorRepository();
$sponsor = $repo->findById(1);

if ($sponsor) {
    echo $sponsor->getNom();           // Getter
    echo Helper::formatMontant($sponsor->getMontant());
}
```

### Valider & Sauvegarder
```php
$email = Helper::getParam('email');

if (Helper::validateEmail($email)) {
    $sponsor->setEmail($email);
    $repo->save($sponsor);
}
```

### Convertir en Array
```php
$data = $sponsor->toArray();
echo json_encode($data);
```

---

## 🚀 Bonnes Pratiques

| ✅ À FAIRE | ❌ À ÉVITER |
|-----------|-----------|
| `$sponsor = new Sponsor()` | Pas de constructeur |
| `$sponsor->setNom('...')` | `$sponsor->nom = '...'` |
| `$sponsor->getId()` | Accès direct à `$id` |
| `Helper::formatDateFr()` | `temps_ecoule_fr()` |
| `new SponsorRepository()` | Requêtes SQL directes |
| Chaînage: `->set()->set()` | Appels multiples |

---

## 📂 Structure des Fichiers

```
config/
  ├── Helper.php              ✨ Classe OOP (statique)
  ├── helpers.php             📦 Wrappers compatibilité
  ├── database.php
  └── Validator.php

model/
  ├── Sponsor.php             ✅ OOP Complète
  ├── Evenement.php           ✅ OOP Complète
  ├── Participation.php       ✅ OOP Complète
  ├── EvenementAvance.php     ✅ OOP Complète
  ├── SponsorRepository.php   ✅ Repository OOP
  ├── EvenementRepository.php ✅ Repository OOP
  └── ParticipationRepository.php ✅ Repository OOP

controller/
  └── *.php                   Utilise les classes OOP
```

---

## 💡 Avantages de cette Approche

✅ **Pas de fonctions globales** — Tout est organisé en classes  
✅ **Type-safe** — `declare(strict_types=1)` partout  
✅ **Maintenabilité** — Code lisible et structuré  
✅ **Réutilisabilité** — Méthodes statiques disponibles partout  
✅ **Testabilité** — Facile à tester avec des mocks  
✅ **Scalabilité** — Architecture extensible  

---

## 📝 Notes de Migration

Si du code ancien utilise encore les fonctions globales :

```php
// Ancien (deprecated)
$temps = temps_ecoule_fr($date);

// Nouveau (recommandé)
$temps = Helper::tempsEcouleFr($date);
```

Les wrappers dans `helpers.php` garantissent la compatibilité jusqu'à la migration complète.

---

**Version**: 1.0  
**Date**: 26/04/2026  
**Architecture**: 100% OOP sans fonctions globales
