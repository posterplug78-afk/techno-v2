<?php
// ============================================================
//  EduQueue – API: Live Board Data (public)
// ============================================================
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/queue.php';

header('Content-Type: application/json');
echo json_encode(getLiveBoardData());
