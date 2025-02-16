<?php
session_start();

include './config.php';
$query = new Database();
$query->checkUserSession('user');

$sessions = $query->select('active_sessions', '*', 'user_id = ?', [$_SESSION['user_id']], 'i');

if (isset($_GET['token'])) {
    $query->delete('active_sessions', 'user_id = ? AND session_token = ?', [$_SESSION['user_id'], $_GET['token']], 'is');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_POST['update_session'])) {
    $device_name = $_POST['device_name'];

    $query->update(
        'active_sessions',
        ['device_name' => $device_name],
        'session_token = ? AND user_id = ?',
        [session_id(), $_SESSION['user_id']],
        'si'
    );

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <link rel="stylesheet" href="./src/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
</head>

<body class="hold-transition sidebar-mini">
    <div class="wrapper">
        <?php include './header.php'; ?>
        <div class="content-wrapper">

            <section class="content">
                <div class="container-fluid">

                    <table class="table table-striped table-hover table-bordered">
                        <thead class="bg-dark">
                            <tr>
                                <th> №</th>
                                <th><i class="fas fa-desktop"></i> Device Name</th>
                                <th><i class="fas fa-network-wired"></i> IP Address</th>
                                <th><i class="fas fa-clock"></i> Last Activity</th>
                                <th><i class="fas fa-cog"></i> Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sessions as $index => $session): ?>
                                <tr id="session-<?php echo htmlspecialchars($session['session_token']); ?>">
                                    <td><?= $index + 1 ?></td>
                                    <td class="device-name"> <?php echo htmlspecialchars($session['device_name']); ?></td>
                                    <td><?php echo htmlspecialchars($session['ip_address']); ?></td>
                                    <td><?php echo date('H:i:s d-m-Y', strtotime($session['last_activity'])); ?></td>
                                    <td class="text-center">
                                        <?php if (session_id() == $session['session_token']): ?>
                                            <button class="btn btn-warning btn-sm"
                                                onclick="openEditModal('<?php echo htmlspecialchars($session['device_name']); ?>')">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                        <?php endif ?>
                                        <button class="btn btn-danger btn-sm"
                                            onclick="confirmRemoval('<?php echo htmlspecialchars($session['session_token']); ?>')">
                                            <i class="fas fa-trash-alt"></i> Remove
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editModalLabel">Edit Device Name</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form method="POST">
                                        <div class="form-group">
                                            <label for="deviceName">Device Name</label>
                                            <input type="text" class="form-control" name="device_name" id="deviceName"
                                                required>
                                        </div>
                                        <button type="submit" name="update_session" class="btn btn-primary">
                                            Save changes
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </section>

        </div>
        <?php include './footer.php'; ?>
    </div>
    <script>
        function openEditModal(deviceName) {
            document.getElementById('deviceName').value = deviceName;
            $('#editModal').modal('show');
        }

        function confirmRemoval(token) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'You won\'t be able to revert this!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, remove it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'active_sessions.php?token=' + token;
                }
            });
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="./src/js/jquery.min.js"></script>
    <script src="./src/js/bootstrap.bundle.min.js"></script>
    <script src="./src/js/adminlte.min.js"></script>
</body>

</html>