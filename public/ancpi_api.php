<?php
header('Content-Type: application/json');

$uploadsDir = '/home/sambla/ancpi/public/uploads/';
$metaFile = $uploadsDir . 'documents.json';

function loadMeta() {
    global $metaFile;
    if (!file_exists($metaFile)) return [];
    $data = json_decode(file_get_contents($metaFile), true);
    return is_array($data) ? $data : [];
}

function saveMeta($data) {
    global $metaFile;
    file_put_contents($metaFile, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

$method = $_SERVER['REQUEST_METHOD'];

// GET - list documents, optionally filtered by titlu
if ($method === 'GET') {
    $docs = loadMeta();
    if (isset($_GET['titlu'])) {
        $titlu = $_GET['titlu'];
        $docs = array_values(array_filter($docs, function($d) use ($titlu) {
            return $d['titlu'] === $titlu;
        }));
    }
    if (isset($_GET['proprietar'])) {
        $prop = strtolower($_GET['proprietar']);
        $docs = array_values(array_filter($docs, function($d) use ($prop) {
            return strtolower($d['proprietar']) === $prop;
        }));
    }
    echo json_encode(['ok' => true, 'documents' => $docs]);
    exit;
}

// POST - upload document
if ($method === 'POST') {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['ok' => false, 'error' => 'No file or upload error']);
        exit;
    }

    $file = $_FILES['file'];
    $titlu = $_POST['titlu'] ?? '';
    $proprietar = $_POST['proprietar'] ?? '';
    $descriere = $_POST['descriere'] ?? '';

    if (empty($titlu) || empty($proprietar)) {
        echo json_encode(['ok' => false, 'error' => 'Titlu and proprietar are required']);
        exit;
    }

    // Validate file type
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
    if (!in_array($ext, $allowed)) {
        echo json_encode(['ok' => false, 'error' => 'Only PDF, JPG, PNG allowed']);
        exit;
    }

    // Max 20MB
    if ($file['size'] > 20 * 1024 * 1024) {
        echo json_encode(['ok' => false, 'error' => 'File too large (max 20MB)']);
        exit;
    }

    $id = uniqid('doc_');
    $safeName = $id . '.' . $ext;
    $dest = $uploadsDir . $safeName;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        echo json_encode(['ok' => false, 'error' => 'Failed to save file']);
        exit;
    }

    $doc = [
        'id' => $id,
        'titlu' => $titlu,
        'proprietar' => $proprietar,
        'descriere' => $descriere,
        'filename' => $safeName,
        'original_name' => basename($file['name']),
        'type' => $ext,
        'size' => $file['size'],
        'uploaded_at' => date('Y-m-d H:i:s')
    ];

    $docs = loadMeta();
    $docs[] = $doc;
    saveMeta($docs);

    echo json_encode(['ok' => true, 'document' => $doc]);
    exit;
}

// DELETE
if ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? '';
    if (empty($id)) {
        echo json_encode(['ok' => false, 'error' => 'ID required']);
        exit;
    }

    $docs = loadMeta();
    $found = null;
    foreach ($docs as $d) {
        if ($d['id'] === $id) { $found = $d; break; }
    }

    if (!$found) {
        echo json_encode(['ok' => false, 'error' => 'Not found']);
        exit;
    }

    // Delete file
    $filepath = $uploadsDir . $found['filename'];
    if (file_exists($filepath)) unlink($filepath);

    // Remove from meta
    $docs = array_values(array_filter($docs, function($d) use ($id) {
        return $d['id'] !== $id;
    }));
    saveMeta($docs);

    echo json_encode(['ok' => true]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
