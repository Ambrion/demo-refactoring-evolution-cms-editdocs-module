<?php
// tests_bootstrap.php
// Этот файл будет использован как bootstrap для PHPUnit

// Подключаем файл с классом editDocs ВРУЧНУЮ
// Путь указывается от корня, где находится phpunit.xml.dist
require_once __DIR__ . '/editdocs.class.php';

// Теперь подключаем автозагрузчик Composer
// Путь также от корня проекта (где phpunit.xml.dist)
require_once __DIR__ . '/libs/vendor/autoload.php';

require_once __DIR__ . '/tests/Stubs/LegacyStubs.php';

require_once __DIR__ . '/tests/Stubs/TestResponseSender.php';

// Любые другие настройки для тестов можно добавить здесь
