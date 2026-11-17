<?php
session_start();
?>
<?php include_once __DIR__ . "/dbconfig.php"; ?>
<?php
$recordsPerPage = 20;
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
$page = $page ?: 1;

$countResult = $conn->query("SELECT COUNT(*) AS total FROM properties");
if (!$countResult) {
    die("Unable to count student records: " . $conn->error);
}

$totalRecords = (int) $countResult->fetch_assoc()['total'];
$totalPages = max(1, (int) ceil($totalRecords / $recordsPerPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $recordsPerPage;

$stmt = $conn->prepare("SELECT * FROM properties ORDER BY id LIMIT ?, ?");
if (!$stmt) {
    die("Unable to prepare student list: " . $conn->error);
}

$stmt->bind_param("ii", $offset, $recordsPerPage);
if (!$stmt->execute()) {
    die("Unable to load properties records: " . $stmt->error);
}

$rawData = $stmt->get_result();
$firstRecord = $totalRecords ? $offset + 1 : 0;
$lastRecord = min($offset + $recordsPerPage, $totalRecords);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student list</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/owner/dashboard.css">
    <link rel="stylesheet" href="../../shared/assets/css/palette.css">

    <style>
        body {
            background: #fff2f2;
        }

        table {
            width: 100%;
            border: 1px solid #a9b5df;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 12px;
            overflow: hidden;
            background: #fff2f2;
        }

        th {
            padding: 12px 14px;
            border-bottom: 1px solid #a9b5df;
            background-color: #a9b5df;
            color: #2d336b;
            text-align: left;
        }

        td {
            padding: 12px 14px;
            border-bottom: 1px solid #eceef7;
            background: #fff2f2;
        }

        tbody tr:last-child td {
            border-bottom: 0;
        }

        .action {
            text-align: center;
        }

        .edit-icon {
            color: #000205;
        }

        .delete-icon {
            color: #f3041c;
        }

        .action a {
            margin: 0 6px;
            font-size: 18px;
            text-decoration: none;
        }
        .new-entry {
            display: inline-block;
            padding: 10px 16px;
            border-radius: 8px;
            background-color: #2d336b;
            color: #fff;
            text-decoration: none;
            font-weight: 700;
        }

        .new-entry:hover {
            background-color: #a9b5df;
            color: #2d336b;
        }

        .pagination {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 16px;
        }

        .pagination a {
            padding: 6px 10px;
            border: 1px solid #a9b5df;
            border-radius: 4px;
            color: #2d336b;
            text-decoration: none;
        }

        .pagination a:hover {
            background-color: #a9b5df;
            color: #2d336b;
        }
    </style>
</head>

<body>
    <div class="owner-layout">
        <?php
        $ownerPageBase = '../';
        $ownerPageTitle = 'Property List';
        $ownerActivePage = '';
        include __DIR__ . '/../include/owner_leftber.php';
        ?>
        <div class="owner-main-shell">
            <?php include __DIR__ . '/../../shared/partials/header.php'; ?>
            <main class="container py-4">
    <h1>Student list</h1>
    <a class="new-entry" href="Addproperty.php">
    <i class="fa-solid fa-plus"></i> New Entry
</a><br><br>

    <table border="1" cellspacing="0" cellpadding="10">
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Action</th>
        </tr>

        <?php while ($row = $rawData->fetch_assoc()) { ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['name']; ?></td>
                <td><?php echo $row['email']; ?></td>
                <td><?php echo $row['phone']; ?></td>
                <td class="action">
                    <a class="edit-icon"
                       title="Edit"
                       href="property_edit.php?id=<?php echo $row['id']; ?>">
                        <i class="fa-solid fa-pencil"></i>
                    </a>

                    <a class="delete-icon"
                       title="Delete"
                       onclick="return confirm('Are you sure to delete?')"
                       href="property_Delete.php?id=<?php echo $row['id']; ?>">
                        <i class="fa-solid fa-trash"></i>
                    </a>
                </td>
            </tr>
        <?php } ?>
    </table>

    <nav class="pagination" aria-label="Student list pages">
        <span>Showing <?php echo $firstRecord; ?>–<?php echo $lastRecord; ?> of <?php echo $totalRecords; ?></span>
        <?php if ($page > 1) { ?>
            <a href="?page=<?php echo $page - 1; ?>" aria-label="Previous page">Previous</a>
        <?php } ?>
        <span>Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
        <?php if ($page < $totalPages) { ?>
            <a href="?page=<?php echo $page + 1; ?>" aria-label="Next page">Next</a>
        <?php } ?>
    </nav>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>