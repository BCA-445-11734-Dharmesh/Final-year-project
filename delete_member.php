<?php
include('auth.php');
checkLogin();
requireAdmin(); // Only admins can delete members

include('db.php');
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM members WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            header("Location: index.php?success=Member deleted successfully");
        } else {
            header("Location: index.php?error=Failed to delete member");
        }
        $stmt->close();
    } else {
        header("Location: index.php?error=Invalid member ID");
    }
} else {
    header("Location: index.php");
}
?>