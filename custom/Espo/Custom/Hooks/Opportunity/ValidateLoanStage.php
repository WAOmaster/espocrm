<?php

namespace Espo\Custom\Hooks\Opportunity;

use Espo\Core\Hook\Hook\BeforeSave;
use Espo\ORM\Entity;
use Espo\ORM\Repository\Option\SaveOptions;
use Espo\Core\Utils\Metadata;

class ValidateLoanStage implements BeforeSave
{
    public static int $order = 10;

    private Metadata $metadata;

    public function __construct(Metadata $metadata)
    {
        $this->metadata = $metadata;
    }

    public function beforeSave(Entity $entity, SaveOptions $options): void
    {
        // If loan stage changed, update standard stage
        if ($entity->isAttributeChanged('cCLoanStage')) {
            $loanStage = $entity->get('cCLoanStage');
            $mapping = $this->metadata->get(['entityDefs', 'Opportunity', 'stageLoanStageMap']) ?? [];
            
            foreach ($mapping as $stage => $loanStages) {
                if (in_array($loanStage, $loanStages)) {
                    $entity->set('stage', $stage);
                    break;
                }
            }
            return;
        }

        // If standard stage changed (e.g., from Kanban drag), validate loan stage
        if ($entity->isAttributeChanged('stage')) {
            $stage = $entity->get('stage');
            $currentLoanStage = $entity->get('cCLoanStage');
            $mapping = $this->metadata->get(['entityDefs', 'Opportunity', 'stageLoanStageMap']) ?? [];
            
            if (empty($mapping) || !isset($mapping[$stage])) {
                return;
            }
            
            $allowedLoanStages = $mapping[$stage];
            
            // If current loan stage is not valid for new stage, set to first valid option
            if (!in_array($currentLoanStage, $allowedLoanStages)) {
                $entity->set('cCLoanStage', $allowedLoanStages[0]);
            }
        }
    }
}
