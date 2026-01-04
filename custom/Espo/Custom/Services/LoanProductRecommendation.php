<?php
/**
 * Loan Product Recommendation Service
 * 
 * Matches leads with suitable loan products based on their profile.
 */

namespace Espo\Custom\Services;

use Espo\ORM\EntityManager;
use Espo\ORM\Entity;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Utils\Log;

class LoanProductRecommendation
{
    private EntityManager $entityManager;
    private Log $log;

    public function __construct(
        EntityManager $entityManager,
        Log $log
    ) {
        $this->entityManager = $entityManager;
        $this->log = $log;
    }

    /**
     * Get product recommendations for a lead
     */
    public function getRecommendations(string $leadId): array
    {
        $lead = $this->entityManager->getEntityById('Lead', $leadId);

        if (!$lead) {
            throw new NotFound("Lead not found: {$leadId}");
        }

        // Get all active loan products
        $products = $this->entityManager
            ->getRDBRepository('LoanProduct')
            ->where(['status' => 'Active'])
            ->order('displayOrder', 'ASC')
            ->find();

        $recommendations = [];

        foreach ($products as $product) {
            $matchResult = $this->calculateProductMatchScore($lead, $product);
            
            if ($matchResult['eligible']) {
                $recommendations[] = [
                    'productId' => $product->getId(),
                    'productName' => $product->get('name'),
                    'productCode' => $product->get('productCode'),
                    'productType' => $product->get('productType'),
                    'matchScore' => $matchResult['score'],
                    'maxEligibleAmount' => $this->calculateMaxEligibleAmount($lead, $product),
                    'estimatedEmi' => $this->calculateEmi($lead, $product),
                    'interestRate' => $product->get('interestRateMin'),
                    'reasons' => $matchResult['reasons'],
                ];
            }
        }

        // Sort by match score descending
        usort($recommendations, function ($a, $b) {
            return $b['matchScore'] - $a['matchScore'];
        });

        $this->log->debug("Found " . count($recommendations) . " product recommendations for lead {$leadId}");

        return [
            'leadId' => $leadId,
            'leadName' => $lead->get('name'),
            'recommendationCount' => count($recommendations),
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Calculate how well a product matches a lead's profile
     */
    private function calculateProductMatchScore(Entity $lead, Entity $product): array
    {
        $score = 0;
        $reasons = [];
        $eligible = true;

        $monthlyIncome = (float) $lead->get('monthlyIncome');
        $creditScore = (int) $lead->get('creditScore');
        $foir = (float) $lead->get('foir');
        $requestedAmount = (float) $lead->get('loanAmountRequested');
        $employmentType = $lead->get('employmentType');

        // Check minimum income
        $minIncome = (float) $product->get('minIncome');
        if ($minIncome > 0) {
            if ($monthlyIncome >= $minIncome) {
                $score += 25;
                $reasons[] = "Income meets requirement";
            } else {
                $eligible = false;
                $reasons[] = "Income below minimum (LKR " . number_format($minIncome) . ")";
            }
        } else {
            $score += 15; // No income requirement
        }

        // Check credit score
        $minCreditScore = (int) $product->get('minCreditScore');
        if ($minCreditScore > 0 && $creditScore > 0) {
            if ($creditScore >= $minCreditScore) {
                $score += 25;
                $reasons[] = "Credit score meets requirement";
            } else {
                $eligible = false;
                $reasons[] = "Credit score below minimum ({$minCreditScore})";
            }
        } elseif ($creditScore === 0) {
            $score += 10; // Credit not yet checked
        }

        // Check FOIR
        $maxFoir = (float) $product->get('maxFoir');
        if ($maxFoir > 0 && $foir > 0) {
            if ($foir <= $maxFoir) {
                $score += 20;
                $reasons[] = "FOIR within limit";
            } else {
                $score += 5; // Partial credit
                $reasons[] = "FOIR exceeds product limit";
            }
        }

        // Check loan amount range
        $minLoanAmount = (float) $product->get('minLoanAmount');
        $maxLoanAmount = (float) $product->get('maxLoanAmount');
        if ($requestedAmount >= $minLoanAmount && $requestedAmount <= $maxLoanAmount) {
            $score += 15;
            $reasons[] = "Requested amount within product range";
        } elseif ($requestedAmount > 0) {
            $score += 5;
            $reasons[] = "Requested amount outside product range";
        }

        // Check employment type eligibility
        $eligibleEmploymentTypes = $product->get('eligibleEmploymentTypes');
        if (!empty($eligibleEmploymentTypes) && $employmentType) {
            if (in_array($employmentType, $eligibleEmploymentTypes)) {
                $score += 15;
                $reasons[] = "Employment type eligible";
            } else {
                $eligible = false;
                $reasons[] = "Employment type not eligible for this product";
            }
        }

        return [
            'score' => $score,
            'eligible' => $eligible,
            'reasons' => $reasons,
        ];
    }

    /**
     * Calculate maximum eligible amount for a specific product
     */
    private function calculateMaxEligibleAmount(Entity $lead, Entity $product): float
    {
        $monthlyIncome = (float) $lead->get('monthlyIncome');
        $additionalIncome = (float) ($lead->get('additionalIncome') ?? 0);
        $monthlyObligations = (float) ($lead->get('monthlyObligations') ?? 0);
        $tenure = (int) ($lead->get('loanTenureRequested') ?? $product->get('maxTenure'));

        $totalIncome = $monthlyIncome + $additionalIncome;
        $maxFoir = (float) $product->get('maxFoir') ?: 60.0;
        $interestRate = (float) $product->get('interestRateMin') ?: 24.0;

        if ($totalIncome <= 0) {
            return 0.0;
        }

        $maxEmi = ($totalIncome * $maxFoir / 100) - $monthlyObligations;

        if ($maxEmi <= 0) {
            return 0.0;
        }

        $monthlyRate = $interestRate / 12 / 100;
        $loanAmount = $maxEmi * ((pow(1 + $monthlyRate, $tenure) - 1) /
                      ($monthlyRate * pow(1 + $monthlyRate, $tenure)));

        // Cap at product max
        $productMax = (float) $product->get('maxLoanAmount');
        return min(floor($loanAmount / 10000) * 10000, $productMax);
    }

    /**
     * Calculate EMI for the requested loan amount with product terms
     */
    private function calculateEmi(Entity $lead, Entity $product): float
    {
        $loanAmount = (float) ($lead->get('loanAmountRequested') ?? 0);
        $tenure = (int) ($lead->get('loanTenureRequested') ?? $product->get('maxTenure'));
        $interestRate = (float) $product->get('interestRateMin') ?: 24.0;

        if ($loanAmount <= 0 || $tenure <= 0) {
            return 0.0;
        }

        $monthlyRate = $interestRate / 12 / 100;
        $emi = $loanAmount * $monthlyRate * pow(1 + $monthlyRate, $tenure) /
               (pow(1 + $monthlyRate, $tenure) - 1);

        return round($emi, 2);
    }
}
