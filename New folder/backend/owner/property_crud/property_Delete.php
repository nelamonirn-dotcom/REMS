<?php
session_start();
require_once __DIR__ . '/dbconfig.php';

if (!isset($_SESSION['email'])) {
    header('Location: ../../admin/index.php');
    exit;
}

if (($_SESSION['role'] ?? '') !== 'owner') {
    header('Location: ../../admin/unauthorize.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

$csrf = $_POST['csrf'] ?? '';
if (
    !is_string($csrf)
    || empty($_SESSION['property_csrf'])
    || !hash_equals($_SESSION['property_csrf'], $csrf)
) {
    http_response_code(400);
    exit('Invalid request.');
}

$ownerId = (int)($_SESSION['user_id'] ?? 0);
$propertyId = filter_input(INPUT_POST, 'property_id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
if ($ownerId < 1 || !$propertyId) {
    header('Location: myproperty.php?msg=not_found');
    exit;
}

$message = 'not_found';
$conn->begin_transaction();
try {
    $check = $conn->prepare('SELECT property_id FROM properties WHERE property_id = ? AND owner_id = ? FOR UPDATE');
    $check->bind_param('ii', $propertyId, $ownerId);
    if (!$check->execute()) {
        throw new RuntimeException('Unable to verify property ownership.');
    }

    if ($check->get_result()->num_rows === 1) {
        $deleteForProperty = static function (string $sql) use ($conn, $propertyId): void {
            $statement = $conn->prepare($sql);
            $statement->bind_param('i', $propertyId);
            if (!$statement->execute()) {
                throw new RuntimeException('Unable to delete related property records.');
            }
        };

        // Remove dependent records before their parent rows to satisfy foreign keys.
        $deleteForProperty(
            'DELETE c FROM commissions AS c
             INNER JOIN agreements AS a ON a.agreement_id = c.agreement_id
             WHERE a.property_id = ?'
        );
        $deleteForProperty(
            'DELETE p FROM payments AS p
             INNER JOIN agreements AS a ON a.agreement_id = p.agreement_id
             WHERE a.property_id = ?'
        );
        $deleteForProperty('DELETE FROM visits WHERE property_id = ?');
        $deleteForProperty('DELETE FROM agreements WHERE property_id = ?');
        $deleteForProperty('DELETE FROM bookings WHERE property_id = ?');
        $deleteForProperty('DELETE FROM maintenance WHERE property_id = ?');
        $deleteForProperty('DELETE FROM agent_properties WHERE property_id = ?');

        $delete = $conn->prepare('DELETE FROM properties WHERE property_id = ? AND owner_id = ?');
        $delete->bind_param('ii', $propertyId, $ownerId);
        if (!$delete->execute()) {
            throw new RuntimeException('Unable to delete property.');
        }
        if ($delete->affected_rows !== 1) {
            throw new RuntimeException('Unable to delete property after removing related records.');
        }
        $message = 'deleted';
    }

    $conn->commit();
} catch (Throwable $error) {
    $conn->rollback();
    if ($error instanceof mysqli_sql_exception && (int)$error->getCode() === 1451) {
        $message = 'linked';
    } else {
        throw $error;
    }
}

header('Location: myproperty.php?msg=' . $message);
exit;
