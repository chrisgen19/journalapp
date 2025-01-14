<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'journal.php';
require_once 'tag_manager.php';

$auth = new Auth($conn);
$journal = new Journal($conn);
$tagManager = new TagManager($conn);

if (!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Get tag details
$tag_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $conn->prepare("SELECT * FROM tags WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $tag_id, $_SESSION['user_id']);
$stmt->execute();
$tag = $stmt->get_result()->fetch_assoc();

if (!$tag) {
    header("Location: journals.php");
    exit();
}

// Pagination settings
$entries_per_page = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $entries_per_page;

// Get total entries for this tag
$stmt = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM journal_tags 
    WHERE tag_id = ?
");
$stmt->bind_param("i", $tag_id);
$stmt->execute();
$total_entries = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_entries / $entries_per_page);

// Get entries for current page
$entries = $tagManager->getJournalsByTag($tag_id, $_SESSION['user_id'], $entries_per_page, $offset);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entries tagged with "<?php echo htmlspecialchars($tag['name']); ?>"</title>
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
        .tag-header {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .tag-name {
            font-family: 'Merriweather', serif;
            font-size: 2rem;
            color: #1a202c;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .tag-count {
            font-size: 1rem;
            color: #6c757d;
        }
        .btn-back {
            text-decoration: none;
            color: #6c757d;
            display: inline-flex;
            align-items: center;
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
            transition: color 0.2s;
        }
        .btn-back:hover {
            color: #343a40;
        }
        .journal-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
            overflow: hidden;
            position: relative;
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
        .journal-date {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
        .pagination-container {
            margin-top: 2rem;
            padding: 1rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .pagination {
            margin: 0;
            gap: 0.5rem;
        }
        .page-link {
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            color: #4a5568;
            transition: all 0.3s ease;
        }
        .page-link:hover {
            background: #edf2f7;
            color: #2d3748;
        }
        .page-item.active .page-link {
            background: #4a90e2;
            color: white;
        }
        .btn-group .btn {
            padding: 0.4rem 0.8rem;
            font-size: 0.9rem;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .empty-icon {
            font-size: 4rem;
            color: #cbd5e0;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container-fluid">
        <div class="journal-container">
            <a href="journals.php" class="btn-back">
                <i class="fas fa-arrow-left me-2"></i>
                Back to All Journals
            </a>

            <div class="tag-header">
                <h1 class="tag-name">
                    <i class="fas fa-tag text-primary"></i>
                    <?php echo htmlspecialchars($tag['name']); ?>
                </h1>
                <p class="tag-count">
                    <?php echo $total_entries; ?> 
                    <?php echo $total_entries === 1 ? 'entry' : 'entries'; ?>
                </p>
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
                                    
                                    <div class="journal-date">
                                        <i class="far fa-calendar-alt"></i>
                                        <?php echo date('F j, Y', strtotime($entry['entry_date'])); ?>
                                    </div>

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
                                        <a href="journal_view.php?id=<?php echo $entry['id']; ?>" 
                                           class="btn btn-sm btn-outline-secondary">
                                            <i class="far fa-eye"></i>
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

                <?php if ($total_pages > 1): ?>
                <div class="pagination-container">
                    <nav aria-label="Journal navigation">
                        <ul class="pagination justify-content-center">
                            <!-- First Page -->
                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?id=<?php echo $tag_id; ?>&page=1" aria-label="First">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                            </li>
                            
                            <!-- Previous Page -->
                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?id=<?php echo $tag_id; ?>&page=<?php echo $page - 1; ?>" aria-label="Previous">
                                    <i class="fas fa-angle-left"></i>
                                </a>
                            </li>

                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $start_page + 4);
                            $start_page = max(1, min($start_page, $end_page - 4));

                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                                <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?id=<?php echo $tag_id; ?>&page=<?php echo $i; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <!-- Next Page -->
                            <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?id=<?php echo $tag_id; ?>&page=<?php echo $page + 1; ?>" aria-label="Next">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                            </li>

                            <!-- Last Page -->
                            <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?id=<?php echo $tag_id; ?>&page=<?php echo $total_pages; ?>" aria-label="Last">
                                    <i class="fas fa-angle-double-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-tag empty-icon"></i>
                    <h3>No entries found</h3>
                    <p class="text-muted">No journal entries found with this tag</p>
                    <a href="journals.php" class="btn btn-primary mt-3">View All Entries</a>
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