<?php
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$db = getenv('DB_NAME') ?: 'todolist';

$conn = new mysqli('db', 'root', 'rootpassword', 'todolist');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// The rest of your code continues here...


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

// CREATE - Add new todo
if (isset($_POST['add']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $stmt = $conn->prepare("INSERT INTO todos (task, description, status, due_date) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $_POST['task'], $_POST['description'], $_POST['status'], $_POST['due_date']);
    
    if ($stmt->execute()) {
        echo "<script>alert('Todo added successfully');</script>";
        echo "<script>window.location.href = '".$_SERVER['PHP_SELF']."';</script>";
        exit();
    } else {
        echo "<script>alert('Error adding todo: " . $stmt->error . "');</script>";
    }
    $stmt->close();
}

// UPDATE - Edit todo
if (isset($_POST['update'])) {
    $stmt = $conn->prepare("UPDATE todos SET task=?, description=?, status=?, due_date=? WHERE id=?");
    $stmt->bind_param("ssssi", $_POST['task'], $_POST['description'], $_POST['status'], $_POST['due_date'], $_POST['id']);
    
    if ($stmt->execute()) {
        echo "<script>alert('Todo updated successfully');</script>";
        echo "<script>window.location.href = '".$_SERVER['PHP_SELF']."';</script>";
        exit();
    } else {
        echo "<script>alert('Error updating todo: " . $stmt->error . "');</script>";
    }
    $stmt->close();
}

// DELETE - Remove todo
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM todos WHERE id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo "<script>alert('Todo deleted successfully');</script>";
        echo "<script>window.location.href = '".strtok($_SERVER['REQUEST_URI'], '?')."';</script>";
        exit();
    } else {
        echo "<script>alert('Error deleting todo');</script>";
    }
    $stmt->close();
}

// READ - Get all todos
$result = $conn->query("SELECT * FROM todos ORDER BY status, due_date ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Todo List</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f8f9fa;
    }
    .main-content {
      padding: 20px;
    }
    .todo-card {
      transition: all 0.3s ease;
      margin-bottom: 15px;
      border-radius: 10px;
    }
    .todo-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    .status-pending {
      border-left: 5px solid #ffc107;
    }
    .status-in_progress {
      border-left: 5px solid #17a2b8;
    }
    .status-completed {
      border-left: 5px solid #28a745;
    }
    .badge-pending {
      background-color: #ffc107;
      color: #212529;
    }
    .badge-in_progress {
      background-color: #17a2b8;
    }
    .badge-completed {
      background-color: #28a745;
    }
  </style>
</head>
<body>



<!-- Main Content -->
<div class="main-content">
  <div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2><i class="bi bi-check2-circle"></i> My Todo List</h2>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTodoModal">
        <i class="bi bi-plus-lg"></i> Add Todo
      </button>
    </div>

    <!-- Todo List -->
    <div class="row">
      <?php while ($row = $result->fetch_assoc()): ?>
        <div class="col-md-4">
          <div class="card todo-card status-<?= $row['status'] ?>">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="card-title"><?= htmlspecialchars($row['task']) ?></h5>
                <span class="badge bg-<?= $row['status'] == 'pending' ? 'warning' : ($row['status'] == 'in_progress' ? 'info' : 'success') ?>">
                  <?= str_replace('_', ' ', ucfirst($row['status'])) ?>
                </span>
              </div>
              <p class="card-text text-muted"><?= htmlspecialchars($row['description']) ?></p>
              <?php if ($row['due_date']): ?>
                <p class="text-muted small">
                  <i class="bi bi-calendar"></i> Due: <?= date('M j, Y g:i A', strtotime($row['due_date'])) ?>
                </p>
              <?php endif; ?>
              <div class="d-flex justify-content-end">
                <button class="btn btn-sm btn-outline-primary me-2" data-bs-toggle="modal" 
                  data-bs-target="#editTodoModal<?= $row['id'] ?>">
                  <i class="bi bi-pencil"></i>
                </button>
                <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger" 
                  onclick="return confirm('Delete this todo?')">
                  <i class="bi bi-trash"></i>
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Edit Todo Modal -->
        <div class="modal fade" id="editTodoModal<?= $row['id'] ?>" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <form method="POST">
                <div class="modal-header">
                  <h5 class="modal-title">Edit Todo</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <input type="hidden" name="id" value="<?= $row['id'] ?>">
                  <div class="mb-3">
                    <label class="form-label">Task</label>
                    <input type="text" name="task" class="form-control" value="<?= htmlspecialchars($row['task']) ?>" required>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($row['description']) ?></textarea>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                      <option value="pending" <?= $row['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                      <option value="in_progress" <?= $row['status'] == 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                      <option value="completed" <?= $row['status'] == 'completed' ? 'selected' : '' ?>>Completed</option>
                    </select>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Due Date</label>
                    <input type="datetime-local" name="due_date" class="form-control" 
                      value="<?= $row['due_date'] ? str_replace(' ', 'T', substr($row['due_date'], 0, 16)) : '' ?>">
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                  <button type="submit" name="update" class="btn btn-primary">Save Changes</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
  </div>
</div>

<!-- Add Todo Modal -->
<div class="modal fade" id="addTodoModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title">Add New Todo</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Task</label>
            <input type="text" name="task" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              <option value="pending">Pending</option>
              <option value="in_progress">In Progress</option>
              <option value="completed">Completed</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Due Date</label>
            <input type="datetime-local" name="due_date" class="form-control">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" name="add" class="btn btn-primary">Add Todo</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>