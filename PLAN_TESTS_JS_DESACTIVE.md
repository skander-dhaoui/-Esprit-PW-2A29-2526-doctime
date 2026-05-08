# 🧪 PLAN DE TEST - JAVASCRIPT DÉSACTIVÉ

**Objectif:** Prouver que les validations fonctionnent CÔTÉ SERVEUR sans JavaScript

---

## 📋 PROCÉDURE DE TEST

### ÉTAPE 1: Désactiver JavaScript

1. **Ouvrir DevTools** → F12
2. **Ouvrir Command Palette** → Ctrl+Shift+P (Windows/Linux) ou Cmd+Shift+P (Mac)
3. **Taper:** "disable javascript"
4. **Sélectionner:** "Debugger: Disable JavaScript" (option avec Ctrl+Shift+P)
5. **Rafraîchir la page** → F5

### ÉTAPE 2: Tester les 3 Formulaires Critiques

---

## TEST 1️⃣: UserController::updateProfil()

**URL:** `index.php?page=modifier_profil`

### Cas 1: EMAIL INVALIDE (SANS JavaScript)
1. Remplir le formulaire avec:
   - Nom: "Test"
   - Prénom: "User"
   - Email: `invalid-email` (SANS @)
   - Cliquer: **Enregistrer**

**Résultat ATTENDU:**
- ❌ Pas de soumission (validation SERVEUR)
- ✅ Message d'erreur: "Email invalide"
- ✅ Les données sont rappelées en $_SESSION['old']

### Cas 2: NOM VIDE
1. Remplir avec:
   - Nom: `` (VIDE)
   - Prénom: "User"
   - Email: `test@example.com`
   - Cliquer: **Enregistrer**

**Résultat ATTENDU:**
- ✅ Message d'erreur: "Nom obligatoire"

### Cas 3: DONNÉES VALIDES
1. Remplir avec:
   - Nom: "Dupont"
   - Prénom: "Jean"
   - Email: `jean@example.com` (unique)
   - Cliquer: **Enregistrer**

**Résultat ATTENDU:**
- ✅ Message de succès
- ✅ Redirection vers `/profil`

---

## TEST 2️⃣: UserController::changePassword()

**URL:** `index.php?page=profil` → Section "Changer le mot de passe"

### Cas 1: MOT DE PASSE ACTUEL INCORRECT
1. Remplir avec:
   - Mot de passe actuel: `wrongpass` (INCORRECT)
   - Nouveau: `NewPass123`
   - Confirmer: `NewPass123`
   - Cliquer: **Changer le mot de passe**

**Résultat ATTENDU:**
- ✅ Message d'erreur: "Mot de passe actuel incorrect"

### Cas 2: TROP COURT (< 8 chars)
1. Remplir avec:
   - Mot de passe actuel: `correct` (correct)
   - Nouveau: `Pass1` (5 chars)
   - Confirmer: `Pass1`
   - Cliquer: **Changer**

**Résultat ATTENDU:**
- ✅ Message d'erreur: "Minimum 8 caractères"

### Cas 3: SANS MAJUSCULE
1. Remplir avec:
   - Mot de passe actuel: `correct`
   - Nouveau: `password123` (pas de majuscule)
   - Confirmer: `password123`
   - Cliquer: **Changer**

**Résultat ATTENDU:**
- ✅ Message d'erreur: "Au moins une majuscule requise"

### Cas 4: SANS CHIFFRE
1. Remplir avec:
   - Mot de passe actuel: `correct`
   - Nouveau: `PasswordAbc` (pas de chiffre)
   - Confirmer: `PasswordAbc`
   - Cliquer: **Changer**

**Résultat ATTENDU:**
- ✅ Message d'erreur: "Au moins un chiffre requis"

### Cas 5: CONFIRMATION NE CORRESPOND PAS
1. Remplir avec:
   - Mot de passe actuel: `correct`
   - Nouveau: `NewPass123`
   - Confirmer: `DifferentPass456`
   - Cliquer: **Changer**

**Résultat ATTENDU:**
- ✅ Message d'erreur: "Les mots de passe ne correspondent pas"

### Cas 6: DONNÉES VALIDES
1. Remplir avec:
   - Mot de passe actuel: `correct` (le vrai mot de passe)
   - Nouveau: `NewSecurePass123`
   - Confirmer: `NewSecurePass123`
   - Cliquer: **Changer**

**Résultat ATTENDU:**
- ✅ Message de succès: "Mot de passe modifié avec succès"

---

## TEST 3️⃣: FrontController::medecinStoreDisponibilite()

**URL:** `index.php?page=medecin_disponibilites` → "Ajouter une disponibilité"

### Cas 1: JOUR VIDE
1. Remplir avec:
   - Jour: `` (VIDE)
   - Heure début: `09:00`
   - Heure fin: `12:00`
   - Cliquer: **Ajouter**

**Résultat ATTENDU:**
- ✅ Message d'erreur: "Jour obligatoire"

### Cas 2: FORMAT HEURE INVALIDE
1. Remplir avec:
   - Jour: `Lundi`
   - Heure début: `9:00` (format invalide - doit être HH:MM)
   - Heure fin: `12:00`
   - Cliquer: **Ajouter**

**Résultat ATTENDU:**
- ✅ Message d'erreur: "Format d'heure invalide (HH:MM)"

### Cas 3: HEURE FIN < HEURE DÉBUT
1. Remplir avec:
   - Jour: `Lundi`
   - Heure début: `14:00`
   - Heure fin: `10:00` (avant début)
   - Cliquer: **Ajouter**

**Résultat ATTENDU:**
- ✅ Message d'erreur: "L'heure de fin doit être après l'heure de début"

### Cas 4: DONNÉES VALIDES
1. Remplir avec:
   - Jour: `Mardi`
   - Heure début: `09:00`
   - Heure fin: `12:00`
   - Cliquer: **Ajouter**

**Résultat ATTENDU:**
- ✅ Message de succès: "Disponibilité ajoutée avec succès"
- ✅ Redirection vers liste des disponibilités
- ✅ Nouvelle disponibilité visible dans la liste

---

## TEST 4️⃣: ReviewController::store() (Avis)

**URL:** Page d'un événement → Section "Laisser un avis"

### Cas 1: TITRE VIDE
**Données:**
- Titre: `` (VIDE)
- Note: `5`
- Contenu: `Très bon événement, j'ai beaucoup apprécié!`
- Cliquer: **Soumettre avis**

**Résultat ATTENDU:**
- ✅ Erreur affichée: "Titre obligatoire"

### Cas 2: TITRE < 3 CARACTÈRES
**Données:**
- Titre: `OK` (2 chars)
- Note: `5`
- Contenu: `Très bon événement!`
- Cliquer: **Soumettre**

**Résultat ATTENDU:**
- ✅ Erreur: "Titre doit avoir au moins 3 caractères"

### Cas 3: CONTENU < 10 CARACTÈRES
**Données:**
- Titre: `Bon événement`
- Note: `5`
- Contenu: `Très bon!` (8 chars)
- Cliquer: **Soumettre**

**Résultat ATTENDU:**
- ✅ Erreur: "L'avis doit contenir au moins 10 caractères"

### Cas 4: NOTE INVALIDE
**Données:**
- Titre: `Bon événement`
- Note: `10` (hors 1-5)
- Contenu: `Très bon événement, j'ai beaucoup apprécié!`
- Cliquer: **Soumettre**

**Résultat ATTENDU:**
- ✅ Erreur: "La note doit être entre 1 et 5"

### Cas 5: CONTENU > 2000 caractères
**Données:**
- Titre: `Excellent`
- Note: `5`
- Contenu: ` (plus de 2000 caractères)`
- Cliquer: **Soumettre**

**Résultat ATTENDU:**
- ✅ Erreur: "L'avis ne peut pas dépasser 2000 caractères"

### Cas 6: DONNÉES VALIDES
**Données:**
- Titre: `Excellent événement!`
- Note: `5`
- Contenu: `C'était un excellent événement avec de bons intervenants`
- Cliquer: **Soumettre**

**Résultat ATTENDU:**
- ✅ Message de succès: "Avis publié!"
- ✅ Avis apparaît dans la liste

---

## 📊 RÉSUMÉ DES TESTS

| Test | Contrôleur | Méthode | Cases | Status |
|------|-----------|---------|-------|--------|
| 1 | UserController | updateProfil() | 3 | ⏳ À tester |
| 2 | UserController | changePassword() | 6 | ⏳ À tester |
| 3 | FrontController | medecinStoreDisponibilite() | 4 | ⏳ À tester |
| 4 | ReviewController | store() | 6 | ⏳ À tester |
| **TOTAL** | **3 controllers** | **4 méthodes** | **19 cases** | ⏳ |

---

## ✅ CHECKLIST AVANT TESTS

- [ ] JavaScript est bien DÉSACTIVÉ (vérifier dans DevTools)
- [ ] Navigateur est en mode privé (pas de cache)
- [ ] User est connecté et autorisé
- [ ] Base de données est accessible
- [ ] Serveur Apache tourne
- [ ] Logs PHP sont activés

---

## 📝 RÉSULTATS À DOCUMENTER

Pour chaque test:
1. **Screenshot** du message d'erreur
2. **URL** de la page
3. **Données saisies**
4. **Message d'erreur reçu**
5. **Date du test**

Exemple:
```
TEST: UserController::updateProfil() - EMAIL INVALIDE
URL: localhost/valorys/index.php?page=modifier_profil
Données: email="invalid-email"
Message: "Email invalide"
Date: 2026-05-08 14:30:00
Status: ✅ PASSED
```

---

## 🎓 PREUVE ACADÉMIQUE

**Ce document prouve:**
- ✅ Pas d'HTML5 validation (`required`, `email`, `pattern`, etc.)
- ✅ Validations côté SERVEUR uniquement
- ✅ Fonctionne SANS JavaScript
- ✅ PDO sécurisé (prepared statements)
- ✅ Conforme MVC/POO/PDO

**Pour la soumission:**
1. Archiver ce document
2. Ajouter screenshots des erreurs validées
3. Inclure dans le rapport final
4. Mentionner dans le README

---

**DEADLINE: AUJOURD'HUI (8 Mai 2026) ⏰**
