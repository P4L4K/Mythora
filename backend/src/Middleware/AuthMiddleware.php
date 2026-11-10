<?php
/**
 * VYRAL STUDIO - Supabase JWT & Local Auth Middleware
 */

namespace App\Middleware;

use App\Database\Database;
use PDO;

class AuthMiddleware {
    public static function authenticate(): array {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            http_response_code(401);
            echo json_encode(['error' => 'Missing or invalid Authorization header']);
            exit;
        }

        $token = substr($authHeader, 7);
        $user = self::verifyToken($token);

        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid or expired session token']);
            exit;
        }

        return $user;
    }

    private static function verifyToken(string $token): ?array {
        // Fast decode JWT payload
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        if (!$payload) return null;

        $userId = $payload['sub'] ?? $payload['id'] ?? null;
        if (!$userId) return null;

        // Fetch user from DB or auto-upsert if valid Supabase token
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM profiles WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();

        if (!$user) {
            // Auto register profile for Supabase-authenticated user
            $email = $payload['email'] ?? "user_{$userId}@mythora.app";
            $displayName = $payload['user_metadata']['full_name'] ?? explode('@', $email)[0];
            
            $insert = $pdo->prepare("
                INSERT INTO profiles (id, email, display_name, role, credits_balance)
                VALUES (:id, :email, :name, 'user', 50)
            ");
            $insert->execute([
                ':id' => $userId,
                ':email' => $email,
                ':name' => $displayName
            ]);

            // Default Brand Profile
            $bpId = \App\Database\Database::generateId('bp');
            $bp = $pdo->prepare("INSERT INTO brand_profiles (id, user_id, brand_name, visual_aesthetic) VALUES (:id, :uid, 'My Brand', 'Cinematic')");
            $bp->execute([':id' => $bpId, ':uid' => $userId]);

            return [
                'id' => $userId,
                'email' => $email,
                'display_name' => $displayName,
                'role' => 'user',
                'credits_balance' => 50
            ];
        }

        return $user;
    }
}

