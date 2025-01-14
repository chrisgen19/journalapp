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
    <title>Create New Journal Entry</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.css" rel="stylesheet">
    <style>
        .editor-toolbar {
            border-radius: 0;
        }
        .CodeMirror {
            min-height: 300px;
        }
        .custom-file-label::after {
            content: "Browse";
        }
        #preview-images {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        .preview-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Create New Journal Entry</h1>
                    <a href="journals.php" class="btn btn-outline-secondary">Back to Journals</a>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="title" class="form-label">Title</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" 
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="entry_date" class="form-label">Date</label>
                                <input type="date" class="form-control" id="entry_date" name="entry_date" 
                                       value="<?php echo isset($_POST['entry_date']) ? $_POST['entry_date'] : date('Y-m-d'); ?>" 
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="content" class="form-label">Content</label>
                                <textarea id="content" name="content" class="form-control" rows="10"><?php 
                                    echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; 
                                ?></textarea>
                                <small class="text-muted">Supports Markdown formatting</small>
                            </div>

                            <div class="mb-3">
                                <label for="images" class="form-label">Images (optional)</label>
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
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
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
            toolbar: [
                'bold', 'italic', 'heading', '|',
                'quote', 'unordered-list', 'ordered-list', '|',
                'link', 'image', '|',
                'preview', 'side-by-side', 'fullscreen', '|',
                'guide'
            ]
        });

        // Image preview function
        function previewImages(event) {
            const preview = document.getElementById('preview-images');
            preview.innerHTML = '';
            
            const files = event.target.files;
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'preview-image';
                        preview.appendChild(img);
                    }
                    reader.readAsDataURL(file);
                }
            }
        }
    </script>
</body>
</html>