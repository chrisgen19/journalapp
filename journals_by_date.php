<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'journal.php';

$auth = new Auth($conn);
$journal = new Journal($conn);

$user = $auth->getUserDetails($_SESSION['user_id']);

if (!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Get the date from URL
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Validate date format
if (!DateTime::createFromFormat('Y-m-d', $date)) {
    header("Location: calendar.php");
    exit();
}

// Get all entries for the specific date
$query = "SELECT j.*, 
        (SELECT image_path FROM journal_images WHERE journal_id = j.id LIMIT 1) as thumbnail,
        (SELECT COUNT(*) FROM journal_images WHERE journal_id = j.id) as image_count
        FROM journals j 
        WHERE j.user_id = ? 
        AND DATE(j.entry_date) = ?
        ORDER BY j.entry_date DESC, j.id DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("is", $_SESSION['user_id'], $date);
$stmt->execute();
$entries = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Journal Entries for <?php echo date('F j, Y', strtotime($date)); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:ital,wght@0,300;0,400;0,700;1,400&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Inter', sans-serif;
            color: #2c3e50;
        }
        .container-fluid {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        .journal-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .page-header {
            margin-bottom: 2rem;
        }
        .date-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .date-title {
            font-family: 'Merriweather', serif;
            font-size: 2rem;
            color: #1a202c;
            margin-bottom: 0.5rem;
        }
        .btn-back {
            text-decoration: none;
            color: #6c757d;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .btn-back:hover {
            background: rgba(255, 255, 255, 0.5);
            color: #4a5568;
        }
        .journal-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .journal-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }
        .journal-thumbnail {
            height: 200px;
            position: relative;
            overflow: hidden;
            border-bottom: 1px solid #e9ecef;
        }
        .journal-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .journal-card:hover .journal-thumbnail img {
            transform: scale(1.05);
        }
        .no-image-icon {
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            color: #cbd5e0;
            font-size: 2rem;
        }
        .journal-content {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .journal-title {
            font-family: 'Merriweather', serif;
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }
        .journal-title a {
            color: #2d3748;
            text-decoration: none;
        }
        .journal-title a:hover {
            color: #4a90e2;
        }
        .journal-preview {
            color: #4a5568;
            margin-bottom: 1rem;
            flex: 1;
        }
        .journal-footer {
            padding: 1rem 1.5rem;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .tag {
            font-size: 0.8rem;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            background: #e9ecef;
            color: #495057;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container-fluid">
        <div class="journal-container">
            <a href="calendar.php" class="btn-back">
                <i class="fas fa-arrow-left"></i>
                Back to Calendar
            </a>

            <div class="date-header">
                <h1 class="date-title"><?php echo date('F j, Y', strtotime($date)); ?></h1>
                <p class="text-muted">All journal entries for this date</p>
            </div>

            <?php if ($entries->num_rows > 0): ?>
                <div class="row g-4">
                    <?php while ($entry = $entries->fetch_assoc()): 
                        $preview = strip_tags(substr($entry['content'], 0, 150));
                    ?>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="journal-card">
                                <div class="journal-thumbnail">
                                    <?php if ($entry['thumbnail']): ?>
                                        <img src="<?php echo htmlspecialchars($entry['thumbnail']); ?>" alt="Journal thumbnail">
                                    <?php else: ?>
                                        <div class="no-image-icon">
                                            <i class="fas fa-file-alt"></i>
                                            <span>Text Only</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="journal-content">
                                    <h2 class="journal-title">
                                        <a href="journal_view.php?id=<?php echo $entry['id']; ?>">
                                            <?php echo htmlspecialchars($entry['title']); ?>
                                        </a>
                                    </h2>

                                    <div class="journal-preview">
                                        <?php echo htmlspecialchars($preview) . '...'; ?>
                                    </div>
                                </div>

                                <div class="journal-footer">
                                    <span class="tag">
                                        <i class="fas fa-images"></i>
                                        <?php echo $entry['image_count']; ?> images
                                    </span>
                                    
                                    <div class="btn-group">
                                        <a href="journal_view.php?id=<?php echo $entry['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                           <i class="far fa-eye me-1"></i>
                                        </a>
                                        <a href="journal_edit.php?id=<?php echo $entry['id']; ?>" 
                                           class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="deleteEntry(<?php echo $entry['id']; ?>)" 
                                                class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center">
                    <div class="alert alert-info">
                        No journal entries found for this date.
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteEntry(id) {
            if (confirm('Are you sure you want to delete this entry?')) {
                window.location.href = 'journal_delete.php?id=' + id;
            }
        }
    </script>
</body>
</html>