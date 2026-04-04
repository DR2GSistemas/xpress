<?php

declare(strict_types=1);

use Xpress\Cli\XpressCli;

describe('Xpress CLI', function () {
    beforeEach(function () {
        $this->tempDir = sys_get_temp_dir() . '/xpress-test-' . uniqid();
        mkdir($this->tempDir, 0755, true);
        chdir($this->tempDir);
        // Crear CLI después de cambiar al directorio temporal
        $this->cli = new XpressCli();
        $this->cli->setProjectRoot($this->tempDir);
    });

    afterEach(function () {
        // Limpiar directorio temporal
        if (is_dir($this->tempDir)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iterator as $file) {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }
            rmdir($this->tempDir);
        }
    });

    describe('help command', function () {
        it('returns 0 for help command', function () {
            $exitCode = $this->cli->run(['xpress', 'help']);
            expect($exitCode)->toBe(0);
        });

        it('returns 1 for unknown command', function () {
            $exitCode = $this->cli->run(['xpress', 'unknown']);
            expect($exitCode)->toBe(1);
        });

        it('shows help by default when no command provided', function () {
            $exitCode = $this->cli->run(['xpress']);
            expect($exitCode)->toBe(0);
        });
    });

    describe('init command', function () {
        it('creates project structure', function () {
            $exitCode = $this->cli->run(['xpress', 'init']);

            expect($exitCode)->toBe(0);
            expect(is_dir($this->tempDir . '/public'))->toBeTrue();
            expect(is_dir($this->tempDir . '/src/Controllers'))->toBeTrue();
            expect(is_dir($this->tempDir . '/src/Middleware'))->toBeTrue();
            expect(is_dir($this->tempDir . '/routes'))->toBeTrue();
            expect(is_dir($this->tempDir . '/config'))->toBeTrue();
            expect(is_dir($this->tempDir . '/storage/logs'))->toBeTrue();
            expect(is_dir($this->tempDir . '/storage/cache'))->toBeTrue();
        });

        it('creates public/index.php', function () {
            $this->cli->run(['xpress', 'init']);

            $indexFile = $this->tempDir . '/public/index.php';
            expect(file_exists($indexFile))->toBeTrue();

            $content = file_get_contents($indexFile);
            expect($content)->toContain('Xpress\\XRouter');
            expect($content)->toContain('require_once __DIR__');
        });

        it('creates public/.htaccess', function () {
            $this->cli->run(['xpress', 'init']);

            $htaccessFile = $this->tempDir . '/public/.htaccess';
            expect(file_exists($htaccessFile))->toBeTrue();

            $content = file_get_contents($htaccessFile);
            expect($content)->toContain('RewriteEngine On');
            expect($content)->toContain('RewriteRule');
        });

        it('creates HomeController.php', function () {
            $this->cli->run(['xpress', 'init']);

            $controllerFile = $this->tempDir . '/src/Controllers/HomeController.php';
            expect(file_exists($controllerFile))->toBeTrue();

            $content = file_get_contents($controllerFile);
            expect($content)->toContain('namespace App\\Controllers');
            expect($content)->toContain('class HomeController');
            expect($content)->toContain('#[XRoute');
        });

        it('creates CorsMiddleware.php', function () {
            $this->cli->run(['xpress', 'init']);

            $middlewareFile = $this->tempDir . '/src/Middleware/CorsMiddleware.php';
            expect(file_exists($middlewareFile))->toBeTrue();

            $content = file_get_contents($middlewareFile);
            expect($content)->toContain('namespace App\\Middleware');
            expect($content)->toContain('class CorsMiddleware');
            expect($content)->toContain('Access-Control-Allow-Origin');
        });

        it('creates routes/web.php', function () {
            $this->cli->run(['xpress', 'init']);

            $routesFile = $this->tempDir . '/routes/web.php';
            expect(file_exists($routesFile))->toBeTrue();

            $content = file_get_contents($routesFile);
            expect($content)->toContain('$router->');
            expect($content)->toContain('HomeController');
        });

        it('creates bootstrap.php', function () {
            $this->cli->run(['xpress', 'init']);

            $bootstrapFile = $this->tempDir . '/bootstrap.php';
            expect(file_exists($bootstrapFile))->toBeTrue();

            $content = file_get_contents($bootstrapFile);
            expect($content)->toContain('.env');
            expect($content)->toContain('APP_DEBUG');
        });

        it('creates .env and .env.example', function () {
            $this->cli->run(['xpress', 'init']);

            expect(file_exists($this->tempDir . '/.env'))->toBeTrue();
            expect(file_exists($this->tempDir . '/.env.example'))->toBeTrue();

            $content = file_get_contents($this->tempDir . '/.env');
            expect($content)->toContain('APP_ENV');
            expect($content)->toContain('DB_HOST');
        });

        it('creates composer.json', function () {
            $this->cli->run(['xpress', 'init']);

            $composerFile = $this->tempDir . '/composer.json';
            expect(file_exists($composerFile))->toBeTrue();

            $content = file_get_contents($composerFile);
            $json = json_decode($content, true);

            expect($json)->toHaveKey('name');
            expect($json)->toHaveKey('require.dr2gsistemas/xpress');
            expect($json['autoload']['psr-4'])->toHaveKey('App\\');
        });

        it('skips existing files without force flag', function () {
            // Crear archivo existente
            $publicDir = $this->tempDir . '/public';
            mkdir($publicDir, 0755, true);
            $indexFile = $publicDir . '/index.php';
            file_put_contents($indexFile, 'existing content');
            
            // Debug
            expect(file_exists($indexFile))->toBeTrue('El archivo debería existir antes de init');
            
            $this->cli->run(['xpress', 'init']);

            $content = file_get_contents($indexFile);
            expect($content)->toBe('existing content');
        });

        it('overwrites existing files with force flag', function () {
            // Crear archivo existente
            mkdir($this->tempDir . '/public', 0755, true);
            file_put_contents($this->tempDir . '/public/index.php', 'existing content');

            $this->cli->run(['xpress', 'init', '--force']);

            $content = file_get_contents($this->tempDir . '/public/index.php');
            expect($content)->not->toBe('existing content');
            expect($content)->toContain('Xpress\\XRouter');
        });
    });

    describe('htaccess command', function () {
        it('generates htaccess file', function () {
            mkdir($this->tempDir . '/public', 0755, true);

            $exitCode = $this->cli->run(['xpress', 'htaccess']);

            expect($exitCode)->toBe(0);
            expect(file_exists($this->tempDir . '/public/.htaccess'))->toBeTrue();
        });

        it('returns error if public directory does not exist', function () {
            $exitCode = $this->cli->run(['xpress', 'htaccess']);

            expect($exitCode)->toBe(1);
        });

        it('generates htaccess with custom base path', function () {
            mkdir($this->tempDir . '/public', 0755, true);

            $this->cli->run(['xpress', 'htaccess', '--base=/api/v1']);

            $content = file_get_contents($this->tempDir . '/public/.htaccess');
            expect($content)->toContain('RewriteBase /api/v1');
        });
    });

    describe('CLI utilities', function () {
        it('parses options correctly', function () {
            // Test through public behavior
            mkdir($this->tempDir . '/public', 0755, true);

            $this->cli->run(['xpress', 'htaccess', '--base=/test', '--entry=app.php']);

            $content = file_get_contents($this->tempDir . '/public/.htaccess');
            expect($content)->toContain('RewriteBase /test');
            expect($content)->toContain('app.php');
        });
    });
});
