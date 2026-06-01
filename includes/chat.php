<?php

declare(strict_types=1);

function chat_send_system_message(mysqli $conn, int $receiverId, string $message): bool
{
    if ($receiverId <= 0) {
        return false;
    }

    $message = trim((string)$message);
    if ($message === '') {
        return false;
    }

    if (function_exists('mb_substr')) {
        $message = mb_substr($message, 0, 2000);
    } else {
        $message = substr($message, 0, 2000);
    }

    $createdAt = (new DateTime('now', new DateTimeZone('Europe/Riga')))->format('Y-m-d H:i:s');
    $stmt = $conn->prepare('INSERT INTO est_zinas (sutitaja_id, sanemeja_id, zina, izlasita, created_at) VALUES (NULL, ?, ?, 0, ?)');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('iss', $receiverId, $message, $createdAt);
    $ok = $stmt->execute();
    $stmt->close();
    return (bool)$ok;
}
