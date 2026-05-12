<?php

declare(strict_types=1);

namespace AntiPatterns\StructureAndArchitecture\antipattern;

use AntiPatterns\Common\Database;
use PDO;

/**
 * BaseManager - Inheritance Abuse Antipattern
 *
 * Clase base de la que heredan todos los "managers" del sistema.
 * Proporciona: conexion a BD, autenticacion, sistema de templates,
 * logging, deteccion de entorno, carga de configuracion.
 *
 * Problemas que demuestra:
 * - Herencia usada como mecanismo de inyeccion de dependencias
 * - Constructor que hace trabajo pesado (Constructor Heavy)
 * - Estado compartido mutable heredado
 * - Logica de infraestructura mezclada con dominio
 * - Imposibilidad de cambiar implementacion sin afectar hijos
 */
class BaseManager
{
    // Infraestructura heredada - todo hijo tiene acceso a esto
    protected PDO $db;
    protected array $config;
    protected ?object $currentUser;
    protected string $environment;
    protected array $performanceMetrics;
    protected string $templatePath;
    protected array $translations;
    protected string $defaultLanguage;
    protected bool $isAuthenticated;
    protected string $sessionId;
    protected array $userPermissions;
    protected string $logFile;
    protected bool $debugMode;
    protected string $baseUrl;
    protected string $apiVersion;

    /**
     * Constructor Heavy + Inheritance Abuse
     * Hace demasiadas cosas antes de que el hijo pueda hacer nada.
     */
    public function __construct(array $config = [])
    {
        // 1. Conecta a BD - efecto secundario en constructor
        $this->db = Database::getInstance();

        // 2. Carga configuracion desde BD (otra query)
        $this->config = $this->loadSystemConfig();

        // 3. Sobreescribe con config pasada por parametro
        $this->config = array_merge($this->config, $config);

        // 4. Detecta entorno - logica hardcodeada
        $this->environment = $this->detectEnvironment();

        // 5. Inicializa sistema de autenticacion
        $this->isAuthenticated = $this->comprobarAutenticacion();
        $this->currentUser = $this->loadCurrentUser();
        $this->userPermissions = $this->loadUserPermissions();
        $this->sessionId = session_id() ?: 'no-session-' . uniqid();

        // 6. Configura logging
        $this->debugMode = $this->config['debug'] ?? false;
        $this->logFile = $this->getLogFilePath();
        $this->performanceMetrics = [];

        // 7. Configura sistema de templates
        $this->templatePath = $this->config['template_path'] ?? '/var/www/templates/';
        $this->defaultLanguage = $this->config['language'] ?? 'es';
        $this->translations = $this->loadTranslations();

        // 8. Configura URLs base hardcodeadas
        $this->baseUrl = $this->detectBaseUrl();
        $this->apiVersion = 'v2.1'; // hardcoded version
    }

    /**
     * Autenticacion - metodo heredado por todos los hijos
     * aunque no todos lo necesiten.
     */
    protected function comprobarAutenticacion(): bool
    {
        // Logica de autenticacion hardcodeada
        $hash = $_COOKIE['auth_hash'] ?? '';
        if (empty($hash)) {
            return false;
        }

        // Hash debil - otro antipatron de seguridad
        $expectedHash = sha1('secret_salt_' . date('Y-m-d'));
        return $hash === $expectedHash;
    }

    /**
     * Carga usuario actual desde BD - query en cada request.
     */
    protected function loadCurrentUser(): ?object
    {
        $userId = $_COOKIE['user_id'] ?? null;
        if (!$userId) {
            return null;
        }

        try {
            $query = "SELECT * FROM users WHERE id = $userId";
            $stmt = $this->db->query($query);
            $user = $stmt->fetch();
            return $user ? (object) $user : null;
        } catch (\Exception $e) {
            // Silent catch - error silencioso
            return null;
        }
    }

    /**
     * Carga permisos del usuario - otra query.
     */
    protected function loadUserPermissions(): array
    {
        if (!$this->currentUser) {
            return [];
        }

        try {
            $userId = $this->currentUser->id;
            $query = "SELECT permission FROM user_permissions WHERE user_id = $userId";
            $stmt = $this->db->query($query);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Configuracion del sistema desde BD - query adicional.
     */
    protected function loadSystemConfig(): array
    {
        try {
            $query = "SELECT config_key, config_value FROM system_config";
            $stmt = $this->db->query($query);
            $rows = $stmt->fetchAll();
            $config = [];
            foreach ($rows as $row) {
                $config[$row['config_key']] = $row['config_value'];
            }
            return $config;
        } catch (\Exception $e) {
            // Si no existe la tabla, defaults hardcodeados
            return [
                'debug' => false,
                'language' => 'es',
                'template_path' => '/var/www/templates/',
                'max_items' => 36,
                'currency' => 'EUR',
            ];
        }
    }

    /**
     * Deteccion de entorno con logica hardcodeada.
     */
    protected function detectEnvironment(): string
    {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        if (strpos($host, 'produccion') !== false) {
            return 'production';
        }
        if (strpos($host, 'staging') !== false) {
            return 'staging';
        }
        return 'development';
    }

    /**
     * Detecta URL base - hardcodeada por entorno.
     */
    protected function detectBaseUrl(): string
    {
        switch ($this->environment) {
            case 'production':
                return 'https://api.example.com';
            case 'staging':
                return 'https://staging-api.example.com';
            default:
                return 'http://localhost:8080';
        }
    }

    /**
     * Ruta de logs hardcodeada por entorno.
     */
    protected function getLogFilePath(): string
    {
        $paths = [
            'production' => '/var/log/app/production.log',
            'staging' => '/var/log/app/staging.log',
            'development' => '/tmp/app-dev.log',
        ];
        return $paths[$this->environment] ?? '/tmp/app.log';
    }

    /**
     * Carga traducciones - otra query a BD.
     */
    protected function loadTranslations(): array
    {
        try {
            $lang = $this->defaultLanguage;
            $query = "SELECT translation_key, translation_value FROM translations WHERE language = '$lang'";
            $stmt = $this->db->query($query);
            $rows = $stmt->fetchAll();
            $translations = [];
            foreach ($rows as $row) {
                $translations[$row['translation_key']] = $row['translation_value'];
            }
            return $translations;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Traduccion de textos - metodo heredado.
     */
    protected function traducir(string $key): string
    {
        return $this->translations[$key] ?? $key;
    }

    /**
     * Log de rendimiento - llamado desde todos los hijos.
     */
    protected function logPerformance(string $label): void
    {
        if ($this->debugMode) {
            $this->performanceMetrics[$label] = microtime(true);
        }
    }

    /**
     * Log de errores - escribe a archivo.
     */
    protected function logError(string $message, \Exception $e): void
    {
        $logLine = date('Y-m-d H:i:s') . " [ERROR] {$message}: " . $e->getMessage() . "\n";
        if ($this->debugMode) {
            error_log($logLine);
        }
    }

    /**
     * Verifica permiso - metodo heredado por todos.
     */
    protected function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->userPermissions);
    }

    /**
     * Requiere autenticacion - lanza excepcion si no autenticado.
     */
    protected function requireAuth(): void
    {
        if (!$this->isAuthenticated) {
            throw new \Exception('Usuario no autenticado');
        }
    }

    /**
     * Query helper - expone PDO directamente.
     */
    protected function executeSQL(string $query): int
    {
        return (int) $this->db->exec($query);
    }

    /**
     * Query helper - devuelve resultados directamente.
     */
    protected function getResultFromSelectSQL(string $query): array
    {
        $stmt = $this->db->query($query);
        return $stmt->fetchAll();
    }

    /**
     * Renderiza template - mezcla presentacion con dominio.
     */
    protected function renderTemplate(string $templateName, array $data = []): string
    {
        $templateFile = $this->templatePath . $templateName . '.html';
        if (!file_exists($templateFile)) {
            return '<div class="error">Template not found: ' . $templateName . '</div>';
        }

        $html = file_get_contents($templateFile);
        foreach ($data as $key => $value) {
            $html = str_replace('{{' . $key . '}}', (string) $value, $html);
        }
        return $html;
    }

    /**
     * Genera URL para API - hardcodeada con version.
     */
    protected function buildApiUrl(string $endpoint): string
    {
        return $this->baseUrl . '/' . $this->apiVersion . '/' . ltrim($endpoint, '/');
    }
}
