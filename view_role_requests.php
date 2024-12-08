<?php
include 'db.php'; // Подключение к базе данных
session_start();



if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: view_posts.php"); // Перенаправление, если не админ
    exit();
}

// Обработка одобрения или отклонения заявки
if (isset($_GET['action']) && isset($_GET['id'])) {
    $requestId = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action === 'approve') {
        // Одобрение заявки
        $updateSql = "UPDATE role_requests SET status = 'approved' WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("i", $requestId);
        $updateStmt->execute();
        
        // Присвоение роли "traveler" запрашивающему пользователю
        $userIdSql = "SELECT user_id FROM role_requests WHERE id = ?";
        $userIdStmt = $conn->prepare($udserIdSql);
        $userIdStmt->bind_param("i", $requestId);
        $userIdStmt->execute();
        $userIdResult = $userIdStmt->get_result();
        
        if ($userIdResult->num_rows > 0) {
            $userRow = $userIdResult->fetch_assoc();
            $userId = $userRow['user_id'];

            // Обновление роли пользователя
            $updateUserRoleSql = "UPDATE users SET role = 'traveler' WHERE id = ?";
            $updateUserRoleStmt = $conn->prepare($updateUserRoleSql);
            $updateUserRoleStmt->bind_param("i", $userId);
            $updateUserRoleStmt->execute();
            $updateUserRoleStmt->close();

            // Добавление уведомления пользователю
            $notificationMessage = 'Ваша заявка на роль путешественника одобрена.';
            $notificationSql = "INSERT INTO notifications (user_id, message) VALUES (?, ?)";
            $notificationStmt = $conn->prepare($notificationSql);
            $notificationStmt->bind_param("is", $userId, $notificationMessage);
            $notificationStmt->execute();
            $notificationStmt->close();

            // Установка уведомления в сессии
            $_SESSION['notification'] = $notificationMessage; // Добавить эту строку
        }

        // Удаление заявки
        $deleteSql = "DELETE FROM role_requests WHERE id = ?";
        $deleteStmt = $conn->prepare($deleteSql);
        $deleteStmt->bind_param("i", $requestId);
        $deleteStmt->execute();
        $deleteStmt->close();
    } elseif ($action === 'deny') {
        // Отклонение заявки
        $updateSql = "UPDATE role_requests SET status = 'denied' WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("i", $requestId);
        $updateStmt->execute();

        // Добавление уведомления пользователю
        $userIdSql = "SELECT user_id FROM role_requests WHERE id = ?";
        $userIdStmt = $conn->prepare($userIdSql);
        $userIdStmt->bind_param("i", $requestId);
        $userIdStmt->execute();
        $userIdResult = $userIdStmt->get_result();

        if ($userIdResult->num_rows > 0) {
            $userRow = $userIdResult->fetch_assoc();
            $userId = $userRow['user_id'];

            $notificationMessage = 'Ваша заявка на роль отклонена.';
            $notificationSql = "INSERT INTO notifications (user_id, message) VALUES (?, ?)";
            $notificationStmt = $conn->prepare($notificationSql);
            $notificationStmt->bind_param("is", $userId, $notificationMessage);
            $notificationStmt->execute();
            $notificationStmt->close();

            // Установка уведомления в сессии
            $_SESSION['notification'] = $notificationMessage; // Добавить эту строку
        }

        // Удаление заявки
        $deleteSql = "DELETE FROM role_requests WHERE id = ?";
        $deleteStmt = $conn->prepare($deleteSql);
        $deleteStmt->bind_param("i", $requestId);
        $deleteStmt->execute();
        $deleteStmt->close();
    }

    header("Location: view_role_requests.php");
    exit();
}

// Получение списка заявок
$sql = "SELECT r.id, u.username, r.created_at 
        FROM role_requests r 
        JOIN users u ON r.user_id = u.id
        ORDER BY r.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заявки на роль</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Заявки на роль</h1>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Пользователь</th>
                <th>Дата создания</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                        <td>
                            <a href="?action=approve&id=<?php echo $row['id']; ?>">Одобрить</a>
                            <a href="?action=deny&id=<?php echo $row['id']; ?>">Отклонить</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td x>Нет заявок на роль.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <a href="view_posts.php" class="add-post-btn">Вернуться к постам</a>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>