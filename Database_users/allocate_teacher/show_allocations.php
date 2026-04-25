<?php
// Suppress PHP warnings to ensure clean JSON output
error_reporting(0);
ini_set('display_errors', 0);

// Set timezone to Somalia (East Africa Time)
date_default_timezone_set('Africa/Mogadishu');

// Prevent caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Start output buffering to catch any unexpected output
ob_start();

// Include the faculty session management
include "../../Account_users/session_faculty.php";

// Include database connection
include "../../connection/connect.php";

// Clear any unexpected output from includes
ob_clean();

try {
    // Get faculty information from session
    $sessionInfo = getSessionInfo();
    if (!$sessionInfo) {
        throw new Exception("Session error - please login again");
    }

    $faculty_id = $sessionInfo['faculty_id'];

    // Check if this is a request for allocation count
    if (isset($_GET['action']) && $_GET['action'] === 'count') {
        header('Content-Type: application/json');
        $class_id = $_GET['class_id'] ?? '';
        $faculty_id_param = $_GET['faculty_id'] ?? '';
        
        if ($class_id && $faculty_id_param) {
            $count_sql = "SELECT COUNT(*) as total FROM teacher_subject_allocation tsa
                         JOIN classes c ON tsa.class_id = c.id
                         WHERE tsa.class_id = ? AND c.faculty_id = ?";
            $count_stmt = $conn->prepare($count_sql);
            $count_stmt->execute([$class_id, $faculty_id]);
        } else {
            $count_sql = "SELECT COUNT(*) as total FROM teacher_subject_allocation tsa
                         JOIN classes c ON tsa.class_id = c.id
                         WHERE c.faculty_id = ?";
            $count_stmt = $conn->prepare($count_sql);
            $count_stmt->execute([$faculty_id]);
        }
        
        $total = $count_stmt->fetchColumn();
        echo json_encode(['total' => $total]);
        exit();
    }

    // Default behavior: show allocations for this faculty and class
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $class_id = $_GET['class_id'] ?? '';
    $faculty_id_param = $_GET['faculty_id'] ?? '';
    
    $sql = "SELECT tsa.id, t.teacher_id as teacher_identifier, t.full_name as teacher_name, s.subject_name, 
                   tsa.start_time, tsa.end_time, tsa.status, tsa.created_at
            FROM teacher_subject_allocation tsa 
            JOIN teachers t ON tsa.teacher_id = t.id 
            JOIN subjects s ON tsa.subject_id = s.id 
            JOIN classes c ON tsa.class_id = c.id
            WHERE c.faculty_id = ?";
    
    $params = [$faculty_id];
    
    if ($class_id) {
        $sql .= " AND tsa.class_id = ?";
        $params[] = $class_id;
    }
    
    if (!empty($search)) {
        $sql .= " AND (t.full_name LIKE ? OR s.subject_name LIKE ? OR t.teacher_id LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    $sql .= " ORDER BY tsa.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $allocations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Generate HTML table rows
    if (count($allocations) > 0) {
        foreach ($allocations as $allocation) {
            $current_time = date('H:i:s');
            $start_time = $allocation['start_time'];
            $end_time = $allocation['end_time'];
            
            // Convert times to comparable format (seconds since midnight)
            $current_time_seconds = strtotime($current_time);
            $start_time_seconds = strtotime($start_time);
            $end_time_seconds = strtotime($end_time);
            
            // Check time conditions
            $is_before_class = ($current_time_seconds < $start_time_seconds);
            $is_during_class = ($current_time_seconds >= $start_time_seconds && $current_time_seconds <= $end_time_seconds);
            $is_after_class = ($current_time_seconds > $end_time_seconds);
            
            // Determine status badge based on current status
            $statusBadge = 'bg-label-secondary';
            
            switch ($allocation['status']) {
                case 'pending':
                    $statusBadge = 'bg-label-danger'; // Red for pending
                    break;
                case 'waiting':
                    $statusBadge = 'bg-label-warning'; // Yellow for waiting
                    break;
                case 'approved':
                    $statusBadge = 'bg-label-success'; // Green for approved
                    break;
            }
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($allocation['teacher_identifier']) . "</td>";
            echo "<td>" . htmlspecialchars($allocation['teacher_name']) . "</td>";
            echo "<td>" . htmlspecialchars($allocation['subject_name']) . "</td>";
            echo "<td>" . date('H:i', strtotime($allocation['start_time'])) . "</td>";
            echo "<td>" . date('H:i', strtotime($allocation['end_time'])) . "</td>";
            echo "<td>";
            echo "<span class='badge $statusBadge status-btn' style='cursor: pointer;' data-id='" . $allocation['id'] . "' data-teacher-name='" . htmlspecialchars($allocation['teacher_name']) . "' data-subject='" . htmlspecialchars($allocation['subject_name']) . "'>" . ucfirst($allocation['status']) . "</span>";
            
            // Show time status info
            if ($allocation['status'] === 'waiting') {
                if ($is_before_class) {
                    // Class hasn't started today
                    $time_diff = $start_time_seconds - $current_time_seconds;
                    $hours = floor($time_diff / 3600);
                    $minutes = floor(($time_diff % 3600) / 60);
                    
                    if ($hours > 0) {
                        echo "<br><small class='text-muted'>Auto-approve in {$hours}h {$minutes}m</small>";
                    } else {
                        echo "<br><small class='text-muted'>Auto-approve in {$minutes} min</small>";
                    }
                } elseif ($is_after_class) {
                    // Class has ended today, calculate time until tomorrow's class
                    $tomorrow = date('Y-m-d', strtotime('+1 day'));
                    $next_class_datetime = $tomorrow . ' ' . $start_time;
                    $next_class_seconds = strtotime($next_class_datetime);
                    $current_full_seconds = strtotime(date('Y-m-d H:i:s'));
                    
                    $time_diff = $next_class_seconds - $current_full_seconds;
                    $hours = floor($time_diff / 3600);
                    $minutes = floor(($time_diff % 3600) / 60);
                    
                    if ($hours > 0) {
                        echo "<br><small class='text-muted'>Auto-approve in {$hours}h {$minutes}m (tomorrow)</small>";
                    } else {
                        echo "<br><small class='text-muted'>Auto-approve in {$minutes} min</small>";
                    }
                } else {
                    echo "<br><small class='text-success'>Will auto-approve now</small>";
                }
            } elseif ($allocation['status'] === 'approved') {
                if ($is_during_class) {
                    echo "<br><small class='text-success'>Active Now</small>";
                } elseif ($is_after_class) {
                    echo "<br><small class='text-muted'>Class Completed</small>";
                } elseif ($is_before_class) {
                    $time_diff = $start_time_seconds - $current_time_seconds;
                    $hours = floor($time_diff / 3600);
                    $minutes = floor(($time_diff % 3600) / 60);
                    if ($hours > 0) {
                        echo "<br><small class='text-info'>Approved, starts in {$hours}h {$minutes}m</small>";
                    } else {
                        echo "<br><small class='text-info'>Approved, starts in {$minutes} min</small>";
                    }
                }
            } elseif ($allocation['status'] === 'pending') {
                if ($is_during_class) {
                    echo "<br><small class='text-warning'>During class time</small>";
                } elseif ($is_before_class) {
                    $time_diff = $start_time_seconds - $current_time_seconds;
                    $hours = floor($time_diff / 3600);
                    $minutes = floor(($time_diff % 3600) / 60);
                    if ($hours > 0) {
                        echo "<br><small class='text-muted'>Class starts in {$hours}h {$minutes}m</small>";
                    } else {
                        echo "<br><small class='text-muted'>Class starts in {$minutes} min</small>";
                    }
                } elseif ($is_after_class) {
                    // Class has ended today, show time until tomorrow's class
                    $tomorrow = date('Y-m-d', strtotime('+1 day'));
                    $next_class_datetime = $tomorrow . ' ' . $start_time;
                    $next_class_seconds = strtotime($next_class_datetime);
                    $current_full_seconds = strtotime(date('Y-m-d H:i:s'));
                    
                    $time_diff = $next_class_seconds - $current_full_seconds;
                    $hours = floor($time_diff / 3600);
                    $minutes = floor(($time_diff % 3600) / 60);
                    
                    if ($hours > 0) {
                        echo "<br><small class='text-muted'>Next class in {$hours}h {$minutes}m (tomorrow)</small>";
                    } else {
                        echo "<br><small class='text-muted'>Next class in {$minutes} min</small>";
                    }
                }
            }
            
            echo "</td>";
            echo "<td>" . date('Y-m-d H:i', strtotime($allocation['created_at'])) . "</td>";
            echo "<td class='text-end'>";
            
            // 3-dot menu dropdown
            echo "<div class='dropdown'>";
            echo "<button class='btn btn-sm btn-outline-secondary dropdown-toggle' type='button' data-bs-toggle='dropdown' aria-expanded='false'>";
            echo "<i class='bx bx-dots-vertical-rounded'></i>";
            echo "</button>";
            echo "<ul class='dropdown-menu'>";
            echo "<li><a class='dropdown-item edit-time-btn' href='#' data-id='" . $allocation['id'] . "' data-start-time='" . $allocation['start_time'] . "' data-end-time='" . $allocation['end_time'] . "' data-teacher-name='" . htmlspecialchars($allocation['teacher_name']) . "' data-subject='" . htmlspecialchars($allocation['subject_name']) . "'><i class='bx bx-edit me-1'></i>Edit Time</a></li>";
            echo "<li><a class='dropdown-item text-danger delete-btn' href='#' data-id='" . $allocation['id'] . "' data-teacher-name='" . htmlspecialchars($allocation['teacher_name']) . "' data-subject='" . htmlspecialchars($allocation['subject_name']) . "'><i class='bx bx-trash me-1'></i>Delete</a></li>";
            echo "</ul>";
            echo "</div>";
            
            echo "</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='8' class='text-center'>No allocations found.</td></tr>";
    }

} catch (Exception $e) {
    echo "<tr><td colspan='8'>Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
}

ob_end_flush();
?>