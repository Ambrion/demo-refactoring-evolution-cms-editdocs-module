<?php

declare(strict_types=1);


namespace EditDocs\Service;

use editDocs;
use EditDocs\Dto\EditDocsMassMoveInput;
use EditDocs\Repository\EditDocsDocumentRepositoryInterface;
use EditDocs\Validation\EditDocsMassMoveValidatorInterface;
use EditDocs\Exceptions\NoChildrenError;
use EditDocs\Exceptions\SameParentError;
use EditDocs\Exceptions\TargetDoesNotExistError;
use EditDocs\Exceptions\TargetIsChildError;
use EditDocs\Exceptions\ValidationError;
use PDOException;
use Throwable;

/**
 * Содержит бизнес-логику перемещения ветки.
 * Не знает про $_POST, не знает про HTTP, работает только с объектами.
 */
class EditDocsMassMoveService implements EditDocsMassMoveServiceInterface
{
    /** @var EditDocsDocumentRepositoryInterface */
    private EditDocsDocumentRepositoryInterface $repository;

    /** @var editDocs */
    private editDocs $module;
    private EditDocsMassMoveValidatorInterface $validator;

    public function __construct(
        EditDocsDocumentRepositoryInterface $repository,
        editDocs                            $module,
        EditDocsMassMoveValidatorInterface  $validator
    )
    {
        $this->repository = $repository;
        $this->module = $module;
        $this->validator = $validator;
    }

    /**
     * Выполняет перемещение документов.
     *
     * @param EditDocsMassMoveInput $input
     * @return string
     */
    public function execute(EditDocsMassMoveInput $input): string
    {
        try {
            $this->validator->validate($input);
        } catch (SameParentError|TargetIsChildError|NoChildrenError|TargetDoesNotExistError|ValidationError $e) {
            return '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div>';
        }

        $sourceId = $input->sourceParent->toInt();
        $targetId = $input->targetParent->toInt();

        try {
            // Бизнес-операция
            $moved = $this->repository->moveChildren($sourceId, $targetId);

            if (!$moved) {
                return '<div class="alert alert-danger">' . $this->module->lang['error_tree'] . '</div>';
            }

            // Обновление флагов
            if ($this->repository->updateFolderFlag($targetId, true) && $this->repository->updateFolderFlag($sourceId, false)) {
                $this->module->clearCache();

                return '<div class="alert alert-success">' . $this->module->lang['ok_move'] . '</div>';
            }

            return '<div class="alert alert-danger">' . $this->module->lang['error_mass_move'] . '</div>';

        } catch (PDOException $e) {
            return '<div class="alert alert-danger">' . $this->module->lang['error_database'] . '</div>';
        } catch (Throwable $e) {
            return '<div class="alert alert-danger">' . $this->module->lang['error_unknown'] . '</div>';
        }
    }

}
