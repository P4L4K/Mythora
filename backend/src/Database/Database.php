<?php
/**
 * VYRAL STUDIO - Database Connection & SQLite/Postgres PDO Adapter
 * Supports Supabase PostgreSQL in Cloud and SQLite file fallback for effortless local testing
 */

namespace App\Database;

use PDO;
use PDOException;
use App\Config\Config;

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $dbHost = Config::get('DB_HOST');
            $dbName = Config::get('DB_NAME', 'postgres');
            $dbUser = Config::get('DB_USER');
            $dbPass = Config::get('DB_PASSWORD');
            $dbPort = Config::get('DB_PORT', '5432');

            // If Supabase host is configured, connect to PostgreSQL; otherwise use local SQLite file for standalone running
            if (!empty($dbHost) && $dbHost !== 'aws-0-region.pooler.supabase.com') {
                $dsn = "pgsql:host={$dbHost};port={$dbPort};dbname={$dbName};sslmode=require";
                try {
                    self::$instance = new PDO($dsn, $dbUser, $dbPass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]);
                } catch (PDOException $e) {
                    error_log("PostgreSQL connection failed: " . $e->getMessage() . " - Falling back to local SQLite.");
                    self::$instance = self::createSqliteFallback();
                }
            } else {
                self::$instance = self::createSqliteFallback();
            }
        }
        return self::$instance;
    }

    private static function createSqliteFallback(): PDO {
        $storageDir = __DIR__ . '/../../../storage';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0777, true);
        }
        $sqliteFile = $storageDir . '/vyral_dev.sqlite';
        $pdo = new PDO("sqlite:{$sqliteFile}", null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        // Auto-initialize tables if not present
        self::initSqliteTables($pdo);
        return $pdo;
    }

    private static function initSqliteTables(PDO $pdo): void {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS profiles (
                id TEXT PRIMARY KEY,
                email TEXT UNIQUE,
                display_name TEXT,
                avatar_url TEXT,
                role TEXT DEFAULT 'user',
                credits_balance INTEGER DEFAULT 50,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS brand_profiles (
                id TEXT PRIMARY KEY,
                user_id TEXT,
                brand_name TEXT DEFAULT 'My Brand',
                brand_colors TEXT DEFAULT '{\"primary\": \"#10b981\", \"secondary\": \"#8b5cf6\"}',
                preferred_font TEXT DEFAULT 'Inter, sans-serif',
                narration_tone TEXT DEFAULT 'energetic',
                preferred_language TEXT DEFAULT 'en-US',
                visual_aesthetic TEXT DEFAULT 'Cinematic',
                content_style TEXT DEFAULT 'Storytelling',
                subtitle_styling TEXT,
                is_default INTEGER DEFAULT 1,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS projects (
                id TEXT PRIMARY KEY,
                user_id TEXT,
                title TEXT,
                input_type TEXT,
                source_content TEXT,
                target_platform TEXT DEFAULT 'tiktok',
                aspect_ratio TEXT DEFAULT '9:16',
                target_duration_sec INTEGER DEFAULT 30,
                visual_style TEXT DEFAULT 'Cinematic',
                voice_id TEXT DEFAULT 'rachel',
                background_music TEXT DEFAULT 'mystic_ambient',
                selected_hook TEXT,
                status TEXT DEFAULT 'draft',
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS scenes (
                id TEXT PRIMARY KEY,
                project_id TEXT,
                scene_order INTEGER,
                narration_text TEXT,
                visual_prompt TEXT,
                subtitle_text TEXT,
                estimated_duration REAL DEFAULT 4.0,
                media_asset_url TEXT,
                audio_asset_url TEXT,
                transition_type TEXT DEFAULT 'fade',
                custom_styling TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS videos (
                id TEXT PRIMARY KEY,
                project_id TEXT,
                user_id TEXT,
                storage_path TEXT,
                download_url TEXT,
                duration_sec REAL,
                file_size_bytes INTEGER DEFAULT 0,
                resolution TEXT DEFAULT '1080x1920',
                aspect_ratio TEXT DEFAULT '9:16',
                format TEXT DEFAULT 'mp4',
                rendered_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS generation_jobs (
                id TEXT PRIMARY KEY,
                user_id TEXT,
                project_id TEXT,
                job_type TEXT DEFAULT 'full_video',
                status TEXT DEFAULT 'pending',
                current_stage TEXT DEFAULT 'queued',
                progress_percent INTEGER DEFAULT 0,
                stage_metadata TEXT,
                error_message TEXT,
                retry_count INTEGER DEFAULT 0,
                target_scene_id TEXT,
                started_at TEXT,
                completed_at TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS credit_transactions (
                id TEXT PRIMARY KEY,
                user_id TEXT,
                amount INTEGER,
                transaction_type TEXT,
                description TEXT,
                reference_id TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            );
        ");
    }
}

