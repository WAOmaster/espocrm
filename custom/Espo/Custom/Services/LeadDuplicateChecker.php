<?php
/**
 * Lead Duplicate Checker Service
 * 
 * Detects and manages duplicate leads based on NIC, phone, and email.
 */

namespace Espo\Custom\Services;

use Espo\ORM\EntityManager;
use Espo\ORM\Entity;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Utils\Log;

class LeadDuplicateChecker
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
     * Check for duplicates of a lead
     */
    public function checkDuplicates(string $leadId): array
    {
        $lead = $this->entityManager->getEntityById('Lead', $leadId);

        if (!$lead) {
            throw new NotFound("Lead not found: {$leadId}");
        }

        $duplicates = [];

        // Check by NIC number (strongest match)
        $nicNumber = $lead->get('nicNumber');
        if (!empty($nicNumber)) {
            $nicDuplicates = $this->findByNic($nicNumber, $leadId);
            foreach ($nicDuplicates as $dup) {
                $duplicates[$dup->getId()] = [
                    'entity' => $dup,
                    'matchType' => 'NIC',
                    'matchStrength' => 100,
                ];
            }
        }

        // Check by phone number
        $phoneNumber = $lead->get('phoneNumber');
        if (!empty($phoneNumber)) {
            $phoneDuplicates = $this->findByPhone($phoneNumber, $leadId);
            foreach ($phoneDuplicates as $dup) {
                if (!isset($duplicates[$dup->getId()])) {
                    $duplicates[$dup->getId()] = [
                        'entity' => $dup,
                        'matchType' => 'Phone',
                        'matchStrength' => 80,
                    ];
                } else {
                    $duplicates[$dup->getId()]['matchType'] .= ', Phone';
                    $duplicates[$dup->getId()]['matchStrength'] = min(100, 
                        $duplicates[$dup->getId()]['matchStrength'] + 20);
                }
            }
        }

        // Check by email
        $email = $lead->get('emailAddress');
        if (!empty($email)) {
            $emailDuplicates = $this->findByEmail($email, $leadId);
            foreach ($emailDuplicates as $dup) {
                if (!isset($duplicates[$dup->getId()])) {
                    $duplicates[$dup->getId()] = [
                        'entity' => $dup,
                        'matchType' => 'Email',
                        'matchStrength' => 60,
                    ];
                } else {
                    $duplicates[$dup->getId()]['matchType'] .= ', Email';
                    $duplicates[$dup->getId()]['matchStrength'] = min(100, 
                        $duplicates[$dup->getId()]['matchStrength'] + 15);
                }
            }
        }

        // Format results
        $result = [];
        foreach ($duplicates as $id => $data) {
            $entity = $data['entity'];
            $result[] = [
                'id' => $id,
                'name' => $entity->get('name'),
                'status' => $entity->get('status'),
                'phoneNumber' => $entity->get('phoneNumber'),
                'emailAddress' => $entity->get('emailAddress'),
                'nicNumber' => $entity->get('nicNumber'),
                'matchType' => $data['matchType'],
                'matchStrength' => $data['matchStrength'],
                'createdAt' => $entity->get('createdAt'),
            ];
        }

        // Sort by match strength descending
        usort($result, function ($a, $b) {
            return $b['matchStrength'] - $a['matchStrength'];
        });

        $this->log->debug("Found " . count($result) . " potential duplicates for lead {$leadId}");

        return [
            'leadId' => $leadId,
            'duplicateCount' => count($result),
            'duplicates' => $result,
        ];
    }

    /**
     * Mark a lead as duplicate of another lead
     */
    public function markAsDuplicate(string $duplicateLeadId, string $originalLeadId): array
    {
        $duplicateLead = $this->entityManager->getEntityById('Lead', $duplicateLeadId);
        $originalLead = $this->entityManager->getEntityById('Lead', $originalLeadId);

        if (!$duplicateLead) {
            throw new NotFound("Duplicate lead not found: {$duplicateLeadId}");
        }

        if (!$originalLead) {
            throw new NotFound("Original lead not found: {$originalLeadId}");
        }

        $duplicateLead->set('isDuplicate', true);
        $duplicateLead->set('duplicateOfId', $originalLeadId);
        $duplicateLead->set('status', 'Dead');

        $this->entityManager->saveEntity($duplicateLead);

        $this->log->info("Lead {$duplicateLeadId} marked as duplicate of {$originalLeadId}");

        return [
            'duplicateLeadId' => $duplicateLeadId,
            'originalLeadId' => $originalLeadId,
            'success' => true,
        ];
    }

    /**
     * Find leads by NIC number
     */
    private function findByNic(string $nicNumber, string $excludeId): array
    {
        return $this->entityManager
            ->getRDBRepository('Lead')
            ->where([
                'nicNumber' => $nicNumber,
                'id!=' => $excludeId,
                'deleted' => false,
            ])
            ->find()
            ->getValueMapList();
    }

    /**
     * Find leads by phone number
     */
    private function findByPhone(string $phoneNumber, string $excludeId): array
    {
        // Normalize phone number (remove spaces, +, etc.)
        $normalizedPhone = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Get last 9 digits for comparison (handles +94 vs 0)
        if (strlen($normalizedPhone) >= 9) {
            $lastNineDigits = substr($normalizedPhone, -9);
        } else {
            $lastNineDigits = $normalizedPhone;
        }

        return $this->entityManager
            ->getRDBRepository('Lead')
            ->where([
                'OR' => [
                    'phoneNumber*' => '%' . $lastNineDigits,
                ],
                'id!=' => $excludeId,
                'deleted' => false,
            ])
            ->find()
            ->getValueMapList();
    }

    /**
     * Find leads by email address
     */
    private function findByEmail(string $email, string $excludeId): array
    {
        $email = strtolower(trim($email));

        return $this->entityManager
            ->getRDBRepository('Lead')
            ->where([
                'emailAddress=' => $email,
                'id!=' => $excludeId,
                'deleted' => false,
            ])
            ->find()
            ->getValueMapList();
    }

    /**
     * Auto-check for duplicates on lead creation
     * Returns the first strong duplicate if found
     */
    public function findStrongDuplicate(Entity $lead): ?array
    {
        $nicNumber = $lead->get('nicNumber');
        
        if (!empty($nicNumber)) {
            $existingLead = $this->entityManager
                ->getRDBRepository('Lead')
                ->where([
                    'nicNumber' => $nicNumber,
                    'deleted' => false,
                ])
                ->findOne();

            if ($existingLead) {
                return [
                    'id' => $existingLead->getId(),
                    'name' => $existingLead->get('name'),
                    'matchType' => 'NIC',
                ];
            }
        }

        return null;
    }
}
