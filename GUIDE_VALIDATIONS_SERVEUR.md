# 🔒 GUIDE: Validations Serveur Obligatoires

**Statut:** CRITIQUE - Doit être implémenté partout avant la soumission.

## 📋 Checklist des Contrôleurs

### ✅ TERMINÉS
- [x] **AuthController.php**
  - ✅ `login()` - Validations avec Validator
  - ✅ `register()` - Validations avec Validator

### ⏳ EN COURS
- [ ] **AdminController.php**
  - [ ] `createUser()` - À améliorer
  - [ ] `updateUser()` - À améliorer
  
- [ ] **UserController.php**
  - [ ] `updateProfil()` - À améliorer
  
- [ ] **FrontController.php**
  - [ ] `createEvent()` - À ajouter
  - [ ] `updateEvent()` - À ajouter

### 📌 AUTRES CONTRÔLEURS
- [ ] CategorieController
- [ ] ArticleController
- [ ] ReviewController
- [ ] RendezVousController
- [ ] OrdonnanceController
- [ ] PatientController
- [ ] MedecinController
- [ ] Etc.

---

## 🔧 PATTERN À SUIVRE

### 1️⃣ Importer Validator
```php
require_once __DIR__ . '/../config/Validator.php';
```

### 2️⃣ Nettoyer les données
```php
// ========== NETTOYAGE DES DONNÉES ==========
$nom    = trim($_POST['nom'] ?? '');
$email  = trim($_POST['email'] ?? '');
$prix   = trim($_POST['prix'] ?? '');
```

### 3️⃣ Valider avec Validator
```php
// ========== VALIDATION AVEC VALIDATOR ==========
$validator = new Validator();
$validator
    ->required('nom', $nom, 'Nom')
    ->minLength('nom', $nom, 3, 'Nom')
    ->required('email', $email, 'Email')
    ->email('email', $email, 'Email')
    ->numeric('prix', $prix, 'Prix');

// Récupérer les erreurs
$errors = $validator->getErrors();
```

### 4️⃣ Ajouter les validations personnalisées
```php
// Vérifications supplémentaires si nécessaire
if (empty($errors['email'])) {
    // Vérifier que l'email n'existe pas déjà
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    if ($stmt->fetch()) {
        $errors['email'] = 'Cet email est déjà utilisé.';
    }
}
```

### 5️⃣ Gérer les erreurs
```php
// ========== GESTION DES ERREURS ==========
if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    $_SESSION['old']    = $_POST;
    // Rediriger vers le formulaire
    header('Location: index.php?page=form_page&action=create');
    exit;
}
```

### 6️⃣ Traiter les données valides
```php
// ========== TRAITEMENT DES DONNÉES VALIDES ==========
// Procéder à la création/mise à jour en base de données
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("INSERT INTO table_name (nom, email, prix) VALUES (:nom, :email, :prix)");
$stmt->execute([
    ':nom'   => $nom,
    ':email' => $email,
    ':prix'  => $prix
]);

// Rediriger après succès
$_SESSION['success'] = 'Enregistrement créé avec succès.';
header('Location: index.php?page=list');
exit;
```

---

## ✅ MÉTHODES DISPONIBLES DANS Validator

```php
// Champ obligatoire
->required('field', $value, 'Label')

// Longueur minimale
->minLength('field', $value, 5, 'Label')

// Longueur maximale
->maxLength('field', $value, 100, 'Label')

// Format email
->email('field', $value, 'Label')

// Format URL
->url('field', $value, 'Label')

// Nombre
->numeric('field', $value, 'Label')

// Nombre positif
->positiveNumber('field', $value, 'Label')

// Entier
->integer('field', $value, 'Label')

// Date (format YYYY-MM-DD)
->date('field', $value, 'Label')

// Date après une autre date
->dateAfter('field', $value, 'field_debut', 'Label', 'Label Début')

// Valeur dans une liste
->inArray('field', $value, ['val1', 'val2'], 'Label')
```

---

## 📝 AFFICHAGE DES ERREURS DANS LES VUES

### Dans la vue PHP:
```php
<?php
// Récupérer les erreurs de la session
$errors = $_SESSION['errors'] ?? [];
$old    = $_SESSION['old']    ?? [];

// Nettoyer la session
unset($_SESSION['errors'], $_SESSION['old']);
?>

<!-- Affichage des erreurs globales -->
<?php if (!empty($errors['__form'])): ?>
    <div class="alert alert-danger">
        <?= htmlspecialchars($errors['__form']) ?>
    </div>
<?php endif; ?>

<!-- Formulaire -->
<form method="POST" action="index.php?page=form&action=create">
    <!-- Champ avec erreur -->
    <div class="mb-3">
        <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
        <input type="text" 
               id="nom" 
               name="nom" 
               class="form-control <?= !empty($errors['nom']) ? 'is-invalid' : '' ?>"
               value="<?= htmlspecialchars($old['nom'] ?? '') ?>"
               placeholder="Entrez le nom">
        <?php if (!empty($errors['nom'])): ?>
            <div class="invalid-feedback d-block">
                <?= htmlspecialchars($errors['nom']) ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Autre champ -->
    <div class="mb-3">
        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
        <input type="text" 
               id="email" 
               name="email" 
               class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>"
               value="<?= htmlspecialchars($old['email'] ?? '') ?>"
               placeholder="email@exemple.com">
        <?php if (!empty($errors['email'])): ?>
            <div class="invalid-feedback d-block">
                <?= htmlspecialchars($errors['email']) ?>
            </div>
        <?php endif; ?>
    </div>

    <button type="submit" class="btn btn-primary">Envoyer</button>
</form>
```

---

## 🧪 TESTS À FAIRE

Pour chaque formulaire:

### 1. Tester avec JavaScript DÉSACTIVÉ
- Ouvrir DevTools (F12)
- Appuyer sur Ctrl+Shift+P → "disable javascript"
- Rafraîchir la page
- Soumettre le formulaire avec données invalides
- ✅ Les erreurs serveur DOIVENT s'afficher

### 2. Tester chaque validation
```
Test: Champ vide
- Laisser un champ obligatoire vide
- Soumettre
- ✅ Erreur "Le champ ... est obligatoire"

Test: Email invalide
- Entrer "abc" dans un champ email
- Soumettre
- ✅ Erreur "Le champ ... doit être une adresse e-mail valide"

Test: Données valides
- Remplir tous les champs correctement
- Soumettre
- ✅ Redirection vers page de succès
```

---

## 📂 FICHIERS À MODIFIER (PRIORITÉ)

1. **AuthController.php** ✅ TERMINÉ
   - login() ✅
   - register() ✅

2. **AdminController.php** ⏳ À FAIRE
   - createUser() 
   - updateUser()
   - Autres actions CRUD

3. **UserController.php** ⏳ À FAIRE
   - updateProfil()
   - updatePassword()

4. **FrontController.php** ⏳ À FAIRE
   - createEvent()
   - updateEvent()
   - submitReview()
   - submitComment()

5. **CategorieController.php** ⏳ À FAIRE
   - create()
   - edit()

6. **ArticleController.php** ⏳ À FAIRE
   - create()
   - edit()
   - update()

7. **ReviewController.php** ⏳ À FAIRE
   - create()
   - update()

---

## 🎯 RÉSUMÉ

- ❌ **JAMAIS** de HTML5 `required`, `minlength`, `maxlength`, etc.
- ✅ **TOUJOURS** valider côté serveur avec `Validator`
- ✅ **TOUJOURS** stocker les erreurs dans `$_SESSION['errors']`
- ✅ **TOUJOURS** afficher les erreurs dans la vue
- ✅ **TOUJOURS** tester avec JavaScript DÉSACTIVÉ

**Délai:** Une semaine avant la deadline !
