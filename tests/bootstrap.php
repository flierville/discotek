<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

// ChromeDriver installed by "vendor/bin/bdi detect drivers": an absolute path in the PATH, because
// Panther's default "./drivers/chromedriver" cannot be started by the Windows command interpreter.
$driversDir = dirname(__DIR__).DIRECTORY_SEPARATOR.'drivers';
putenv('PATH='.$driversDir.PATH_SEPARATOR.getenv('PATH'));
$_SERVER['PATH'] = $_ENV['PATH'] = getenv('PATH');

/*
 * Fresh test database with the fixtures, once per run: every functional test is then wrapped in a
 * transaction rolled back by DAMADoctrineTestBundle. Set SKIP_DB_RESET=1 to reuse the database.
 */
if (!getenv('SKIP_DB_RESET')) {
    $console = escapeshellarg(PHP_BINARY).' '.escapeshellarg(dirname(__DIR__).'/bin/console').' --env=test --no-interaction --quiet ';
    foreach (['doctrine:database:drop --force --if-exists', 'doctrine:database:create', 'doctrine:migrations:migrate', 'doctrine:fixtures:load'] as $command) {
        passthru($console.$command, $exitCode);
        if (0 !== $exitCode) {
            throw new RuntimeException(sprintf('Test database setup failed on "%s".', $command));
        }
    }
}
