<?php
session_start();

// Initialize the list if it doesn't exist
if (!isset($_SESSION['compare_ids'])) {
    $_SESSION['compare_ids'] = [];
}

$action = $_POST['action'] ?? '';
$id = $_POST['id'] ?? 0;

if ($action == 'add' && $id > 0) {
    // Limit to 3 cars max
    if (count($_SESSION['compare_ids']) < 3 && !in_array($id, $_SESSION['compare_ids'])) {
        $_SESSION['compare_ids'][] = $id;
    }
}

if ($action == 'remove' && $id > 0) {
    // Remove specific ID
    $key = array_search($id, $_SESSION['compare_ids']);
    if ($key !== false) {
        unset($_SESSION['compare_ids'][$key]);
        // Re-index array
        $_SESSION['compare_ids'] = array_values($_SESSION['compare_ids']);
    }
}

if ($action == 'clear') {
    $_SESSION['compare_ids'] = [];
}

// Return the current count so the frontend can update the bubble
echo count($_SESSION['compare_ids']);
?>