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
use PHPMailer\PHPMailer\Exception;

function sanitize(string $value): string {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function sanitizePlain(string $value): string {
    return trim($value);
}

function validateWarrantyUrl(string $url): bool {
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    return in_array($scheme, ['http', 'https'], true);
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
    $filename = 'wrt_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = UPLOAD_DIR . $filename;

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Falha ao guardar o ficheiro.');
    }

    return $filename;
}

function buildCopyRow(string $label, string $value, int $id): string {
    $safeValue = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

    return <<<HTML
    <tr>
        <td style="padding:12px 16px;font-weight:600;color:#333;background:#f8f8f8;border:1px solid #e0e0e0;width:180px;vertical-align:top;">{$label}</td>
        <td style="padding:12px 16px;color:#555;border:1px solid #e0e0e0;vertical-align:top;">
            <span id="val-{$id}">{$safeValue}</span>
            <button onclick="copyValue('val-{$id}', this)" style="margin-left:8px;padding:4px 10px;font-size:11px;background:#FF9017;color:#fff;border:none;border-radius:4px;cursor:pointer;white-space:nowrap;">Copiar</button>
        </td>
    </tr>
HTML;
}

function sendRedirectEmail(array $data): void {
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
        $mail->Subject = 'Cliente Redirecionado para a Página da Marca #' . $data['id'];

        $emailText = $data['email'] ?: 'Não indicado';
        $warrantyUrl = htmlspecialchars($data['warranty_url'], ENT_QUOTES, 'UTF-8');

        $id = 0;
        $rows = '';
        $rows .= buildCopyRow('Nome', $data['client_name'], ++$id);
        $rows .= buildCopyRow('Morada', $data['address'], ++$id);
        $rows .= buildCopyRow('Telemóvel', $data['phone'], ++$id);
        $rows .= buildCopyRow('Email', $emailText, ++$id);
        $rows .= buildCopyRow('Equipamento', $data['equipment_type'], ++$id);
        $rows .= buildCopyRow('Marca', $data['equipment_brand'], ++$id);
        $rows .= buildCopyRow('Página da Marca', $warrantyUrl, ++$id);
        $rows .= buildCopyRow('Data', date('d/m/Y \à\s H:i'), ++$id);

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
        <h1 style="margin:0;color:#fff;font-size:20px;">Cliente Redirecionado para a Página da Marca #{$data['id']}</h1>
    </div>

    <div style="padding:24px 32px;">
        <p style="color:#555;font-size:14px;margin-top:0;">Um cliente com equipamento em garantia foi redirecionado para a página oficial da marca para abrir o pedido de assistência.</p>
        <table style="width:100%;border-collapse:collapse;margin-bottom:16px;">
            {$rows}
            {$attachments}
        </table>

        <p style="color:#999;font-size:12px;text-align:center;margin-top:24px;">
            Registo gerado via <strong>megactec.pt</strong> — Reply-To definido para o email do cliente.
        </p>
    </div>

</div>
<script>
function copyValue(id, btn) {
    var el = document.getElementById(id);
    if (!el) return;
    var text = el.textContent || el.innerText;
    navigator.clipboard.writeText(text).then(function() {
        btn.textContent = 'Copiado!';
        setTimeout(function() { btn.textContent = 'Copiar'; }, 2000);
    });
}
</script>
</body>
</html>
HTML;

        $mail->Body    = $html;
        $mail->AltBody = "Cliente redirecionado para a página da marca #{$data['id']}\nNome: {$data['client_name']}\nMorada: {$data['address']}\nTelemóvel: {$data['phone']}\nEmail: {$emailText}\nEquipamento: {$data['equipment_type']}\nMarca: {$data['equipment_brand']}\nPágina da Marca: {$data['warranty_url']}";

        $mail->send();
    } catch (Exception $e) {
        throw new RuntimeException('Falha no envio do email: ' . $mail->ErrorInfo);
    }
}

try {

    $required = ['client_name', 'address', 'phone', 'equipment_type', 'equipment_brand', 'warranty_url'];
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

    $warrantyUrl = sanitizePlain($_POST['warranty_url'] ?? '');
    if ($warrantyUrl !== '' && !validateWarrantyUrl($warrantyUrl)) {
        $errors[] = 'O URL da página da marca não é válido.';
    }

    $labelFile = $_FILES['label_photo'] ?? null;
    if ($labelFile && $labelFile['error'] !== UPLOAD_ERR_NO_FILE) {
        $error = validateFile($labelFile, false);
        if ($error) {
            $errors[] = $error;
        }
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

    $labelFilename = null;
    if ($labelFile && $labelFile['error'] !== UPLOAD_ERR_NO_FILE) {
        $labelFilename = saveFile($labelFile);
    }

    $invoiceFilename = null;
    if ($invoiceFile && $invoiceFile['error'] !== UPLOAD_ERR_NO_FILE) {
        $invoiceFilename = saveFile($invoiceFile);
    }

    $stmt = getDb()->prepare("
        INSERT INTO warranty_redirects
            (client_name, address, phone, email, equipment_type,
             equipment_brand, warranty_url, ip_address)
        VALUES
            (:client_name, :address, :phone, :email, :equipment_type,
             :equipment_brand, :warranty_url, :ip_address)
    ");

    $stmt->execute([
        ':client_name'      => trim($_POST['client_name']),
        ':address'          => trim($_POST['address']),
        ':phone'            => trim($_POST['phone']),
        ':email'            => $email ?: null,
        ':equipment_type'   => trim($_POST['equipment_type']),
        ':equipment_brand'  => trim($_POST['equipment_brand']),
        ':warranty_url'     => $warrantyUrl,
        ':ip_address'       => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);

    $redirectId = getDb()->lastInsertId();

    sendRedirectEmail([
        'id'              => (int) $redirectId,
        'client_name'     => sanitizePlain($_POST['client_name']),
        'address'         => sanitizePlain($_POST['address']),
        'phone'           => sanitizePlain($_POST['phone']),
        'email'           => $email,
        'equipment_type'  => sanitizePlain($_POST['equipment_type']),
        'equipment_brand' => sanitizePlain($_POST['equipment_brand']),
        'warranty_url'    => $warrantyUrl,
        'files'           => array_filter([
            ['path' => $labelFilename,   'label' => 'Fotografia da Etiqueta'],
            ['path' => $invoiceFilename, 'label' => 'Fotografia da Fatura'],
        ], fn($f) => $f['path'] !== null),
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Dados registados. A Megactec poderá entrar em contacto consigo.',
        'id'      => (int) $redirectId,
    ]);

} catch (Throwable $e) {
    error_log('Warranty redirect error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
    ]);
}