<?php
session_start();
require_once '../config.php';
$required_role = 'admin';
require_once '../includes/auth-guard.php';

$page_title = 'Manage Departments';
$extra_css  = ['forms.css'];
$errors     = [];

// Handle Add / Edit / Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid request.']; header("Location: ?"); exit();
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if (empty($name)) {
            $errors[] = 'Department name is required.';
        } else {
            $s = mysqli_prepare($conn, "INSERT INTO departments (name, description) VALUES (?,?)");
            mysqli_stmt_bind_param($s, 'ss', $name, $desc);
            if (mysqli_stmt_execute($s)) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => "Department '$name' added."];
                header("Location: ?"); exit();
            } else {
                $errors[] = 'Department already exists or could not be saved.';
            }
            mysqli_stmt_close($s);
        }
    } elseif ($action === 'edit') {
        $id   = (int)$_POST['dept_id'];
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if (empty($name)) {
            $errors[] = 'Name is required.';
        } else {
            $s = mysqli_prepare($conn, "UPDATE departments SET name=?, description=? WHERE id=?");
            mysqli_stmt_bind_param($s, 'ssi', $name, $desc, $id);
            if (mysqli_stmt_execute($s)) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Department updated.'];
                header("Location: ?"); exit();
            } else {
                $errors[] = 'Update failed.';
            }
            mysqli_stmt_close($s);
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['dept_id'];
        // Check if any doctors are assigned
        $chk = mysqli_prepare($conn, "SELECT COUNT(*) AS c FROM doctors WHERE department_id=?");
        mysqli_stmt_bind_param($chk, 'i', $id);
        mysqli_stmt_execute($chk);
        $cnt = mysqli_stmt_get_result($chk)->fetch_assoc()['c'];
        mysqli_stmt_close($chk);
        if ($cnt > 0) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => "Cannot delete: $cnt doctor(s) assigned to this department."];
        } else {
            $s = mysqli_prepare($conn, "DELETE FROM departments WHERE id=?");
            mysqli_stmt_bind_param($s, 'i', $id);
            mysqli_stmt_execute($s);
            mysqli_stmt_close($s);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Department deleted.'];
        }
        header("Location: ?"); exit();
    }
}

$departments = mysqli_query($conn,
    "SELECT d.id, d.name, d.description, COUNT(dr.id) AS doctor_count
     FROM departments d LEFT JOIN doctors dr ON dr.department_id = d.id
     GROUP BY d.id ORDER BY d.name")->fetch_all(MYSQLI_ASSOC);

$edit_id = (int)($_GET['edit'] ?? 0);
$editing = null;
if ($edit_id) {
    foreach ($departments as $d) {
        if ($d['id'] === $edit_id) { $editing = $d; break; }
    }
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

include '../includes/header.php';
include '../includes/sidebar-admin.php';
?>

<div class="page-header"><h1>Departments</h1></div>

<div class="dash-grid">
  <!-- Add / Edit form -->
  <div class="form-card" style="height:fit-content;">
    <h3 style="font-size:1rem;font-weight:700;margin-bottom:18px;">
      <?= $editing ? 'Edit Department' : 'Add New Department' ?>
    </h3>

    <?php if ($errors): ?>
      <div class="error-msg" style="margin-bottom:14px;"><i class="fa fa-exclamation-circle"></i>
        <?= htmlspecialchars($errors[0]) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
      <input type="hidden" name="action"     value="<?= $editing ? 'edit' : 'add' ?>">
      <?php if ($editing): ?>
      <input type="hidden" name="dept_id"    value="<?= $editing['id'] ?>">
      <?php endif; ?>

      <div class="field">
        <label>Department Name <span class="required">*</span></label>
        <input type="text" name="name" value="<?= htmlspecialchars($editing['name'] ?? '') ?>" required>
      </div>
      <div class="field">
        <label>Description</label>
        <textarea name="description" rows="3"><?= htmlspecialchars($editing['description'] ?? '') ?></textarea>
      </div>
      <div class="form-actions" style="padding-top:12px;border-top:none;margin-top:0;">
        <button type="submit" class="btn btn-primary btn-sm">
          <i class="fa fa-save"></i> <?= $editing ? 'Save Changes' : 'Add Department' ?>
        </button>
        <?php if ($editing): ?>
        <a href="?" class="btn btn-outline btn-sm">Cancel</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- List -->
  <div class="card">
    <div class="card-title"><i class="fa fa-hospital"></i> All Departments</div>
    <div class="table-wrapper">
      <table>
        <thead><tr><th>Name</th><th>Description</th><th>Doctors</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($departments as $d): ?>
          <tr>
            <td><strong><?= htmlspecialchars($d['name']) ?></strong></td>
            <td style="font-size:.82rem;color:var(--text-muted);max-width:200px;">
              <?= htmlspecialchars(substr($d['description'] ?? '', 0, 80)) ?>
            </td>
            <td><?= $d['doctor_count'] ?></td>
            <td style="white-space:nowrap;">
              <a href="?edit=<?= $d['id'] ?>" class="btn btn-sm btn-outline"><i class="fa fa-edit"></i> Edit</a>
              <?php if ($d['doctor_count'] == 0): ?>
              <form method="POST" id="deleteDeptForm<?= $d['id'] ?>" style="display:none;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="action"     value="delete">
                <input type="hidden" name="dept_id"    value="<?= $d['id'] ?>">
              </form>
              <button class="btn btn-sm btn-danger"
                      data-confirm-form="deleteDeptForm<?= $d['id'] ?>"
                      data-confirm-name="<?= htmlspecialchars($d['name']) ?>">
                <i class="fa fa-trash"></i>
              </button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Delete confirm modal -->
<div class="modal-overlay" id="confirmModal">
  <div class="modal">
    <div class="modal-icon danger"><i class="fa fa-trash"></i></div>
    <h3>Delete Department?</h3>
    <p id="confirmModalMsg">This action cannot be undone.</p>
    <div class="modal-actions">
      <button class="btn btn-danger" id="confirmModalOk">Yes, Delete</button>
      <button class="btn btn-ghost" onclick="closeModal('confirmModal')">Cancel</button>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
