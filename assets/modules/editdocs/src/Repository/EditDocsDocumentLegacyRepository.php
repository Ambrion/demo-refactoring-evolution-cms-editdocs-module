<?php

declare(strict_types=1);

namespace EditDocs\Repository;

use DocumentParser;

/**
 * Legacy-реализация репозитория.
 *
 * Использует прямые вызовы $modx->db->query и $modx->getFullTableName.
 * Подходит для обеспечения обратной совместимости или если предпочтителен старый стиль API.
 */
class EditDocsDocumentLegacyRepository implements EditDocsDocumentRepositoryInterface
{
    private DocumentParser $modx;

    public function __construct(DocumentParser $modx)
    {
        $this->modx = $modx;
    }

    public function moveChildren(int $from, int $to): bool
    {
        $table = $this->modx->getFullTableName('site_content');

        return $this->modx->db->query(
                "UPDATE {$table} SET parent = {$to} WHERE parent = {$from}"
            ) !== false;
    }

    public function updateFolderFlag(int $docId, bool $isFolder): bool
    {
        $table = $this->modx->getFullTableName('site_content');
        $flag = $isFolder ? 1 : 0;

        return $this->modx->db->query(
                "UPDATE {$table} SET isfolder = {$flag} WHERE id = {$docId}"
            ) !== false;
    }

    public function documentExists(int $docId): bool
    {
        $table = $this->modx->getFullTableName('site_content');

        $result = $this->modx->db->getValue("SELECT id FROM {$table} WHERE id = {$docId} LIMIT 1");

        return $result !== false;
    }

    public function isFolder(int $docId): ?bool
    {
        $table = $this->modx->getFullTableName('site_content');

        $result = $this->modx->db->getValue("SELECT isfolder FROM {$table} WHERE id = {$docId} LIMIT 1");
        if ($result === false) {
            return null;
        }

        return (bool)(int)$result;
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
            $escapedCurrentId = $currentParentId;
            $table = $this->modx->getFullTableName('site_content');
            $sql = "SELECT parent FROM {$table} WHERE id = {$escapedCurrentId} LIMIT 1";

            $parentIdResult = $this->modx->db->getValue($sql);

            if ($parentIdResult == 0) {
                break;
            }

            $parentId = (int)$parentIdResult;

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

        $table = $this->modx->getFullTableName('site_content');

        $result = $this->modx->db->getValue("SELECT id FROM {$table} WHERE parent = {$parentId} LIMIT 1");

        return $result !== false;
    }
}
