<?php
/**
 * AtrRecord Model
 * ÁTR Beragadt Betegek - Data Model
 */

require_once __DIR__ . '/../config/database.php';

class AtrRecord {
    private $pdo;

    public function __construct() {
        $this->pdo = getDbConnection();
    }

    /**
     * Create a new ATR record
     * @param array $data
     * @return int Last inserted ID
     */
    public function create($data) {
        // atr_nursing_cycle_id is removed from form - will be NULL by default in DB
        $sql = "INSERT INTO atr_records (
            intezmeny,
            osztaly,
            tavido,
            atr_dismissing_type,
            atr_nursing_cycle_data_id,
            created_ip,
            created_by_admin_id
        ) VALUES (
            :intezmeny,
            :osztaly,
            :tavido,
            :atr_dismissing_type,
            :atr_nursing_cycle_data_id,
            :created_ip,
            :created_by_admin_id
        )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':intezmeny' => $data['intezmeny'],
            ':osztaly' => $data['osztaly'],
            ':tavido' => $data['tavido'],
            ':atr_dismissing_type' => $data['atr_dismissing_type'],
            ':atr_nursing_cycle_data_id' => $data['atr_nursing_cycle_data_id'],
            ':created_ip' => $data['created_ip'],
            ':created_by_admin_id' => $data['created_by_admin_id'] ?? null,
        ]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Get all records with pagination
     * @param int $page
     * @param int $perPage
     * @param string $search
     * @return array
     */
    public function getAll($page = 1, $perPage = 20, $search = '') {
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT
            r.*,
            a.display_name as creator_name
        FROM atr_records r
        LEFT JOIN admins a ON r.created_by_admin_id = a.id
        WHERE 1=1";

        if (!empty($search)) {
            $sql .= " AND (
                r.osztaly LIKE :search1
                OR r.atr_nursing_cycle_id LIKE :search2
                OR r.atr_nursing_cycle_data_id LIKE :search3
            )";
        }

        $sql .= " ORDER BY r.created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);

        // Bind search parameters if exists
        if (!empty($search)) {
            $searchParam = '%' . $search . '%';
            $stmt->bindValue(':search1', $searchParam, PDO::PARAM_STR);
            $stmt->bindValue(':search2', $searchParam, PDO::PARAM_STR);
            $stmt->bindValue(':search3', $searchParam, PDO::PARAM_STR);
        }

        $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Get total count of records
     * @param string $search
     * @return int
     */
    public function getTotalCount($search = '') {
        $sql = "SELECT COUNT(*) as count FROM atr_records WHERE 1=1";

        if (!empty($search)) {
            $sql .= " AND (
                osztaly LIKE :search1
                OR atr_nursing_cycle_id LIKE :search2
                OR atr_nursing_cycle_data_id LIKE :search3
            )";
        }

        $stmt = $this->pdo->prepare($sql);

        if (!empty($search)) {
            $searchParam = '%' . $search . '%';
            $stmt->bindValue(':search1', $searchParam, PDO::PARAM_STR);
            $stmt->bindValue(':search2', $searchParam, PDO::PARAM_STR);
            $stmt->bindValue(':search3', $searchParam, PDO::PARAM_STR);
        }

        $stmt->execute();

        return $stmt->fetch()['count'];
    }

    /**
     * Get record by ID
     * @param int $id
     * @return array|null
     */
    public function getById($id) {
        $sql = "SELECT
            r.*,
            a.display_name as creator_name
        FROM atr_records r
        LEFT JOIN admins a ON r.created_by_admin_id = a.id
        WHERE r.id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Update a record
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data) {
        $sql = "UPDATE atr_records SET
            osztaly = :osztaly,
            tavido = :tavido,
            atr_dismissing_type = :atr_dismissing_type,
            atr_nursing_cycle_id = :atr_nursing_cycle_id,
            atr_nursing_cycle_data_id = :atr_nursing_cycle_data_id
        WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':osztaly' => $data['osztaly'],
            ':tavido' => $data['tavido'],
            ':atr_dismissing_type' => $data['atr_dismissing_type'],
            ':atr_nursing_cycle_id' => $data['atr_nursing_cycle_id'],
            ':atr_nursing_cycle_data_id' => $data['atr_nursing_cycle_data_id'],
        ]);
    }

    /**
     * Delete a record
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        $sql = "DELETE FROM atr_records WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Get all records for export (only 6 columns)
     * @return array
     */
    public function getAllForExport() {
        $sql = "SELECT
            intezmeny as INTEZMENY,
            osztaly as OSZTALY,
            DATE_FORMAT(tavido, '%Y.%m.%d %H:%i') as TAVIDO,
            atr_dismissing_type as ATR_DISMISSING_TYPE,
            atr_nursing_cycle_id as ATR_NURSING_CYCLE_ID,
            atr_nursing_cycle_data_id as ATR_NURSING_CYCLE_DATA_ID
        FROM atr_records
        ORDER BY created_at DESC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Get dismissing type options
     * @return array
     */
    public static function getDismissingTypes() {
        return [
            'OTHER_DEPARTMENT' => 'Belső kórházi áthelyezés',
            'OTHER_INSTITUTE' => 'Más fekvőbeteg gyógyintézetbe történő áthelyezés',
            'HOME' => 'Otthonába bocsátva',
            'DECEASED' => 'Meghalt',
            'PARTIAL_INVOICE' => 'Részszámla',
            'HOME_ADAPTATION' => 'Otthonába adaptációs szabadságra bocsátva',
            'LEFT_VOLUNTARILY' => 'Önkényesen távozott',
            'LEFT_SOCIAL_HOME' => 'Szociális otthonba távozott',
            'FOLLOW_RECORD' => 'Folytató rekord következik',
            'PARTIAL_REPORT' => 'Naparányos finanszírozás, részjelentés',
            'CLOSURE_CARE' => 'Naparányos finanszírozást megelőző ellátás lezárása',
            'HIGH_VALUE_DISPOSAL' => 'Nagy értékű, országosan még nem elterjedt műtéti eljárás előtti ápolás lezárása',
            'CHEMO_TP_PROTOCOL' => 'Kemoterápiás protokollváltás történt',
            'REFER_REHAB' => 'Otthonába bocsátva, rehabilitációra irányítva',
            'REFER_PRIO_REHAB' => 'Otthonába bocsátva, elsőbbségi rehabilitációs ellátás megszakítása miatt',
            'PARENTERAL_NUTRITION' => 'Otthoni parenterális táplálás',
        ];
    }
}
