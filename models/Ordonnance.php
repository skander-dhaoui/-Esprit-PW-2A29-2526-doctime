<?php

require_once __DIR__ . '/../config/database.php';  // ✅
class Ordonnance {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ─────────────────────────────────────────
    //  CRUD de base
    // ─────────────────────────────────────────
    public function create(array $data): ?int {
        try {
            $sql = "INSERT INTO ordonnances (numero, client_id, praticien_id, date_ordonnance, date_validite, statut, notes, created_at, updated_at)
                    VALUES (:numero, :client_id, :praticien_id, :date_ordonnance, :date_validite, :statut, :notes, NOW(), NOW())";

            $result = $this->db->execute($sql, $data);
            return $result ? $this->db->lastInsertId() : null;
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::create - ' . $e->getMessage());
            return null;
        }
    }

    public function getById(int $id): ?array {
        try {
            $sql = "SELECT o.*, 
                           c.nom as client_nom, c.prenom as client_prenom, c.email as client_email, c.telephone as client_telephone,
                           p.nom as praticien_nom, p.prenom as praticien_prenom, p.email as praticien_email
                    FROM ordonnances o
                    LEFT JOIN clients c ON o.client_id = c.id
                    LEFT JOIN praticiens p ON o.praticien_id = p.id
                    WHERE o.id = :id";

            $result = $this->db->query($sql, ['id' => $id]);
            return $result ? $result[0] : null;
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::getById - ' . $e->getMessage());
            return null;
        }
    }

    public function getByNumero(string $numero): ?array {
        try {
            $sql = "SELECT o.*, 
                           c.nom as client_nom, c.prenom as client_prenom,
                           p.nom as praticien_nom, p.prenom as praticien_prenom
                    FROM ordonnances o
                    LEFT JOIN clients c ON o.client_id = c.id
                    LEFT JOIN praticiens p ON o.praticien_id = p.id
                    WHERE o.numero = :numero";

            $result = $this->db->query($sql, ['numero' => $numero]);
            return $result ? $result[0] : null;
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::getByNumero - ' . $e->getMessage());
            return null;
        }
    }

    public function update(int $id, array $data): bool {
        try {
            $fields = [];
            $values = ['id' => $id];

            foreach ($data as $key => $value) {
                $fields[] = "$key = :$key";
                $values[$key] = $value;
            }

            $fields[] = "updated_at = NOW()";

            $sql = "UPDATE ordonnances SET " . implode(', ', $fields) . " WHERE id = :id";
            return $this->db->execute($sql, $values);
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::update - ' . $e->getMessage());
            return false;
        }
    }

    public function delete(int $id): bool {
        try {
            $sql = "DELETE FROM ordonnances WHERE id = :id";
            return $this->db->execute($sql, ['id' => $id]);
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::delete - ' . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────
    //  Récupération avec filtres
    // ─────────────────────────────────────────
    public function getAll(int $offset = 0, int $limit = 20, string $filter = 'tous', string $search = ''): array {
        try {
            $where = "WHERE 1=1";

            if ($filter !== 'tous') {
                $where .= " AND o.statut = :statut";
            }

            if (!empty($search)) {
                $where .= " AND (o.numero LIKE :search OR c.nom LIKE :search OR c.prenom LIKE :search)";
            }

            $sql = "SELECT o.*, 
                           c.nom as client_nom, c.prenom as client_prenom, c.email as client_email,
                           p.nom as praticien_nom, p.prenom as praticien_prenom
                    FROM ordonnances o
                    LEFT JOIN clients c ON o.client_id = c.id
                    LEFT JOIN praticiens p ON o.praticien_id = p.id
                    $where
                    ORDER BY o.date_ordonnance DESC
                    LIMIT :offset, :limit";

            $params = ['offset' => $offset, 'limit' => $limit];
            if ($filter !== 'tous') {
                $params['statut'] = $filter;
            }
            if (!empty($search)) {
                $params['search'] = "%$search%";
            }

            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::getAll - ' . $e->getMessage());
            return [];
        }
    }

    public function countAll(string $filter = 'tous', string $search = ''): int {
        try {
            $where = "WHERE 1=1";

            if ($filter !== 'tous') {
                $where .= " AND o.statut = :statut";
            }

            if (!empty($search)) {
                $where .= " AND (o.numero LIKE :search OR c.nom LIKE :search OR c.prenom LIKE :search)";
            }

            $sql = "SELECT COUNT(*) as count FROM ordonnances o
                    LEFT JOIN clients c ON o.client_id = c.id
                    $where";

            $params = [];
            if ($filter !== 'tous') {
                $params['statut'] = $filter;
            }
            if (!empty($search)) {
                $params['search'] = "%$search%";
            }

            $result = $this->db->query($sql, $params);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::countAll - ' . $e->getMessage());
            return 0;
        }
    }

    public function getByClient(int $clientId, int $offset = 0, int $limit = 20, string $filter = 'tous'): array {
        try {
            $where = "WHERE o.client_id = :client_id";

            if ($filter !== 'tous') {
                $where .= " AND o.statut = :statut";
            }

            $sql = "SELECT o.*, 
                           p.nom as praticien_nom, p.prenom as praticien_prenom, p.specialite
                    FROM ordonnances o
                    LEFT JOIN praticiens p ON o.praticien_id = p.id
                    $where
                    ORDER BY o.date_ordonnance DESC
                    LIMIT :offset, :limit";

            $params = ['client_id' => $clientId, 'offset' => $offset, 'limit' => $limit];
            if ($filter !== 'tous') {
                $params['statut'] = $filter;
            }

            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::getByClient - ' . $e->getMessage());
            return [];
        }
    }

    public function countByClient(int $clientId, string $filter = 'tous'): int {
        try {
            $where = "WHERE o.client_id = :client_id";

            if ($filter !== 'tous') {
                $where .= " AND o.statut = :statut";
            }

            $sql = "SELECT COUNT(*) as count FROM ordonnances o $where";

            $params = ['client_id' => $clientId];
            if ($filter !== 'tous') {
                $params['statut'] = $filter;
            }

            $result = $this->db->query($sql, $params);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::countByClient - ' . $e->getMessage());
            return 0;
        }
    }

    public function getByPraticien(int $praticienId, int $offset = 0, int $limit = 20, string $filter = 'tous'): array {
        try {
            $where = "WHERE o.praticien_id = :praticien_id";

            if ($filter !== 'tous') {
                $where .= " AND o.statut = :statut";
            }

            $sql = "SELECT o.*, 
                           c.nom as client_nom, c.prenom as client_prenom, c.email as client_email
                    FROM ordonnances o
                    LEFT JOIN clients c ON o.client_id = c.id
                    $where
                    ORDER BY o.date_ordonnance DESC
                    LIMIT :offset, :limit";

            $params = ['praticien_id' => $praticienId, 'offset' => $offset, 'limit' => $limit];
            if ($filter !== 'tous') {
                $params['statut'] = $filter;
            }

            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::getByPraticien - ' . $e->getMessage());
            return [];
        }
    }

    public function countByPraticien(int $praticienId, string $filter = 'tous'): int {
        try {
            $where = "WHERE o.praticien_id = :praticien_id";

            if ($filter !== 'tous') {
                $where .= " AND o.statut = :statut";
            }

            $sql = "SELECT COUNT(*) as count FROM ordonnances o $where";

            $params = ['praticien_id' => $praticienId];
            if ($filter !== 'tous') {
                $params['statut'] = $filter;
            }

            $result = $this->db->query($sql, $params);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::countByPraticien - ' . $e->getMessage());
            return 0;
        }
    }

    public function getByDateRange(string $dateDebut, string $dateFin, int $offset = 0, int $limit = 50): array {
        try {
            $sql = "SELECT o.*, 
                           c.nom as client_nom, c.prenom as client_prenom,
                           p.nom as praticien_nom, p.prenom as praticien_prenom
                    FROM ordonnances o
                    LEFT JOIN clients c ON o.client_id = c.id
                    LEFT JOIN praticiens p ON o.praticien_id = p.id
                    WHERE o.date_ordonnance BETWEEN :date_debut AND :date_fin
                    ORDER BY o.date_ordonnance DESC
                    LIMIT :offset, :limit";

            return $this->db->query($sql, [
                'date_debut' => $dateDebut . ' 00:00:00',
                'date_fin' => $dateFin . ' 23:59:59',
                'offset' => $offset,
                'limit' => $limit,
            ]);
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::getByDateRange - ' . $e->getMessage());
            return [];
        }
    }

    public function getRecentOrdonnances(int $limit = 10): array {
        try {
            $sql = "SELECT o.*, 
                           c.nom as client_nom, c.prenom as client_prenom,
                           p.nom as praticien_nom, p.prenom as praticien_prenom
                    FROM ordonnances o
                    LEFT JOIN clients c ON o.client_id = c.id
                    LEFT JOIN praticiens p ON o.praticien_id = p.id
                    ORDER BY o.created_at DESC
                    LIMIT :limit";

            return $this->db->query($sql, ['limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::getRecentOrdonnances - ' . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────
    //  Statut et validation
    // ─────────────────────────────────────────
    public function getByStatut(string $statut, int $offset = 0, int $limit = 20): array {
        try {
            $sql = "SELECT o.*, 
                           c.nom as client_nom, c.prenom as client_prenom,
                           p.nom as praticien_nom, p.prenom as praticien_prenom
                    FROM ordonnances o
                    LEFT JOIN clients c ON o.client_id = c.id
                    LEFT JOIN praticiens p ON o.praticien_id = p.id
                    WHERE o.statut = :statut
                    ORDER BY o.date_ordonnance DESC
                    LIMIT :offset, :limit";

            return $this->db->query($sql, ['statut' => $statut, 'offset' => $offset, 'limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::getByStatut - ' . $e->getMessage());
            return [];
        }
    }

    public function countByStatut(string $statut): int {
        try {
            $sql = "SELECT COUNT(*) as count FROM ordonnances WHERE statut = :statut";
            $result = $this->db->query($sql, ['statut' => $statut]);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::countByStatut - ' . $e->getMessage());
            return 0;
        }
    }

    public function updateStatut(int $id, string $statut): bool {
        try {
            $sql = "UPDATE ordonnances SET statut = :statut, updated_at = NOW() WHERE id = :id";
            return $this->db->execute($sql, ['id' => $id, 'statut' => $statut]);
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::updateStatut - ' . $e->getMessage());
            return false;
        }
    }

    public function validate(int $id): bool {
        try {
            return $this->updateStatut($id, 'validée');
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::validate - ' . $e->getMessage());
            return false;
        }
    }

    public function reject(int $id, string $raison = ''): bool {
        try {
            $fields = ['statut = :statut'];
            $values = ['id' => $id, 'statut' => 'rejetée'];

            if (!empty($raison)) {
                $fields[] = 'notes = :notes';
                $values['notes'] = $raison;
            }

            $fields[] = 'updated_at = NOW()';

            $sql = "UPDATE ordonnances SET " . implode(', ', $fields) . " WHERE id = :id";
            return $this->db->execute($sql, $values);
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::reject - ' . $e->getMessage());
            return false;
        }
    }

    public function archive(int $id): bool {
        try {
            return $this->updateStatut($id, 'archivée');
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::archive - ' . $e->getMessage());
            return false;
        }
    }

    public function isValid(int $id): bool {
        try {
            $ordonnance = $this->getById($id);
            if (!$ordonnance) {
                return false;
            }

            // Vérifier que la date de validité n'est pas dépassée
            if (strtotime($ordonnance['date_validite']) < time()) {
                return false;
            }

            return $ordonnance['statut'] === 'validée';
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::isValid - ' . $e->getMessage());
            return false;
        }
    }

    public function isExpired(int $id): bool {
        try {
            $ordonnance = $this->getById($id);
            if (!$ordonnance) {
                return false;
            }

            return strtotime($ordonnance['date_validite']) < time();
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::isExpired - ' . $e->getMessage());
            return false;
        }
    }

    public function getExpiredOrdonnances(int $limit = 50): array {
        try {
            $sql = "SELECT o.*, 
                           c.nom as client_nom, c.prenom as client_prenom, c.email as client_email,
                           p.nom as praticien_nom, p.prenom as praticien_prenom
                    FROM ordonnances o
                    LEFT JOIN clients c ON o.client_id = c.id
                    LEFT JOIN praticiens p ON o.praticien_id = p.id
                    WHERE o.date_validite < NOW() AND o.statut IN ('validée', 'en attente')
                    ORDER BY o.date_validite ASC
                    LIMIT :limit";

            return $this->db->query($sql, ['limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::getExpiredOrdonnances - ' . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────
    //  Medicaments et lignes
    // ─────────────────────────────────────────
    public function addMedicament(int $ordonnanceId, array $data): ?int {
        try {
            $sql = "INSERT INTO ordonnance_medicaments (ordonnance_id, medicament_id, quantite, dosage, frequence, duree_jours, notes, created_at)
                    VALUES (:ordonnance_id, :medicament_id, :quantite, :dosage, :frequence, :duree_jours, :notes, NOW())";

            $data['ordonnance_id'] = $ordonnanceId;
            $result = $this->db->execute($sql, $data);
            return $result ? $this->db->lastInsertId() : null;
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::addMedicament - ' . $e->getMessage());
            return null;
        }
    }

    public function removeMedicament(int $medicamentId): bool {
        try {
            $sql = "DELETE FROM ordonnance_medicaments WHERE id = :id";
            return $this->db->execute($sql, ['id' => $medicamentId]);
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::removeMedicament - ' . $e->getMessage());
            return false;
        }
    }

    public function getMedicaments(int $ordonnanceId): array {
        try {
            $sql = "SELECT om.*, m.nom as medicament_nom, m.denomination_commune as medicament_dci
                    FROM ordonnance_medicaments om
                    LEFT JOIN medicaments m ON om.medicament_id = m.id
                    WHERE om.ordonnance_id = :ordonnance_id
                    ORDER BY om.created_at ASC";

            return $this->db->query($sql, ['ordonnance_id' => $ordonnanceId]);
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::getMedicaments - ' . $e->getMessage());
            return [];
        }
    }

    public function countMedicaments(int $ordonnanceId): int {
        try {
            $sql = "SELECT COUNT(*) as count FROM ordonnance_medicaments WHERE ordonnance_id = :ordonnance_id";
            $result = $this->db->query($sql, ['ordonnance_id' => $ordonnanceId]);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::countMedicaments - ' . $e->getMessage());
            return 0;
        }
    }

    public function updateMedicament(int $medicamentId, array $data): bool {
        try {
            $fields = [];
            $values = ['id' => $medicamentId];

            foreach ($data as $key => $value) {
                $fields[] = "$key = :$key";
                $values[$key] = $value;
            }

            $sql = "UPDATE ordonnance_medicaments SET " . implode(', ', $fields) . " WHERE id = :id";
            return $this->db->execute($sql, $values);
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::updateMedicament - ' . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────
    //  Document et PDF
    // ─────────────────────────────────────────
    public function uploadDocument(int $ordonnanceId, array $fileData): ?int {
        try {
            $sql = "INSERT INTO ordonnance_documents (ordonnance_id, nom_fichier, chemin_fichier, type_mime, taille, created_at)
                    VALUES (:ordonnance_id, :nom_fichier, :chemin_fichier, :type_mime, :taille, NOW())";

            $fileData['ordonnance_id'] = $ordonnanceId;
            $result = $this->db->execute($sql, $fileData);
            return $result ? $this->db->lastInsertId() : null;
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::uploadDocument - ' . $e->getMessage());
            return null;
        }
    }

    public function removeDocument(int $documentId): bool {
        try {
            $sql = "DELETE FROM ordonnance_documents WHERE id = :id";
            return $this->db->execute($sql, ['id' => $documentId]);
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::removeDocument - ' . $e->getMessage());
            return false;
        }
    }

    public function getDocuments(int $ordonnanceId): array {
        try {
            $sql = "SELECT * FROM ordonnance_documents WHERE ordonnance_id = :ordonnance_id ORDER BY created_at DESC";
            return $this->db->query($sql, ['ordonnance_id' => $ordonnanceId]);
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::getDocuments - ' . $e->getMessage());
            return [];
        }
    }

    public function getDocument(int $documentId): ?array {
        try {
            $sql = "SELECT * FROM ordonnance_documents WHERE id = :id";
            $result = $this->db->query($sql, ['id' => $documentId]);
            return $result ? $result[0] : null;
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::getDocument - ' . $e->getMessage());
            return null;
        }
    }

    public function generatePDF(int $id): bool {
        try {
            // Cette fonction dépend d'une bibliothèque PDF (TCPDF, mPDF, etc.)
            // À adapter selon votre implémentation
            $ordonnance = $this->getById($id);
            if (!$ordonnance) {
                return false;
            }

            // Marquer comme PDF généré
            return $this->update($id, ['pdf_genere' => 1]);
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::generatePDF - ' . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────
    //  Historique et audit
    // ─────────────────────────────────────────
    public function addHistorique(int $ordonnanceId, array $data): bool {
        try {
            $sql = "INSERT INTO ordonnance_historiques (ordonnance_id, action, description, user_id, created_at)
                    VALUES (:ordonnance_id, :action, :description, :user_id, NOW())";

            $data['ordonnance_id'] = $ordonnanceId;
            return $this->db->execute($sql, $data);
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::addHistorique - ' . $e->getMessage());
            return false;
        }
    }

    public function getHistorique(int $ordonnanceId): array {
        try {
            $sql = "SELECT h.*, u.nom, u.prenom FROM ordonnance_historiques h
                    LEFT JOIN users u ON h.user_id = u.id
                    WHERE h.ordonnance_id = :ordonnance_id
                    ORDER BY h.created_at DESC";

            return $this->db->query($sql, ['ordonnance_id' => $ordonnanceId]);
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::getHistorique - ' . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────
    //  Génération numéro d'ordonnance
    // ─────────────────────────────────────────
    public function generateNumero(): string {
        try {
            $date = date('Ymd');
            $random = strtoupper(substr(md5(time() . mt_rand()), 0, 4));

            $numero = 'ORD-' . $date . '-' . $random;

            // Vérifier l'unicité
            while ($this->getByNumero($numero)) {
                $random = strtoupper(substr(md5(time() . mt_rand()), 0, 4));
                $numero = 'ORD-' . $date . '-' . $random;
            }

            return $numero;
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::generateNumero - ' . $e->getMessage());
            return '';
        }
    }

    public function generateNumeroSequential(): string {
        try {
            $sql = "SELECT MAX(CAST(SUBSTRING_INDEX(numero, '-', -1) AS UNSIGNED)) as max_num FROM ordonnances WHERE DATE(created_at) = CURDATE()";
            $result = $this->db->query($sql);

            $num = (int)($result[0]['max_num'] ?? 0) + 1;
            return 'ORD-' . date('Ymd') . '-' . str_pad($num, 6, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::generateNumeroSequential - ' . $e->getMessage());
            return '';
        }
    }

    // ─────────────────────────────────────────
    //  Recherche avancée
    // ─────────────────────────────────────────
    public function search(string $query, int $limit = 20): array {
        try {
            $sql = "SELECT o.*, 
                           c.nom as client_nom, c.prenom as client_prenom,
                           p.nom as praticien_nom, p.prenom as praticien_prenom
                    FROM ordonnances o
                    LEFT JOIN clients c ON o.client_id = c.id
                    LEFT JOIN praticiens p ON o.praticien_id = p.id
                    WHERE (o.numero LIKE :query OR c.nom LIKE :query OR c.prenom LIKE :query OR p.nom LIKE :query)
                    ORDER BY 
                        CASE WHEN o.numero LIKE :query_exact THEN 1 ELSE 2 END,
                        o.date_ordonnance DESC
                    LIMIT :limit";

            return $this->db->query($sql, [
                'query' => "%$query%",
                'query_exact' => $query,
                'limit' => $limit,
            ]);
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::search - ' . $e->getMessage());
            return [];
        }
    }

    public function getExpiredsoon(int $days = 30, int $limit = 50): array {
        try {
            $sql = "SELECT o.*, 
                           c.nom as client_nom, c.prenom as client_prenom, c.email as client_email,
                           p.nom as praticien_nom, p.prenom as praticien_prenom
                    FROM ordonnances o
                    LEFT JOIN clients c ON o.client_id = c.id
                    LEFT JOIN praticiens p ON o.praticien_id = p.id
                    WHERE o.date_validite BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL :days DAY)
                    AND o.statut = 'validée'
                    ORDER BY o.date_validite ASC
                    LIMIT :limit";

            return $this->db->query($sql, ['days' => $days, 'limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::getExpiredSoon - ' . $e->getMessage());
            return [];
        }
    }

    public function getPendingValidation(): array {
        try {
            $sql = "SELECT o.*, 
                           c.nom as client_nom, c.prenom as client_prenom,
                           p.nom as praticien_nom, p.prenom as praticien_prenom
                    FROM ordonnances o
                    LEFT JOIN clients c ON o.client_id = c.id
                    LEFT JOIN praticiens p ON o.praticien_id = p.id
                    WHERE o.statut = 'en attente'
                    ORDER BY o.created_at ASC";

            return $this->db->query($sql);
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::getPendingValidation - ' . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────
    //  Statistiques
    // ─────────────────────────────────────────
    public function getStats(): array {
        try {
            $sql = "SELECT 
                           COUNT(*) as total,
                           SUM(CASE WHEN statut = 'validée' THEN 1 ELSE 0 END) as validees,
                           SUM(CASE WHEN statut = 'en attente' THEN 1 ELSE 0 END) as en_attente,
                           SUM(CASE WHEN statut = 'rejetée' THEN 1 ELSE 0 END) as rejetees,
                           SUM(CASE WHEN statut = 'archivée' THEN 1 ELSE 0 END) as archivees,
                           SUM(CASE WHEN date_validite < NOW() THEN 1 ELSE 0 END) as expirees,
                           COUNT(DISTINCT client_id) as nb_clients_uniques,
                           COUNT(DISTINCT praticien_id) as nb_praticiens_uniques
                    FROM ordonnances";

            $result = $this->db->query($sql);
            return $result ? $result[0] : [];
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::getStats - ' . $e->getMessage());
            return [];
        }
    }

    public function getStatsByClient(int $clientId): array {
        try {
            $sql = "SELECT 
                           COUNT(*) as total,
                           SUM(CASE WHEN statut = 'validée' THEN 1 ELSE 0 END) as validees,
                           SUM(CASE WHEN statut = 'en attente' THEN 1 ELSE 0 END) as en_attente,
                           AVG(DATEDIFF(date_validite, date_ordonnance)) as duree_moyenne_validite
                    FROM ordonnances
                    WHERE client_id = :client_id";

            $result = $this->db->query($sql, ['client_id' => $clientId]);
            return $result ? $result[0] : [];
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::getStatsByClient - ' . $e->getMessage());
            return [];
        }
    }

    public function getStatsByPraticien(int $praticienId): array {
        try {
            $sql = "SELECT 
                           COUNT(*) as total,
                           SUM(CASE WHEN statut = 'validée' THEN 1 ELSE 0 END) as validees,
                           SUM(CASE WHEN statut = 'rejetée' THEN 1 ELSE 0 END) as rejetees,
                           COUNT(DISTINCT client_id) as nb_clients
                    FROM ordonnances
                    WHERE praticien_id = :praticien_id";

            $result = $this->db->query($sql, ['praticien_id' => $praticienId]);
            return $result ? $result[0] : [];
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::getStatsByPraticien - ' . $e->getMessage());
            return [];
        }
    }

    public function getStatsByPeriod(string $dateDebut, string $dateFin): array {
        try {
            $sql = "SELECT 
                           COUNT(*) as total,
                           SUM(CASE WHEN statut = 'validée' THEN 1 ELSE 0 END) as validees,
                           SUM(CASE WHEN statut = 'en attente' THEN 1 ELSE 0 END) as en_attente,
                           COUNT(DISTINCT client_id) as nb_clients,
                           COUNT(DISTINCT praticien_id) as nb_praticiens
                    FROM ordonnances
                    WHERE date_ordonnance BETWEEN :date_debut AND :date_fin";

            $result = $this->db->query($sql, [
                'date_debut' => $dateDebut . ' 00:00:00',
                'date_fin' => $dateFin . ' 23:59:59',
            ]);

            return $result ? $result[0] : [];
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::getStatsByPeriod - ' . $e->getMessage());
            return [];
        }
    }

    public function getMedicamentsRanking(int $limit = 20): array {
        try {
            $sql = "SELECT m.id, m.nom, m.denomination_commune, COUNT(om.id) as nb_prescriptions
                    FROM ordonnance_medicaments om
                    LEFT JOIN medicaments m ON om.medicament_id = m.id
                    GROUP BY m.id, m.nom, m.denomination_commune
                    ORDER BY nb_prescriptions DESC
                    LIMIT :limit";

            return $this->db->query($sql, ['limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::getMedicamentsRanking - ' . $e->getMessage());
            return [];
        }
    }

    public function getPraticienStat(int $limit = 10): array {
        try {
            $sql = "SELECT p.id, p.nom, p.prenom, COUNT(o.id) as nb_ordonnances, COUNT(DISTINCT o.client_id) as nb_clients
                    FROM praticiens p
                    LEFT JOIN ordonnances o ON p.id = o.praticien_id
                    GROUP BY p.id, p.nom, p.prenom
                    ORDER BY nb_ordonnances DESC
                    LIMIT :limit";

            return $this->db->query($sql, ['limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::getPraticienStat - ' . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────
    //  Export et rapports
    // ─────────────────────────────────────────
    public function exportList(int $limit = 1000): array {
        try {
            $sql = "SELECT o.*, 
                           c.nom as client_nom, c.prenom as client_prenom, c.email as client_email,
                           p.nom as praticien_nom, p.prenom as praticien_prenom,
                           GROUP_CONCAT(m.nom SEPARATOR ', ') as medicaments
                    FROM ordonnances o
                    LEFT JOIN clients c ON o.client_id = c.id
                    LEFT JOIN praticiens p ON o.praticien_id = p.id
                    LEFT JOIN ordonnance_medicaments om ON o.id = om.ordonnance_id
                    LEFT JOIN medicaments m ON om.medicament_id = m.id
                    GROUP BY o.id
                    ORDER BY o.date_ordonnance DESC
                    LIMIT :limit";

            return $this->db->query($sql, ['limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::exportList - ' . $e->getMessage());
            return [];
        }
    }

    public function getReport(string $dateDebut, string $dateFin): array {
        try {
            $stats = $this->getStatsByPeriod($dateDebut, $dateFin);
            $medicaments = $this->getMedicamentsRanking(10);
            $praticiens = $this->getPraticienStat(10);

            return [
                'period' => [
                    'start' => $dateDebut,
                    'end' => $dateFin,
                ],
                'stats' => $stats,
                'top_medicaments' => $medicaments,
                'top_praticiens' => $praticiens,
                'generated_at' => date('Y-m-d H:i:s'),
            ];
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::getReport - ' . $e->getMessage());
            return [];
        }
    }

    public function getApiListing(int $offset = 0, int $limit = 50): array {
        try {
            $sql = "SELECT o.id, o.numero, o.date_ordonnance, o.date_validite, o.statut,
                           c.nom as client_nom, c.prenom as client_prenom,
                           p.nom as praticien_nom, p.prenom as praticien_prenom,
                           COUNT(om.id) as nb_medicaments
                    FROM ordonnances o
                    LEFT JOIN clients c ON o.client_id = c.id
                    LEFT JOIN praticiens p ON o.praticien_id = p.id
                    LEFT JOIN ordonnance_medicaments om ON o.id = om.ordonnance_id
                    GROUP BY o.id
                    ORDER BY o.date_ordonnance DESC
                    LIMIT :offset, :limit";

            return $this->db->query($sql, ['offset' => $offset, 'limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::getApiListing - ' . $e->getMessage());
            return [];
        }
    }

    public function duplicate(int $id): ?int {
        try {
            $ordonnance = $this->getById($id);
            if (!$ordonnance) {
                return null;
            }

            $numero = $this->generateNumero();
            $dateValidite = date('Y-m-d', strtotime('+1 year'));

            $newOrdonnance = [
                'numero' => $numero,
                'client_id' => $ordonnance['client_id'],
                'praticien_id' => $ordonnance['praticien_id'],
                'date_ordonnance' => date('Y-m-d H:i:s'),
                'date_validite' => $dateValidite,
                'statut' => 'en attente',
                'notes' => 'Copie de ' . $ordonnance['numero'],
            ];

            $newId = $this->create($newOrdonnance);

            if ($newId) {
                // Dupliquer les médicaments
                $medicaments = $this->getMedicaments($id);
                foreach ($medicaments as $med) {
                    $this->addMedicament($newId, [
                        'medicament_id' => $med['medicament_id'],
                        'quantite' => $med['quantite'],
                        'dosage' => $med['dosage'],
                        'frequence' => $med['frequence'],
                        'duree_jours' => $med['duree_jours'],
                        'notes' => $med['notes'],
                    ]);
                }
            }

            return $newId;
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::duplicate - ' . $e->getMessage());
            return null;
        }
    }
}
?>
