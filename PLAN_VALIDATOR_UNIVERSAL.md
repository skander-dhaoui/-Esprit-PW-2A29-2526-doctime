# 🎯 PLAN VALIDATOR UNIVERSAL - 40% → 100%

**Date:** 8 Mai 2026
**Status:** IN PROGRESS
**Objectif:** Ajouter Validator à TOUS les contrôleurs avec POST methods

---

## 📊 AUDIT DES CONTRÔLEURS

| # | Contrôleur | Méthodes POST | Status | Priorité |
|----|-----------|---------------|--------|----------|
| 1 | **AuthController** | login(), register() | ✅ DONE | - |
| 2 | **AdminController** | createUser(), updateUser() | ✅ DONE | - |
| 3 | **FrontController** | medecinStoreDisponibilite() | ⏳ TODO | CRITICAL |
| 4 | **UserController** | updateProfil(), updatePassword(), updateAvatar() | ⏳ TODO | CRITICAL |
| 5 | **ReviewController** | store(), update() | ⏳ TODO | HIGH |
| 6 | **EventController** | store(), update() | ⏳ TODO | HIGH |
| 7 | **ArticleController** | store(), update() | ⏳ TODO | HIGH |
| 8 | **CategorieController** | create(), update() | ⏳ TODO | MEDIUM |
| 9 | **RendezVousController** | store(), update() | ⏳ TODO | MEDIUM |
| 10 | **PatientController** | store(), update() | ⏳ TODO | MEDIUM |
| 11 | **MedecinController** | store(), update() | ⏳ TODO | MEDIUM |
| 12 | **OrdonnanceController** | store(), update() | ⏳ TODO | LOW |
| 13 | **SponsorController** | store(), update() | ⏳ TODO | LOW |
| 14 | **ParticipationController** | store() | ⏳ TODO | LOW |
| 15 | **CommandeController** | store() | ⏳ TODO | LOW |

---

## 🎯 PRIORITÉ PAR SEMAINE

### CRITICAL (This Week - 3 contrôleurs)
1. **FrontController::medecinStoreDisponibilite** - Créer disponibilité médecin
2. **UserController** - 3 méthodes (updateProfil, updatePassword, updateAvatar)
3. **ReviewController::store** - Soumettre un avis

### HIGH (Next 2 Days - 2 contrôleurs)
1. **EventController** - Créer/Modifier événement
2. **ArticleController** - Créer/Modifier article

### MEDIUM (Optional - Bonus)
1. CategorieController
2. PatientController
3. MedecinController

---

## 📋 PATTERN VALIDATOR À APPLIQUER

### Template Standard:
```php
public function store(): void {
    // 1. Vérifier méthode POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: index.php?page=form');
        exit;
    }

    // 2. Récupérer et nettoyer les données
    $titre    = trim($_POST['titre'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // 3. CRÉER VALIDATOR
    $validator = new Validator();
    
    // 4. AJOUTER VALIDATIONS
    $validator
        ->required('titre', $titre, 'Titre')
        ->minLength('titre', $titre, 3, 'Titre')
        ->maxLength('titre', $titre, 100, 'Titre')
        ->required('email', $email, 'Email')
        ->email('email', $email, 'Email')
        ->required('password', $password, 'Mot de passe')
        ->minLength('password', $password, 6, 'Mot de passe');

    // 5. RÉCUPÉRER LES ERREURS
    $errors = $validator->getErrors();

    // 6. VÉRIFICATIONS PERSONNALISÉES (si besoin)
    if (empty($errors['email'])) {
        if ($this->emailExists($email)) {
            $errors['email'] = 'Cet email existe déjà.';
        }
    }

    // 7. SI ERREURS → RETOUR AU FORMULAIRE
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['old']    = $_POST;
        header('Location: index.php?page=form');
        exit;
    }

    // 8. TOUT BON → CRÉER L'ENREGISTREMENT
    $id = $this->model->create([
        'titre'    => $titre,
        'email'    => $email,
        'password' => password_hash($password, PASSWORD_DEFAULT)
    ]);

    // 9. REDIRIGER AVEC SUCCÈS
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Créé avec succès!'];
    header('Location: index.php?page=list');
    exit;
}
```

---

## ✅ CHECKLIST POUR CHAQUE MÉTHODE

- [ ] Importer Validator (`require_once` ou use)
- [ ] Créer instance `$validator = new Validator()`
- [ ] Ajouter tous les champs à valider
- [ ] Récupérer les erreurs `$validator->getErrors()`
- [ ] Ajouter vérifications personnalisées (email unique, etc.)
- [ ] Stocker erreurs et olddata en SESSION
- [ ] Rediriger avec code 303 ou 307
- [ ] Tester avec données invalides
- [ ] Tester avec données valides

---

## 🔐 VALIDATIONS PAR TYPE DE CHAMP

### Email
```php
$validator->email('email', $email, 'Email')
```

### Mot de Passe
```php
$validator->minLength('password', $password, 6, 'Mot de passe')
          ->maxLength('password', $password, 50, 'Mot de passe')
// Vérifier force optionnelle:
if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password)) {
    $errors['password'] = 'Minimum 8 caractères avec majuscule';
}
```

### Nombres
```php
$validator->numeric('capacite', $capacite, 'Capacité')
          ->positiveNumber('capacite', $capacite, 'Capacité')
```

### Dates
```php
$validator->date('date_debut', $date, 'Date')
          ->dateAfter('date_fin', $date_fin, $date_debut, 'Date fin')
```

### Téléphone
```php
if (!empty($telephone) && !preg_match('/^[0-9\s\-\+\(\)]+$/', $telephone)) {
    $errors['telephone'] = 'Format de téléphone invalide';
}
```

---

## 🧪 PLAN DE TEST

### Phase 1: Tests sans JavaScript (PROOF FOR ACADEMIC)
```
1. Ouvrir DevTools (F12)
2. Press Ctrl+Shift+P → "disable javascript"
3. Submit form avec données INVALIDES
4. Vérifier que erreur s'affiche (serveur)
5. Submit form avec données VALIDES
6. Vérifier que succès (serveur)
```

### Données de Test
```php
// INVALIDES
[
    'email' => 'invalid-email',  // Pas @ - ERROR
    'password' => '123',         // < 6 chars - ERROR
    'titre' => '',               // Vide - ERROR
    'capacite' => 'abc'          // Pas nombre - ERROR
]

// VALIDES
[
    'email' => 'test@example.com',
    'password' => 'password123',
    'titre' => 'Mon titre',
    'capacite' => '100'
]
```

---

## 📝 ORDRE D'EXÉCUTION

### Jour 1 (TODAY):
1. ✅ FrontController::medecinStoreDisponibilite
2. ✅ UserController (3 methods)
3. ✅ ReviewController::store
4. ✅ Test ALL 3 with JS disabled

### Jour 2:
5. EventController::store & update
6. ArticleController::store & update
7. Test with JS disabled

### Jour 3 (Bonus):
8. CategorieController
9. PatientController
10. MedecinController

---

## 📚 RÉFÉRENCES

### Validator Methods:
- `required(field, value, label)`
- `minLength(field, value, min, label)`
- `maxLength(field, value, max, label)`
- `email(field, value, label)`
- `url(field, value, label)`
- `numeric(field, value, label)`
- `positiveNumber(field, value, label)`
- `integer(field, value, label)`
- `date(field, value, label)` → YYYY-MM-DD
- `dateAfter(field, value, minDate, label)`
- `inArray(field, value, allowedArray, label)`

### Fichiers Concernés:
- `config/Validator.php` - Classe de validation
- `config/database.php` - Connexion PDO
- `controllers/AuthController.php` - Exemple DÉJÀ IMPLÉMENTÉ ✅
- `controllers/AdminController.php` - Exemple DÉJÀ IMPLÉMENTÉ ✅

---

## 🎓 RÉSULTAT ATTENDU

**Avant:** 40% Validator (2 contrôleurs)
**Après:** 100% Validator (15 contrôleurs)
**Grade:** 20/20 - Validations serveur COMPLÈTES

**Pour l'académie:**
- ✅ Pas d'HTML5 validation
- ✅ Validations serveur partout
- ✅ Testé sans JavaScript
- ✅ PDO sécurisé
- ✅ Documentation complète

---

## 🚀 DÉMARRAGE IMMÉDIAT

**À faire maintenant:**
1. Éditer FrontController::medecinStoreDisponibilite
2. Éditer UserController (updateProfil, updatePassword, updateAvatar)
3. Éditer ReviewController::store
4. Tester les 3 avec JavaScript désactivé
5. Documenter les résultats

**Estimated Time:** 2-3 heures pour le premier lot

---

**OBJECTIF FINAL: Prêt pour soumission académique! 🎓**
