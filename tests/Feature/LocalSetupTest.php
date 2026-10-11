<?php

namespace Tests\Feature;

use Tests\TestCase;

class LocalSetupTest extends TestCase
{
    public function test_example_environment_matches_the_documented_mariadb_compose_service(): void
    {
        $environment = file_get_contents(base_path('.env.example'));
        $compose = file_get_contents(base_path('docker-compose.yml'));
        $readme = file_get_contents(base_path('README.md'));

        $this->assertStringContainsString("DB_CONNECTION=mysql\n", $environment);
        $this->assertStringContainsString("DB_PORT=3340\n", $environment);
        $this->assertStringContainsString("DB_DATABASE=shoe_commerce\n", $environment);
        $this->assertStringContainsString('${DB_PORT:-3340}:3306', $compose);
        $this->assertStringContainsString('MariaDB persists in the Docker volume.', $readme);
    }
}
