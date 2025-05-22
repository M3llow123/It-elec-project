<?php
// Use environment variables for database connection
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$dbname = getenv('DB_NAME') ?: 'todolist';

// Create DB connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create todos table if it doesn't exist
$conn->query("CREATE TABLE IF NOT EXISTS todos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
    due_date DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// CREATE
if (isset($_POST['add'])) {
    $stmt = $conn->prepare("INSERT INTO todos (task, description, status, due_date) VALUES (?, ?, ?, ?)");
    $task = $_POST['task'];
    $description = $_POST['description'];
    $status = $_POST['status'];
    $due_date = $_POST['due_date'] ?: null;
    $stmt->bind_param("ssss", $task, $description, $status, $due_date);
    $stmt->execute();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// UPDATE
if (isset($_POST['update'])) {
    $stmt = $conn->prepare("UPDATE todos SET task=?, description=?, status=?, due_date=? WHERE id=?");
    $task = $_POST['task'];
    $description = $_POST['description'];
    $status = $_POST['status'];
    $due_date = $_POST['due_date'] ?: null;
    $id = (int)$_POST['id'];
    $stmt->bind_param("ssssi", $task, $description, $status, $due_date, $id);
    $stmt->execute();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// DELETE
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM todos WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

// READ
$result = $conn->query("SELECT * FROM todos ORDER BY status, due_date ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Todo List</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
</head>
<body>
<div class="container py-4">
  <h2 class="mb-4"><i class="bi bi-check2-circle"></i> Todo List</h2>
  <form method="POST" class="mb-4">
    <div class="row g-2">
      <div class="col-md-3"><input type="text" name="task" class="form-control" placeholder="Task" required></div>
      <div class="col-md-3"><input type="text" name="description" class="form-control" placeholder="Description"></div>
      <div class="col-md-2">
        <select name="status" class="form-select">
          <option value="pending">Pending</option>
          <option value="in_progress">In Progress</option>
          <option value="completed">Completed</option>
        </select>
      </div>
      <div class="col-md-3"><input type="datetime-local" name="due_date" class="form-control"></div>
      <div class="col-md-1"><button type="submit" name="add" class="btn btn-success w-100">Add</button></div>
    </div>
  </form>

  <table class="table table-bordered">
    <thead>
      <tr>
        <th>Task</th>
        <th>Description</th>
        <th>Status</th>
        <th>Due Date</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php while ($row = $result->fetch_assoc()): ?>
      <tr>
        <td><?= htmlspecialchars($row['task']) ?></td>
        <td><?= htmlspecialchars($row['description']) ?></td>
        <td><?= ucfirst(str_replace('_', ' ', $row['status'])) ?></td>
        <td><?= $row['due_date'] ? date('M d, Y H:i', strtotime($row['due_date'])) : '' ?></td>
        <td>
          <form method="POST" style="display:inline;">
            <input type="hidden" name="id" value="<?= $row['id'] ?>">
            <button type="submit" name="delete" value="<?= $row['id'] ?>" class="btn btn-sm btn-danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
