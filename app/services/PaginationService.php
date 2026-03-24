<?php

class PaginationService {
    
    public static function paginate($query, $page = 1, $perPage = 20) {
        $page = max(1, (int)$page);
        $offset = ($page - 1) * $perPage;
        
        // Compter le total
        $countQuery = preg_replace('/SELECT .+ FROM/i', 'SELECT COUNT(*) as total FROM', $query);
        $countQuery = preg_replace('/ORDER BY .+$/i', '', $countQuery);
        
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query($countQuery);
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Ajouter LIMIT et OFFSET
        $query .= " LIMIT $perPage OFFSET $offset";
        
        $stmt = $db->query($query);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'data' => $data,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => ceil($total / $perPage),
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $total)
        ];
    }
    
    public static function renderLinks($pagination, $baseUrl) {
        $html = '<nav><ul class="pagination">';
        
        // Précédent
        if ($pagination['current_page'] > 1) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . ($pagination['current_page'] - 1) . '">Précédent</a></li>';
        }
        
        // Pages
        for ($i = 1; $i <= $pagination['last_page']; $i++) {
            $active = $i == $pagination['current_page'] ? 'active' : '';
            $html .= '<li class="page-item ' . $active . '"><a class="page-link" href="' . $baseUrl . '?page=' . $i . '">' . $i . '</a></li>';
        }
        
        // Suivant
        if ($pagination['current_page'] < $pagination['last_page']) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . ($pagination['current_page'] + 1) . '">Suivant</a></li>';
        }
        
        $html .= '</ul></nav>';
        
        return $html;
    }
}
