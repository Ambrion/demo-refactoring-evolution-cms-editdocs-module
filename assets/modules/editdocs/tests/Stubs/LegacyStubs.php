<?php
/**
 * Минимальные заглушки для классов MODX и легаси-кода.
 * Должны находиться в глобальном неймспейсе, как оригиналы.
 * Подключаются через tests/bootstrap.php
 */

// DocumentParser - заглушка для $modx (глобальный неймспейс MODX)
if (!class_exists('DocumentParser', false)) {
    // Создаём класс без extends, если реальное наследование не нужно в тестах
    class DocumentParser
    {
        public function logEvent(int $eid, int $type, string $msg, string $module): void {}
        public function clearCache(): void {}
        public function getFullTableName(string $table): string { return 'modx_' . $table; }
    }
}

// editDocs - заглушка для легаси-объекта (глобальный неймспейс)
if (!class_exists('editDocs', false)) {
    class editDocs
    {
        public array $lang = [
            'notall' => 'Заполните оба поля',
            'cleared' => 'Кэш очищен',
        ];

        // Методы-заглушки для других веток хендлера
        public function clearCache(): void {}
        public function getAllList(): string { return ''; }
        public function editDoc(): string { return ''; }
        public function uploadFile(): string { return ''; }
        public function importExcel(): string { return ''; }
        public function export(): string { return ''; }
        public function saveConfig(array $data): void {}
        public function loadListFiles(string $path): string { return ''; }
        public function loadCfgFile(string $file): string { return ''; }
    }
}
