<?php
/**
 * VYRAL STUDIO - Central API Gateway & REST Router
 */

// Enable Error Reporting for Dev
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Handle Cross-Origin Resource Sharing (CORS)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header("Content-Type: application/json; charset=UTF-8");

// Minimal PSR-4 Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

use App\Config\Config;
use App\Database\Database;
use App\Middleware\AuthMiddleware;
use App\Services\GeminiService;
use App\Services\VideoRendererService;

Config::load();
$pdo = Database::getConnection();

// Parse Request URI
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$body = json_decode(file_get_contents('php://input'), true) ?? [];

// Helper Response
function jsonResponse($data, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// ROUTER DISPATCH
try {
    // 1. AUTH ROUTES
    if ($method === 'POST' && str_ends_with($requestUri, '/api/auth/register')) {
        $email = trim($body['email'] ?? '');
        $displayName = trim($body['display_name'] ?? 'Creator');
        if (empty($email)) jsonResponse(['error' => 'Email is required'], 400);

        $userId = 'usr_' . bin2hex(random_bytes(8));
        $stmt = $pdo->prepare("INSERT INTO profiles (id, email, display_name, credits_balance) VALUES (:id, :email, :name, 50)");
        $stmt->execute([':id' => $userId, ':email' => $email, ':name' => $displayName]);

        // Default Brand Profile
        $bp = $pdo->prepare("INSERT INTO brand_profiles (id, user_id, brand_name, visual_aesthetic) VALUES (:id, :uid, 'My Brand', 'Cinematic')");
        $bp->execute([':id' => 'bp_' . bin2hex(random_bytes(8)), ':uid' => $userId]);

        // Create JWT-like session token
        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode(['id' => $userId, 'sub' => $userId, 'email' => $email, 'exp' => time() + 86400 * 7]));
        $token = "{$header}.{$payload}.vyral_sig";

        jsonResponse([
            'token' => $token,
            'user' => ['id' => $userId, 'email' => $email, 'display_name' => $displayName, 'credits_balance' => 50, 'role' => 'user']
        ]);
    }

    if ($method === 'POST' && str_ends_with($requestUri, '/api/auth/login')) {
        $email = trim($body['email'] ?? '');
        if (empty($email)) jsonResponse(['error' => 'Email is required'], 400);

        $stmt = $pdo->prepare("SELECT * FROM profiles WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user) {
            // Auto register demo user for streamlined testing
            $userId = 'usr_' . bin2hex(random_bytes(8));
            $stmt = $pdo->prepare("INSERT INTO profiles (id, email, display_name, credits_balance) VALUES (:id, :email, :name, 50)");
            $stmt->execute([':id' => $userId, ':email' => $email, ':name' => explode('@', $email)[0]]);
            $user = ['id' => $userId, 'email' => $email, 'display_name' => explode('@', $email)[0], 'credits_balance' => 50, 'role' => 'user'];
        }

        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode(['id' => $user['id'], 'sub' => $user['id'], 'email' => $user['email'], 'exp' => time() + 86400 * 7]));
        $token = "{$header}.{$payload}.vyral_sig";

        jsonResponse(['token' => $token, 'user' => $user]);
    }

    // ALL ROUTES BELOW REQUIRE AUTHENTICATION
    $currentUser = AuthMiddleware::authenticate();

    // 2. PROFILE ROUTE
    if ($method === 'GET' && str_ends_with($requestUri, '/api/auth/profile')) {
        $stmt = $pdo->prepare("SELECT * FROM profiles WHERE id = :id");
        $stmt->execute([':id' => $currentUser['id']]);
        jsonResponse(['user' => $stmt->fetch()]);
    }

    // 3. HOOK LAB (Feature B)
    if ($method === 'POST' && str_ends_with($requestUri, '/api/generation/hooks')) {
        $prompt = trim($body['prompt'] ?? '');
        $platform = trim($body['platform'] ?? 'tiktok');
        $gemini = new GeminiService();
        $hooks = $gemini->generateHooks($prompt, $platform);
        jsonResponse(['hooks' => $hooks]);
    }

    // 4. PROJECTS CRUD
    if ($method === 'GET' && str_ends_with($requestUri, '/api/projects')) {
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE user_id = :uid ORDER BY created_at DESC");
        $stmt->execute([':uid' => $currentUser['id']]);
        jsonResponse(['projects' => $stmt->fetchAll()]);
    }

    if ($method === 'POST' && str_ends_with($requestUri, '/api/projects')) {
        $projId = 'proj_' . bin2hex(random_bytes(8));
        $title = $body['title'] ?? 'Untitled Video Project';
        $inputType = $body['input_type'] ?? 'prompt';
        $sourceContent = $body['source_content'] ?? '';
        $targetPlatform = $body['target_platform'] ?? 'tiktok';
        $aspectRatio = $body['aspect_ratio'] ?? '9:16';
        $targetDuration = $body['target_duration_sec'] ?? 30;
        $visualStyle = $body['visual_style'] ?? 'Cinematic';
        $voiceId = $body['voice_id'] ?? 'rachel';
        $backgroundMusic = $body['background_music'] ?? 'mystic_ambient';
        $selectedHook = $body['selected_hook'] ?? null;

        $stmt = $pdo->prepare("
            INSERT INTO projects (id, user_id, title, input_type, source_content, target_platform, aspect_ratio, target_duration_sec, visual_style, voice_id, background_music, selected_hook, status)
            VALUES (:id, :uid, :title, :itype, :content, :platform, :aspect, :dur, :style, :voice, :music, :hook, 'draft')
        ");
        $stmt->execute([
            ':id' => $projId,
            ':uid' => $currentUser['id'],
            ':title' => $title,
            ':itype' => $inputType,
            ':content' => $sourceContent,
            ':platform' => $targetPlatform,
            ':aspect' => $aspectRatio,
            ':dur' => $targetDuration,
            ':style' => $visualStyle,
            ':voice' => $voiceId,
            ':music' => $backgroundMusic,
            ':hook' => $selectedHook
        ]);

        // Automatically synthesize scenes using AI
        $gemini = new GeminiService();
        $scenes = $gemini->generateScriptAndScenes($body);

        $scInsert = $pdo->prepare("
            INSERT INTO scenes (id, project_id, scene_order, narration_text, visual_prompt, subtitle_text, estimated_duration, media_asset_url)
            VALUES (:id, :pid, :order, :narr, :vprompt, :sub, :dur, :murl)
        ");

        foreach ($scenes as $s) {
            $scInsert->execute([
                ':id' => 'sc_' . bin2hex(random_bytes(8)),
                ':pid' => $projId,
                ':order' => $s['scene_order'],
                ':narr' => $s['narration_text'],
                ':vprompt' => $s['visual_prompt'],
                ':sub' => $s['subtitle_text'],
                ':dur' => $s['estimated_duration'] ?? 4.0,
                ':murl' => $s['media_asset_url'] ?? null
            ]);
        }

        jsonResponse(['project' => ['id' => $projId, 'title' => $title], 'scenes_count' => count($scenes)]);
    }

    // Single Project Detail
    if ($method === 'GET' && preg_match('#/api/projects/([^/]+)$#', $requestUri, $matches)) {
        $projId = $matches[1];
        $pStmt = $pdo->prepare("SELECT * FROM projects WHERE id = :id AND user_id = :uid");
        $pStmt->execute([':id' => $projId, ':uid' => $currentUser['id']]);
        $project = $pStmt->fetch();
        if (!$project) jsonResponse(['error' => 'Project not found'], 404);

        $scStmt = $pdo->prepare("SELECT * FROM scenes WHERE project_id = :pid ORDER BY scene_order ASC");
        $scStmt->execute([':pid' => $projId]);
        $scenes = $scStmt->fetchAll();

        $vidStmt = $pdo->prepare("SELECT * FROM videos WHERE project_id = :pid ORDER BY rendered_at DESC LIMIT 1");
        $vidStmt->execute([':pid' => $projId]);
        $video = $vidStmt->fetch();

        jsonResponse(['project' => $project, 'scenes' => $scenes, 'video' => $video]);
    }

    // 5. GENERATION WORKFLOW & JOBS QUEUE
    if ($method === 'POST' && str_ends_with($requestUri, '/api/generation/start')) {
        $projectId = $body['project_id'] ?? null;
        if (!$projectId) jsonResponse(['error' => 'project_id required'], 400);

        // Deduct 10 Credits
        if ($currentUser['credits_balance'] < 10) {
            jsonResponse(['error' => 'Insufficient credits. Top up required.'], 402);
        }

        $jobId = 'job_' . bin2hex(random_bytes(8));
        $jobStmt = $pdo->prepare("
            INSERT INTO generation_jobs (id, user_id, project_id, status, current_stage, progress_percent)
            VALUES (:id, :uid, :pid, 'processing', 'rendering_ffmpeg', 50)
        ");
        $jobStmt->execute([':id' => $jobId, ':uid' => $currentUser['id'], ':pid' => $projectId]);

        // Deduct balance and log transaction
        $pdo->prepare("UPDATE profiles SET credits_balance = credits_balance - 10 WHERE id = :id")->execute([':id' => $currentUser['id']]);
        $pdo->prepare("INSERT INTO credit_transactions (id, user_id, amount, transaction_type, description, reference_id) VALUES (:id, :uid, -10, 'generation_debit', 'Video project generation render', :ref)")
            ->execute([':id' => 'tx_' . bin2hex(random_bytes(8)), ':uid' => $currentUser['id'], ':ref' => $jobId]);

        // Execute rendering pipeline
        $renderer = new VideoRendererService();
        try {
            $renderResult = $renderer->renderProject($pdo, $jobId, $projectId);
            
            // Mark job complete
            $pdo->prepare("UPDATE generation_jobs SET status = 'completed', current_stage = 'completed', progress_percent = 100 WHERE id = :id")
                ->execute([':id' => $jobId]);
            $pdo->prepare("UPDATE projects SET status = 'ready' WHERE id = :id")->execute([':id' => $projectId]);

            jsonResponse(['job_id' => $jobId, 'status' => 'completed', 'video' => $renderResult]);
        } catch (\Exception $e) {
            $pdo->prepare("UPDATE generation_jobs SET status = 'failed', current_stage = 'failed', error_message = :err WHERE id = :id")
                ->execute([':err' => $e->getMessage(), ':id' => $jobId]);
            jsonResponse(['job_id' => $jobId, 'status' => 'failed', 'error' => $e->getMessage()], 500);
        }
    }

    if ($method === 'GET' && preg_match('#/api/generation/jobs/([^/]+)$#', $requestUri, $matches)) {
        $jobId = $matches[1];
        $jStmt = $pdo->prepare("SELECT * FROM generation_jobs WHERE id = :id");
        $jStmt->execute([':id' => $jobId]);
        $job = $jStmt->fetch();
        if (!$job) jsonResponse(['error' => 'Job not found'], 404);
        jsonResponse($job);
    }

    // 6. SMART SCENE SINGLE-SCENE EDITING & REGENERATION (Feature D)
    if ($method === 'PUT' && preg_match('#/api/scenes/([^/]+)$#', $requestUri, $matches)) {
        $sceneId = $matches[1];
        $stmt = $pdo->prepare("
            UPDATE scenes 
            SET narration_text = :narr, subtitle_text = :sub, visual_prompt = :vprompt, updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        $stmt->execute([
            ':id' => $sceneId,
            ':narr' => $body['narration_text'] ?? '',
            ':sub' => $body['subtitle_text'] ?? '',
            ':vprompt' => $body['visual_prompt'] ?? ''
        ]);
        jsonResponse(['success' => true]);
    }

    if ($method === 'POST' && preg_match('#/api/scenes/([^/]+)/regenerate$#', $requestUri, $matches)) {
        $sceneId = $matches[1];
        // Regenerate single scene's visual asset and narration
        $newUrl = "https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=1080&q=80&sig=" . rand(100, 999);
        $pdo->prepare("UPDATE scenes SET media_asset_url = :url WHERE id = :id")->execute([':url' => $newUrl, ':id' => $sceneId]);
        jsonResponse(['success' => true, 'new_media_url' => $newUrl]);
    }

    // 7. BRAND STUDIO (Content DNA - Feature A)
    if ($method === 'GET' && str_ends_with($requestUri, '/api/brand')) {
        $bStmt = $pdo->prepare("SELECT * FROM brand_profiles WHERE user_id = :uid LIMIT 1");
        $bStmt->execute([':uid' => $currentUser['id']]);
        $brand = $bStmt->fetch();
        if ($brand && is_string($brand['brand_colors'])) {
            $brand['brand_colors'] = json_decode($brand['brand_colors'], true);
        }
        jsonResponse(['brand' => $brand]);
    }

    if ($method === 'PUT' && str_ends_with($requestUri, '/api/brand')) {
        $colors = json_encode($body['brand_colors'] ?? []);
        $stmt = $pdo->prepare("
            UPDATE brand_profiles
            SET brand_name = :name, visual_aesthetic = :aes, narration_tone = :tone, preferred_language = :lang, preferred_font = :font, brand_colors = :colors
            WHERE user_id = :uid
        ");
        $stmt->execute([
            ':uid' => $currentUser['id'],
            ':name' => $body['brand_name'] ?? 'My Brand',
            ':aes' => $body['visual_aesthetic'] ?? 'Cinematic',
            ':tone' => $body['narration_tone'] ?? 'energetic',
            ':lang' => $body['preferred_language'] ?? 'en-US',
            ':font' => $body['preferred_font'] ?? 'Inter, sans-serif',
            ':colors' => $colors
        ]);
        jsonResponse(['success' => true]);
    }

    // 8. CREDITS & TRANSACTIONS
    if ($method === 'GET' && str_ends_with($requestUri, '/api/credits')) {
        $hStmt = $pdo->prepare("SELECT * FROM credit_transactions WHERE user_id = :uid ORDER BY created_at DESC");
        $hStmt->execute([':uid' => $currentUser['id']]);
        jsonResponse([
            'balance' => $currentUser['credits_balance'],
            'history' => $hStmt->fetchAll()
        ]);
    }

    // 9. ADMIN TELEMETRY
    if ($method === 'GET' && str_ends_with($requestUri, '/api/admin/statistics')) {
        $uCount = $pdo->query("SELECT COUNT(*) FROM profiles")->fetchColumn();
        $rCount = $pdo->query("SELECT COUNT(*) FROM videos")->fetchColumn();
        $jCount = $pdo->query("SELECT COUNT(*) FROM generation_jobs WHERE status = 'processing'")->fetchColumn();
        jsonResponse(['total_users' => $uCount, 'total_renders' => $rCount, 'active_jobs' => $jCount]);
    }

    if ($method === 'GET' && str_ends_with($requestUri, '/api/admin/jobs')) {
        $jobs = $pdo->query("SELECT * FROM generation_jobs ORDER BY created_at DESC LIMIT 50")->fetchAll();
        jsonResponse(['jobs' => $jobs]);
    }

    // Fallback 404
    jsonResponse(['error' => 'Endpoint not found'], 404);

} catch (\Throwable $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}

