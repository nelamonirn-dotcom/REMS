<?php
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

    <style>
        table {
            width: 50%;
            border-collapse: collapse;
        }

        th {
            background-color: #4f60fa;
            color: white;
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
    background-color: #5cf748;
    color: black;
    border-radius: 5px;
    text-decoration: none;
    font-weight: bold;
}

.new-entry:hover {
    background-color: #146c43;
}

        .pagination {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 16px;
        }

        .pagination a {
            padding: 6px 10px;
            border: 1px solid #4f60fa;
            border-radius: 4px;
            color: #000205;
            text-decoration: none;
        }

        .pagination a:hover {
            background-color: #4f60fa;
            color: white;
        }
    </style>
</head>

<body>
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
</body>
</html>