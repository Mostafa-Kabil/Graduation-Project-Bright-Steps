<?php
session_start();
include 'connection.php';
header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$parentId = $_SESSION['id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'save':
        $input = json_decode(file_get_contents('php://input'), true);
        
        $childName = $input['child_name'] ?? '';
        $childDob = $input['child_dob'] ?? null;
        $childGender = $input['child_gender'] ?? '';
        $concerns = json_encode($input['primary_concerns'] ?? []);
        $activities = json_encode($input['preferred_activities'] ?? []);
        $goals = json_encode($input['development_goals'] ?? []);

        if (empty($childName) || empty($childDob) || empty($childGender)) {
            http_response_code(400);
            echo json_encode(['error' => 'Child name, DOB, and gender are required.']);
            exit();
        }

        try {
            // Calculate birth details for child table
            $dobParts = explode('-', $childDob); // YYYY-MM-DD
            $bYear = (int)$dobParts[0];
            $bMonth = (int)$dobParts[1];
            $bDay = (int)$dobParts[2];
            
            // Generate a random SSN (or temporary string) for the child table as it is required
            $ssn = substr(str_shuffle("0123456789"), 0, 14);

            $connect->beginTransaction();

            // 1. Save onboarding data
            $stmt = $connect->prepare("INSERT INTO parent_onboarding 
                (parent_id, child_name, child_dob, child_gender, primary_concerns, preferred_activities, development_goals) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$parentId, $childName, $childDob, $childGender, $concerns, $activities, $goals]);

            // 2. Automatically create the first child profile for the parent
            // Fetch parent last name to use for the child
            $stmtP = $connect->prepare("SELECT last_name FROM users WHERE user_id = ?");
            $stmtP->execute([$parentId]);
            $parentData = $stmtP->fetch(PDO::FETCH_ASSOC);
            $cLname = $parentData ? $parentData['last_name'] : '';
            
            // Only use first part of inputted name as first name
            $nameParts = explode(' ', trim($childName));
            $cFname = $nameParts[0];

            $stmtChild = $connect->prepare("INSERT INTO child 
                (ssn, parent_id, first_name, last_name, birth_day, birth_month, birth_year, gender) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtChild->execute([$ssn, $parentId, $cFname, $cLname, $bDay, $bMonth, $bYear, $childGender]);

            // Give them the free plan by default
            $freePlanSql = "SELECT subscription_id FROM subscription WHERE plan_name='Free' LIMIT 1";
            $freePlanStmt = $connect->query($freePlanSql);
            $freePlan = $freePlanStmt->fetch(PDO::FETCH_ASSOC);

            if ($freePlan) {
                try {
                    $subStmt = $connect->prepare("INSERT INTO parent_subscription (parent_id, subscription_id, child_name) VALUES (?, ?, ?)");
                    $subStmt->execute([$parentId, $freePlan['subscription_id'], $childName]);
                } catch(Exception $e) {} // ignore if failed
            }

            $connect->commit();

            echo json_encode(['success' => true, 'redirect' => 'dashboards/parent/dashboard.php']);
        } catch (Exception $e) {
            $connect->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save onboarding data: ' . $e->getMessage()]);
        }
        break;

    case 'check':
        // Check if user has already completed onboarding
        try {
            $stmt = $connect->prepare("SELECT id FROM parent_onboarding WHERE parent_id = ? LIMIT 1");
            $stmt->execute([$parentId]);
            $hasCompleted = $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;
        } catch (Exception $e) {
            // Table may not exist — treat as not completed but don't block
            $hasCompleted = false;
        }
        
        // Also check if user already has children (they may have been added without onboarding)
        try {
            $childStmt = $connect->prepare("SELECT child_id FROM child WHERE parent_id = ? LIMIT 1");
            $childStmt->execute([$parentId]);
            if ($childStmt->fetch()) {
                $hasCompleted = true; // They have children, skip onboarding
            }
        } catch (Exception $e) {}
        
        echo json_encode(['completed' => $hasCompleted]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}
?>
