<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'journal.php';

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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $entry_date = $_POST['entry_date'];
    $images = isset($_FILES['images']) ? $_FILES['images'] : [];
    
    if ($journal->update($id, $_SESSION['user_id'], $title, $content, $entry_date, $images)) {
        header("Location: journal_view.php?id=" . $id);
        exit();
    } else {
        $error = "Failed to update journal entry.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Journal Entry</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <!-- EasyMDE -->
    <link href="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:ital,wght@0,300;0,400;0,700;1,400&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
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
            max-width: 900px;
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
        .page-title {
            font-family: 'Merriweather', serif;
            font-size: 2rem;
            color: #1a202c;
            margin-bottom: 0.5rem;
        }
        .page-subtitle {
            color: #6c757d;
            margin-bottom: 2rem;
        }
        .form-label {
            font-weight: 500;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }
        .form-control {
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #4a90e2;
            box-shadow: 0 0 0 0.2rem rgba(74, 144, 226, 0.15);
        }
        .editor-toolbar {
            border-color: #e9ecef;
            background-color: #f8f9fa;
        }
        .CodeMirror {
            border-color: #e9ecef;
            border-radius: 8px;
            font-family: 'Merriweather', serif;
            font-size: 1.1rem;
            line-height: 1.8;
            padding: 1rem;
        }
        .CodeMirror-scroll {
            min-height: 400px;
        }
        .preview-container {
            background: #fff;
            border: 2px dashed #e9ecef;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 0.5rem;
            min-height: 120px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .preview-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .preview-image:hover {
            transform: scale(1.05);
        }
        .current-images {
            margin-bottom: 2rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .current-images-title {
            font-size: 1rem;
            color: #4a5568;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 1rem;
        }
        .image-wrapper {
            position: relative;
        }
        .image-remove {
            position: absolute;
            top: -8px;
            right: -8px;
            width: 24px;
            height: 24px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 12px;
            opacity: 0;
            transition: opacity 0.2s;
        }
        .image-wrapper:hover .image-remove {
            opacity: 1;
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
        .help-text {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 0.5rem;
        }

        .current-images {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .image-wrapper {
            position: relative;
        }
        .preview-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            transition: transform 0.2s;
        }
        .image-remove {
            position: absolute;
            top: -8px;
            right: -8px;
            width: 24px;
            height: 24px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.2s;
        }
        .image-wrapper:hover .image-remove {
            opacity: 1;
        }
        .image-wrapper:hover .preview-image {
            transform: scale(1.05);
        }

        @media (max-width: 768px) {
            .journal-card {
                padding: 1.5rem;
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
            <a href="journal_view.php?id=<?php echo $id; ?>" class="btn-back">
                <i class="fas fa-arrow-left me-2"></i>
                Back to Entry
            </a>

            <div class="journal-card">
                <h1 class="page-title">Edit Journal Entry</h1>
                <p class="page-subtitle">Update your thoughts and memories</p>

                <form method="post" enctype="multipart/form-data">
                    <!-- Title Field -->
                    <div class="mb-4">
                        <label for="title" class="form-label">
                            <i class="fas fa-heading me-2"></i>Entry Title
                        </label>
                        <input type="text" class="form-control form-control-lg" id="title" 
                               name="title" value="<?php echo htmlspecialchars($entry['title']); ?>" required>
                    </div>

                    <!-- Date Field -->
                    <div class="mb-4">
                        <label for="entry_date" class="form-label">
                            <i class="fas fa-calendar me-2"></i>Date
                        </label>
                        <input type="date" class="form-control" id="entry_date" 
                               name="entry_date" value="<?php echo $entry['entry_date']; ?>" required>
                    </div>

                    <!-- Content Field -->
                    <div class="mb-4">
                        <label for="content" class="form-label">
                            <i class="fas fa-pen-fancy me-2"></i>Your Entry
                        </label>
                        <textarea id="content" name="content" required><?php 
                            echo htmlspecialchars($entry['content']); 
                        ?></textarea>
                        <div class="help-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Use Markdown to format your text. You can add headings, lists, links, and more.
                        </div>
                    </div>

                    <!-- Current Images -->
                    <?php if (!empty($entry['images'])): ?>
                    <div class="mb-3">
                        <label class="form-label">Current Images</label>
                        <div class="current-images">
                            <?php foreach ($entry['images'] as $image): ?>
                                <div class="image-wrapper">
                                    <img src="<?php echo htmlspecialchars($image); ?>" class="preview-image">
                                    <div class="image-remove" onclick="removeImage(this)" 
                                        data-image="<?php echo htmlspecialchars($image); ?>">
                                        <i class="fas fa-times"></i>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- New Images -->
                    <div class="mb-4">
                        <label for="images" class="form-label">
                            <i class="fas fa-plus-circle me-2"></i>Add New Images
                        </label>
                        <input type="file" class="form-control" id="images" name="images[]" 
                               accept="image/*" multiple onchange="previewImages(event)">
                        <div id="preview-container" class="preview-container">
                            <div class="empty-preview">
                                <i class="fas fa-cloud-upload-alt fa-2x mb-2"></i>
                                <p class="mb-0">Selected images will appear here</p>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                        <a href="journal_view.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.js"></script>
    <script>
        // Initialize EasyMDE
        const easyMDE = new EasyMDE({
            element: document.getElementById('content'),
            spellChecker: false,
            autosave: {
                enabled: true,
                unique_id: "journal_edit_<?php echo $id; ?>"
            },
            toolbar: [
                "bold", "italic", "heading", "|",
                "quote", "unordered-list", "ordered-list", "|",
                "link", "image", "|",
                "preview", "side-by-side", "fullscreen", "|",
                "guide"
            ],
            status: ["autosave", "words", "lines"],
            minHeight: "400px"
        });

        // Image preview function
        function previewImages(event) {
            const container = document.getElementById('preview-container');
            container.innerHTML = '';
            
            const files = event.target.files;
            if (files.length === 0) {
                container.innerHTML = `
                    <div class="empty-preview">
                        <i class="fas fa-cloud-upload-alt fa-2x mb-2"></i>
                        <p class="mb-0">Selected images will appear here</p>
                    </div>`;
                return;
            }
            
            for (let file of files) {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'preview-image';
                        img.title = file.name;
                        container.appendChild(img);
                    }
                    reader.readAsDataURL(file);
                }
            }
        }

        // Remove image function
        function removeImage(element) {
            if (confirm('Are you sure you want to remove this image?')) {
                const imagePath = element.dataset.image;
                // Here you would typically make an AJAX call to remove the image
                // For now, we'll just hide it visually
                element.parentElement.style.display = 'none';
            }
        }
    </script>

    <script>
    function removeImage(element) {
        if (confirm('Are you sure you want to remove this image?')) {
            const imagePath = element.dataset.image;
            const journalId = <?php echo $id; ?>; // Get the journal ID from PHP

            // Send AJAX request to delete the image
            fetch('delete_image.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    journal_id: journalId,
                    image_path: imagePath
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    element.parentElement.style.display = 'none';
                } else {
                    alert('Failed to delete image: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to delete image');
            });
        }
    }
    </script>
</body>
</html>