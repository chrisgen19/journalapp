<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'journal.php';
require_once 'tag_manager.php';
$tagManager = new TagManager($conn);

$auth = new Auth($conn);
$journal = new Journal($conn);

if (!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$id = $_GET['id'] ?? 0;
$entry = $journal->read($id, $_SESSION['user_id']);

if (!$entry) {
    header("Location: journals.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($entry['title']); ?> - Journal Entry</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:ital,wght@0,300;0,400;0,700;1,300;1,400&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery@2.7.1/css/lightgallery.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery@2.7.1/css/lg-zoom.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery@2.7.1/css/lg-thumbnail.min.css">

    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: #2c3e50;
        }
        .container-fluid {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        .journal-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px;
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
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 2.5rem;
            margin-bottom: 2rem;
        }
        .journal-header {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid #e9ecef;
        }
        .journal-title {
            font-family: 'Merriweather', serif;
            font-size: 2.5rem;
            color: #1a202c;
            font-weight: 700;
            line-height: 1.3;
            margin-bottom: 0.75rem;
        }
        .journal-date {
            font-size: 1.1rem;
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .journal-content {
            font-family: 'Merriweather', serif;
            font-size: 1.15rem;
            line-height: 1.8;
            color: #2d3748;
        }
        .journal-content p {
            margin-bottom: 1.5rem;
        }
        .journal-content h1, 
        .journal-content h2, 
        .journal-content h3 {
            font-family: 'Merriweather', serif;
            color: #1a202c;
            margin-top: 2rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }
        .journal-content h1 { font-size: 1.8rem; }
        .journal-content h2 { font-size: 1.5rem; }
        .journal-content h3 { font-size: 1.3rem; }
        
        .journal-content blockquote {
            border-left: 4px solid #4a5568;
            padding-left: 1.5rem;
            margin: 1.5rem 0;
            font-style: italic;
            color: #4a5568;
        }
        .journal-content pre {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            overflow-x: auto;
            margin: 1.5rem 0;
        }
        .journal-content code {
            background: #f1f3f5;
            padding: 0.2em 0.4em;
            border-radius: 4px;
            font-size: 0.9em;
            color: #e83e8c;
        }
        .journal-content img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin: 1.5rem 0;
        }
        .journal-content ul, 
        .journal-content ol {
            margin: 1.5rem 0;
            padding-left: 2rem;
        }
        .journal-content li {
            margin-bottom: 0.5rem;
        }
        .image-gallery {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 2px solid #e9ecef;
        }
        .gallery-title {
            font-family: 'Inter', sans-serif;
            font-size: 1.25rem;
            color: #4a5568;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }
        .gallery-item {
            aspect-ratio: 1;
            overflow: hidden;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .gallery-item:hover {
            transform: scale(1.02);
        }
        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        .action-buttons .btn {
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .journal-tags {
            margin: 1.5rem 0;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .journal-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.8rem;
            background: #f8f9fa;
            border-radius: 50px;
            color: #4a5568;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .journal-tag:hover {
            background: #4a90e2;
            color: white;
            transform: translateY(-1px);
        }

        @media (max-width: 768px) {
            .journal-card {
                padding: 1.5rem;
            }
            .journal-title {
                font-size: 2rem;
            }
            .journal-content {
                font-size: 1.1rem;
            }
            .action-buttons {
                flex-direction: column;
            }
            .action-buttons .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container-fluid">
        <div class="journal-container">
            <a href="journals.php" class="btn-back">
                <i class="fas fa-arrow-left me-2"></i>
                Back to Journals
            </a>

            <div class="journal-card">
                <!-- Journal Header -->
                <header class="journal-header">
                    <h1 class="journal-title"><?php echo htmlspecialchars($entry['title']); ?></h1>
                    <div class="journal-date">
                        <i class="far fa-calendar"></i>
                        <?php echo date('F j, Y', strtotime($entry['entry_date'])); ?>
                    </div>
                </header>

                <!-- Journal Content -->
                <div class="journal-content">
                    <?php echo $entry['content_html']; ?>
                </div>

                <!-- Image Gallery -->
                <?php if (!empty($entry['images'])): ?>
                <div class="gallery-container">
                    <h2 class="gallery-title">
                        <i class="fas fa-images me-2"></i>
                        Attached Images
                    </h2>
                    <div class="gallery" id="lightgallery">
                        <?php foreach ($entry['images'] as $image): ?>
                            <a href="<?php echo htmlspecialchars($image); ?>" class="gallery-item">
                                <img src="<?php echo htmlspecialchars($image); ?>" alt="Journal image" />
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php
                $journalTags = $tagManager->getJournalTags($entry['id']);
                if ($journalTags->num_rows > 0):
                ?>
                <div class="journal-tags">
                    <?php while ($tag = $journalTags->fetch_assoc()): ?>
                        <a href="journals_by_tag.php?id=<?php echo $tag['id']; ?>" class="journal-tag">
                            <i class="fas fa-tag"></i>
                            <?php echo htmlspecialchars($tag['name']); ?>
                        </a>
                    <?php endwhile; ?>
                </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="journal_edit.php?id=<?php echo $entry['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i>
                        Edit Entry
                    </a>
                    <a href="journals.php" class="btn btn-secondary">
                        <i class="fas fa-list"></i>
                        All Entries
                    </a>
                    <button onclick="if(confirm('Are you sure you want to delete this entry?')) window.location.href='journal_delete.php?id=<?php echo $entry['id']; ?>'" 
                            class="btn btn-danger">
                        <i class="fas fa-trash-alt"></i>
                        Delete Entry
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Add these scripts just before closing body tag -->
<script src="https://cdn.jsdelivr.net/npm/lightgallery@2.7.1/lightgallery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/lightgallery@2.7.1/plugins/zoom/lg-zoom.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/lightgallery@2.7.1/plugins/thumbnail/lg-thumbnail.min.js"></script>

<script>
    // Initialize lightGallery
    const lgElement = document.getElementById('lightgallery');
    if (lgElement) {
        lightGallery(lgElement, {
            speed: 500,
            plugins: [lgZoom, lgThumbnail],
            thumbnail: true,
            download: false,
            mode: 'lg-fade',
        });
    }
</script>

<style>
    .gallery-container {
        margin-top: 3rem;
        padding-top: 2rem;
        border-top: 2px solid #e9ecef;
    }
    .gallery-title {
        font-family: 'Inter', sans-serif;
        font-size: 1.25rem;
        color: #4a5568;
        margin-bottom: 1.5rem;
        font-weight: 600;
    }
    .gallery {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1rem;
    }
    .gallery-item {
        aspect-ratio: 1;
        overflow: hidden;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: transform 0.2s;
        cursor: pointer;
    }
    .gallery-item:hover {
        transform: scale(1.02);
    }
    .gallery-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* LightGallery custom styles */
    .lg-backdrop {
        background-color: rgba(0, 0, 0, 0.9);
    }
    .lg-outer .lg-img-wrap {
        padding: 1rem;
    }
</style>
</body>
</html>