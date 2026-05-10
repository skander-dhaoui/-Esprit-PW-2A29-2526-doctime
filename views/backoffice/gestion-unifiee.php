<?php
/**
 * Vue - Gestion Unifiée (Utilisateurs + Événements + Pharmacie)
 * Tableau de bord et gestion intégrée
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Unifiée - Utilisateurs, Événements & Pharmacie</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/gestion-unifiee.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container-gestion-unifiee">
        <!-- Navigation -->
        <nav class="navbar-gestion">
            <div class="navbar-brand">
                <h1>🎯 Gestion Unifiée</h1>
                <p class="subtitle">Utilisateurs • Événements • Pharmacie</p>
            </div>
            <ul class="nav-tabs">
                <li><a href="#tab-dashboard" class="tab-link active">Tableau de Bord</a></li>
                <li><a href="#tab-utilisateurs" class="tab-link">Utilisateurs</a></li>
                <li><a href="#tab-evenements" class="tab-link">Événements</a></li>
                <li><a href="#tab-pharmacie" class="tab-link">Pharmacie</a></li>
                <li><a href="#tab-participations" class="tab-link">Participations</a></li>
                <li><a href="#tab-rapports" class="tab-link">Rapports</a></li>
            </ul>
        </nav>

        <div class="gestion-content">
            <!-- TAB: Tableau de Bord -->
            <section id="tab-dashboard" class="tab-content active">
                <div class="dashboard-grid">
                    <!-- Statistiques Utilisateurs -->
                    <div class="stat-card utilisateurs-card">
                        <div class="stat-header">
                            <h3>👥 Utilisateurs</h3>
                            <span class="stat-badge">TOTAL</span>
                        </div>
                        <div class="stat-body">
                            <div class="stat-number" id="total-utilisateurs">0</div>
                            <div class="stat-items">
                                <span>✓ Actifs: <strong id="users-actifs">0</strong></span>
                                <span>⊘ Inactifs: <strong id="users-inactifs">0</strong></span>
                                <span>👨‍⚕️ Admins: <strong id="users-admins">0</strong></span>
                                <span>🏥 Patients: <strong id="users-patients">0</strong></span>
                            </div>
                        </div>
                    </div>

                    <!-- Statistiques Événements -->
                    <div class="stat-card evenements-card">
                        <div class="stat-header">
                            <h3>📅 Événements</h3>
                            <span class="stat-badge">TOTAL</span>
                        </div>
                        <div class="stat-body">
                            <div class="stat-number" id="total-evenements">0</div>
                            <div class="stat-items">
                                <span>📋 Planifiés: <strong id="events-planifies">0</strong></span>
                                <span>▶️ En Cours: <strong id="events-en-cours">0</strong></span>
                                <span>✅ Terminés: <strong id="events-termines">0</strong></span>
                                <span>🔜 À Venir: <strong id="events-a-venir">0</strong></span>
                            </div>
                        </div>
                    </div>

                    <!-- Statistiques Pharmacie -->
                    <div class="stat-card pharmacie-card">
                        <div class="stat-header">
                            <h3>💊 Pharmacie</h3>
                            <span class="stat-badge">STOCKS</span>
                        </div>
                        <div class="stat-body">
                            <div class="stat-number" id="nombre-pharmacies">0</div>
                            <div class="stat-items">
                                <span>🏪 Pharmacies: <strong id="pharmacies-count">0</strong></span>
                                <span>💉 Produits: <strong id="produits-count">0</strong></span>
                                <span>📦 Stock Total: <strong id="stock-total">0</strong></span>
                                <span>💰 Valeur: <strong id="stock-valeur">0€</strong></span>
                            </div>
                        </div>
                    </div>

                    <!-- Statistiques Participations -->
                    <div class="stat-card participations-card">
                        <div class="stat-header">
                            <h3>🎫 Participations</h3>
                            <span class="stat-badge">TOTAL</span>
                        </div>
                        <div class="stat-body">
                            <div class="stat-number" id="total-participations">0</div>
                            <div class="stat-items">
                                <span>✓ Confirmées: <strong id="part-confirmees">0</strong></span>
                                <span>⏳ En Attente: <strong id="part-en-attente">0</strong></span>
                                <span>✗ Annulées: <strong id="part-annulees">0</strong></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Graphiques -->
                <div class="charts-row">
                    <div class="chart-container">
                        <h3>Distribution Utilisateurs par Rôle</h3>
                        <canvas id="chart-roles"></canvas>
                    </div>
                    <div class="chart-container">
                        <h3>Statut des Événements</h3>
                        <canvas id="chart-events"></canvas>
                    </div>
                </div>
            </section>

            <!-- TAB: Utilisateurs -->
            <section id="tab-utilisateurs" class="tab-content">
                <div class="section-header">
                    <h2>👥 Gestion des Utilisateurs</h2>
                    <button class="btn btn-primary" onclick="ouvrirFormulaire('utilisateur')">+ Nouvel Utilisateur</button>
                </div>
                <div class="filters">
                    <input type="text" id="filter-users" placeholder="Chercher par nom, email...">
                    <select id="filter-users-role">
                        <option value="">Tous les rôles</option>
                        <option value="admin">Administrateur</option>
                        <option value="patient">Patient</option>
                        <option value="medecin">Médecin</option>
                    </select>
                    <select id="filter-users-statut">
                        <option value="">Tous les statuts</option>
                        <option value="actif">Actif</option>
                        <option value="inactif">Inactif</option>
                    </select>
                </div>
                <table class="table-gestion">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Statut</th>
                            <th>Participations</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-utilisateurs">
                        <tr><td colspan="7" class="text-center">Chargement...</td></tr>
                    </tbody>
                </table>
            </section>

            <!-- TAB: Événements -->
            <section id="tab-evenements" class="tab-content">
                <div class="section-header">
                    <h2>📅 Gestion des Événements</h2>
                    <button class="btn btn-primary" onclick="ouvrirFormulaire('evenement')">+ Nouvel Événement</button>
                </div>
                <div class="filters">
                    <input type="text" id="filter-events" placeholder="Chercher par titre...">
                    <select id="filter-events-statut">
                        <option value="">Tous les statuts</option>
                        <option value="planifie">Planifié</option>
                        <option value="en_cours">En Cours</option>
                        <option value="termine">Terminé</option>
                    </select>
                </div>
                <div class="events-grid">
                    <div id="grid-evenements"></div>
                </div>
            </section>

            <!-- TAB: Pharmacie -->
            <section id="tab-pharmacie" class="tab-content">
                <div class="section-header">
                    <h2>💊 Gestion de la Pharmacie</h2>
                    <button class="btn btn-primary" onclick="ouvrirFormulaire('produit')">+ Nouveau Produit</button>
                </div>
                <div class="filters">
                    <input type="text" id="filter-produits" placeholder="Chercher un produit...">
                    <select id="filter-pharmacies">
                        <option value="">Toutes les pharmacies</option>
                    </select>
                </div>
                <table class="table-gestion">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Produit</th>
                            <th>Pharmacie</th>
                            <th>Stock</th>
                            <th>Prix</th>
                            <th>Valeur</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-produits">
                        <tr><td colspan="7" class="text-center">Chargement...</td></tr>
                    </tbody>
                </table>
            </section>

            <!-- TAB: Participations -->
            <section id="tab-participations" class="tab-content">
                <div class="section-header">
                    <h2>🎫 Gestion des Participations</h2>
                    <button class="btn btn-primary" onclick="ouvrirFormulaire('participation')">+ Nouvelle Participation</button>
                </div>
                <div class="filters">
                    <input type="text" id="filter-participations" placeholder="Chercher...">
                    <select id="filter-part-statut">
                        <option value="">Tous les statuts</option>
                        <option value="confirmee">Confirmée</option>
                        <option value="en_attente">En Attente</option>
                        <option value="annulee">Annulée</option>
                    </select>
                </div>
                <table class="table-gestion">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Utilisateur</th>
                            <th>Événement</th>
                            <th>Date</th>
                            <th>Statut</th>
                            <th>Produits</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-participations">
                        <tr><td colspan="7" class="text-center">Chargement...</td></tr>
                    </tbody>
                </table>
            </section>

            <!-- TAB: Rapports -->
            <section id="tab-rapports" class="tab-content">
                <div class="section-header">
                    <h2>📊 Rapports Intégrés</h2>
                </div>
                <div class="rapports-grid">
                    <div class="rapport-card">
                        <h3>Rapport Complet</h3>
                        <p>Téléchargez un rapport intégrant toutes les données</p>
                        <button class="btn btn-secondary" onclick="telechargerRapport('complet')">PDF</button>
                        <button class="btn btn-secondary" onclick="telechargerRapport('json')">JSON</button>
                    </div>
                    <div class="rapport-card">
                        <h3>Rapport par Période</h3>
                        <input type="date" id="rapport-debut">
                        <input type="date" id="rapport-fin">
                        <button class="btn btn-secondary" onclick="genererRapportPeriode()">Générer</button>
                    </div>
                    <div class="rapport-card">
                        <h3>Exporter Données</h3>
                        <p>Exporter les données brutes</p>
                        <button class="btn btn-secondary" onclick="exporterDonnees('json')">JSON</button>
                        <button class="btn btn-secondary" onclick="exporterDonnees('csv')">CSV</button>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- Modal Formulaire -->
    <div id="modal-formulaire" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="fermerModal()">&times;</span>
            <form id="form-gestion" onsubmit="soumettreFormulaire(event)">
                <div id="form-body"></div>
                <div class="modal-actions">
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                    <button type="button" class="btn btn-secondary" onclick="fermerModal()">Annuler</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            chargerTableauBord();
            chargerUtilisateurs();
            chargerEvenements();
            chargerProduits();
            chargerParticipations();
            
            // Navigation tabs
            document.querySelectorAll('.tab-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    changerTab(this.getAttribute('href'));
                });
            });
        });

        // Charger le tableau de bord
        async function chargerTableauBord() {
            try {
                const response = await fetch('/api/gestion-unifiee/dashboard');
                const result = await response.json();
                
                if (result.statut === 'succes') {
                    const data = result.data;
                    
                    // Utilisateurs
                    document.getElementById('total-utilisateurs').textContent = data.utilisateurs.total || 0;
                    document.getElementById('users-actifs').textContent = data.utilisateurs.actifs || 0;
                    document.getElementById('users-inactifs').textContent = data.utilisateurs.inactifs || 0;
                    document.getElementById('users-admins').textContent = data.utilisateurs.admins || 0;
                    document.getElementById('users-patients').textContent = data.utilisateurs.patients || 0;
                    
                    // Événements
                    document.getElementById('total-evenements').textContent = data.evenements.total || 0;
                    document.getElementById('events-planifies').textContent = data.evenements.planifies || 0;
                    document.getElementById('events-en-cours').textContent = data.evenements.en_cours || 0;
                    document.getElementById('events-termines').textContent = data.evenements.termines || 0;
                    document.getElementById('events-a-venir').textContent = data.evenements.a_venir || 0;
                    
                    // Pharmacie
                    document.getElementById('nombre-pharmacies').textContent = data.pharmacie.nombre_pharmacies || 0;
                    document.getElementById('pharmacies-count').textContent = data.pharmacie.nombre_pharmacies || 0;
                    document.getElementById('produits-count').textContent = data.pharmacie.nombre_produits || 0;
                    document.getElementById('stock-total').textContent = data.pharmacie.stock_total || 0;
                    document.getElementById('stock-valeur').textContent = (data.pharmacie.valeur_stock || 0).toFixed(2) + '€';
                    
                    // Participations
                    document.getElementById('total-participations').textContent = data.participations.total || 0;
                    document.getElementById('part-confirmees').textContent = data.participations.confirmees || 0;
                    document.getElementById('part-en-attente').textContent = data.participations.en_attente || 0;
                    document.getElementById('part-annulees').textContent = data.participations.annulees || 0;
                }
            } catch (error) {
                console.error('Erreur:', error);
            }
        }

        // Charger les utilisateurs
        async function chargerUtilisateurs() {
            try {
                const response = await fetch('/api/gestion-unifiee/utilisateurs');
                const result = await response.json();
                
                if (result.statut === 'succes') {
                    const tbody = document.getElementById('tbody-utilisateurs');
                    tbody.innerHTML = '';
                    
                    result.data.forEach(user => {
                        tbody.innerHTML += `
                            <tr>
                                <td>${user.id}</td>
                                <td>${user.nom} ${user.prenom}</td>
                                <td>${user.email}</td>
                                <td><span class="badge badge-${user.role}">${user.role}</span></td>
                                <td><span class="badge badge-${user.statut}">${user.statut}</span></td>
                                <td>${user.nombre_participations}</td>
                                <td class="actions">
                                    <button class="btn-sm btn-edit" onclick="editerUtilisateur(${user.id})">✎</button>
                                    <button class="btn-sm btn-delete" onclick="supprimerUtilisateur(${user.id})">🗑</button>
                                </td>
                            </tr>
                        `;
                    });
                }
            } catch (error) {
                console.error('Erreur:', error);
            }
        }

        // Charger les événements
        async function chargerEvenements() {
            try {
                const response = await fetch('/api/gestion-unifiee/evenements');
                const result = await response.json();
                
                if (result.statut === 'succes') {
                    const grid = document.getElementById('grid-evenements');
                    grid.innerHTML = '';
                    
                    result.data.forEach(event => {
                        grid.innerHTML += `
                            <div class="event-card">
                                <h4>${event.titre}</h4>
                                <p>📅 ${event.date_debut}</p>
                                <p>📍 ${event.lieu}</p>
                                <p>👥 ${event.nombre_participants} participants</p>
                                <span class="badge badge-${event.statut}">${event.statut}</span>
                                <div class="actions">
                                    <button class="btn-sm btn-edit" onclick="editerEvenement(${event.id})">✎</button>
                                    <button class="btn-sm btn-delete" onclick="supprimerEvenement(${event.id})">🗑</button>
                                </div>
                            </div>
                        `;
                    });
                }
            } catch (error) {
                console.error('Erreur:', error);
            }
        }

        // Charger les produits
        async function chargerProduits() {
            // À implémenter
        }

        // Charger les participations
        async function chargerParticipations() {
            // À implémenter
        }

        // Changer d'onglet
        function changerTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-link').forEach(link => {
                link.classList.remove('active');
            });
            
            const tab = document.querySelector(tabId);
            if (tab) {
                tab.classList.add('active');
                event.target.classList.add('active');
            }
        }

        // Formulaire
        function ouvrirFormulaire(type) {
            // À implémenter selon le type
            document.getElementById('modal-formulaire').style.display = 'block';
        }

        function fermerModal() {
            document.getElementById('modal-formulaire').style.display = 'none';
        }

        function soumettreFormulaire(e) {
            e.preventDefault();
            // À implémenter
            fermerModal();
        }

        // Fonctions d'action
        async function supprimerUtilisateur(id) {
            if (confirm('Êtes-vous sûr ?')) {
                // À implémenter
                chargerUtilisateurs();
            }
        }

        async function supprimerEvenement(id) {
            if (confirm('Êtes-vous sûr ?')) {
                // À implémenter
                chargerEvenements();
            }
        }

        async function telechargerRapport(format) {
            // À implémenter
        }

        async function exporterDonnees(format) {
            // À implémenter
        }
    </script>
</body>
</html>
