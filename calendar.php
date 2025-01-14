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

// Get current month and year
$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Get the first and last day of the month
$firstDay = date('Y-m-01', strtotime("$year-$month-01"));
$lastDay = date('Y-m-t', strtotime("$year-$month-01"));

// Get all journal entries for the current month
$query = "
    SELECT j.*, 
        (SELECT image_path FROM journal_images WHERE journal_id = j.id LIMIT 1) as thumbnail,
        (SELECT COUNT(*) FROM journal_images WHERE journal_id = j.id) as image_count
    FROM journals j 
    WHERE user_id = ? 
    AND entry_date BETWEEN ? AND ?
    ORDER BY entry_date ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("iss", $_SESSION['user_id'], $firstDay, $lastDay);
$stmt->execute();
$result = $stmt->get_result();

// Create an array of entries indexed by date
$entries = [];
while ($row = $result->fetch_assoc()) {
    $date = date('Y-m-d', strtotime($row['entry_date']));
    if (!isset($entries[$date])) {
        $entries[$date] = [];
    }
    $entries[$date][] = $row;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Journal Calendar</title>
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
        .calendar-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .calendar-nav {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .month-nav {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .month-title {
            font-family: 'Merriweather', serif;
            font-size: 1.5rem;
            color: #2d3748;
            margin: 0;
            min-width: 200px;
            text-align: center;
        }
        .month-nav .btn {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            border: none;
            background: #f8f9fa;
            color: #4a5568;
            transition: all 0.3s;
        }
        .month-nav .btn:hover {
            background: #edf2f7;
            color: #2d3748;
        }
        .calendar-wrapper {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .weekday-header {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            background: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
        }
        .weekday-cell {
            padding: 1rem;
            text-align: center;
            font-weight: 600;
            color: #4a5568;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
        }
        .calendar-day {
            min-height: 150px;
            border: 1px solid #e9ecef;
            padding: 0.5rem;
            position: relative;
            background: white;
        }
        .calendar-day.other-month {
            background: #f8f9fa;
        }
        .calendar-day.today {
            background: #f7fafc;
        }
        .day-number {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            color: #4a5568;
            border-radius: 50%;
        }
        .calendar-day.today .day-number {
            background: #4a90e2;
            color: white;
        }
        .calendar-day.other-month .day-number {
            color: #a0aec0;
        }
        .entry-list {
            margin-top: 2rem;
        }
        .entry-item {
            display: flex;
            align-items: start;
            gap: 0.5rem;
            padding: 0.5rem;
            background: white;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.2s;
        }
        .entry-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .entry-thumbnail {
            width: 40px;
            height: 40px;
            border-radius: 6px;
            overflow: hidden;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .entry-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .entry-thumbnail i {
            color: #a0aec0;
            font-size: 1.2rem;
        }
        .entry-content {
            flex: 1;
            min-width: 0;
        }
        .entry-title {
            margin: 0;
            font-size: 0.875rem;
            font-weight: 500;
            color: #2d3748;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .entry-meta {
            font-size: 0.75rem;
            color: #718096;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .view-toggle {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            color: #4a5568;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .view-toggle:hover {
            background: #f8f9fa;
            color: #2d3748;
        }
        .btn-create {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #4a90e2;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 4px 10px rgba(74, 144, 226, 0.3);
            transition: transform 0.3s;
            text-decoration: none;
            z-index: 1000;
        }
        .btn-create:hover {
            transform: scale(1.1);
            color: white;
        }

        .calendar-day {
            min-height: 120px;
            max-height: 120px;
            border: 1px solid #e9ecef;
            padding: 0.5rem;
            position: relative;
            overflow: hidden;
        }
        .entry-list {
            margin-top: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            max-height: 75px;
            overflow: hidden;
        }
        .entry-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem;
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            color: inherit;
            font-size: 0.8rem;
            transition: all 0.2s;
        }
        .entry-item:hover {
            background: white;
        }
        .entry-thumbnail {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            overflow: hidden;
            flex-shrink: 0;
        }
        .entry-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .entry-title {
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex: 1;
            max-width: calc(100% - 25px);
        }
        .more-entries {
            font-size: 0.75rem;
            color: #6c757d;
            text-align: center;
            padding: 0.25rem;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 4px;
            margin-top: 0.25rem;
        }
        .day-number {
            position: absolute;
            top: 0.25rem;
            right: 0.25rem;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            color: #6c757d;
            border-radius: 50%;
        }
        @media (max-width: 768px) {
            .weekday-cell {
                padding: 0.5rem;
                font-size: 0.8rem;
            }
            .calendar-day {
                min-height: 120px;
            }
            .month-title {
                font-size: 1.2rem;
                min-width: 150px;
            }
            .btn-create {
                bottom: 1rem;
                right: 1rem;
            }
            .entry-thumbnail {
                width: 32px;
                height: 32px;
            }
            .entry-title {
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container-fluid">
        <div class="calendar-container">
            <!-- Calendar Navigation -->
            <div class="calendar-nav">
                <div class="month-nav">
                    <?php 
                    $prevMonth = $month == 1 ? 12 : $month - 1;
                    $prevYear = $month == 1 ? $year - 1 : $year;
                    $nextMonth = $month == 12 ? 1 : $month + 1;
                    $nextYear = $month == 12 ? $year + 1 : $year;
                    ?>
                    <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" 
                       class="btn btn-sm">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <h2 class="month-title">
                        <?php echo date('F Y', strtotime("$year-$month-01")); ?>
                    </h2>
                    <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" 
                       class="btn btn-sm">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>

                <a href="journals.php" class="view-toggle">
                    <i class="fas fa-th-list"></i>
                    List View
                </a>
            </div>

            <!-- Calendar -->
            <div class="calendar-wrapper">
                <div class="weekday-header">
                    <?php
                    $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                    foreach ($days as $day) {
                        echo "<div class='weekday-cell'>" . substr($day, 0, 3) . "</div>";
                    }
                    ?>
                </div>

                <div class="calendar-grid">
                    <?php
                    $firstDayOfWeek = date('w', strtotime($firstDay));
                    $lastDayOfPrevMonth = date('t', strtotime('-1 month', strtotime($firstDay)));
                    $daysInMonth = date('t', strtotime($firstDay));
                    
                    // Previous month days
                    for ($i = $firstDayOfWeek - 1; $i >= 0; $i--) {
                        $day = $lastDayOfPrevMonth - $i;
                        echo "<div class='calendar-day other-month'><div class='day-number'>$day</div></div>";
                    }

                    // Current month days
                    for ($day = 1; $day <= $daysInMonth; $day++) {
                        $currentDate = date('Y-m-d', strtotime("$year-$month-$day"));
                        $isToday = $currentDate == date('Y-m-d');
                        
                        echo "<div class='calendar-day" . ($isToday ? " today" : "") . "'>";
                        echo "<div class='day-number'>$day</div>";
                        
                        if (isset($entries[$currentDate])) {
                            echo "<div class='entry-list'>";
                            $entryCount = count($entries[$currentDate]);
                            $displayLimit = 1; // Show only 2 entries maximum
                            
                            for ($i = 0; $i < min($displayLimit, $entryCount); $i++) {
                                $entry = $entries[$currentDate][$i];
                                echo "<a href='journal_view.php?id={$entry['id']}' class='entry-item'>";
                                if ($entry['thumbnail']) {
                                    echo "<div class='entry-thumbnail'>";
                                    echo "<img src='" . htmlspecialchars($entry['thumbnail']) . "' alt='Entry thumbnail'>";
                                    echo "</div>";
                                } else {
                                    echo "<div class='entry-thumbnail'>";
                                    echo "<i class='fas fa-file-alt'></i>";
                                    echo "</div>";
                                }
                                echo "<div class='entry-title'>" . htmlspecialchars($entry['title']) . "</div>";
                                echo "</a>";
                            }
                            
                            if ($entryCount > $displayLimit) {
                                $remainingCount = $entryCount - $displayLimit;
                                echo "<a href='journals_by_date.php?date=" . $currentDate . "' class='more-entries'>";
                                echo "<i class='fas fa-plus-circle me-1'></i>";
                                echo "$remainingCount more";
                                echo "</a>";
                            }
                            
                            echo "</div>";
                        }
                        echo "</div>";
                    }

                    // Next month days
                    $daysShown = $firstDayOfWeek + $daysInMonth;
                    $remainingDays = ceil($daysShown / 7) * 7 - $daysShown;
                    
                    for ($i = 1; $i <= $remainingDays; $i++) {
                        echo "<div class='calendar-day other-month'><div class='day-number'>$i</div></div>";
                    }
                    ?>
                </div>
            </div>

            <!-- Floating Create Button -->
            <a href="journal_create.php" class="btn-create" title="Create new entry">
                <i class="fas fa-plus"></i>
            </a>
        </div>
    </div><!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>