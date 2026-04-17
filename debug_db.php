<?php
include('db.php');

echo "Database Status Check:\n\n";

$result = mysqli_query($conn, 'SELECT COUNT(*) as count FROM members');
$row = mysqli_fetch_assoc($result);
echo 'Total members: ' . $row['count'] . "\n";

$result2 = mysqli_query($conn, 'SELECT COUNT(*) as count FROM users WHERE role="member"');
$row2 = mysqli_fetch_assoc($result2);
echo 'Total member users: ' . $row2['count'] . "\n";

echo "\nUser roles breakdown:\n";
$roles = mysqli_query($conn, 'SELECT role, COUNT(*) AS c FROM users GROUP BY role');
if ($roles) {
    while ($r = mysqli_fetch_assoc($roles)) {
        $roleLabel = ($r['role'] === '' || $r['role'] === null) ? '(empty)' : $r['role'];
        echo "- " . $roleLabel . ": " . $r['c'] . "\n";
    }
}

$result3 = mysqli_query($conn, 'SELECT COUNT(*) as count FROM workout_schedules');
$row3 = mysqli_fetch_assoc($result3);
echo 'Total workout schedules: ' . $row3['count'] . "\n";

echo "\nRecent members:\n";
$members = mysqli_query($conn, 'SELECT name, email, phone FROM members LIMIT 5');
while ($member = mysqli_fetch_assoc($members)) {
    echo "- " . $member['name'] . " (" . $member['email'] . ") - " . $member['phone'] . "\n";
}

echo "\nRecent workout schedules:\n";
$schedules = mysqli_query($conn, 'SELECT title, target_type FROM workout_schedules LIMIT 5');
while ($schedule = mysqli_fetch_assoc($schedules)) {
    echo "- " . $schedule['title'] . " (target: " . $schedule['target_type'] . ")\n";
}
?>