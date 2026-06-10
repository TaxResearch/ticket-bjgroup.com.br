<?php

/**
 * Cria a sessão PHP a partir do token JWT, VALIDANDO o token no servidor.
 * Chamado pelo front após login/cadastro.
 *
 * Segurança: NÃO confia em isDevTeam/email/name vindos do cliente. Bate em
 * GET /user/me com o token; só grava o que a API (fonte da verdade) devolve.
 */

require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$token = $input['token'] ?? null;

if (!$token) {
    http_response_code(400);
    echo json_encode(['error' => 'Token required']);
    exit;
}

// Valida o token na API e obtém os dados reais do usuário (inclusive isDevTeam).
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "Authorization: Bearer {$token}\r\nContent-Type: application/json",
        'ignore_errors' => true,
        'timeout' => 8,
    ],
]);
$response = @file_get_contents(API_BASE_URL . '/user/me', false, $context);

$httpCode = 0;
if (isset($http_response_header) && preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $m)) {
    $httpCode = (int) $m[1];
}
$me = $response ? json_decode($response, true) : null;

if ($httpCode !== 200 || !is_array($me) || empty($me['email'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Token inválido']);
    exit;
}

// Sessão limpa + dados validados pelo servidor (não os do cliente).
session_unset();
setAuthData(
    $token,
    $me['email'],
    $me['name'] ?? '',
    !empty($me['isDevTeam'])
);
session_regenerate_id(true);  // contra session fixation
session_write_close();

http_response_code(200);
echo json_encode(['success' => true, 'isDevTeam' => !empty($me['isDevTeam'])]);
