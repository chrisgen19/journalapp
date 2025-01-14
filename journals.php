<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'journal.php';
require_once 'tag_manager.php';

$tagManager = new TagManager($conn);
$auth = new Auth($conn);
$journal = new Journal($conn);

$user = $auth->getUserDetails($_SESSION['user_id']);

if (!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Pagination settings
$entries_per_page = 30;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page); // Ensure page is at least 1
$offset = ($page - 1) * $entries_per_page;

// Handle search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get total number of entries for pagination
$count_query = "SELECT COUNT(*) as total FROM journals WHERE user_id = ?";
$count_params = [$_SESSION['user_id']];
$count_types = "i";

if (!empty($search)) {
    $count_query .= " AND (title LIKE ? OR content LIKE ?)";
    $count_params[] = "%$search%";
    $count_params[] = "%$search%";
    $count_types .= "ss";
}

$stmt = $conn->prepare($count_query);
$stmt->bind_param($count_types, ...$count_params);
$stmt->execute();
$total_entries = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_entries / $entries_per_page);

// Ensure current page doesn't exceed total pages
$page = min($page, max(1, $total_pages));

// Get entries for current page
$query = "SELECT j.*, 
        (SELECT image_path FROM journal_images WHERE journal_id = j.id LIMIT 1) as thumbnail,
        (SELECT COUNT(*) FROM journal_images WHERE journal_id = j.id) as image_count
        FROM journals j 
        WHERE j.user_id = ?";

$params = array($_SESSION['user_id']);
$types = "i";

if (!empty($search)) {
    $query .= " AND (j.title LIKE ? OR j.content LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "ss";
}

$query .= " ORDER BY j.entry_date DESC, j.id DESC LIMIT ? OFFSET ?";
$params[] = $entries_per_page;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$entries = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Journals</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:ital,wght@0,300;0,400;0,700;1,400&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Previous styles remain the same -->
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
        .page-title {
            font-family: 'Merriweather', serif;
            font-size: 2rem;
            color: #1a202c;
            margin-bottom: 0.5rem;
        }
        .search-bar {
            max-width: 600px;
            margin: 2rem auto;
        }
        .search-input {
            border: none;
            border-radius: 50px;
            padding: 1rem 1.5rem;
            font-size: 1.1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .search-input:focus {
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        .search-btn {
            border-radius: 50px;
            padding: 0.7rem 1.5rem;
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
            background-color: #f8f9fa;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
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
            color: #cbd5e0;
            font-size: 3rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }
        .no-image-icon span {
            font-size: 0.9rem;
            color: #a0aec0;
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
            color: #2d3748;
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }
        .journal-title a {
            text-decoration: none;
            color: inherit;
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
            font-family: 'Inter', sans-serif;
            color: #4a5568;
            line-height: 1.7;
            flex: 1;
            display: -webkit-box;
            -webkit-line-clamp: 4;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 1rem;
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
        .btn-create {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 60px;
            height: 60px;
            border-radius: 30px;
            background: #4a90e2;
            color: white;
            box-shadow: 0 4px 10px rgba(74, 144, 226, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            transition: transform 0.3s ease;
            z-index: 1000;
            text-decoration: none;
        }
        .btn-create:hover {
            transform: scale(1.1);
            color: white;
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
        .btn-group .btn {
            padding: 0.4rem 0.8rem;
            font-size: 0.9rem;
        }
        @media (max-width: 768px) {
            .journal-thumbnail {
                height: 150px;
            }
            .btn-create {
                bottom: 1rem;
                right: 1rem;
            }
            .journal-content {
                padding: 1rem;
            }
            .journal-footer {
                padding: 0.75rem 1rem;
            }
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
        .page-info {
            color: #6c757d;
            font-size: 0.9rem;
            text-align: center;
            margin-top: 0.5rem;
        }

        .journal-card-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e9ecef;
        }
        .journal-card-tag {
            font-size: 0.8rem;
            padding: 0.25rem 0.75rem;
            background: #f8f9fa;
            color: #4a5568;
            border-radius: 50px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            transition: all 0.2s;
        }
        .journal-card-tag:hover {
            background: #4a90e2;
            color: white;
            transform: translateY(-1px);
        }
        .journal-card-tag i {
            font-size: 0.75rem;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container-fluid">
        <div class="journal-container">
            <!-- Page Header -->
            <div class="page-header text-center">
                <h1 class="page-title">My Journal Entries</h1>
                <p class="text-muted">Your personal space for thoughts and memories</p>
            </div>

            <!-- Search Bar -->
            <div class="search-bar">
                <form method="GET" action="" class="input-group">
                    <input type="text" name="search" class="form-control search-input" 
                           placeholder="Search your journals..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>

            <!-- Search Results Info -->
            <?php if (!empty($search)): ?>
                <div class="text-center mb-4">
                    <p class="text-muted">
                        <?php echo "Found $total_entries " . ($total_entries == 1 ? "entry" : "entries") . " for \"" . htmlspecialchars($search) . "\""; ?>
                        <a href="journals.php" class="ms-2"><i class="fas fa-times"></i> Clear search</a>
                    </p>
                </div>
            <?php endif; ?>

            <!-- Journal Entries Grid -->
            <?php if ($entries->num_rows > 0): ?>
                <div class="row g-4">
                    <?php while ($entry = $entries->fetch_assoc()): 
                        $preview = strip_tags(substr($entry['content'], 0, 150));
                    ?>
                        <!-- Journal card content remains the same -->
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

                                    <!-- Add this new section for tags -->
                                    <?php
                                    $entryTags = $tagManager->getJournalTags($entry['id']);
                                    if ($entryTags->num_rows > 0): ?>
                                        <div class="journal-card-tags">
                                            <?php while ($tag = $entryTags->fetch_assoc()): ?>
                                                <a href="journals_by_tag.php?id=<?php echo $tag['id']; ?>" class="journal-card-tag">
                                                    <i class="fas fa-tag"></i>
                                                    <?php echo htmlspecialchars($tag['name']); ?>
                                                </a>
                                            <?php endwhile; ?>
                                        </div>
                                    <?php endif; ?>
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

                <!-- Pagination -->
                <div class="pagination-container">
                    <nav aria-label="Journal navigation">
                        <ul class="pagination justify-content-center">
                            <!-- First Page -->
                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" aria-label="First">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                            </li>
                            
                            <!-- Previous Page -->
                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" aria-label="Previous">
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
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <!-- Next Page -->
                            <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" aria-label="Next">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                            </li>

                            <!-- Last Page -->
                            <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" aria-label="Last">
                                    <i class="fas fa-angle-double-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <div class="page-info">
                        Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $entries_per_page, $total_entries); ?> of <?php echo $total_entries; ?> entries
                    </div>
                </div>

            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-book-open empty-icon"></i>
                    <?php if (!empty($search)): ?>
                        <h3>No entries found</h3>
                        <p class="text-muted">Try searching with different keywords</p>
                    <?php else: ?>
                        <h3>No journal entries yet</h3>
                        <p class="text-muted">Start writing your first journal entry today!</p>
                    <?php endif; ?>
                    <a href="journal_create.php" class="btn btn-primary mt-3">
                        <i class="fas fa-plus me-2"></i>Create New Entry
                    </a>
                </div>
            <?php endif; ?>

            <!-- Floating Create Button -->
            <a href="journal_create.php" class="btn-create" title="Create new entry">
                <i class="fas fa-plus"></i>
            </a>
        </div>
    </div>

    <!-- Scripts -->
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