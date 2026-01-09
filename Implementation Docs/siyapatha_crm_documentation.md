# Siyapatha Finance CRM - Entity Documentation
## EspoCRM Implementation Analysis & Enhancement Guide

**Version:** 1.0  
**Date:** January 2026  
**Platform:** EspoCRM 9.2.5 on Google Cloud Platform  
**Instance:** Development Environment

---

## Executive Summary

This document provides a complete technical analysis of the Lead, Case, and Opportunity entities in Siyapatha Finance's EspoCRM implementation. The analysis is based on direct API exploration of the production instance and identifies both current capabilities and enhancement opportunities.

**Key Findings:**
- **54 custom fields** implemented on Lead entity for financial services workflows
- **16 active workflows** automating lead scoring, assignment, and case management
- **7 BPM flowcharts** created (1 active for Case lifecycle)
- Strong foundation for microfinance operations with room for optimization

---

## 1. LEAD ENTITY

### 1.1 Current Implementation Overview

| Metric | Value |
|--------|-------|
| Total Leads | 52 |
| Custom Fields | 54 |
| Active Workflows | 8 |
| Status Options | 7 |

**Status Distribution:**
- New: 22 (42%)
- Converted: 9 (17%)
- Contacted: 8 (15%)
- Disqualified: 6 (12%)
- Qualified: 4 (8%)
- Validated: 3 (6%)

### 1.2 Lead Status Flow

```
NEW → CONTACTED → VALIDATED → QUALIFIED → CONVERTED
                    ↓                          ↓
               DEFERRED ←←←←←←←←←←←←←←←←← DISQUALIFIED
```

**Status Definitions:**
| Status | Description | Color |
|--------|-------------|-------|
| New | Fresh lead, uncontacted | Info (Blue) |
| Contacted | First contact made via call/meeting | Primary (Dark Blue) |
| Validated | Financial information verified | Warning (Yellow) |
| Qualified | Meets lending criteria | Success (Green) |
| Deferred | On hold for future follow-up | Default |
| Converted | Successfully converted to Opportunity | Success (Green) |
| Disqualified | Does not meet criteria | Danger (Red) |

### 1.3 Custom Field Categories

#### A. Lead Identification (3 fields)
| Field | Type | Purpose |
|-------|------|---------|
| cLeadNumber | varchar (20) | Auto-generated unique ID (e.g., L0401202600064) |
| cLeadID | autoincrement | Internal sequential ID |
| cLeadGeneratedBy | varchar (50) | Source agent/channel name |

#### B. Personal Information (7 fields)
| Field | Type | Validation | Notes |
|-------|------|------------|-------|
| cDateOfBirth | date | - | Required for age verification |
| cGender | enum | Male/Female/Other | Default: Male |
| cMaritalStatus | enum | Single/Married/Divorced/Widowed | Default: Single |
| cNICNo | varchar (12) | Pattern: `^([0-9]{9}[vVxX]|[0-9]{12})$` | Sri Lankan NIC validation, audited, PDPA-sensitive |
| cPassport | varchar (10) | Pattern: `^[A-Z][0-9]{7,8}$` | International ID |
| cDrivingLicense | varchar (10) | Pattern: `^([A-Z][0-9]{7}|[0-9]{8})$` | Secondary ID |
| cProofType | enum | Owned/Rented/Family | Residence ownership |

#### C. Employment & Financial (8 fields)
| Field | Type | Purpose |
|-------|------|---------|
| cEmployeeType | enum | Salaried/Self-Employed/Business/Retired |
| cBusinessName | varchar (100) | For self-employed/business owners |
| cMonthlyIncome | currency | Primary income (audited) |
| cTurnover | currency | For business customers |
| cExistingMonthlyObligations | currency | Current EMIs + fixed expenses |
| cBankName | varchar (100) | Salary account bank |
| cAccountNo | int | Bank account number |
| cBranch | varchar (100) | Bank branch name |

#### D. Loan Interest (6 fields)
| Field | Type | Options |
|-------|------|---------|
| cInterestedLoan | enum | Personal Loan/Vehicle Loan/Gold Loan/Business Loan/Lease |
| cInterestedProductType | enum | Same as above |
| cProductSubType | enum | 13 sub-products including Salary-based Personal Loan, Vehicle Refinancing, SME Loan, etc. |
| cDesiredLoanAmount | currency | Requested amount (min: 0) |
| cPreferredTenure | enum | 1-48 months in 3-month increments |
| cPurposeOfLoan | varchar (100) | Loan purpose description |

#### E. Financial Assessment (12 fields)
| Field | Type | Calculation | Range |
|-------|------|-------------|-------|
| cLeadScore | int | Automated via workflow | 0-100, read-only |
| cConversionScore | int | Conversion probability | 0-100, read-only |
| cCreditScore | int | CRIB/bureau score | 250-900, audited |
| cDTI | float | Debt-to-Income ratio | 0-100%, read-only |
| cFOIR | float | Fixed Obligation to Income Ratio | 0-100%, read-only |
| cProposedEMI | currency | Calculated EMI | Read-only |
| cCalculatedEMI | currency | Final EMI calculation | Read-only |
| cMaxEligibleAmount | currency | Maximum loan eligibility | Read-only |
| cFinancialQualification | enum | Pass/Fail/Pending | Read-only, based on DTI/FOIR |
| cCRIBStatus | enum | Clean/Defaults/Pending/Not Checked | Audited |
| cCRIBLastChecked | datetime | Last bureau check timestamp | Audited |
| cExistingLoans | text | Details of current loans | Multi-line |

#### F. Customer Classification (3 fields)
| Field | Type | Options |
|-------|------|---------|
| cCustomerType | enum (required, audited) | Individual/Corporate/Sole Proprietor/Partnership |
| cCustomerSegment | enum (required, audited) | New Customer/Existing Customer/Returning Customer/Dormant Customer Reactivation |
| cDisqualifiedReason | enum (audited) | 12 options including Insufficient Income, Failed Credit Check, Policy Violation |

#### G. Lead Source & Campaign (4 fields)
| Field | Type | Purpose |
|-------|------|---------|
| cLeadSource | enum | Social Media/Marketing Campaign/Television/News Paper |
| cLeadSourceReadOnly | enum | Preserved original source (read-only) |
| cCampaignReadOnly | varchar (50) | Campaign name snapshot |
| cSpecialNotes | text | Agent notes |

#### H. SLA Tracking (3 fields)
| Field | Type | Style |
|-------|------|-------|
| cSLATime | varchar (20) | Time remaining display |
| cSLAStatus | enum | Ok (green)/Warning (yellow)/Breached (red) |
| cScoreLastUpdated | datetime | Last score calculation |

#### I. Computed Flags (3 fields)
| Field | Type | Purpose |
|-------|------|---------|
| cIsConvertedInt | int (0-1) | Conversion flag for reporting |
| cIsQualified | int (0-1) | Qualification flag |
| cIsPermanentAddressSameAsCurrent | bool | Address match flag |

### 1.4 Active Workflows

| Workflow Name | Trigger | Purpose |
|---------------|---------|---------|
| Lead: Read Only Side Panel Fields | afterRecordSaved | Sync editable → read-only fields |
| Lead: Existing Customer | afterRecordSaved | Special handling for existing customers |
| Assign the lead | afterRecordSaved | Round-robin assignment when unassigned + New status |
| EMI Calculator | afterRecordSaved | Calculate EMI when tenure & amount provided |
| Validated To Qualified | afterRecordSaved | Auto-status transition |
| Lead Scoring | afterRecordSaved | Calculate lead score, triggers Ratios Assessment |
| Ratios Assessment | afterRecordSaved | Calculate DTI/FOIR when income/obligations exist |
| New to Contacted | afterRecordSaved (Call) | Update lead status when call held |
| Contacted to Validate | afterRecordSaved (Call) | Progress lead status after successful call |

### 1.5 Entity Relationships

```
Lead
├── assignedUser → User
├── teams → Team (many)
├── campaign → Campaign
├── calls → Call (many)
├── meetings → Meeting (many)
├── tasks → Task (many, parent)
├── emails → Email (many, parent)
├── cases → Case (many)
├── documents → Document (many)
├── cContact → Contact (custom link)
├── createdAccount → Account (on conversion)
├── createdContact → Contact (on conversion)
└── createdOpportunity → Opportunity (on conversion)
```

### 1.6 Improvement Recommendations

#### Critical Priority

1. **Branch Assignment Integration**
   - Current: Leads assigned to "Call Center Operations" team only
   - Enhancement: Multi-tier routing based on source → product → geography
   - Implementation: Add cBranch link field similar to Case entity

2. **SLA Implementation**
   - Current: cSLATime/cSLAStatus fields exist but not actively calculated
   - Enhancement: Implement scheduled workflow for time-based SLA tracking
   - Targets: Hot leads (3 days), Warm (8 days), Cold (17 days)

3. **Lead Temperature Field**
   - Missing: leadTemperature enum (Hot/Warm/Cold)
   - Impact: Required for SLA tier assignment and prioritization

#### High Priority

4. **Duplicate Detection**
   - Current: No duplicate checking
   - Enhancement: Pre-save workflow to check NIC + Phone combination
   - Action: Merge or flag duplicates

5. **Document Checklist**
   - Current: Documents linked but no structured checklist
   - Enhancement: Add cDocumentStatus enum and checklist tracking

6. **Age Calculation**
   - Current: DOB captured but age not calculated
   - Enhancement: Formula field for age + age-based eligibility flag

#### Medium Priority

7. **Lead Aging Report**
   - Add cDaysInPipeline calculated field
   - Enable aging analysis and stale lead identification

8. **Source Attribution Enhancement**
   - Add UTM parameter tracking fields
   - Enable campaign ROI analysis

9. **Mobile Number Validation**
   - Add pattern validation for Sri Lankan mobile format
   - Pattern: `^\+94[0-9]{9}$`

---

## 2. CASE ENTITY

### 2.1 Current Implementation Overview

| Metric | Value |
|--------|-------|
| Total Cases | 20 |
| Custom Fields | 15 |
| Active Workflows | 6 |
| BPM Flowcharts | 1 (active) |

**Status Distribution:**
- Escalated: 13 (65%)
- New: 3 (15%)
- Resolve: 2 (10%)
- Open: 1 (5%)
- Pending: 1 (5%)

### 2.2 Case Status Flow

```
OPEN → IN PROGRESS → RESOLVED → CLOSE
           ↓
       ESCALATED
```

**Status Definitions:**
| Status | Description | Color |
|--------|-------------|-------|
| Open | New case, pending assignment | Primary (Blue) |
| In Progress | Agent actively working | Info (Light Blue) |
| Resolved | Solution provided | Success (Green) |
| Close | Case formally closed | Default |
| Escalated | Exceeded SLA, needs attention | Danger (Red) |

### 2.3 Standard Fields

| Field | Type | Options/Notes |
|-------|------|---------------|
| name | varchar | Case subject (required) |
| number | autoincrement | System case number |
| status | enum (required, audited) | Open/In Progress/Resolved/Close/Escalated |
| priority | enum (audited) | Low/Normal/High/Urgent |
| type | enum (required) | Service Request/Complaint/Inquiry |
| description | text | Case details |
| account | link | Related account |
| lead | link | Related lead |
| contact | link | Primary contact |
| contacts | linkMultiple | All related contacts |
| inboundEmail | link | Source email |

### 2.4 Custom Fields

| Field | Type | Purpose |
|-------|------|---------|
| cCaseNumber | varchar (20) | Custom case ID (e.g., C0401202600018) |
| cSearchBy | enum | NIC/Mobile No/Email - customer lookup |
| cSearchInput | varchar (50) | Search value input |
| cSearchResults | text | Search results display (read-only) |
| cProductAffected | enum (required) | Loan/Savings/Leasing |
| cBranch | link | Related branch (CBranch entity) |
| cTargetDate | datetime | SLA deadline (read-only) |
| cRemainingTime | varchar (20) | Time to deadline (read-only) |
| cSLAStatus | enum (read-only) | Ok/Warning/Breached |
| cIsOpen | int (0-1) | Open flag for reporting |
| cIsClosed | int (0-1) | Closed flag for reporting |
| cIsBreached | int (0-1) | SLA breach flag |

### 2.5 Active Workflows

| Workflow Name | Type | Action |
|---------------|------|--------|
| Case: Priority base time | afterRecordSaved | Calculate SLA deadline based on priority |
| Case: Assign Agent | afterRecordSaved | Round-robin assignment when unassigned |
| Case: Open to In Progress | afterRecordSaved | Status transition when agent assigned |
| Case: Remaining Time Calculation | scheduled | Update remaining time display |
| Case: In Progress to Escalated | scheduled | Auto-escalate overdue cases |
| Case: Resolve to Close | scheduled | Auto-close resolved cases after period |

### 2.6 SLA Configuration (Priority-Based)

| Priority | Response Time | Resolution Time |
|----------|---------------|-----------------|
| Urgent | 2 hours | 2 hours |
| High | 4 hours | 8 hours |
| Normal | 8 hours | 24 hours |
| Low | 24 hours | 72 hours |

### 2.7 Entity Relationships

```
Case
├── account → Account
├── lead → Lead
├── contact → Contact (primary)
├── contacts → Contact (many)
├── cBranch → CBranch (custom)
├── assignedUser → User
├── teams → Team (many)
├── meetings → Meeting (many, parent)
├── calls → Call (many, parent)
├── tasks → Task (many, parent)
├── emails → Email (many, parent)
├── articles → KnowledgeBaseArticle (many)
└── inboundEmail → InboundEmail
```

### 2.8 Improvement Recommendations

#### Critical Priority

1. **Complaint Type Categorization**
   - Current: Only 3 types (Service Request/Complaint/Inquiry)
   - Enhancement: Add cComplaintType enum with detailed categories
   - Categories: Product Issue, Charges & Fees, Processing Delay, Staff Conduct, Interest Dispute, Transaction Error

2. **Escalation Level Tracking**
   - Missing: Escalation hierarchy tracking
   - Enhancement: Add cEscalationLevel (Level 1/Level 2/Manager/Compliance)
   - Track escalation history and reasons

3. **Resolution Details**
   - Current: No structured resolution tracking
   - Enhancement: Add cResolutionType, cResolutionDetails, cResolutionDate fields

#### High Priority

4. **Customer Satisfaction Tracking**
   - Add cCustomerSatisfaction rating (1-5)
   - Trigger survey on case closure

5. **Financial Impact Assessment**
   - Add cFinancialImpact currency field
   - Add cCompensationAmount for refunds/waivers

6. **Root Cause Analysis**
   - Add cRootCause enum field
   - Enable complaint pattern analysis

#### Medium Priority

7. **SLA Warning Notifications**
   - Current: Status updates but no proactive alerts
   - Enhancement: Email/SMS notifications at warning threshold

8. **Case Merge Capability**
   - Handle duplicate case submissions
   - Link related cases

9. **Regulatory Compliance Fields**
   - Add cReportableToCBSL boolean
   - Add cRegulatoryReference for external tracking

---

## 3. OPPORTUNITY ENTITY

### 3.1 Current Implementation Overview

| Metric | Value |
|--------|-------|
| Total Opportunities | 15 |
| Custom Fields | 1 |
| Active Workflows | 0 |
| Conversion from Lead | Active |

**Stage Distribution:**
- Prospecting: 10 (67%)
- Qualification: 2 (13%)
- Negotiation: 1 (7%)
- Proposal: 1 (7%)
- Closed Won: 1 (6%)

### 3.2 Opportunity Stage Flow

```
PROSPECTING → QUALIFICATION → PROPOSAL → NEGOTIATION → CLOSED WON
                                              ↓
                                        CLOSED LOST
```

**Stage Configuration:**
| Stage | Probability | Color |
|-------|------------|-------|
| Prospecting | 10% | Default |
| Qualification | 20% | Default |
| Proposal | 50% | Primary (Blue) |
| Negotiation | 80% | Warning (Yellow) |
| Closed Won | 100% | Success (Green) |
| Closed Lost | 0% | Info (Grey) |

### 3.3 Standard Fields

| Field | Type | Notes |
|-------|------|-------|
| name | varchar (required) | Opportunity name |
| amount | currency (required) | Loan amount |
| amountConverted | currencyConverted | System base currency |
| amountWeightedConverted | float | Amount × Probability |
| stage | enum (audited) | Pipeline stage |
| lastStage | enum | Previous stage |
| probability | int (0-100) | Win probability |
| leadSource | enum | References Lead.source |
| closeDate | date (required, audited) | Expected close |
| description | text | Details |
| campaign | link | Source campaign |
| originalLead | linkOne | Source lead (read-only) |
| account | link | Customer account |
| contact | link | Primary contact |
| contacts | linkMultiple | All contacts |

### 3.4 Custom Fields

| Field | Type | Purpose |
|-------|------|---------|
| cOpportunityNumber | varchar (20) | Custom ID (e.g., OP0401202600013) |

### 3.5 Entity Relationships

```
Opportunity
├── account → Account
├── contact → Contact (primary)
├── contacts → Contact (many, with role)
├── originalLead → Lead
├── campaign → Campaign
├── assignedUser → User
├── teams → Team (many)
├── meetings → Meeting (many, parent)
├── calls → Call (many, parent)
├── tasks → Task (many, parent)
├── emails → Email (many, parent)
└── documents → Document (many)
```

### 3.6 Improvement Recommendations

#### Critical Priority (Financial Services Specific)

1. **Loan Product Fields**
   - Add cLoanProduct enum (Personal/Vehicle/Gold/Business/Lease)
   - Add cLoanSubProduct enum (matching Lead.cProductSubType)
   - Add cLoanTenure int (approved tenure in months)

2. **Financial Details**
   - Add cApprovedAmount currency
   - Add cInterestRate float
   - Add cProcessingFee currency
   - Add cMonthlyEMI currency

3. **Approval Workflow**
   - Add cApprovalStatus enum (Pending/Approved/Rejected/On Hold)
   - Add cApprovalDate datetime
   - Add cApprovedBy link to User
   - Add cApprovalRemarks text

#### High Priority

4. **Stage-Specific Fields**
   - Add cDocumentsComplete boolean
   - Add cCRIBVerified boolean
   - Add cSanctionLetterIssued boolean

5. **Disbursement Tracking**
   - Add cDisbursementStatus enum (Pending/Partial/Complete)
   - Add cDisbursementAmount currency
   - Add cDisbursementDate date

6. **Co-Applicant/Guarantor**
   - Create link to Contact with role specification
   - Add cGuarantorRequired boolean

#### Medium Priority

7. **Competitor Tracking**
   - Add cCompetitorInvolved boolean
   - Add cCompetitorName varchar
   - Add cCompetitiveRate float

8. **Lost Opportunity Analysis**
   - Add cLostReason enum when Closed Lost
   - Enable loss pattern analysis

9. **Integration Fields**
   - Add cCoreSystemReference for LMS integration
   - Add cDisbursementReference

---

## 4. SUPPORTING ENTITIES

### 4.1 CBranch Entity

**Purpose:** Branch master data for geographic assignment

| Field | Type | Purpose |
|-------|------|---------|
| name | varchar (required) | Branch name |
| branchCode | varchar (20, required) | Unique code |
| branchAddress | address | Full address |
| telephoneNumber | varchar (20) | Contact |
| emailAddress | varchar (100) | Branch email |
| bankingHours | varchar (100) | Operating hours (default: 9AM-4PM) |
| user | link | Branch manager |
| branchAddressMap | map | Google Maps integration |

**Current Records:** Not visible in API count (access issue)

### 4.2 CGoldLoan Entity

**Purpose:** Gold loan specific data (planned implementation)

Status: Entity exists but minimal customization visible

### 4.3 Team Structure

**Total Teams:** 13

| Category | Teams |
|----------|-------|
| Call Center | Call Center Operations |
| Head Office | Colombo Head Office, Corporate Management, Marketing-Head Office |
| Regional Branches | Akkaraipattu, Galle, Jaffna, Kandy, Mahiyangana |
| Specialized | Gold Loan Department, Marketing team |

---

## 5. WORKFLOW AUTOMATION SUMMARY

### 5.1 Lead Workflows (8 active)

| Type | Count | Purpose |
|------|-------|---------|
| Field Sync | 2 | Maintain read-only copies |
| Assignment | 1 | Round-robin to team |
| Status Progression | 3 | Automated transitions |
| Calculations | 2 | EMI, DTI/FOIR, Lead Score |

### 5.2 Case Workflows (6 active)

| Type | Count | Purpose |
|------|-------|---------|
| Assignment | 1 | Auto-assign agents |
| Status Progression | 2 | Open→In Progress, Resolved→Close |
| SLA Management | 2 | Time calculation, escalation |
| Calculation | 1 | Priority-based target dates |

### 5.3 BPM Flowcharts

| Name | Entity | Status |
|------|--------|--------|
| Case Lifecycle Management | Case | Active |
| Lead Status Progression (Activity-Based) | Lead | Inactive |
| Lead Auto-Assignment & Welcome SMS | Lead | Inactive |
| Example: User task | Lead | Inactive |

---

## 6. IMPLEMENTATION PRIORITY MATRIX

### Phase 1: Quick Wins (1-2 weeks)

| Entity | Enhancement | Effort |
|--------|-------------|--------|
| Lead | Add cLeadTemperature field | Low |
| Lead | Implement SLA scheduled workflow | Medium |
| Lead | Add cBranch link field | Low |
| Opportunity | Add cLoanProduct fields | Low |
| Case | Add cComplaintType field | Low |

### Phase 2: Core Enhancements (2-4 weeks)

| Entity | Enhancement | Effort |
|--------|-------------|--------|
| Lead | Duplicate detection workflow | Medium |
| Lead | Age calculation formula | Low |
| Opportunity | Approval workflow implementation | High |
| Opportunity | Disbursement tracking fields | Medium |
| Case | Resolution tracking fields | Medium |
| Case | Customer satisfaction survey | Medium |

### Phase 3: Advanced Features (4-8 weeks)

| Entity | Enhancement | Effort |
|--------|-------------|--------|
| All | CRIB API integration | High |
| All | SMS gateway integration | Medium |
| Lead | Document checklist entity | High |
| Opportunity | Core banking integration | High |
| Case | Regulatory reporting automation | High |

---

## 7. DATA QUALITY OBSERVATIONS

### 7.1 Lead Data Quality

**Positive:**
- NIC numbers properly validated with regex
- Financial fields have appropriate min/max constraints
- Audit trail enabled on sensitive fields

**Concerns:**
- 42% leads still in "New" status (potential stale leads)
- Some leads missing income data (cMonthlyIncome = null)
- Phone number validation not enforced

### 7.2 Case Data Quality

**Positive:**
- SLA tracking implemented
- Branch linkage available

**Concerns:**
- 65% cases in "Escalated" status (indicates SLA issues or workflow problems)
- "Resolve" vs "Resolved" status inconsistency (should standardize)

### 7.3 Opportunity Data Quality

**Positive:**
- Lead-to-Opportunity linkage working
- Amount tracking functional

**Concerns:**
- 67% stuck in Prospecting (pipeline velocity issue)
- Missing loan-specific fields limits analysis

---

## 8. TECHNICAL SPECIFICATIONS

### 8.1 API Endpoints

```
Base URL: https://espocrm-dev-220642639797.us-central1.run.app/api/v1/
Authentication: HTTP Basic Auth

Key Endpoints:
- GET /Lead - List leads
- GET /Lead/{id} - Get lead details
- POST /Lead - Create lead
- PUT /Lead/{id} - Update lead
- GET /Metadata - Entity definitions
- GET /Workflow - List workflows
- GET /BpmnFlowchart - List BPM processes
```

### 8.2 Field Naming Convention

All custom fields follow the pattern: `c{FieldName}`

Examples:
- `cLeadNumber` - Lead identifier
- `cSLAStatus` - SLA status
- `cMonthlyIncome` - Monthly income

### 8.3 Number Formatting

Auto-generated numbers follow pattern: `{Prefix}{DDMMYYYY}{Sequence}`

- Lead: L0401202600064 (L + date + 5-digit sequence)
- Case: C0401202600018 (C + date + 5-digit sequence)
- Opportunity: OP0401202600013 (OP + date + 5-digit sequence)

---

## APPENDIX A: FORMULA REFERENCES

### Lead Scoring Formula (Conceptual)

```
leadScore = 
  (hasValidNIC ? 10 : 0) +
  (hasPhoneNumber ? 10 : 0) +
  (hasEmailAddress ? 5 : 0) +
  (monthlyIncome > 50000 ? 20 : monthlyIncome > 30000 ? 15 : 10) +
  (employmentType == 'Salaried' ? 15 : 10) +
  (creditScore > 700 ? 20 : creditScore > 600 ? 15 : 10) +
  (existingCustomer ? 10 : 0) +
  (documentComplete ? 10 : 0)
```

### EMI Calculation Formula

```
EMI = P × r × (1 + r)^n / ((1 + r)^n - 1)

Where:
P = Principal (cDesiredLoanAmount)
r = Monthly interest rate (assumed 24% annual = 2% monthly)
n = Tenure in months (cPreferredTenure)
```

### DTI Calculation

```
DTI = (cExistingMonthlyObligations + cProposedEMI) / cMonthlyIncome × 100
```

### FOIR Calculation

```
FOIR = cExistingMonthlyObligations / cMonthlyIncome × 100
```

---

## APPENDIX B: STATUS STYLE MAPPINGS

### Lead Status Colors
```json
{
  "New": "info",
  "Contacted": "primary",
  "Validated": "warning",
  "Qualified": "success",
  "Deferred": null,
  "Converted": "success",
  "Disqualified": "danger"
}
```

### Case Status Colors
```json
{
  "Open": "primary",
  "In Progress": "info",
  "Resolved": "success",
  "Close": null,
  "Escalated": "danger"
}
```

### Opportunity Stage Colors
```json
{
  "Prospecting": null,
  "Qualification": null,
  "Proposal": "primary",
  "Negotiation": "warning",
  "Closed Won": "success",
  "Closed Lost": "info"
}
```

---

**Document End**

*Generated from EspoCRM instance analysis via API exploration*  
*Instance URL: espocrm-dev-220642639797.us-central1.run.app*
