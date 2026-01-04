<?php
/**
 * Lead Qualification Service
 * 
 * Handles lead qualification logic including:
 * - Running full qualification checks
 * - Calculating maximum eligible amounts
 * - Updating lead scores and eligibility status
 */

namespace Espo\Custom\Services;

use Espo\Custom\Services\GeminiService;

use Espo\ORM\EntityManager;
use Espo\ORM\Entity;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Acl;
use Espo\Core\Utils\Log;

class LeadQualification
{
    private EntityManager $entityManager;
    private Acl $acl;
    private Log $log;
    private GeminiService $geminiService;

    // FOIR/DTI thresholds for Sri Lankan microfinance context
    private const MAX_FOIR_THRESHOLD = 60.0;  // Maximum 60% FOIR
    private const MAX_DTI_THRESHOLD = 50.0;   // Maximum 50% DTI
    private const MIN_CREDIT_SCORE = 550;     // Minimum credit score for qualification

    public function __construct(
        EntityManager $entityManager,
        Acl $acl,
        Log $log,
        GeminiService $geminiService
    ) {
        $this->entityManager = $entityManager;
        $this->acl = $acl;
        $this->log = $log;
        $this->geminiService = $geminiService;
    }

    /**
     * Run full qualification check for a lead
     */
    public function runQualification(string $leadId): array
    {
        $lead = $this->entityManager->getEntityById('Lead', $leadId);

        if (!$lead) {
            throw new NotFound("Lead not found: {$leadId}");
        }

        if (!$this->acl->checkEntityEdit($lead)) {
            throw new Forbidden("No permission to qualify this lead.");
        }

        // Calculate financial ratios
        $foirResult = $this->calculateFOIR($lead);
        $dtiResult = $this->calculateDTI($lead);

        // Determine eligibility
        $eligibilityResult = $this->determineEligibility($lead, $foirResult, $dtiResult);

        // Calculate max eligible amount
        $maxEligibleAmount = $this->calculateMaxEligibleAmount($lead);

        // Create qualification check record
        $qualificationCheck = $this->entityManager->createEntity('QualificationCheck', [
            'leadId' => $leadId,
            'checkDate' => date('Y-m-d H:i:s'),
            'checkType' => 'Full Qualification',
            'eligibilityResult' => $eligibilityResult['status'],
            'maxLoanAmount' => $maxEligibleAmount,
            'foirCalculated' => $foirResult,
            'dtiCalculated' => $dtiResult,
            'qualificationRemarks' => $eligibilityResult['remarks'],
        ]);

        // Update lead entity
        $lead->set('eligibilityStatus', $eligibilityResult['status']);
        $lead->set('maxEligibleAmount', $maxEligibleAmount);
        $lead->set('foir', $foirResult);
        $lead->set('dti', $dtiResult);
        
        if ($eligibilityResult['status'] === 'Qualified') {
            $lead->set('status', 'Qualified');
        } elseif ($eligibilityResult['status'] === 'Not Qualified') {
            $lead->set('eligibilityStatus', 'Not Qualified');
        }

        $this->entityManager->saveEntity($lead);

        $this->log->info("Lead {$leadId} qualified with status: {$eligibilityResult['status']}");

        return [
            'leadId' => $leadId,
            'eligibilityStatus' => $eligibilityResult['status'],
            'maxEligibleAmount' => $maxEligibleAmount,
            'foir' => $foirResult,
            'dti' => $dtiResult,
            'remarks' => $eligibilityResult['remarks'],
            'qualificationCheckId' => $qualificationCheck->getId(),
        ];
    }

    /**
     * Calculate Fixed Obligation to Income Ratio
     */
    private function calculateFOIR(Entity $lead): float
    {
        $monthlyIncome = (float) $lead->get('monthlyIncome');
        $additionalIncome = (float) ($lead->get('additionalIncome') ?? 0);
        $monthlyObligations = (float) ($lead->get('monthlyObligations') ?? 0);

        $totalIncome = $monthlyIncome + $additionalIncome;

        if ($totalIncome <= 0) {
            return 0.0;
        }

        return round(($monthlyObligations / $totalIncome) * 100, 2);
    }

    /**
     * Calculate Debt-to-Income Ratio
     */
    private function calculateDTI(Entity $lead): float
    {
        $monthlyIncome = (float) $lead->get('monthlyIncome');
        $additionalIncome = (float) ($lead->get('additionalIncome') ?? 0);
        $monthlyObligations = (float) ($lead->get('monthlyObligations') ?? 0);

        $totalIncome = $monthlyIncome + $additionalIncome;

        if ($totalIncome <= 0) {
            return 0.0;
        }

        // Calculate proposed EMI for requested loan
        $proposedEmi = $this->calculateProposedEMI($lead);
        $totalDebt = $monthlyObligations + $proposedEmi;

        return round(($totalDebt / $totalIncome) * 100, 2);
    }

    /**
     * Calculate proposed EMI for the requested loan amount
     */
    private function calculateProposedEMI(Entity $lead): float
    {
        $loanAmount = (float) ($lead->get('loanAmountRequested') ?? 0);
        $tenure = (int) ($lead->get('loanTenureRequested') ?? 24);
        $interestRate = 24.0; // Default 24% p.a. for microfinance

        if ($loanAmount <= 0 || $tenure <= 0) {
            return 0.0;
        }

        // EMI formula: P * r * (1+r)^n / ((1+r)^n - 1)
        $monthlyRate = $interestRate / 12 / 100;
        $emi = $loanAmount * $monthlyRate * pow(1 + $monthlyRate, $tenure) /
               (pow(1 + $monthlyRate, $tenure) - 1);

        return round($emi, 2);
    }

    /**
     * Determine eligibility based on various criteria
     */
    private function determineEligibility(Entity $lead, float $foir, float $dti): array
    {
        $reasons = [];
        $status = 'Qualified';

        // Check FOIR
        if ($foir > self::MAX_FOIR_THRESHOLD) {
            $reasons[] = "FOIR ({$foir}%) exceeds maximum (" . self::MAX_FOIR_THRESHOLD . "%)";
            $status = 'Not Qualified';
        }

        // Check DTI
        if ($dti > self::MAX_DTI_THRESHOLD) {
            $reasons[] = "DTI ({$dti}%) exceeds maximum (" . self::MAX_DTI_THRESHOLD . "%)";
            $status = 'Not Qualified';
        }

        // Check credit score if available
        $creditScore = (int) $lead->get('creditScore');
        if ($creditScore > 0 && $creditScore < self::MIN_CREDIT_SCORE) {
            $reasons[] = "Credit score ({$creditScore}) below minimum (" . self::MIN_CREDIT_SCORE . ")";
            $status = 'Not Qualified';
        }

        // Check KYC status
        $kycStatus = $lead->get('kycStatus');
        if ($kycStatus === 'Rejected') {
            $reasons[] = "KYC verification was rejected";
            $status = 'Not Qualified';
        } elseif ($kycStatus !== 'Completed' && $status === 'Qualified') {
            $reasons[] = "KYC verification not yet completed";
            $status = 'Conditional';
        }

        // Check fraud status
        $fraudCheckStatus = $lead->get('fraudCheckStatus');
        if ($fraudCheckStatus === 'Flagged') {
            $reasons[] = "Fraud check flagged - requires manual review";
            $status = 'Not Qualified';
        }

        // Minimum income check (LKR 25,000)
        $monthlyIncome = (float) $lead->get('monthlyIncome');
        if ($monthlyIncome < 25000) {
            $reasons[] = "Monthly income below minimum requirement (LKR 25,000)";
            $status = 'Not Qualified';
        }

        $remarks = empty($reasons) 
            ? "Lead meets all qualification criteria." 
            : implode("; ", $reasons);

        return [
            'status' => $status,
            'remarks' => $remarks,
        ];
    }

    /**
     * Calculate maximum eligible loan amount
     */
    public function calculateMaxEligibleAmount(Entity $lead): float
    {
        $monthlyIncome = (float) $lead->get('monthlyIncome');
        $additionalIncome = (float) ($lead->get('additionalIncome') ?? 0);
        $monthlyObligations = (float) ($lead->get('monthlyObligations') ?? 0);
        $tenure = (int) ($lead->get('loanTenureRequested') ?? 24);

        $totalIncome = $monthlyIncome + $additionalIncome;

        if ($totalIncome <= 0) {
            return 0.0;
        }

        // Calculate max allowable EMI based on FOIR
        $maxFoirEmi = ($totalIncome * self::MAX_FOIR_THRESHOLD / 100) - $monthlyObligations;

        if ($maxFoirEmi <= 0) {
            return 0.0;
        }

        // Calculate loan amount from EMI
        $interestRate = 24.0; // 24% p.a.
        $monthlyRate = $interestRate / 12 / 100;
        
        $loanAmount = $maxFoirEmi * ((pow(1 + $monthlyRate, $tenure) - 1) /
                      ($monthlyRate * pow(1 + $monthlyRate, $tenure)));

        // Round to nearest 10,000 LKR
        return floor($loanAmount / 10000) * 10000;
    }

    /**
     * Qualify lead using Gemini AI
     */
    public function qualifyWithAI(string $leadId): string
    {
        $lead = $this->entityManager->getEntityById('Lead', $leadId);

        if (!$lead) {
            throw new NotFound("Lead not found: {$leadId}");
        }

        $prompt = $this->buildQualificationPrompt($lead);
        
        try {
            return $this->geminiService->generateContent($prompt);
        } catch (\Exception $e) {
            $this->log->error("AI Qualification failed: " . $e->getMessage());
            return "AI Qualification unavailable at the moment.";
        }
    }

    private function buildQualificationPrompt(Entity $lead): string
    {
        $data = [
            'Monthly Income' => $lead->get('monthlyIncome'),
            'Monthly Obligations' => $lead->get('monthlyObligations'),
            'Loan Amount Requested' => $lead->get('loanAmountRequested'),
            'Loan Tenure' => $lead->get('loanTenureRequested'),
            'Credit Score' => $lead->get('creditScore'),
            'Employment Type' => $lead->get('employmentType'),
            'Employer' => $lead->get('employerName'),
            'Purpose' => $lead->get('description'),
        ];

        $prompt = "Act as a credit risk analyst for a microfinance institution in Sri Lanka.\n";
        $prompt .= "Analyze the following lead application data and provide a risk assessment and qualification recommendation.\n\n";
        
        foreach ($data as $key => $value) {
            $prompt .= "{$key}: {$value}\n";
        }

        $prompt .= "\nPlease provide:\n";
        $prompt .= "1. Risk Level (Low, Medium, High)\n";
        $prompt .= "2. Key Risk Factors\n";
        $prompt .= "3. Qualification Recommendation (Approve, Reject, Conditional)\n";
        $prompt .= "4. Reasoning\n";

        return $prompt;
    }
}
