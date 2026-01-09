# Financial Services Case Management Module for EspoCRM

## Executive Summary

This document specifies a comprehensive enhancement to EspoCRM's Case module, transforming it into a **Customer Complaints & Service Request Management** system for financial companies in Sri Lanka. Cases in financial services encompass complaints, inquiries, service requests, and regulatory escalations requiring SLA-driven resolution.

---

## 1. Current EspoCRM Case Entity Analysis

### 1.1 Out-of-Box Case Fields

**Core Fields:**
- `name` - Case subject/title (auto-generated from email subject or manual)
- `number` - Auto-generated case number
- `status` - Case status (enum: New, Assigned, Pending, Closed, Rejected, Duplicate)
- `priority` - Priority level (enum: Low, Normal, High, Urgent)
- `type` - Case type (enum: Question, Incident, Problem)
- `description` - Detailed description

**Relationship Fields:**
- `account` - Related company/account
- `contact` - Primary contact (single)
- `contacts` - Multiple contacts (link-multiple, as of v8.0+)
- `lead` - Related lead (optional, not shown by default)
- `inboundEmail` - Originating email account (for Email-to-Case)

**Assignment & Ownership:**
- `assignedUser` - Case owner
- `teams` - Team assignments

**Additional Fields:**
- `resolution` - Resolution details (text)
- `isHiddenFromPortal` - Hide from customer portal (bool, v9.0+)

### 1.2 Current Capabilities

**Strengths:**
- Email-to-Case auto-creation from group mailboxes
- Stream-based communication (internal and external posts)
- Portal access for customers to create/track cases
- Knowledge Base article linking
- Auto-assignment rules (Round-Robin, Least-Busy)
- Activity tracking (calls, meetings, emails)
- Collaborators feature (v9.0+) for team collaboration
- Workflow automation support

**Critical Gaps for Financial Services:**
- No complaint categorization (product, service, charges, mis-selling)
- No regulatory compliance tracking (CBSL complaints, Ombudsman escalations)
- No SLA management (response time, resolution time)
- No compensation/refund tracking
- No root cause analysis fields
- No customer impact assessment
- No escalation management
- No complaint trends analysis
- Limited integration with loan/account systems
- No compliance reporting (mandatory regulatory reporting)

---

## 2. Financial Services Case Context

### 2.1 What is a Financial Services Case?

A case represents:
- **Customer Complaint** - Dissatisfaction about products, services, charges, or conduct
- **Service Request** - Requests for statements, certificates, account changes
- **Product Inquiry** - Questions about loan products, account features, eligibility
- **Technical Issue** - Problems with online banking, mobile app, ATM/POS
- **Regulatory Escalation** - Complaints escalated to Central Bank, Ombudsman
- **Fraud Report** - Suspected fraud or unauthorized transactions

**Key Differences from Generic Support Tickets:**
- Regulatory oversight (CBSL monitoring of complaint handling)
- Mandatory resolution timelines
- Escalation to external authorities
- Financial impact (compensation, refunds, waivers)
- Legal implications
- Reputation risk
- Audit trail requirements

### 2.2 Case Categories in Financial Services

**Product-Related Complaints:**
- Loan processing delays
- Interest rate disputes
- Hidden charges
- Mis-selling of products
- Loan rejection without proper reason
- Unfair collection practices

**Service-Related Complaints:**
- Poor customer service
- Branch staff conduct
- Delayed responses
- Inaccurate information provided
- Account maintenance issues

**Operational Complaints:**
- Transaction errors
- Statement inaccuracies
- Payment processing failures
- Account access issues
- Card-related problems

**Charges & Fees Complaints:**
- Unauthorized charges
- Fee disputes
- Penalty charges
- Processing fee disputes

**Technology Complaints:**
- Online banking issues
- Mobile app problems
- ATM/POS failures
- Payment gateway errors

**Service Requests (Non-Complaint):**
- Account statements
- Loan closure certificates
- No Objection Certificates (NOC)
- Account balance confirmations
- Repayment schedules
- Interest certificates

**Inquiries:**
- Product information
- Eligibility queries
- Interest rate inquiries
- Process clarifications
- Documentation requirements

### 2.3 Regulatory Compliance Requirements

**Sri Lankan Context:**
- **Finance Business Act No. 42 of 2011** - Requires proper complaint handling mechanisms
- **CBSL Directions** - Mandates complaint resolution timelines
- **Consumer Protection** - Fair treatment of customers
- **Mandatory Reporting** - Quarterly complaint reports to CBSL

**Compliance Obligations:**
- Acknowledge complaint within 24 hours
- Resolve within prescribed timelines (typically 15-30 days)
- Escalation path to senior management
- Right to escalate to CBSL/Financial Ombudsman
- Maintain complaint register
- Quarterly complaint analysis and reporting

---

## 3. Enhanced FinancialCase Entity Structure

### 3.1 FinancialCase Entity Definition

```json
{
  "entityType": "FinancialCase",
  "extends": "Case",
  "fields": {
    
    // === CASE CLASSIFICATION ===
    "caseCategory": {
      "type": "enum",
      "options": [
        "Complaint",
        "Service Request",
        "Inquiry",
        "Technical Issue",
        "Fraud Report",
        "Regulatory Escalation"
      ],
      "required": true,
      "default": "Complaint",
      "style": {
        "Complaint": "danger",
        "Service Request": "primary",
        "Inquiry": "default",
        "Technical Issue": "warning",
        "Fraud Report": "danger",
        "Regulatory Escalation": "danger"
      }
    },
    "complaintType": {
      "type": "enum",
      "options": [
        "Product Issue",
        "Service Quality",
        "Charges & Fees",
        "Processing Delay",
        "Staff Conduct",
        "Mis-selling",
        "Collection Practices",
        "Interest Rate Dispute",
        "Transaction Error",
        "Technology Issue",
        "Documentation Issue",
        "Other"
      ],
      "tooltip": "Specific type of complaint"
    },
    "productAffected": {
      "type": "enum",
      "options": [
        "Personal Loan",
        "Vehicle Loan",
        "Gold Loan",
        "Home Loan",
        "Business Loan",
        "Leasing",
        "Pawning",
        "Savings Account",
        "Fixed Deposit",
        "Current Account",
        "Credit Card",
        "Debit Card",
        "Online Banking",
        "Mobile Banking",
        "Other"
      ],
      "tooltip": "Product/service related to the case"
    },
    "serviceRequestType": {
      "type": "enum",
      "options": [
        "Account Statement",
        "Loan Closure Certificate",
        "NOC Request",
        "Interest Certificate",
        "Repayment Schedule",
        "Account Balance Confirmation",
        "Duplicate Statement",
        "Cheque Book Request",
        "Card Replacement",
        "Account Modification",
        "Address Change",
        "Other"
      ],
      "tooltip": "Type of service request (for non-complaint cases)"
    },
    
    // === CUSTOMER IMPACT ===
    "financialImpact": {
      "type": "currency",
      "tooltip": "Financial loss/impact to customer (if any)"
    },
    "hasFinancialImpact": {
      "type": "bool",
      "default": false
    },
    "customerSentiment": {
      "type": "enum",
      "options": ["Very Dissatisfied", "Dissatisfied", "Neutral", "Satisfied", "Very Satisfied"],
      "default": "Neutral",
      "style": {
        "Very Dissatisfied": "danger",
        "Dissatisfied": "warning",
        "Neutral": "default",
        "Satisfied": "success",
        "Very Satisfied": "success"
      }
    },
    "urgencyLevel": {
      "type": "enum",
      "options": ["Low", "Medium", "High", "Critical"],
      "default": "Medium",
      "style": {
        "Critical": "danger",
        "High": "danger",
        "Medium": "warning",
        "Low": "default"
      }
    },
    
    // === SLA MANAGEMENT ===
    "slaAcknowledgmentDue": {
      "type": "datetime",
      "readOnly": true,
      "tooltip": "When acknowledgment is due (typically 24 hours)"
    },
    "slaResolutionDue": {
      "type": "datetime",
      "required": true,
      "tooltip": "When case must be resolved by"
    },
    "acknowledgedAt": {
      "type": "datetime",
      "tooltip": "When customer was acknowledged"
    },
    "acknowledgedBy": {
      "type": "link",
      "entity": "User"
    },
    "slaMissed": {
      "type": "bool",
      "default": false,
      "readOnly": true,
      "style": {
        "true": "danger"
      }
    },
    "slaStatus": {
      "type": "enum",
      "options": ["On Track", "At Risk", "Breached"],
      "default": "On Track",
      "readOnly": true,
      "style": {
        "On Track": "success",
        "At Risk": "warning",
        "Breached": "danger"
      }
    },
    "hoursToResolution": {
      "type": "int",
      "readOnly": true,
      "tooltip": "Business hours taken to resolve"
    },
    "resolutionTargetDays": {
      "type": "int",
      "default": 15,
      "tooltip": "Target resolution time in days"
    },
    
    // === RESOLUTION TRACKING ===
    "resolutionType": {
      "type": "enum",
      "options": [
        "Resolved - Satisfied",
        "Resolved - Compensated",
        "Resolved - Waiver Granted",
        "Resolved - Corrected",
        "Closed - Duplicate",
        "Closed - No Action Required",
        "Closed - Customer Withdrew",
        "Rejected - Invalid",
        "Escalated Externally"
      ],
      "style": {
        "Resolved - Satisfied": "success",
        "Resolved - Compensated": "success",
        "Escalated Externally": "danger",
        "Rejected - Invalid": "danger"
      }
    },
    "resolutionDate": {
      "type": "datetime"
    },
    "resolutionSummary": {
      "type": "text",
      "rows": 5,
      "required": false,
      "tooltip": "Detailed explanation of how case was resolved"
    },
    "customerSatisfactionRating": {
      "type": "enum",
      "options": ["1 - Very Dissatisfied", "2 - Dissatisfied", "3 - Neutral", "4 - Satisfied", "5 - Very Satisfied"],
      "tooltip": "Post-resolution customer feedback"
    },
    "rootCauseCategory": {
      "type": "enum",
      "options": [
        "Process Gap",
        "System Error",
        "Human Error",
        "Policy Issue",
        "Communication Gap",
        "Training Gap",
        "Third-Party Issue",
        "Customer Misunderstanding",
        "Other"
      ]
    },
    "rootCauseAnalysis": {
      "type": "text",
      "rows": 4,
      "tooltip": "Detailed root cause analysis"
    },
    "preventiveActions": {
      "type": "text",
      "rows": 3,
      "tooltip": "Actions taken to prevent recurrence"
    },
    
    // === COMPENSATION & REMEDIATION ===
    "compensationRequired": {
      "type": "bool",
      "default": false
    },
    "compensationType": {
      "type": "enum",
      "options": ["Refund", "Fee Waiver", "Interest Adjustment", "Goodwill Gesture", "Other"]
    },
    "compensationAmount": {
      "type": "currency",
      "tooltip": "Amount refunded/waived"
    },
    "compensationApprovedBy": {
      "type": "link",
      "entity": "User"
    },
    "compensationProcessed": {
      "type": "bool",
      "default": false
    },
    "compensationProcessedDate": {
      "type": "date"
    },
    "compensationRemarks": {
      "type": "text",
      "rows": 3
    },
    
    // === ESCALATION MANAGEMENT ===
    "escalationLevel": {
      "type": "enum",
      "options": ["None", "Supervisor", "Manager", "Senior Management", "Compliance Officer", "CEO"],
      "default": "None",
      "style": {
        "CEO": "danger",
        "Senior Management": "danger",
        "Compliance Officer": "warning"
      }
    },
    "escalatedAt": {
      "type": "datetime"
    },
    "escalatedTo": {
      "type": "link",
      "entity": "User",
      "tooltip": "User to whom case was escalated"
    },
    "escalationReason": {
      "type": "text",
      "rows": 3
    },
    "requiresLegalReview": {
      "type": "bool",
      "default": false
    },
    "legalReviewStatus": {
      "type": "enum",
      "options": ["Not Required", "Pending", "In Progress", "Completed"],
      "default": "Not Required"
    },
    
    // === REGULATORY COMPLIANCE ===
    "isRegulatoryComplaint": {
      "type": "bool",
      "default": false,
      "tooltip": "Complaint escalated to/received from regulator"
    },
    "regulatoryBody": {
      "type": "enum",
      "options": [
        "Central Bank of Sri Lanka",
        "Financial Ombudsman",
        "Consumer Affairs Authority",
        "Police",
        "Other"
      ]
    },
    "regulatoryReferenceNumber": {
      "type": "varchar",
      "maxLength": 100,
      "tooltip": "Reference number from regulatory body"
    },
    "regulatoryDeadline": {
      "type": "date",
      "tooltip": "Deadline set by regulator for response"
    },
    "regulatoryResponseSubmitted": {
      "type": "bool",
      "default": false
    },
    "regulatoryResponseDate": {
      "type": "date"
    },
    "regulatoryOutcome": {
      "type": "text",
      "rows": 3,
      "tooltip": "Final outcome from regulatory body"
    },
    "reportableToCBSL": {
      "type": "bool",
      "default": false,
      "tooltip": "Must be included in quarterly CBSL complaint report"
    },
    
    // === CHANNEL TRACKING ===
    "receivedVia": {
      "type": "enum",
      "options": [
        "Email",
        "Phone Call",
        "Walk-in",
        "Letter",
        "Website Form",
        "Mobile App",
        "Social Media",
        "WhatsApp",
        "Customer Portal",
        "Regulator",
        "Other"
      ],
      "required": true
    },
    "branchLocation": {
      "type": "varchar",
      "maxLength": 255,
      "tooltip": "Branch where complaint was received (if walk-in)"
    },
    "complainantType": {
      "type": "enum",
      "options": ["Customer", "Guarantor", "Third Party", "Regulatory Body"],
      "default": "Customer"
    },
    
    // === RELATED RECORDS ===
    "loanAccount": {
      "type": "link",
      "entity": "LoanAccount",
      "tooltip": "Related loan account"
    },
    "loanAccountNumber": {
      "type": "varchar",
      "maxLength": 50,
      "tooltip": "Loan/account number related to case"
    },
    "transactionReference": {
      "type": "varchar",
      "maxLength": 100,
      "tooltip": "Transaction reference (if case is about specific transaction)"
    },
    "relatedOpportunity": {
      "type": "link",
      "entity": "FinancialOpportunity",
      "tooltip": "Related loan application (for application-related complaints)"
    },
    
    // === CASE METRICS ===
    "reopenCount": {
      "type": "int",
      "default": 0,
      "readOnly": true,
      "tooltip": "Number of times case was reopened"
    },
    "responseTime": {
      "type": "int",
      "readOnly": true,
      "tooltip": "Hours to first response"
    },
    "daysSinceCreation": {
      "type": "int",
      "readOnly": true,
      "formula": "datetime\\diff(createdAt, datetime\\now(), 'days')"
    },
    "isOverdue": {
      "type": "bool",
      "readOnly": true,
      "formula": "datetime\\now() > slaResolutionDue && status != 'Closed'"
    },
    
    // === ENHANCED STATUS ===
    "caseStatus": {
      "type": "enum",
      "options": [
        "New",
        "Acknowledged",
        "Under Investigation",
        "Pending Customer Response",
        "Pending Internal Approval",
        "Pending Compensation",
        "Escalated",
        "Resolved",
        "Closed",
        "Rejected"
      ],
      "default": "New",
      "required": true,
      "style": {
        "New": "primary",
        "Acknowledged": "default",
        "Under Investigation": "warning",
        "Pending Customer Response": "info",
        "Escalated": "danger",
        "Resolved": "success",
        "Closed": "default",
        "Rejected": "danger"
      }
    },
    
    // === INTERNAL NOTES ===
    "internalNotes": {
      "type": "text",
      "rows": 5,
      "tooltip": "Internal notes not visible to customer"
    },
    "actionsTaken": {
      "type": "text",
      "rows": 5,
      "tooltip": "Detailed log of actions taken to resolve"
    }
  },
  
  "links": {
    "loanAccount": {
      "type": "belongsTo",
      "entity": "LoanAccount"
    },
    "relatedOpportunity": {
      "type": "belongsTo",
      "entity": "FinancialOpportunity"
    },
    "escalatedTo": {
      "type": "belongsTo",
      "entity": "User"
    },
    "acknowledgedBy": {
      "type": "belongsTo",
      "entity": "User"
    },
    "compensationApprovedBy": {
      "type": "belongsTo",
      "entity": "User"
    },
    "caseActions": {
      "type": "hasMany",
      "entity": "CaseAction",
      "foreign": "case"
    },
    "relatedCases": {
      "type": "hasMany",
      "entity": "FinancialCase",
      "foreign": "parentCase"
    },
    "parentCase": {
      "type": "belongsTo",
      "entity": "FinancialCase",
      "foreign": "relatedCases"
    }
  }
}
```

### 3.2 Supporting Entities

#### CaseAction Entity

```json
{
  "entityType": "CaseAction",
  "fields": {
    "actionDate": {
      "type": "datetime",
      "required": true,
      "default": "javascript: return new Date();"
    },
    "actionType": {
      "type": "enum",
      "options": [
        "Customer Contacted",
        "Information Gathered",
        "Investigation Completed",
        "Escalated",
        "Compensation Approved",
        "Resolution Communicated",
        "Case Closed",
        "Case Reopened",
        "Other"
      ],
      "required": true
    },
    "performedBy": {
      "type": "link",
      "entity": "User",
      "required": true
    },
    "description": {
      "type": "text",
      "rows": 4,
      "required": true
    },
    "isVisibleToCustomer": {
      "type": "bool",
      "default": false,
      "tooltip": "Should this action be visible in customer portal"
    },
    "case": {
      "type": "link",
      "entity": "FinancialCase",
      "required": true
    }
  }
}
```

---

## 4. Business Logic & Services

### 4.1 SLA Calculation Service

```php
<?php
namespace Espo\Custom\Services;

class CaseSlaService extends \Espo\Core\Services\Base
{
    // Business hours configuration
    private $businessHoursStart = 9; // 9 AM
    private $businessHoursEnd = 17;  // 5 PM
    private $businessDays = [1, 2, 3, 4, 5]; // Monday to Friday
    
    public function calculateSlaDates($case)
    {
        $createdAt = new \DateTime($case->get('createdAt'));
        $category = $case->get('caseCategory');
        $priority = $case->get('priority');
        
        // Acknowledgment SLA: 24 hours for all cases
        $acknowledgmentDue = $this->addBusinessHours($createdAt, 24);
        
        // Resolution SLA based on category and priority
        $resolutionHours = $this->getResolutionSla($category, $priority);
        $resolutionDue = $this->addBusinessHours($createdAt, $resolutionHours);
        
        return [
            'acknowledgmentDue' => $acknowledgmentDue,
            'resolutionDue' => $resolutionDue
        ];
    }
    
    private function getResolutionSla($category, $priority)
    {
        // Resolution SLA matrix (in business hours)
        $slaMatrix = [
            'Complaint' => [
                'Urgent' => 48,    // 2 days
                'High' => 72,      // 3 days
                'Normal' => 120,   // 5 days
                'Low' => 168       // 7 days
            ],
            'Service Request' => [
                'Urgent' => 24,
                'High' => 48,
                'Normal' => 72,
                'Low' => 120
            ],
            'Inquiry' => [
                'Urgent' => 8,
                'High' => 24,
                'Normal' => 48,
                'Low' => 72
            ],
            'Technical Issue' => [
                'Urgent' => 4,
                'High' => 24,
                'Normal' => 48,
                'Low' => 72
            ],
            'Fraud Report' => [
                'Urgent' => 24,
                'High' => 48,
                'Normal' => 72,
                'Low' => 120
            ]
        ];
        
        return $slaMatrix[$category][$priority] ?? 120; // Default: 5 days
    }
    
    private function addBusinessHours(\DateTime $startDate, $hoursToAdd)
    {
        $date = clone $startDate;
        $hoursAdded = 0;
        
        while ($hoursAdded < $hoursToAdd) {
            // Skip weekends
            if (!in_array($date->format('N'), $this->businessDays)) {
                $date->modify('+1 day');
                $date->setTime($this->businessHoursStart, 0);
                continue;
            }
            
            $currentHour = (int)$date->format('H');
            
            // Skip non-business hours
            if ($currentHour < $this->businessHoursStart) {
                $date->setTime($this->businessHoursStart, 0);
            } elseif ($currentHour >= $this->businessHoursEnd) {
                $date->modify('+1 day');
                $date->setTime($this->businessHoursStart, 0);
                continue;
            }
            
            // Add 1 hour
            $date->modify('+1 hour');
            $hoursAdded++;
            
            // If we've gone past business hours, move to next day
            if ((int)$date->format('H') >= $this->businessHoursEnd) {
                $date->modify('+1 day');
                $date->setTime($this->businessHoursStart, 0);
            }
        }
        
        return $date->format('Y-m-d H:i:s');
    }
    
    public function checkSlaStatus($case)
    {
        $now = new \DateTime();
        $resolutionDue = new \DateTime($case->get('slaResolutionDue'));
        $status = $case->get('caseStatus');
        
        // If case is closed, SLA doesn't apply
        if (in_array($status, ['Resolved', 'Closed', 'Rejected'])) {
            return 'On Track';
        }
        
        // Calculate time remaining
        $hoursRemaining = $this->calculateBusinessHoursBetween($now, $resolutionDue);
        
        if ($hoursRemaining < 0) {
            return 'Breached';
        } elseif ($hoursRemaining <= 8) { // Less than 1 business day
            return 'At Risk';
        } else {
            return 'On Track';
        }
    }
}
```

### 4.2 Case Escalation Service

```php
<?php
namespace Espo\Custom\Services;

class CaseEscalationService extends \Espo\Core\Services\Base
{
    public function escalateCase($caseId, $reason)
    {
        $case = $this->getEntityManager()->getEntity('FinancialCase', $caseId);
        
        if (!$case) {
            throw new \Exception('Case not found');
        }
        
        // Determine escalation level
        $currentLevel = $case->get('escalationLevel') ?: 'None';
        $nextLevel = $this->getNextEscalationLevel($currentLevel, $case);
        
        // Get escalation target user
        $escalationUser = $this->getEscalationUser($nextLevel, $case);
        
        // Update case
        $case->set('escalationLevel', $nextLevel);
        $case->set('escalatedAt', date('Y-m-d H:i:s'));
        $case->set('escalatedTo', $escalationUser->get('id'));
        $case->set('escalationReason', $reason);
        $case->set('caseStatus', 'Escalated');
        
        $this->getEntityManager()->saveEntity($case);
        
        // Create escalation activity
        $this->createEscalationActivity($case, $escalationUser, $reason);
        
        // Send notification
        $this->notifyEscalation($case, $escalationUser);
        
        return $nextLevel;
    }
    
    private function getNextEscalationLevel($currentLevel, $case)
    {
        $escalationPath = [
            'None' => 'Supervisor',
            'Supervisor' => 'Manager',
            'Manager' => 'Senior Management',
            'Senior Management' => 'Compliance Officer',
            'Compliance Officer' => 'CEO'
        ];
        
        // For regulatory complaints, skip to Compliance Officer
        if ($case->get('isRegulatoryComplaint') && $currentLevel == 'None') {
            return 'Compliance Officer';
        }
        
        // For high financial impact, escalate faster
        if ($case->get('financialImpact') > 100000 && $currentLevel == 'Supervisor') {
            return 'Senior Management';
        }
        
        return $escalationPath[$currentLevel] ?? 'CEO';
    }
    
    public function autoEscalateOverdueCases()
    {
        // Find cases that are overdue and not yet escalated
        $cases = $this->getEntityManager()
            ->getRepository('FinancialCase')
            ->where([
                'isOverdue' => true,
                'caseStatus' => ['!=', 'Closed'],
                'escalationLevel' => 'None'
            ])
            ->find();
        
        foreach ($cases as $case) {
            $this->escalateCase($case->get('id'), 'Auto-escalated due to SLA breach');
        }
    }
}
```

### 4.3 Compensation Processing Service

```php
<?php
namespace Espo\Custom\Services;

class CompensationService extends \Espo\Core\Services\Base
{
    public function processCompensation($caseId, $amount, $type, $remarks)
    {
        $case = $this->getEntityManager()->getEntity('FinancialCase', $caseId);
        
        if (!$case) {
            throw new \Exception('Case not found');
        }
        
        // Validate compensation authority
        $currentUser = $this->getUser();
        if (!$this->hasCompensationAuthority($currentUser, $amount)) {
            throw new \Exception('Insufficient authority to approve compensation');
        }
        
        // Update case
        $case->set('compensationRequired', true);
        $case->set('compensationType', $type);
        $case->set('compensationAmount', $amount);
        $case->set('compensationApprovedBy', $currentUser->get('id'));
        $case->set('compensationRemarks', $remarks);
        $case->set('compensationProcessed', true);
        $case->set('compensationProcessedDate', date('Y-m-d'));
        
        $this->getEntityManager()->saveEntity($case);
        
        // Process actual refund/waiver in financial system
        $this->executeCompensation($case, $amount, $type);
        
        // Notify customer
        $this->notifyCustomerCompensation($case);
        
        return true;
    }
    
    private function hasCompensationAuthority($user, $amount)
    {
        // Compensation authority matrix
        $role = $user->get('role');
        $authorityLimits = [
            'Customer Service Officer' => 5000,
            'Supervisor' => 25000,
            'Manager' => 100000,
            'Senior Manager' => 500000,
            'CEO' => PHP_INT_MAX
        ];
        
        return ($authorityLimits[$role] ?? 0) >= $amount;
    }
    
    private function executeCompensation($case, $amount, $type)
    {
        // Integration with core banking system
        // This would vary based on the institution's systems
        
        switch ($type) {
            case 'Refund':
                // Process refund to customer account
                break;
            case 'Fee Waiver':
                // Reverse fee charges
                break;
            case 'Interest Adjustment':
                // Adjust interest calculation
                break;
            case 'Goodwill Gesture':
                // Credit goodwill amount
                break;
        }
    }
}
```

### 4.4 Regulatory Reporting Service

```php
<?php
namespace Espo\Custom\Services;

class RegulatoryReportingService extends \Espo\Core\Services\Base
{
    public function generateQuarterlyComplaintReport($year, $quarter)
    {
        $startDate = $this->getQuarterStartDate($year, $quarter);
        $endDate = $this->getQuarterEndDate($year, $quarter);
        
        // Get all complaints in the period
        $complaints = $this->getEntityManager()
            ->getRepository('FinancialCase')
            ->where([
                'caseCategory' => 'Complaint',
                'createdAt>=' => $startDate,
                'createdAt<=' => $endDate,
                'reportableToCBSL' => true
            ])
            ->find();
        
        // Categorize complaints
        $report = [
            'period' => "Q{$quarter} {$year}",
            'totalComplaints' => count($complaints),
            'byType' => [],
            'byProduct' => [],
            'resolved' => 0,
            'pending' => 0,
            'averageResolutionDays' => 0,
            'compensationPaid' => 0,
            'escalatedToRegulator' => 0
        ];
        
        foreach ($complaints as $complaint) {
            // Count by type
            $type = $complaint->get('complaintType');
            $report['byType'][$type] = ($report['byType'][$type] ?? 0) + 1;
            
            // Count by product
            $product = $complaint->get('productAffected');
            $report['byProduct'][$product] = ($report['byProduct'][$product] ?? 0) + 1;
            
            // Resolution status
            if (in_array($complaint->get('caseStatus'), ['Resolved', 'Closed'])) {
                $report['resolved']++;
                $report['averageResolutionDays'] += $complaint->get('hoursToResolution') / 24;
            } else {
                $report['pending']++;
            }
            
            // Compensation
            if ($complaint->get('compensationProcessed')) {
                $report['compensationPaid'] += $complaint->get('compensationAmount');
            }
            
            // Regulatory escalation
            if ($complaint->get('isRegulatoryComplaint')) {
                $report['escalatedToRegulator']++;
            }
        }
        
        if ($report['resolved'] > 0) {
            $report['averageResolutionDays'] = round($report['averageResolutionDays'] / $report['resolved'], 2);
        }
        
        // Generate report document
        $this->generateReportDocument($report);
        
        return $report;
    }
}
```

---

## 5. Workflows & Automation

### 5.1 Case Creation & Acknowledgment

**Trigger:** New case created

**Actions:**
1. Generate unique case number
2. Calculate SLA dates based on category and priority
3. Auto-assign based on category (use round-robin or skill-based routing)
4. Send acknowledgment email to customer within 24 hours
5. Create follow-up task for assigned user
6. Set `caseStatus` = "New"

### 5.2 SLA Monitoring

**Trigger:** Hourly scheduled job

**Actions:**
- Check all open cases against SLA deadlines
- Update `slaStatus` field (On Track / At Risk / Breached)
- If At Risk (< 8 hours remaining): Send warning to assigned user
- If Breached: Auto-escalate to supervisor
- Generate SLA breach report

### 5.3 Auto-Escalation for Priority Cases

**Trigger:** Case created with `priority` = "Urgent" OR `financialImpact` > 100000

**Actions:**
1. Immediately escalate to Manager level
2. Send SMS + Email notification to escalation team
3. Mark case with "Urgent" flag
4. Set `escalationLevel` = "Manager"

### 5.4 Customer Satisfaction Survey

**Trigger:** Case status changes to "Resolved" or "Closed"

**Actions:**
1. Wait 24 hours
2. Send customer satisfaction survey email
3. Link to portal for rating submission
4. Record rating in `customerSatisfactionRating`
5. If rating < 3, trigger management review

### 5.5 Regulatory Complaint Workflow

**Trigger:** `isRegulatoryComplaint` = true

**Actions:**
1. Immediately notify Compliance Officer
2. Create high-priority task with regulatory deadline
3. Set escalation level to "Compliance Officer"
4. Mark for mandatory reporting
5. Send acknowledgment to regulatory body

---

## 6. Reporting & Dashboards

### 6.1 Customer Service Dashboard

**Metrics:**
- Total open cases
- Cases by status
- Cases by category
- SLA compliance rate (%)
- Average resolution time
- Cases at risk
- Cases breached
- Customer satisfaction score

**Charts:**
- Trend: Daily case volume
- Pie: Cases by category
- Bar: Cases by product
- Funnel: Case resolution funnel
- Heat map: SLA performance by agent

### 6.2 Complaint Analysis Report

**Metrics:**
- Total complaints by period
- Complaint types breakdown
- Top 5 complaint reasons
- Product-wise complaint distribution
- Branch-wise complaint distribution
- Repeat complainants
- Root cause analysis summary

### 6.3 Regulatory Compliance Report

**Metrics:**
- Complaints reportable to CBSL
- Regulatory escalations
- Compensation paid
- Average resolution time
- SLA compliance for regulatory cases
- Pending regulatory responses

### 6.4 Financial Impact Report

**Metrics:**
- Total financial impact of complaints
- Compensation/refunds paid
- Fee waivers granted
- Interest adjustments made
- Cost per resolved case
- ROI of complaint resolution

---

## 7. Portal & Customer Interface

### 7.1 Customer Portal Features

**Case Submission:**
- Easy-to-use complaint form
- File upload support (screenshots, documents)
- Auto-population of customer details
- Real-time case number generation

**Case Tracking:**
- View all submitted cases
- Real-time status updates
- Timeline of actions taken
- Estimated resolution date
- Communication history

**Self-Service:**
- FAQ section
- Knowledge base articles
- Common issues troubleshooting
- Contact information for escalation

### 7.2 Portal Access Controls

- Customers can only see their own cases
- Read-only access to case details
- Can add comments/replies
- Can upload additional documents
- Can close cases (mark as resolved)

---

## 8. Integration Points

### 8.1 Email Integration

- Email-to-Case: Auto-create cases from support email
- Case updates sent via email
- Email templates for acknowledgment, resolution, surveys

### 8.2 SMS Integration

- SMS notifications for urgent cases
- SMS acknowledgment to customer
- SMS resolution notification
- Two-way SMS support

### 8.3 Social Media Integration

- Monitor social media mentions
- Create cases from social media complaints
- Respond to public complaints
- Track sentiment

### 8.4 Core Banking Integration

- Fetch customer/account details
- Validate loan account numbers
- Process refunds/waivers
- Update account status

### 8.5 Central Bank Reporting

- API for CBSL complaint submission
- Automated quarterly report generation
- Compliance data export

---

## Conclusion

This enhancement transforms EspoCRM's Case module into a comprehensive **Customer Complaint & Service Request Management** system with SLA tracking, regulatory compliance, escalation management, and compensation processing tailored for Sri Lankan financial institutions.
