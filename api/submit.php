<?php

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido.']);
    exit;
}

require_once __DIR__ . '/../includes/db.php';

require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sanitize(string $value): string {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function sanitizePlain(string $value): string {
    return trim($value);
}

function validateFile(array $file, bool $required): ?string {
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return $required ? 'Ficheiro em falta.' : null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return 'Erro no upload do ficheiro.';
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return 'O ficheiro excede 10MB.';
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, ALLOWED_EXTENSIONS, true)) {
        return 'Formato não permitido. Use JPG, PNG, HEIC ou WebP.';
    }

    if (class_exists('finfo')) {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        if (!in_array($mimeType, ALLOWED_MIME_TYPES, true)) {
            return 'Tipo de ficheiro não permitido.';
        }
    } else {
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            return 'Ficheiro não é uma imagem válida.';
        }
    }

    return null;
}

function saveFile(array $file): string {
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (class_exists('finfo')) {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        $mimeToExt = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/heic' => 'heic',
            'image/webp' => 'webp',
        ];
        $ext = $mimeToExt[$mimeType] ?? $ext;
    }
    $filename = 'srq_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = UPLOAD_DIR . $filename;

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Falha ao guardar o ficheiro.');
    }

    return $filename;
}

function buildRow(string $label, string $value): string {
    $safeValue = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

    return <<<HTML
    <tr>
        <td style="padding:12px 16px;font-weight:600;color:#333;background:#f8f8f8;border:1px solid #e0e0e0;width:180px;vertical-align:top;">{$label}</td>
        <td style="padding:12px 16px;color:#555;border:1px solid #e0e0e0;vertical-align:top;">{$safeValue}</td>
    </tr>
HTML;
}

function sendEmail(array $data): void {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress(EMAIL_TO);

        if (!empty($data['email']) && filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $mail->addReplyTo($data['email'], $data['client_name']);
        }

        $mail->isHTML(true);
        $mail->Subject = $data['email_subject'];

        $warrantyText = $data['has_warranty'] ? 'Sim' : 'Não';
        $emailText = $data['email'] ?: 'Não indicado';

        $rows = '';
        $rows .= buildRow('Nome', $data['client_name']);
        $rows .= buildRow('Morada', $data['address']);
        $rows .= buildRow('Telemóvel', $data['phone']);
        $rows .= buildRow('Email', $emailText);
        $nifText = $data['nif'] ?: 'Não indicado';
        $rows .= buildRow('NIF', $nifText);
        $rows .= buildRow('Equipamento', $data['equipment_type']);
        $rows .= buildRow('Garantia', $warrantyText);
        $rows .= buildRow('Sintoma', $data['symptom']);
        $rows .= buildRow('Data', date('d/m/Y \à\s H:i'));

        $attachments = '';
        foreach ($data['files'] as $file) {
            $filePath = UPLOAD_DIR . $file['path'];
            if (file_exists($filePath)) {
                $mail->addAttachment($filePath, $file['label']);
                $attachments .= '<tr><td colspan="2" style="padding:8px 16px;color:#888;border:1px solid #e0e0e0;font-size:13px;">Anexo: ' . htmlspecialchars($file['label'], ENT_QUOTES, 'UTF-8') . '</td></tr>';
            }
        }

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,Helvetica,sans-serif;">
<div style="max-width:650px;margin:20px auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">

    <div style="background:#FF9017;padding:24px 32px;">
        <h1 style="margin:0;color:#fff;font-size:20px;">Novo Pedido de Assistência Técnica #{$data['request_id']}</h1>
    </div>

    <div style="padding:24px 32px;">
        <table style="width:100%;border-collapse:collapse;margin-bottom:16px;">
            {$rows}
            {$attachments}
        </table>

        <p style="color:#999;font-size:12px;text-align:center;margin-top:24px;">
            Pedido recebido via <strong>megactec.pt</strong> — Reply-To definido para o email do cliente.
        </p>
    </div>

</div>
</body>
</html>
HTML;

        $mail->Body    = $html;
        $mail->AltBody = "Pedido #{$data['request_id']}\nNome: {$data['client_name']}\nMorada: {$data['address']}\nTelemóvel: {$data['phone']}\nEmail: {$emailText}\nNIF: {$nifText}\nEquipamento: {$data['equipment_type']}\nGarantia: {$warrantyText}\nSintoma: {$data['symptom']}";

        $mail->send();
    } catch (Exception $e) {
        throw new RuntimeException('Falha no envio do email: ' . $mail->ErrorInfo);
    }
}

try {

    $required = ['client_name', 'address', 'phone', 'equipment_type', 'symptom'];
    $errors = [];

    foreach ($required as $field) {
        $value = $_POST[$field] ?? '';
        if (empty(trim($value))) {
            $errors[] = "O campo \"{$field}\" é obrigatório.";
        }
    }

    $email = sanitizePlain($_POST['email'] ?? '');
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'O email indicado não é válido.';
    }

    $labelFile = $_FILES['label_photo'] ?? null;
    if ($labelFile) {
        $error = validateFile($labelFile, true);
        if ($error) {
            $errors[] = $error;
        }
    } else {
        $errors[] = 'A fotografia da etiqueta é obrigatória.';
    }

    $invoiceFile = $_FILES['invoice_photo'] ?? null;
    if ($invoiceFile && $invoiceFile['error'] !== UPLOAD_ERR_NO_FILE) {
        $error = validateFile($invoiceFile, false);
        if ($error) {
            $errors[] = $error;
        }
    }

    if (!empty($errors)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    $labelFilename = saveFile($labelFile);
    $invoiceFilename = null;

    if ($invoiceFile && $invoiceFile['error'] !== UPLOAD_ERR_NO_FILE) {
        $invoiceFilename = saveFile($invoiceFile);
    }

    $hasWarranty = isset($_POST['has_warranty']) && $_POST['has_warranty'] === '1' ? 1 : 0;

    $nif = sanitizePlain($_POST['nif'] ?? '');
    if ($nif !== '' && !preg_match('/^\d{9}$/', $nif)) {
        $errors[] = 'O NIF deve conter exatamente 9 dígitos.';
    }

    if (!empty($errors)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    $stmt = getDb()->prepare("
        INSERT INTO assistance_requests
            (client_name, address, phone, email, nif, equipment_type, has_warranty,
             label_photo, invoice_photo, symptom, ip_address)
        VALUES
            (:client_name, :address, :phone, :email, :nif, :equipment_type, :has_warranty,
             :label_photo, :invoice_photo, :symptom, :ip_address)
    ");

    $stmt->execute([
        ':client_name'            => trim($_POST['client_name']),
        ':address'                => trim($_POST['address']),
        ':phone'                  => trim($_POST['phone']),
        ':email'                  => $email ?: null,
        ':nif'                    => $nif ?: null,
        ':equipment_type'         => trim($_POST['equipment_type']),
        ':has_warranty'           => $hasWarranty,
        ':label_photo'            => $labelFilename,
        ':invoice_photo'          => $invoiceFilename,
        ':symptom'                => sanitizePlain($_POST['symptom']),
        ':ip_address'             => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);

    $requestId = getDb()->lastInsertId();

    sendEmail([
        'request_id'      => $requestId,
        'client_name'     => sanitizePlain($_POST['client_name']),
        'address'         => sanitizePlain($_POST['address']),
        'phone'           => sanitizePlain($_POST['phone']),
        'email'           => $email,
        'nif'             => $nif,
        'equipment_type'  => sanitizePlain($_POST['equipment_type']),
        'has_warranty'    => $hasWarranty,
        'symptom'         => sanitizePlain($_POST['symptom']),
        'files'           => array_filter([
            ['path' => $labelFilename,   'label' => 'Fotografia da Etiqueta'],
            ['path' => $invoiceFilename, 'label' => 'Fotografia da Fatura'],
        ], fn($f) => $f['path'] !== null),
        'email_subject'   => EMAIL_SUBJECT . ' #' . $requestId,
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'O seu pedido de assistência foi enviado com sucesso. Entraremos em contacto brevemente.',
        'id'      => (int) $requestId,
    ]);

} catch (Throwable $e) {
    error_log('Service request error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
    ]);
}
