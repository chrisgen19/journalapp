<?php
// journal_create.php
require_once 'config.php';
require_once 'auth.php';
require_once 'journal.php';

$auth = new Auth($conn);
$journal = new Journal($conn);

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $entry_date = $_POST['entry_date'];
    $images = isset($_FILES['images']) ? $_FILES['images'] : [];

    // Basic validation
    $errors = [];
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    if (empty($content)) {
        $errors[] = "Content is required";
    }
    if (empty($entry_date)) {
        $errors[] = "Date is required";
    }

    // If no errors, create the journal entry
    if (empty($errors)) {
        if ($journal->create($_SESSION['user_id'], $title, $content, $entry_date, $images)) {
            $_SESSION['message'] = "Journal entry created successfully!";
            $_SESSION['message_type'] = "success";
            header("Location: journals.php");
            exit();
        } else {
            $errors[] = "Failed to create journal entry";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create New Journal Entry</title>
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
        .empty-preview {
            width: 100%;
            text-align: center;
            color: #6c757d;
            padding: 1.5rem;
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
        .btn-primary {
            background-color: #4a90e2;
            border-color: #4a90e2;
        }
        .btn-primary:hover {
            background-color: #357abd;
            border-color: #357abd;
        }
        .help-text {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 0.5rem;
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
            <a href="journals.php" class="btn-back">
                <i class="fas fa-arrow-left me-2"></i>
                Back to Journals
            </a>

            <div class="journal-card">
                <h1 class="page-title">Create New Entry</h1>
                <p class="page-subtitle">Write down your thoughts and memories</p>

                <form method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="title" class="form-label">
                            <i class="fas fa-heading me-2"></i>Entry Title
                        </label>
                        <input type="text" class="form-control" id="title" name="title" 
                                value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" 
                                required>
                    </div>

                    <div class="mb-3">
                        <label for="entry_date" class="form-label">
                            <i class="fas fa-calendar me-2"></i>Date
                        </label>
                        <input type="date" class="form-control" id="entry_date" name="entry_date" 
                                value="<?php echo isset($_POST['entry_date']) ? $_POST['entry_date'] : date('Y-m-d'); ?>" 
                                required>
                    </div>

                    <div class="mb-3">
                        <label for="content" class="form-label">
                            <i class="fas fa-pen-fancy me-2"></i>Your Entry
                        </label>
                        <textarea id="content" name="content" class="form-control" rows="10"><?php 
                            echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; 
                        ?></textarea>
                        <small class="text-muted">Supports Markdown formatting</small>
                    </div>

                    <div class="mb-3">
                        <label for="images" class="form-label">
                            <i class="fas fa-images me-2"></i>Add Images
                        </label>
                        <input type="file" class="form-control" id="images" name="images[]" 
                                accept="image/*" multiple onchange="previewImages(event)">
                        <div id="preview-images" class="mt-2"></div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="submit" class="btn btn-primary">Create Journal Entry</button>
                        <a href="journals.php" class="btn btn-link">Cancel</a>
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
                unique_id: "journal_create"
            },
            placeholder: "Start writing your journal entry...",
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
    </script>
</body>
</html>