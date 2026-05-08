# ✅ CHECKLIST DE VALIDATION FINALE - PROJET VALORYS

## 🎯 EXIGENCES OBLIGATOIRES

### 1️⃣ PAS DE CONTRÔLES HTML5

**❌ À SUPPRIMER:**
```html
<input required>
<input type="email">
<input type="password">
<input type="number">
<input type="url">
<input pattern="...">
<input minlength="8">
<input maxlength="50">
<select required>
```

**✅ À GARDER UNIQUEMENT:**
```html
<input type="text">
<input type="password">
<input type="checkbox">
<input type="radio">
<input type="file">
<select>
<textarea>
```

**Test:**
```bash
# Chercher les attributs à supprimer
grep -r "required" views/
grep -r "pattern=" views/
grep -r "minlength" views/
grep -r "maxlength" views/
grep -r 'type="email"' views/
grep -r 'type="number"' views/
grep -r 'type="url"' views/
```

---

### 2️⃣ VALIDATION CÔTÉ SERVEUR (PHP)

**Pour chaque formulaire:**

```php
// ✅ BON:
$validator = new Validator();
$validator
    ->required('email', 'Email')
    ->email('email', 'Email')
    ->minLength('password', 8, 'Mot de passe');

if ($validator->fails()) {
    $_SESSION['errors'] = $validator->errors();
    header('Location: ...');
}

// ❌ MAUVAIS:
if (empty($_POST['email'])) { ... }  // Pas d'erreur structurée
// Utiliser Validator pour cohérence
```

**Checklist par formulaire:**

- [ ] Login
- [ ] Register/Inscription
- [ ] Profil (modification)
- [ ] Événements (création/modification)
- [ ] Sponsors
- [ ] Articles/Blog
- [ ] Médecins
- [ ] Patients
- [ ] Commentaires
- [ ] Recherches

---

### 3️⃣ MODÈLE MVC STRICT

**Structure requise:**

```
valorys_Copie/
├── models/              (✅ Toutes les données)
│   ├── User.php
│   ├── Event.php
│   └── ...
├── controllers/         (✅ Toute la logique)
│   ├── AuthController.php
│   ├── EventController.php
│   └── ...
├── views/              (✅ UNIQUEMENT affichage)
│   ├── frontoffice/
│   │   ├── login.php
│   │   └── ...
│   └── backoffice/
│       └── ...
└── config/
    ├── database.php
    └── Validator.php
```

**Règles MVC:**

- [ ] Models: UNIQUEMENT interactions BD + logique métier
- [ ] Controllers: UNIQUEMENT validation + appel models + rendu vues
- [ ] Views: UNIQUEMENT affichage (pas de logique métier)
- [ ] Pas de logique dans les vues
- [ ] Pas d'accès BD direct dans contrôleurs
- [ ] Pas de HTML dans models

**Test:**

```bash
# Vérifier: pas de SQL dans views
grep -r "SELECT\|INSERT\|UPDATE\|DELETE" views/

# Vérifier: pas de HTML en dur dans controllers
grep -r "<html\|<body\|<div\|<p\|<form" controllers/

# Vérifier: pas de logique métier dans vues
grep -r "function\|class" views/ | grep -v "onclick="
```

---

### 4️⃣ PDO OBLIGATOIRE

**✅ À FAIRE:**

```php
// config/database.php
$db = Database::getInstance()->getConnection();

// Prepared statements
$stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
$stmt->execute([':email' => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Insertions
$stmt = $db->prepare("INSERT INTO users (...) VALUES (...)");
$stmt->execute([...]);
```

**❌ À NE PAS FAIRE:**

```php
// ❌ Requêtes directes (injection SQL!)
$query = "SELECT * FROM users WHERE email = '$email'";

// ❌ mysqli
$result = $conn->query($sql);

// ❌ Functions par défaut
mysql_query($sql);  // Fonction disparue!
```

**Test:**

```bash
# Vérifier: toutes les requêtes PDO
grep -r "prepare(" controllers/ | wc -l
grep -r "execute(" controllers/ | wc -l

# Chercher requêtes dangereuses
grep -r "SELECT\|INSERT\|UPDATE" controllers/ | grep -v "prepare("
grep -r "mysqli"
grep -r "mysql_"
```

---

## 📋 CHECKLIST PAR FONCTIONNALITÉ

### Authentification
- [ ] Login sans HTML5 validation
- [ ] Register sans HTML5 validation
- [ ] Validations serveur dans AuthController
- [ ] Email unique (validé en BD)
- [ ] Mot de passe hashé (password_hash)
- [ ] OAuth (Google/GitHub/Facebook)
- [ ] Facial Recognition
- [ ] Sessions sécurisées

### Backoffice
- [ ] Dashboard admin
- [ ] Gestion utilisateurs (CRUD)
- [ ] Gestion événements (CRUD)
- [ ] Gestion sponsors (CRUD)
- [ ] Gestion articles (CRUD)
- [ ] Gestion médecins
- [ ] Gestion patients
- [ ] Statistiques
- [ ] Logs d'activités
- [ ] Accès: admin uniquement

### Frontoffice
- [ ] Accueil avec featured events
- [ ] Liste événements avec recherche
- [ ] Détail événement
- [ ] Inscription événement
- [ ] Profil utilisateur
- [ ] Modification profil
- [ ] Sponsors
- [ ] Blog/Articles
- [ ] Médecins
- [ ] Chatbot événements
- [ ] Responsive mobile

### Sécurité
- [ ] Pas d'injection SQL (PDO)
- [ ] Pas de XSS (htmlspecialchars)
- [ ] Pas de CSRF (tokens si nécessaire)
- [ ] Authentification requise pour actions sensibles
- [ ] Autorisation: admin/user/guest
- [ ] Logs d'erreurs
- [ ] Pas de données sensibles en logs

---

## 🧪 TESTS À EFFECTUER

### Tests de Sécurité

**1. Injection SQL**
```
POST /index.php?page=login
email: ' OR '1'='1
password: anything
=> Doit échouer (PDO + validation)
```

**2. XSS**
```
Créer événement avec titre: <script>alert('XSS')</script>
=> Doit afficher le texte, pas exécuter script
```

**3. Validation côté serveur**
```
Envoyer formulaire avec JavaScript désactivé
=> Validation serveur doit fonctionner
```

### Tests Fonctionnels

- [ ] Login/Logout
- [ ] Register utilisateur
- [ ] OAuth (Google, GitHub, Facebook)
- [ ] Facial Recognition
- [ ] Profil: voir, modifier
- [ ] Événements: voir, chercher, s'inscrire, se désinscrire
- [ ] Sponsors: voir tous
- [ ] Articles: voir, commenter
- [ ] Backoffice: accès admin uniquement
- [ ] CRUD Complet: utilisateurs, événements, sponsors

### Tests UI/UX

- [ ] Desktop (1920px)
- [ ] Tablet (768px)
- [ ] Mobile (375px)
- [ ] Navigateurs: Chrome, Firefox, Safari, Edge
- [ ] Pas d'erreurs JavaScript (console)
- [ ] Chargement rapide

---

## 📊 GIT & DOCUMENTATION

### Commits bien formatés

```bash
git commit -m "feat(auth): ajouter validations serveur login"
git commit -m "feat(backoffice): implémenter dashboard"
git commit -m "fix(validation): supprimer HTML5 attributes"
git commit -m "refactor(mvc): déplacer logique vers models"
git commit -m "test(security): vérifier PDO partout"
```

### Project Board GitHub

- [ ] Créer Issues pour chaque tâche
- [ ] Assigner à vous-même
- [ ] Marquer "In Progress" pendant travail
- [ ] Marquer "Done" quand terminé
- [ ] Décrire ce qu'on fait et comment
- [ ] Lier commits aux Issues

### Documentation

- [ ] README.md mis à jour
- [ ] Instructions d'installation
- [ ] Architecture documentée
- [ ] Endpoints API documentés
- [ ] Schéma BD
- [ ] Guide des validations

---

## 🎓 POINTS D'ÉVALUATION

La note dépend de:

1. **Respect des contraintes** (40%)
   - [ ] Pas de HTML5 validation (0 points si présent!)
   - [ ] Validation serveur partout (5 points/module)
   - [ ] Modèle MVC respecté (-5 points/écart)
   - [ ] PDO obligatoire (0 si mysqli/query directe)

2. **Complétude fonctionnelle** (30%)
   - [ ] Backoffice complet
   - [ ] CRUD pour modules majeurs
   - [ ] Sécurité et authentification
   - [ ] Fonctionnalités avancées (OAuth, Facial, etc.)

3. **Qualité code** (20%)
   - [ ] POO: classes et interfaces
   - [ ] Code lisible et commenté
   - [ ] Pas de code dupliqué
   - [ ] Noms explicites (classes, méthodes, variables)

4. **Git & Collaboration** (10%)
   - [ ] Commits bien nommés (feat/fix/refactor)
   - [ ] Project Board rempli
   - [ ] Documentation code
   - [ ] README.md complet

---

## 🚀 AVANT VALIDATION

1. ✅ Faire tourner le projet localement
2. ✅ Tester login/logout
3. ✅ Tester 3+ formulaires
4. ✅ Vérifier backoffice
5. ✅ Lancer tests sécurité
6. ✅ Vérifier Git commits
7. ✅ Pousser vers GitHub
8. ✅ Remplir Project Board
9. ✅ Mettre à jour README
10. ✅ Déclarer prêt pour validation

---

**Bonne chance! 🎉**
