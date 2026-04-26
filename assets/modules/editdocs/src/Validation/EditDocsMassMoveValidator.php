<?php

declare(strict_types=1);

namespace EditDocs\Validation;

use EditDocs\Dto\EditDocsMassMoveInput;
use EditDocs\Exceptions\NoChildrenError;
use EditDocs\Exceptions\SameParentError;
use EditDocs\Exceptions\TargetDoesNotExistError;
use EditDocs\Exceptions\TargetIsChildError;
use EditDocs\Exceptions\ValidationError;
use EditDocs\Repository\EditDocsDocumentRepositoryInterface;
use editDocs;

class EditDocsMassMoveValidator implements EditDocsMassMoveValidatorInterface
{
    private EditDocsDocumentRepositoryInterface $repository;
    private editDocs $module;

    public function __construct(EditDocsDocumentRepositoryInterface $repository, editDocs $module)
    {
        $this->repository = $repository;
        $this->module = $module;
    }

    /**
     * Выполняет все проверки для перемещения.
     * Выбрасывает исключение ValidationError при ошибке.
     *
     * @param EditDocsMassMoveInput $input
     * @throws ValidationError
     */
    public function validate(EditDocsMassMoveInput $input): void
    {
        if (!$input->isValid()) {
            // Обработка ошибок из DTO (например, неверный формат ID)
            // Можно выбросить общее исключение или специфическое
            throw new ValidationError($input->error ?? 'Input validation failed');
        }

        $sourceId = $input->sourceParent->toInt();
        $targetId = $input->targetParent->toInt();

        // БИЗНЕС-ПРАВИЛО: источник и цель должны быть разными
        if ($input->sourceParent->equals($input->targetParent)) {
            $errorMsg = $this->module->lang['error_same_parent']
                ?? 'Source and target parent cannot be the same (ID=' . $input->sourceParent . ')';
            throw new SameParentError($errorMsg);
        }

        // БИЗНЕС-ПРАВИЛО: цель не должна быть потомком источника
        if ($this->repository->isChildOf($targetId, $sourceId)) {
            $errorMsg = $this->module->lang['error_target_is_child']
                ?? 'Target parent cannot be a child of the source parent (ID=' . $input->targetParent . ' is inside ID=' . $input->sourceParent . ')';
            throw new TargetIsChildError($errorMsg);
        }

        // БИЗНЕС-ПРАВИЛО: у источника должны быть дочерние ресурсы
        if (!$this->repository->hasChildren($sourceId)) {
            $errorMsg = $this->module->lang['error_no_children']
                ?? 'Source parent has no children to move (ID=' . $input->sourceParent . ')';
            throw new NoChildrenError($errorMsg);
        }

        // Проверка существования целевого документа
        if (!$this->repository->documentExists($targetId)) {
            $errorMsg = str_replace(
                '[+id+]',
                (string)$input->targetParent,
                $this->module->lang['error_target_not_found'] ?? 'Target document (ID=[+id+]) not found'
            );
            throw new TargetDoesNotExistError($errorMsg);
        }
    }
}
