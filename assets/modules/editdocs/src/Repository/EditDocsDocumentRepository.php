<?php

declare(strict_types=1);

namespace EditDocs\Repository;

use DocumentParser;
use PDO;

/**
 * Modern-реализация репозитория.
 *
 * Использует PDO prepared statements с именованными плейсхолдерами.
 * Предпочтительно для новой разработки и лучшей безопасности.
 */
class EditDocsDocumentRepository implements EditDocsDocumentRepositoryInterface
{
    private DocumentParser $modx;

    public function __construct(DocumentParser $modx)
    {
        $this->modx = $modx;
    }

    /**
     * Получает PDO объект из DatabaseAPI
     */
    private function getPdo(): PDO
    {
        return $this->modx->getDatabase()->getConnection()->getPdo();
    }

    /**
     * Получает полное имя таблицы
     */
    private function getTable(): string
    {
        return $this->modx->getDatabase()->getFullTableName('site_content');
    }

    public function moveChildren(int $from, int $to): bool
    {
        $table = $this->getTable();
        $pdo = $this->getPdo();

        $sql = "UPDATE {$table}
                SET parent = :to
                WHERE parent = :from";

        $stmt = $pdo->prepare($sql);

        if (!$stmt->execute([':to' => $to, ':from' => $from])) {
            return false;
        }

        return $stmt->rowCount() > 0;
    }

    public function updateFolderFlag(int $docId, bool $isFolder): bool
    {
        $table = $this->getTable();
        $pdo = $this->getPdo();
        $flag = $isFolder ? 1 : 0;

        $sql = "UPDATE {$table}
                SET isfolder = :flag
                WHERE id = :id";

        $stmt = $pdo->prepare($sql);

        if (!$stmt->execute([':flag' => $flag, ':id' => $docId])) {
            return false;
        }

        return $stmt->rowCount() > 0;
    }

    public function documentExists(int $docId): bool
    {
        $table = $this->getTable();
        $pdo = $this->getPdo();

        $sql = "SELECT id
                FROM {$table}
                WHERE id = :id
                LIMIT 1";

        $stmt = $pdo->prepare($sql);
        if (!$stmt->execute([':id' => $docId])) {
            return false;
        }

        return $stmt->rowCount() > 0;
    }

    public function isFolder(int $docId): ?bool
    {
        $table = $this->getTable();
        $pdo = $this->getPdo();

        $sql = "SELECT isfolder
                FROM {$table}
                WHERE id = :id
                LIMIT 1";

        $stmt = $pdo->prepare($sql);
        if (!$stmt->execute([':id' => $docId])) {
            return null;
        }

        if ($stmt->rowCount() === 0) {
            return null;
        }

        return (bool)$stmt->fetchColumn();
    }

    public function isChildOf(int $child, int $parent): bool
    {
        if ($child === $parent) {
            return false;
        }

        if (!$this->documentExists($child)) {
            return false;
        }

        $currentParentId = $child;
        $depthCounter = 0;
        $maxDepth = 10;

        while ($currentParentId > 0 && $depthCounter < $maxDepth) {
            $table = $this->getTable();
            $pdo = $this->getPdo();

            $sql = "SELECT parent
                    FROM {$table}
                    WHERE id = :id
                    LIMIT 1";

            $stmt = $pdo->prepare($sql);
            if (!$stmt->execute([':id' => $currentParentId])) {
                return false;
            }

            $parentIdResult = $stmt->fetchColumn();

            if ($parentIdResult === false) {
                break;
            }

            $parentId = (int)$parentIdResult;

            if ($parentId === 0) {
                break;
            }

            if ($parentId === $parent) {
                return true;
            }

            $currentParentId = $parentId;
            $depthCounter++;
        }

        return false;
    }

    public function hasChildren(int $parentId): bool
    {
        if (!$this->documentExists($parentId)) {
            return false;
        }

        $table = $this->getTable();
        $pdo = $this->getPdo();

        $sql = "SELECT id
                FROM {$table}
                WHERE parent = :parent
                LIMIT 1";

        $stmt = $pdo->prepare($sql);
        if (!$stmt->execute([':parent' => $parentId])) {
            return false;
        }

        return $stmt->rowCount() > 0;
    }
}
