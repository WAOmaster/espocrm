# Financial Services Opportunity Management Module for EspoCRM

## Executive Summary

This document specifies a comprehensive enhancement to EspoCRM's Opportunity module, transforming it into a **Loan Application & Pipeline Management** system for financial companies in Sri Lanka. In financial services context, an "Opportunity" represents a potential loan/credit facility rather than a sales deal.

---

## 1. Current EspoCRM Opportunity Entity Analysis

### 1.1 Out-of-Box Opportunity Fields

**Core Fields:**
- `name` - Opportunity name/title
- `amount` - Deal value (currency)
- `stage` - Pipeline stage (enum: Prospecting, Qualification, Needs Analysis, Value Proposition, Id. Decision Makers, Perception Analysis, Proposal/Price Quote, Negotiation/Review, Closed Won, Closed Lost)
- `probability` - Win probability % (0-100)
- `closeDate` - Expected close date
- `description` - Notes/description

**Relationship Fields:**
- `account` - Related company/account
- `contacts` - Related contacts (link-multiple)
- `campaign` - Marketing campaign source
- `createdOpportunity` - Link from converted Lead

**Sales Tracking:**
- `leadSource` - Origin source (enum: Call, Email, Existing Customer, Partner, Public Relations, Web Site, Campaign, Other)
- `assignedUser` - Owner/sales rep
- `teams` - Team assignments

**Financial Tracking:**
- `amountWeightedConverted` - Probability-weighted amount (auto-calculated)
- `originalLead` - Original lead record

### 1.2 Current Capabilities

**Strengths:**
- Visual pipeline/Kanban view by stage
- Probability-based forecasting
- Activity tracking (calls, meetings, emails)
- Quote generation integration
- Revenue forecasting reports
- Team-based access control
- Lead-to-Opportunity conversion tracking

**Critical Gaps for Financial Services:**
- No loan-specific fields (tenure, interest rate, EMI, collateral)
- No approval workflow stages
- No credit assessment/underwriting tracking
- No disbursement management
- No guarantor/co-borrower relationships
- No regulatory compliance fields
- No document checklist tracking
- No repayment schedule preview
- No collateral valuation tracking
- Limited integration points for credit bureaus, loan origination systems

---

## 2. Financial Services Opportunity Context

### 2.1 What is a Loan Opportunity?

A financial services opportunity represents:
- **Loan Inquiry** - Customer expresses interest in borrowing
- **Loan Application** - Formal application submitted
- **Pipeline Item** - Active loan being processed toward approval/disbursement

**Key Differences from B2B Sales Opportunity:**
- Multi-stage approval process (credit, risk, management)
- Regulatory compliance requirements (KYC, AML, lending limits)
- Collateral valuation and legal verification
- Credit risk assessment
- Disbursement execution
- Post-disbursement handoff to loan servicing

### 2.2 Loan Application Lifecycle Stages

**Stage 1: Inquiry/Pre-Qualification (0-10% probability)**
- Customer expresses interest
- Basic eligibility check
- Product recommendation
- Indicative offer provided

**Stage 2: Application Submitted (10-25% probability)**
- Formal application received
- Application fee collected
- Preliminary document verification
- Application number assigned

**Stage 3: Documentation (25-40% probability)**
- Complete KYC documents collected
- Income/employment verification
- Collateral documents obtained (for secured loans)
- Guarantor documents collected

**Stage 4: Credit Assessment (40-60% probability)**
- Credit bureau report obtained
- Internal credit scoring
- Affordability analysis (FOIR/DTI)
- Credit risk rating assigned

**Stage 5: Underwriting (60-75% probability)**
- Detailed financial analysis
- Collateral valuation (property, vehicle, gold)
- Legal verification of collateral
- Guarantor verification
- Final risk assessment

**Stage 6: Credit Approval (75-85% probability)**
- Credit committee review
- Approval authority decision
- Conditional approval (if deviations needed)
- Final approval granted

**Stage 7: Documentation & Legals (85-95% probability)**
- Loan agreement preparation
- Mortgage/hypothecation documentation
- Insurance arrangement
- Legal documentation execution

**Stage 8: Disbursement Ready (95-98% probability)**
- All conditions fulfilled
- Disbursement instruction prepared
- Account opening (if new customer)
- Standing instructions setup

**Stage 9: Disbursed (100% - Won)**
- Loan disbursed to customer account
- Handover to loan servicing
- Repayment schedule activated
- First EMI scheduled

**Stage 10: Rejected/Withdrawn (0% - Lost)**
- Application rejected (credit, documentation, policy)
- Customer withdrew application
- Competitor offer accepted
- Not interested/unreachable

### 2.3 Loan Product Types

Sri Lankan financial companies typically offer:
- **Personal Loans** - Unsecured, salary-based lending
- **Vehicle Loans** - Auto loans for cars, vans, motorcycles
- **Gold Loans** - Secured by gold jewelry
- **Home Loans/Mortgages** - Property purchase/construction
- **Business Loans/SME Financing** - Working capital, equipment
- **Leasing** - Vehicle leasing, equipment leasing
- **Pawning** - Short-term secured loans
- **Credit Cards** - Revolving credit facilities

---

## 3. Enhanced Loan Opportunity Entity Structure

### 3.1 FinancialOpportunity Entity Definition

```json
{
  "entityType": "FinancialOpportunity",
  "extends": "Opportunity",
  "fields": {
    
    // === LOAN DETAILS ===
    "loanProduct": {
      "type": "link",
      "entity": "LoanProduct",
      "required": true,
      "tooltip": "Type of loan product"
    },
    "loanProductCategory": {
      "type": "enum",
      "options": ["Personal Loan", "Vehicle Loan", "Gold Loan", "Home Loan", "Business Loan", "Leasing", "Pawning", "Credit Card", "Overdraft"],
      "required": true
    },
    "loanAmount": {
      "type": "currency",
      "required": true,
      "tooltip": "Requested/approved loan amount"
    },
    "approvedLoanAmount": {
      "type": "currency",
      "tooltip": "Finally approved amount (may differ from requested)"
    },
    "loanTenure": {
      "type": "int",
      "required": true,
      "tooltip": "Loan tenure in months"
    },
    "interestRate": {
      "type": "float",
      "tooltip": "Annual interest rate %",
      "min": 0,
      "max": 100
    },
    "interestType": {
      "type": "enum",
      "options": ["Fixed", "Variable", "Floating"],
      "default": "Fixed"
    },
    "emi": {
      "type": "currency",
      "readOnly": true,
      "tooltip": "Monthly installment amount (auto-calculated)"
    },
    "totalInterest": {
      "type": "currency",
      "readOnly": true,
      "tooltip": "Total interest payable"
    },
    "totalRepayableAmount": {
      "type": "currency",
      "readOnly": true
    },
    "loanPurpose": {
      "type": "text",
      "rows": 3,
      "required": true
    },
    
    // === BORROWER INFORMATION ===
    "primaryBorrower": {
      "type": "link",
      "entity": "Contact",
      "required": true
    },
    "coBorrowers": {
      "type": "linkMultiple",
      "entity": "Contact",
      "tooltip": "Co-applicants/joint borrowers"
    },
    "guarantors": {
      "type": "linkMultiple",
      "entity": "Contact",
      "tooltip": "Guarantors for the loan"
    },
    "employmentType": {
      "type": "enum",
      "options": ["Salaried", "Self-Employed", "Business Owner", "Professional", "Retired"],
      "required": true
    },
    "monthlyIncome": {
      "type": "currency",
      "required": true
    },
    "existingMonthlyObligations": {
      "type": "currency",
      "tooltip": "Existing EMIs, rent, and other fixed obligations"
    },
    "netMonthlyIncome": {
      "type": "currency",
      "readOnly": true,
      "formula": "monthlyIncome - existingMonthlyObligations"
    },
    
    // === COLLATERAL INFORMATION (for secured loans) ===
    "isSecuredLoan": {
      "type": "bool",
      "default": false
    },
    "collateralType": {
      "type": "enum",
      "options": ["Property", "Vehicle", "Gold", "Fixed Deposit", "Shares", "Equipment", "Other"],
      "tooltip": "Type of security"
    },
    "collateralDescription": {
      "type": "text",
      "rows": 4,
      "tooltip": "Details of collateral (address, make/model, weight, etc.)"
    },
    "collateralValue": {
      "type": "currency",
      "tooltip": "Estimated market value of collateral"
    },
    "valuationDate": {
      "type": "date",
      "tooltip": "Date of collateral valuation"
    },
    "loanToValueRatio": {
      "type": "float",
      "readOnly": true,
      "tooltip": "LTV % (loan amount / collateral value * 100)"
    },
    "collateralOwner": {
      "type": "varchar",
      "maxLength": 255,
      "tooltip": "Name of collateral owner (if different from borrower)"
    },
    
    // === CREDIT ASSESSMENT ===
    "creditScore": {
      "type": "int",
      "min": 300,
      "max": 900,
      "readOnly": true
    },
    "creditBureauReportDate": {
      "type": "date"
    },
    "creditBureauReportFile": {
      "type": "attachment",
      "maxCount": 1
    },
    "numberOfExistingLoans": {
      "type": "int",
      "min": 0
    },
    "totalExistingDebt": {
      "type": "currency"
    },
    "defaultHistory": {
      "type": "bool",
      "default": false,
      "tooltip": "Has payment defaults or credit issues"
    },
    "foir": {
      "type": "float",
      "readOnly": true,
      "tooltip": "Fixed Obligation to Income Ratio %"
    },
    "dti": {
      "type": "float",
      "readOnly": true,
      "tooltip": "Debt-to-Income ratio %"
    },
    "internalCreditRating": {
      "type": "enum",
      "options": ["AAA", "AA", "A", "BBB", "BB", "B", "CCC", "CC", "C", "D"],
      "tooltip": "Internal risk rating"
    },
    "riskCategory": {
      "type": "enum",
      "options": ["Low Risk", "Medium Risk", "High Risk", "Very High Risk"],
      "style": {
        "Low Risk": "success",
        "Medium Risk": "warning",
        "High Risk": "danger",
        "Very High Risk": "danger"
      }
    },
    
    // === APPROVAL TRACKING ===
    "applicationNumber": {
      "type": "varchar",
      "maxLength": 50,
      "unique": true,
      "readOnly": true,
      "tooltip": "System-generated application number"
    },
    "applicationDate": {
      "type": "date",
      "required": true,
      "default": "javascript: return new Date();"
    },
    "targetDisbursementDate": {
      "type": "date"
    },
    "actualDisbursementDate": {
      "type": "date"
    },
    "creditApprovalStatus": {
      "type": "enum",
      "options": ["Pending", "In Review", "Approved", "Conditional Approval", "Rejected"],
      "default": "Pending",
      "style": {
        "Approved": "success",
        "Conditional Approval": "warning",
        "Rejected": "danger",
        "In Review": "primary"
      }
    },
    "approvalAuthority": {
      "type": "enum",
      "options": ["Branch Manager", "Regional Manager", "Credit Manager", "Credit Committee", "CEO"],
      "tooltip": "Who approved/will approve this loan"
    },
    "approvedBy": {
      "type": "link",
      "entity": "User"
    },
    "approvalDate": {
      "type": "date"
    },
    "approvalRemarks": {
      "type": "text",
      "rows": 3
    },
    "deviations": {
      "type": "text",
      "rows": 4,
      "tooltip": "Policy deviations that required special approval"
    },
    "conditions": {
      "type": "text",
      "rows": 4,
      "tooltip": "Conditions to be met before disbursement"
    },
    
    // === PROCESSING & DOCUMENTATION ===
    "kycStatus": {
      "type": "enum",
      "options": ["Not Started", "In Progress", "Pending Documents", "Completed", "Failed"],
      "default": "Not Started",
      "required": true
    },
    "kycCompletedDate": {
      "type": "date"
    },
    "documentChecklistComplete": {
      "type": "bool",
      "default": false,
      "readOnly": true
    },
    "pendingDocuments": {
      "type": "text",
      "readOnly": true,
      "tooltip": "List of pending documents (auto-generated)"
    },
    "legalVerificationStatus": {
      "type": "enum",
      "options": ["Not Required", "Pending", "In Progress", "Completed", "Issues Found"],
      "default": "Not Required"
    },
    "valuationStatus": {
      "type": "enum",
      "options": ["Not Required", "Pending", "Scheduled", "Completed", "Re-valuation Needed"],
      "default": "Not Required"
    },
    "insuranceRequired": {
      "type": "bool",
      "default": false
    },
    "insuranceArranged": {
      "type": "bool",
      "default": false
    },
    "insurancePolicyNumber": {
      "type": "varchar",
      "maxLength": 100
    },
    
    // === FEES & CHARGES ===
    "applicationFee": {
      "type": "currency",
      "default": 0
    },
    "processingFee": {
      "type": "currency",
      "default": 0
    },
    "processingFeePercentage": {
      "type": "float",
      "tooltip": "Processing fee as % of loan amount"
    },
    "valuationFee": {
      "type": "currency",
      "default": 0
    },
    "legalFee": {
      "type": "currency",
      "default": 0
    },
    "stampDuty": {
      "type": "currency",
      "default": 0
    },
    "insurancePremium": {
      "type": "currency",
      "default": 0
    },
    "totalUpfrontCharges": {
      "type": "currency",
      "readOnly": true,
      "tooltip": "Sum of all upfront fees"
    },
    "chargesDeductedFromLoan": {
      "type": "bool",
      "default": false,
      "tooltip": "If true, charges are deducted from disbursement"
    },
    "netDisbursementAmount": {
      "type": "currency",
      "readOnly": true,
      "tooltip": "Amount to be disbursed after fee deduction"
    },
    
    // === DISBURSEMENT ===
    "disbursementMethod": {
      "type": "enum",
      "options": ["Bank Transfer", "Cheque", "Cash", "Direct Payment to Vendor"],
      "default": "Bank Transfer"
    },
    "disbursementBankName": {
      "type": "varchar",
      "maxLength": 100
    },
    "disbursementAccountNumber": {
      "type": "varchar",
      "maxLength": 50,
      "encrypted": true
    },
    "disbursementBranch": {
      "type": "varchar",
      "maxLength": 100
    },
    "disbursementReference": {
      "type": "varchar",
      "maxLength": 100,
      "tooltip": "Payment reference number"
    },
    "disbursementInstructions": {
      "type": "text",
      "rows": 3
    },
    
    // === REJECTION TRACKING ===
    "rejectionReason": {
      "type": "enum",
      "options": [
        "Low Credit Score",
        "Insufficient Income",
        "High DTI/FOIR",
        "Negative Credit History",
        "Incomplete Documentation",
        "Collateral Issues",
        "Policy Violation",
        "Fraud Suspicion",
        "Customer Withdrew",
        "Competitor Offer",
        "Other"
      ]
    },
    "rejectionRemarks": {
      "type": "text",
      "rows": 3
    },
    "rejectionDate": {
      "type": "date"
    },
    
    // === CONVERSION TO LOAN ACCOUNT ===
    "loanAccountNumber": {
      "type": "varchar",
      "maxLength": 50,
      "tooltip": "Loan account number after disbursement"
    },
    "loanAccount": {
      "type": "link",
      "entity": "LoanAccount",
      "tooltip": "Link to loan servicing record"
    },
    
    // === ENHANCED STAGE MANAGEMENT ===
    "loanStage": {
      "type": "enum",
      "options": [
        "Inquiry",
        "Application Submitted",
        "Documentation",
        "Credit Assessment",
        "Underwriting",
        "Credit Approval",
        "Documentation & Legals",
        "Disbursement Ready",
        "Disbursed",
        "Rejected",
        "Withdrawn"
      ],
      "default": "Inquiry",
      "required": true,
      "style": {
        "Inquiry": "default",
        "Application Submitted": "primary",
        "Documentation": "warning",
        "Credit Assessment": "info",
        "Underwriting": "info",
        "Credit Approval": "success",
        "Documentation & Legals": "warning",
        "Disbursement Ready": "success",
        "Disbursed": "success",
        "Rejected": "danger",
        "Withdrawn": "danger"
      }
    },
    "stageEnteredDate": {
      "type": "datetime",
      "readOnly": true
    },
    "daysInCurrentStage": {
      "type": "int",
      "readOnly": true,
      "formula": "datetime\\diff(stageEnteredDate, datetime\\now(), 'days')"
    },
    "daysInPipeline": {
      "type": "int",
      "readOnly": true,
      "formula": "datetime\\diff(applicationDate, datetime\\now(), 'days')"
    },
    
    // === COMPLIANCE & REGULATORY ===
    "regulatoryReporting": {
      "type": "bool",
      "default": false,
      "tooltip": "Requires special regulatory reporting"
    },
    "relatedPartyTransaction": {
      "type": "bool",
      "default": false,
      "tooltip": "Is this a related party loan (directors, shareholders, employees)"
    },
    "exceedsLendingLimit": {
      "type": "bool",
      "default": false,
      "tooltip": "Exceeds single borrower limit requiring board approval"
    },
    "cbslReportingCategory": {
      "type": "enum",
      "options": ["Consumer Credit", "Business Credit", "Lease Finance", "Pawning", "Other"],
      "tooltip": "Central Bank reporting category"
    },
    
    // === SOURCE & CHANNEL ===
    "originationChannel": {
      "type": "enum",
      "options": [
        "Branch Walk-in",
        "Field Agent",
        "DSA/Broker",
        "Website",
        "Mobile App",
        "Phone Call",
        "Partner Referral",
        "Existing Customer",
        "Campaign",
        "Other"
      ],
      "required": true
    },
    "referralSource": {
      "type": "varchar",
      "maxLength": 255,
      "tooltip": "Name of referrer, agent, or partner"
    },
    "campaignCode": {
      "type": "varchar",
      "maxLength": 50
    }
  },
  
  "links": {
    "loanProduct": {
      "type": "belongsTo",
      "entity": "LoanProduct"
    },
    "primaryBorrower": {
      "type": "belongsTo",
      "entity": "Contact",
      "foreign": "primaryLoanApplications"
    },
    "coBorrowers": {
      "type": "hasMany",
      "entity": "Contact",
      "foreign": "coApplications",
      "relationName": "opportunityCoBorrower"
    },
    "guarantors": {
      "type": "hasMany",
      "entity": "Contact",
      "foreign": "guaranteedApplications",
      "relationName": "opportunityGuarantor"
    },
    "documents": {
      "type": "hasMany",
      "entity": "OpportunityDocument",
      "foreign": "opportunity"
    },
    "appraisals": {
      "type": "hasMany",
      "entity": "CollateralAppraisal",
      "foreign": "opportunity"
    },
    "approvals": {
      "type": "hasMany",
      "entity": "LoanApproval",
      "foreign": "opportunity"
    },
    "loanAccount": {
      "type": "belongsTo",
      "entity": "LoanAccount"
    },
    "originalLead": {
      "type": "belongsTo",
      "entity": "FinancialLead"
    }
  }
}
```

### 3.2 Supporting Entities

#### OpportunityDocument Entity

```json
{
  "entityType": "OpportunityDocument",
  "fields": {
    "name": {
      "type": "varchar",
      "required": true
    },
    "documentType": {
      "type": "enum",
      "options": [
        "NIC Copy",
        "Salary Certificate",
        "Bank Statement",
        "Income Tax Return",
        "Business Registration",
        "Financial Statements",
        "Property Title Deed",
        "Vehicle Registration",
        "Valuation Report",
        "Insurance Policy",
        "Guarantor NIC",
        "Loan Agreement",
        "Mortgage Deed",
        "Other"
      ],
      "required": true
    },
    "isMandatory": {
      "type": "bool",
      "default": false
    },
    "documentStatus": {
      "type": "enum",
      "options": ["Pending", "Received", "Verified", "Rejected", "Expired"],
      "default": "Pending",
      "style": {
        "Verified": "success",
        "Rejected": "danger",
        "Received": "warning",
        "Expired": "danger"
      }
    },
    "receivedDate": {
      "type": "date"
    },
    "verifiedDate": {
      "type": "date"
    },
    "verifiedBy": {
      "type": "link",
      "entity": "User"
    },
    "expiryDate": {
      "type": "date",
      "tooltip": "For time-sensitive documents"
    },
    "rejectionReason": {
      "type": "text"
    },
    "attachment": {
      "type": "attachmentMultiple",
      "maxCount": 5
    },
    "opportunity": {
      "type": "link",
      "entity": "FinancialOpportunity",
      "required": true
    }
  }
}
```

#### CollateralAppraisal Entity

```json
{
  "entityType": "CollateralAppraisal",
  "fields": {
    "appraisalDate": {
      "type": "date",
      "required": true
    },
    "appraiser": {
      "type": "varchar",
      "maxLength": 255,
      "tooltip": "Name of valuer/appraiser"
    },
    "collateralType": {
      "type": "enum",
      "options": ["Property", "Vehicle", "Gold", "Equipment", "Other"]
    },
    "description": {
      "type": "text",
      "rows": 5
    },
    "marketValue": {
      "type": "currency",
      "required": true
    },
    "forcedSaleValue": {
      "type": "currency",
      "tooltip": "Estimated value in distress sale"
    },
    "appraisalReport": {
      "type": "attachment",
      "maxCount": 1
    },
    "validityPeriod": {
      "type": "int",
      "tooltip": "Validity in months",
      "default": 6
    },
    "remarks": {
      "type": "text"
    },
    "opportunity": {
      "type": "link",
      "entity": "FinancialOpportunity",
      "required": true
    }
  }
}
```

#### LoanApproval Entity

```json
{
  "entityType": "LoanApproval",
  "fields": {
    "approvalLevel": {
      "type": "enum",
      "options": ["Branch Manager", "Regional Manager", "Credit Manager", "Credit Committee", "CEO", "Board"],
      "required": true
    },
    "approver": {
      "type": "link",
      "entity": "User"
    },
    "approvalDate": {
      "type": "datetime",
      "required": true
    },
    "decision": {
      "type": "enum",
      "options": ["Approved", "Conditional Approval", "Rejected", "Referred to Higher Authority"],
      "required": true,
      "style": {
        "Approved": "success",
        "Conditional Approval": "warning",
        "Rejected": "danger"
      }
    },
    "approvedAmount": {
      "type": "currency"
    },
    "approvedTenure": {
      "type": "int"
    },
    "approvedInterestRate": {
      "type": "float"
    },
    "conditions": {
      "type": "text",
      "rows": 4,
      "tooltip": "Conditions attached to approval"
    },
    "deviationsApproved": {
      "type": "text",
      "rows": 3
    },
    "remarks": {
      "type": "text",
      "rows": 3
    },
    "opportunity": {
      "type": "link",
      "entity": "FinancialOpportunity",
      "required": true
    }
  }
}
```

---

## 4. Business Logic & Services

### 4.1 EMI Calculation Service

```php
<?php
namespace Espo\Custom\Services;

class LoanCalculationService extends \Espo\Core\Services\Base
{
    public function calculateEMI($loanAmount, $interestRate, $tenure)
    {
        // EMI = P * r * (1+r)^n / ((1+r)^n - 1)
        $principal = $loanAmount;
        $monthlyRate = $interestRate / 12 / 100;
        $months = $tenure;
        
        if ($monthlyRate == 0) {
            return $principal / $months;
        }
        
        $emi = $principal * $monthlyRate * pow(1 + $monthlyRate, $months) / 
               (pow(1 + $monthlyRate, $months) - 1);
        
        return round($emi, 2);
    }
    
    public function calculateTotalInterest($emi, $tenure, $principal)
    {
        $totalRepayment = $emi * $tenure;
        $totalInterest = $totalRepayment - $principal;
        
        return round($totalInterest, 2);
    }
    
    public function calculateLTV($loanAmount, $collateralValue)
    {
        if ($collateralValue == 0) {
            return 0;
        }
        
        return round(($loanAmount / $collateralValue) * 100, 2);
    }
    
    public function calculateFOIR($monthlyIncome, $existingObligations, $proposedEMI)
    {
        if ($monthlyIncome == 0) {
            return 0;
        }
        
        $totalObligations = $existingObligations + $proposedEMI;
        return round(($totalObligations / $monthlyIncome) * 100, 2);
    }
}
```

### 4.2 Credit Assessment Service

```php
<?php
namespace Espo\Custom\Services;

class CreditAssessmentService extends \Espo\Core\Services\Base
{
    public function performCreditAssessment($opportunityId)
    {
        $opportunity = $this->getEntityManager()->getEntity('FinancialOpportunity', $opportunityId);
        
        if (!$opportunity) {
            throw new \Exception('Opportunity not found');
        }
        
        // Retrieve credit report from bureau
        $creditScore = $this->getCreditScore($opportunity);
        
        // Calculate FOIR
        $monthlyIncome = $opportunity->get('monthlyIncome');
        $existingObligations = $opportunity->get('existingMonthlyObligations');
        $proposedEMI = $opportunity->get('emi');
        
        $foir = $this->calculateFOIR($monthlyIncome, $existingObligations, $proposedEMI);
        
        // Determine risk category
        $riskCategory = $this->determineRiskCategory($creditScore, $foir, $opportunity);
        
        // Update opportunity
        $opportunity->set('creditScore', $creditScore);
        $opportunity->set('foir', $foir);
        $opportunity->set('riskCategory', $riskCategory);
        $opportunity->set('internalCreditRating', $this->calculateCreditRating($creditScore, $foir));
        
        $this->getEntityManager()->saveEntity($opportunity);
        
        return [
            'creditScore' => $creditScore,
            'foir' => $foir,
            'riskCategory' => $riskCategory
        ];
    }
    
    private function determineRiskCategory($creditScore, $foir, $opportunity)
    {
        if ($creditScore >= 750 && $foir <= 40) {
            return 'Low Risk';
        } elseif ($creditScore >= 700 && $foir <= 50) {
            return 'Medium Risk';
        } elseif ($creditScore >= 650 && $foir <= 60) {
            return 'High Risk';
        } else {
            return 'Very High Risk';
        }
    }
    
    private function calculateCreditRating($creditScore, $foir)
    {
        if ($creditScore >= 800 && $foir <= 35) return 'AAA';
        if ($creditScore >= 750 && $foir <= 40) return 'AA';
        if ($creditScore >= 700 && $foir <= 45) return 'A';
        if ($creditScore >= 650 && $foir <= 50) return 'BBB';
        if ($creditScore >= 600 && $foir <= 55) return 'BB';
        if ($creditScore >= 550 && $foir <= 60) return 'B';
        return 'CCC';
    }
}
```

### 4.3 Loan Approval Workflow Service

```php
<?php
namespace Espo\Custom\Services;

class LoanApprovalService extends \Espo\Core\Services\Base
{
    public function submitForApproval($opportunityId)
    {
        $opportunity = $this->getEntityManager()->getEntity('FinancialOpportunity', $opportunityId);
        
        // Validate readiness
        if (!$this->isReadyForApproval($opportunity)) {
            throw new \Exception('Opportunity not ready for approval');
        }
        
        // Determine approval authority based on loan amount and risk
        $approvalAuthority = $this->determineApprovalAuthority($opportunity);
        
        // Create approval task
        $this->createApprovalTask($opportunity, $approvalAuthority);
        
        // Update stage
        $opportunity->set('loanStage', 'Credit Approval');
        $opportunity->set('creditApprovalStatus', 'In Review');
        $this->getEntityManager()->saveEntity($opportunity);
        
        // Send notification
        $this->notifyApprover($opportunity, $approvalAuthority);
        
        return $approvalAuthority;
    }
    
    private function determineApprovalAuthority($opportunity)
    {
        $amount = $opportunity->get('loanAmount');
        $riskCategory = $opportunity->get('riskCategory');
        
        // Approval matrix
        if ($amount <= 500000 && $riskCategory == 'Low Risk') {
            return 'Branch Manager';
        } elseif ($amount <= 1000000 && in_array($riskCategory, ['Low Risk', 'Medium Risk'])) {
            return 'Regional Manager';
        } elseif ($amount <= 5000000) {
            return 'Credit Manager';
        } elseif ($amount <= 10000000) {
            return 'Credit Committee';
        } else {
            return 'CEO';
        }
    }
    
    private function isReadyForApproval($opportunity)
    {
        // Check if all prerequisites are met
        $checks = [
            $opportunity->get('kycStatus') == 'Completed',
            $opportunity->get('documentChecklistComplete') == true,
            $opportunity->get('creditScore') !== null,
            $opportunity->get('foir') !== null
        ];
        
        return !in_array(false, $checks);
    }
}
```

### 4.4 Disbursement Service

```php
<?php
namespace Espo\Custom\Services;

class DisbursementService extends \Espo\Core\Services\Base
{
    public function processDisbursement($opportunityId)
    {
        $opportunity = $this->getEntityManager()->getEntity('FinancialOpportunity', $opportunityId);
        
        if (!$opportunity) {
            throw new \Exception('Opportunity not found');
        }
        
        // Validate disbursement readiness
        if ($opportunity->get('creditApprovalStatus') != 'Approved') {
            throw new \Exception('Loan not approved for disbursement');
        }
        
        // Check conditions fulfilled
        if ($opportunity->get('conditions') && !$this->areConditionsFulfilled($opportunity)) {
            throw new \Exception('Disbursement conditions not fulfilled');
        }
        
        // Calculate net disbursement
        $loanAmount = $opportunity->get('approvedLoanAmount') ?: $opportunity->get('loanAmount');
        $charges = $this->calculateTotalCharges($opportunity);
        $netDisbursement = $loanAmount - ($opportunity->get('chargesDeductedFromLoan') ? $charges : 0);
        
        // Generate loan account number
        $loanAccountNumber = $this->generateLoanAccountNumber();
        
        // Update opportunity
        $opportunity->set('actualDisbursementDate', date('Y-m-d'));
        $opportunity->set('loanAccountNumber', $loanAccountNumber);
        $opportunity->set('loanStage', 'Disbursed');
        $opportunity->set('stage', 'Closed Won');
        $opportunity->set('probability', 100);
        $this->getEntityManager()->saveEntity($opportunity);
        
        // Create loan account in loan servicing system
        $this->createLoanAccount($opportunity, $loanAccountNumber, $netDisbursement);
        
        // Execute disbursement instruction
        $this->executeDisbursementInstruction($opportunity, $netDisbursement);
        
        // Send notifications
        $this->notifyDisbursement($opportunity);
        
        return [
            'loanAccountNumber' => $loanAccountNumber,
            'netDisbursement' => $netDisbursement,
            'disbursementDate' => date('Y-m-d')
        ];
    }
    
    private function generateLoanAccountNumber()
    {
        // Generate unique loan account number
        $prefix = 'LA';
        $year = date('Y');
        $sequence = $this->getNextSequence();
        
        return $prefix . $year . str_pad($sequence, 6, '0', STR_PAD_LEFT);
    }
    
    private function calculateTotalCharges($opportunity)
    {
        return $opportunity->get('applicationFee') +
               $opportunity->get('processingFee') +
               $opportunity->get('valuationFee') +
               $opportunity->get('legalFee') +
               $opportunity->get('stampDuty') +
               $opportunity->get('insurancePremium');
    }
}
```

---

## 5. Workflows & Automation

### 5.1 Stage Progression Workflow

**Trigger:** When `loanStage` changes

**Actions:**
1. Update `stageEnteredDate` to current datetime
2. Update `probability` based on stage mapping
3. Send notification to assigned user
4. Create stage-specific tasks
5. Log stage change in activity stream

**Stage-to-Probability Mapping:**
- Inquiry: 5%
- Application Submitted: 15%
- Documentation: 30%
- Credit Assessment: 50%
- Underwriting: 65%
- Credit Approval: 80%
- Documentation & Legals: 90%
- Disbursement Ready: 97%
- Disbursed: 100%
- Rejected/Withdrawn: 0%

### 5.2 Document Completeness Checker

**Trigger:** When OpportunityDocument is created/updated

**Actions:**
1. Get list of mandatory documents for loan product
2. Check status of each mandatory document
3. If all mandatory documents are "Verified", set `documentChecklistComplete` = true
4. Update `pendingDocuments` field with list of missing items
5. If complete, trigger next stage progression

### 5.3 Auto-Approval for Low-Risk Loans

**Trigger:** When `loanStage` = "Credit Approval" AND `loanAmount` <= 200000 AND `riskCategory` = "Low Risk" AND `creditScore` >= 750

**Actions:**
1. Set `creditApprovalStatus` = "Approved"
2. Set `approvalAuthority` = "Auto-Approved"
3. Set `approvedBy` = System User
4. Set `approvalDate` = Today
5. Progress stage to "Documentation & Legals"
6. Send approval notification to customer and loan officer

### 5.4 SLA Monitoring

**Trigger:** Daily scheduled job

**Conditions & Actions:**
- If in "Documentation" stage > 7 days → Alert loan officer
- If in "Credit Assessment" stage > 3 days → Alert credit team
- If in "Credit Approval" stage > 5 days → Alert approval authority
- If in "Disbursement Ready" stage > 2 days → Alert operations team

---

## 6. Reporting & Dashboards

### 6.1 Loan Pipeline Dashboard

**Metrics:**
- Total pipeline value (sum of all active opportunities)
- Weighted pipeline value (sum of amount * probability)
- Number of applications by stage
- Average days in pipeline
- Conversion rate (disbursed / total applications)
- Approval rate
- Average loan amount
- Average processing time by stage

**Charts:**
- Funnel chart: Applications by stage
- Bar chart: Monthly disbursements trend
- Pie chart: Loan distribution by product type
- Line chart: Pipeline value trend
- Heat map: Loan officer performance matrix

### 6.2 Credit Quality Report

**Metrics:**
- Average credit score of approved loans
- Distribution by risk category
- Average FOIR
- Average LTV ratio
- Default rate (requires integration with loan servicing)

### 6.3 Operational Efficiency Report

**Metrics:**
- Average processing time (application to disbursement)
- Stage-wise TAT (Turnaround Time)
- Document verification time
- Credit approval time
- Bottleneck identification

---

## 7. Integration Points

### 7.1 Credit Bureau Integration (CRIB)

**Purpose:** Auto-fetch credit reports

**Implementation:**
- API endpoint: `/api/v1/FinancialOpportunity/{id}/fetchCreditReport`
- Service: `CreditBureauService`
- Stores credit score, report PDF, active loans data

### 7.2 Loan Origination System (LOS)

**Purpose:** Sync with core banking/LOS

**Integration:**
- Push approved loan details to LOS
- Receive loan account number
- Sync disbursement status

### 7.3 Payment Gateway

**Purpose:** Collect application/processing fees online

**Implementation:**
- Integrate payment gateway (PayHere, IPG for Sri Lanka)
- Record payment against opportunity
- Auto-progress stage after payment

### 7.4 Valuation Vendors

**Purpose:** Order property/vehicle valuations

**Implementation:**
- API integration with valuation companies
- Order valuation via workflow
- Receive valuation report automatically

---

## 8. UI Enhancements

### 8.1 Loan Calculator Widget

Embed on opportunity detail view:
- Input: Loan amount, tenure, interest rate
- Output: EMI, total interest, total repayment
- Visual: Amortization schedule preview

### 8.2 Document Checklist Panel

Side panel showing:
- ☑ Verified documents (green)
- ⏳ Pending documents (orange)
- ✗ Rejected documents (red)
- Upload button for each document type

### 8.3 Approval Timeline

Visual timeline showing:
- Application date
- Each stage with entry/exit dates
- Approval checkpoints
- Estimated disbursement date
- Actual disbursement date

### 8.4 Risk Dashboard Widget

Display on detail view:
- Credit score gauge
- FOIR meter
- LTV ratio
- Risk category badge
- Credit rating

---

## 9. Mobile Enhancements

### 9.1 Loan Officer Mobile App Requirements

- View pipeline (Kanban by stage)
- Quick loan calculator
- Document capture from camera
- E-signature collection
- Customer visit check-in (GPS)
- Offline mode with sync
- Push notifications for approvals

---

## 10. Implementation Roadmap

**Phase 1 (Weeks 1-2):** Core entity enhancement
- Add all custom fields to FinancialOpportunity
- Create supporting entities
- Implement formula calculations

**Phase 2 (Weeks 3-4):** Business logic services
- EMI calculation
- Credit assessment
- Approval routing
- Disbursement processing

**Phase 3 (Weeks 5-6):** Workflows & automation
- Stage progression
- Document tracking
- SLA monitoring
- Auto-approval rules

**Phase 4 (Weeks 7-8):** UI/UX enhancements
- Custom layouts
- Dashboards
- Loan calculator widget
- Document checklist

**Phase 5 (Weeks 9-10):** Integrations
- Credit bureau API
- Payment gateway
- LOS sync

**Phase 6 (Weeks 11-12):** Testing & deployment
- UAT
- Training
- Production deployment

---

## Conclusion

This enhancement transforms EspoCRM's Opportunity module into a comprehensive **Loan Application Pipeline Management** system tailored for Sri Lankan financial services companies. The enhancement maintains compatibility with EspoCRM's core while adding financial-specific capabilities for credit assessment, approval workflows, disbursement tracking, and regulatory compliance.
