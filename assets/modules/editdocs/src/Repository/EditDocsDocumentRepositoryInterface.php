<?php

declare(strict_types=1);


namespace EditDocs\Repository;

/**
 * Контракт для работы с документами MODX.
 *
 * Позволяет подменять реализацию:
 * • Legacy: прямые SQL-запросы через $modx->db
 * • Modern: подготовленные выражения, кэширование
 * • Mock: для тестов без БД
 */
interface EditDocsDocumentRepositoryInterface
{
    /**
     * Перемещает все дочерние документы из $from в $to
     */
    public function moveChildren(int $from, int $to): bool;

    /**
     * Обновляет флаг isfolder
     */
    public function updateFolderFlag(int $docId, bool $isFolder): bool;

    /**
     * Проверяет, существует ли документ по ID
     *
     * @param int $docId
     * @return bool
     */
    public function documentExists(int $docId): bool;

    /**
     * Проверяет, является ли документ контейнером (папкой)
     * Опционально: можно использовать для дополнительной валидации
     *
     * @param int $docId
     * @return bool|null null если документ не найден
     */
    public function isFolder(int $docId): ?bool;

    /**
     * Проверяет, является ли документ $child потомком документа $parent.
     * Это включает в себя прямое дочернее отношение и вложенность на любом уровне.
     *
     * @param int $child ID документа-потомка.
     * @param int $parent ID документа-родителя.
     * @return bool True, если $child является потомком $parent, иначе False.
     */
    public function isChildOf(int $child, int $parent): bool;

    /**
     * Проверяет, имеет ли документ дочерние ресурсы.
     *
     * @param int $parentId ID документа-родителя.
     * @return bool True, если у документа есть дочерние ресурсы, иначе False.
     *              Возвращает False, если документ не существует.
     */
    public function hasChildren(int $parentId): bool;
}
