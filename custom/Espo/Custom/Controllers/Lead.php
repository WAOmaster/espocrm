<?php
/**
 * Custom Lead Controller
 * 
 * Extends the base Lead controller to add custom action endpoints.
 */

namespace Espo\Custom\Controllers;

use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\InjectableFactory;
use Espo\Custom\Services\LeadQualification;
use Espo\Custom\Services\LoanProductRecommendation;
use Espo\Custom\Services\LeadDuplicateChecker;

class Lead extends \Espo\Modules\Crm\Controllers\Lead
{
    /**
     * POST api/v1/Lead/action/runQualification
     * 
     * Run qualification check for a lead
     */
    public function postActionRunQualification(Request $request): array
    {
        $data = $request->getParsedBody();

        if (empty($data->id)) {
            throw new BadRequest("Lead ID is required.");
        }

        $service = $this->getLeadQualificationService();
        
        return $service->runQualification($data->id);
    }

    /**
     * POST api/v1/Lead/action/qualifyWithAI
     * 
     * Qualify lead using Gemini AI
     */
    public function postActionQualifyWithAI(Request $request): string
    {
        $data = $request->getParsedBody();

        if (empty($data->id)) {
            throw new BadRequest("Lead ID is required.");
        }

        $service = $this->getLeadQualificationService();
        
        return $service->qualifyWithAI($data->id);
    }

    /**
     * GET api/v1/Lead/{id}/productRecommendations
     * 
     * Get product recommendations for a lead
     */
    public function getActionProductRecommendations(Request $request): array
    {
        $id = $request->getRouteParam('id');

        if (empty($id)) {
            throw new BadRequest("Lead ID is required.");
        }

        $service = $this->getLoanProductRecommendationService();
        
        return $service->getRecommendations($id);
    }

    /**
     * POST api/v1/Lead/action/checkDuplicates
     * 
     * Check for duplicate leads
     */
    public function postActionCheckDuplicates(Request $request): array
    {
        $data = $request->getParsedBody();

        if (empty($data->id)) {
            throw new BadRequest("Lead ID is required.");
        }

        $service = $this->getLeadDuplicateCheckerService();
        
        return $service->checkDuplicates($data->id);
    }

    /**
     * POST api/v1/Lead/action/markAsDuplicate
     * 
     * Mark a lead as duplicate of another lead
     */
    public function postActionMarkAsDuplicate(Request $request): array
    {
        $data = $request->getParsedBody();

        if (empty($data->duplicateId) || empty($data->originalId)) {
            throw new BadRequest("Both duplicateId and originalId are required.");
        }

        $service = $this->getLeadDuplicateCheckerService();
        
        return $service->markAsDuplicate($data->duplicateId, $data->originalId);
    }

    private function getLeadQualificationService(): LeadQualification
    {
        return $this->injectableFactory->create(LeadQualification::class);
    }

    private function getLoanProductRecommendationService(): LoanProductRecommendation
    {
        return $this->injectableFactory->create(LoanProductRecommendation::class);
    }

    private function getLeadDuplicateCheckerService(): LeadDuplicateChecker
    {
        return $this->injectableFactory->create(LeadDuplicateChecker::class);
    }
}
