# Case Management Architecture Implementation

**Target:** Siyapatha Finance  
**Platform:** EspoCRM 9.2.5  
**Document Type:** UI Configuration Specification  
**Audience:** Platform Developer (Low-code/No-code Configuration)

---

## 1. Current State Analysis

### Existing Case Entity (Verified)

| Field | Type | Current Values | Status |
|-------|------|----------------|--------|
| `number` | int | Auto-increment | ✓ Keep |
| `cCaseNumber` | varchar | C2812202500001 format | ✓ Keep |
| `name` | varchar | Case subject | ✓ Keep |
| `status` | enum | New, Assigned, Pending, Closed, Rejected, Duplicate | ⚠ Modify |
| `priority` | enum | Low, Normal, High, Urgent | ✓ Keep |
| `type` | enum | Question, Incident, Problem | ⚠ Modify |
| `description` | text | Case details | ✓ Keep |
| `contactId` | link | Customer link | ✓ Keep (displays as "Customer") |
| `assignedUserId` | link | Assigned officer | ✓ Keep |
| `resolution` | text | Resolution notes | ✓ Keep |

### Architecture Note
- **Account entity:** Not used (B2C model)
- **Contact entity:** Renamed to "Customer" - already configured
- **cBranch entity:** Custom branch entity exists with 63 branches

---

## 2. Field Requirements

### 2.1 New Custom Fields to Create

Navigate to: **Administration → Entity Manager → Case → Fields → Add Field**

#### Field 1: Customer Type
```
Field Name:     cCustomerType
Type:           Enum
Label:          Customer Type
Options:
  - Individual
  - Corporate
  - SME
  - Micro Finance
Default:        Individual
Required:       No
Audited:        Yes
```

#### Field 2: Product Type
```
Field Name:     cProductType
Type:           Enum
Label:          Product Type
Options:
  - Personal Loan
  - Vehicle Loan
  - Gold Loan
  - Home Loan
  - Business Loan
  - Leasing
  - Pawning
  - Fixed Deposit
  - Savings Account
  - Insurance
  - Not Applicable
Default:        Not Applicable
Required:       No
Audited:        Yes
```

#### Field 3: Branch Link
```
Field Name:     cBranch
Type:           Link
Label:          Branch
Entity to link: cBranch
Required:       No
Audited:        Yes
```

#### Field 4: SLA Resolution Due
```
Field Name:     cSlaResolutionDue
Type:           Datetime
Label:          Resolution Due Date
Required:       No
Read-only:      Yes (formula-calculated)
Audited:        No
```

#### Field 5: SLA Status
```
Field Name:     cSlaStatus
Type:           Enum
Label:          SLA Status
Options:
  - On Track
  - At Risk
  - Breached
Default:        On Track
Style:
  On Track:     success (green)
  At Risk:      warning (yellow)
  Breached:     danger (red)
Required:       No
Read-only:      Yes
```

#### Field 6: Escalation Level
```
Field Name:     cEscalationLevel
Type:           Enum
Label:          Escalation Level
Options:
  - None
  - Supervisor
  - Manager
  - Senior Management
  - Compliance
Default:        None
Required:       No
Audited:        Yes
```

#### Field 7: Resolution Type
```
Field Name:     cResolutionType
Type:           Enum
Label:          Resolution Type
Options:
  - Resolved - Action Taken
  - Resolved - No Action Required
  - Resolved - Compensation Provided
  - Customer Withdrew
  - Duplicate Case
  - Invalid/Not Applicable
Required:       No (required when status = Resolved)
```

#### Field 8: Customer Satisfaction
```
Field Name:     cCustomerSatisfaction
Type:           Enum
Label:          Customer Satisfaction
Options:
  - 5 - Excellent
  - 4 - Good
  - 3 - Average
  - 2 - Poor
  - 1 - Very Poor
  - Not Rated
Default:        Not Rated
Required:       No
```

#### Field 9: Resolved At
```
Field Name:     cResolvedAt
Type:           Datetime
Label:          Resolved At
Required:       No
Read-only:      Yes
```

#### Field 10: Source Channel
```
Field Name:     cSourceChannel
Type:           Enum
Label:          Source Channel
Options:
  - Phone
  - Email
  - Portal
  - Branch Walk-in
  - Social Media
  - CBSL Referral
Default:        Phone
Required:       Yes
Audited:        Yes
```

---

### 2.2 Existing Fields to Modify

Navigate to: **Administration → Entity Manager → Case → Fields**

#### Modify: type Field
```
Field:          type
New Label:      Case Category
New Options:
  - Service
  - Complaint
  - Inquiry
Style:
  Service:      primary (blue)
  Complaint:    danger (red)
  Inquiry:      default (gray)
Default:        Inquiry
```

#### Modify: status Field
```
Field:          status
New Options:
  - Open
  - In Progress
  - Pending Customer
  - Escalated
  - Resolved
  - Closed
Style:
  Open:             primary
  In Progress:      warning
  Pending Customer: info
  Escalated:        danger
  Resolved:         success
  Closed:           default
Default:        Open
```

---

## 3. Layout Configuration

### 3.1 Detail View Layout

Navigate to: **Administration → Layout Manager → Case → Detail**

#### Panel 1: Case Information (Top Left)
```
Row 1: name (full width)
Row 2: type | priority
Row 3: status | cSlaStatus
Row 4: contactName | cCustomerType
Row 5: cBranch | assignedUserName
Row 6: cSourceChannel | createdAt
```

#### Panel 2: Product & Classification (Top Right)
```
Row 1: cProductType
Row 2: cCaseNumber
Row 3: number
```

#### Panel 3: Description (Full Width)
```
Row 1: description (full width, 4 rows height)
```

#### Panel 4: SLA & Escalation (Side Panel)
```
Row 1: cSlaResolutionDue
Row 2: cSlaStatus
Row 3: cEscalationLevel
```

#### Panel 5: Resolution (Bottom)
```
Row 1: cResolutionType | cResolvedAt
Row 2: resolution (full width, 3 rows height)
Row 3: cCustomerSatisfaction
```

---

### 3.2 List View Layout

Navigate to: **Administration → Layout Manager → Case → List**

```
Columns (in order):
1. cCaseNumber (width: 12%)
2. name (width: 20%)
3. contactName (width: 15%)
4. type (width: 8%)
5. priority (width: 8%)
6. status (width: 10%)
7. cSlaStatus (width: 8%)
8. cBranchName (width: 10%)
9. assignedUserName (width: 12%)
10. createdAt (width: 10%)
```

---

### 3.3 Create View Layout

Navigate to: **Administration → Layout Manager → Case → Detail Small**

```
Required fields order:
1. name
2. type
3. priority
4. contactId (Customer lookup)
5. cBranch
6. cProductType
7. cSourceChannel
8. description
9. assignedUserId
```

---

## 4. Formula Scripts

### 4.1 Before Save Script

Navigate to: **Administration → Entity Manager → Case → Formula → Before Save**

```javascript
// Auto-populate customer type from linked customer
$contactId = contactId;
if ($contactId) {
    $customerType = entity\attribute('Contact', $contactId, 'cCustomerType');
    if ($customerType) {
        cCustomerType = $customerType;
    }
}

// Calculate SLA Resolution Due Date based on category and priority
$category = type;
$priority = priority;
$created = createdAt;

// SLA Matrix (business hours)
$slaHours = 120; // Default: 5 business days

if ($category == 'Complaint') {
    if ($priority == 'Urgent') {
        $slaHours = 48;
    } else if ($priority == 'High') {
        $slaHours = 72;
    } else if ($priority == 'Normal') {
        $slaHours = 120;
    } else {
        $slaHours = 168;
    }
} else if ($category == 'Service') {
    if ($priority == 'Urgent') {
        $slaHours = 24;
    } else if ($priority == 'High') {
        $slaHours = 48;
    } else {
        $slaHours = 72;
    }
} else if ($category == 'Inquiry') {
    if ($priority == 'Urgent') {
        $slaHours = 8;
    } else if ($priority == 'High') {
        $slaHours = 24;
    } else {
        $slaHours = 48;
    }
}

// Simple SLA calculation (add hours to created date)
// Note: For true business hours, implement via scheduled job
if (!cSlaResolutionDue) {
    cSlaResolutionDue = datetime\addHours($created, $slaHours);
}

// Set resolved timestamp when status changes to Resolved
$statusWas = entity\attributeFetched('status');
if (status == 'Resolved' && $statusWas != 'Resolved') {
    cResolvedAt = datetime\now();
}

// Reset resolved timestamp if reopened
if (status != 'Resolved' && status != 'Closed' && cResolvedAt) {
    cResolvedAt = null;
}

// Auto-escalate on SLA breach
if (cSlaStatus == 'Breached' && cEscalationLevel == 'None') {
    cEscalationLevel = 'Supervisor';
    status = 'Escalated';
}
```

---

## 5. Assignment Rules

Navigate to: **Administration → Assignment Rules**

### Rule 1: Service Cases to Customer Service Team

```
Name:           Service Case Assignment
Entity Type:    Case
Is Active:      Yes
Order:          1
Conditions:
  Field: type
  Comparison: Equals
  Value: Service
  
Assignment:
  Type: Round-Robin
  Team: Customer Service Team
  Target Field: assignedUser
```

### Rule 2: Complaints to Complaints Team

```
Name:           Complaint Case Assignment
Entity Type:    Case
Is Active:      Yes
Order:          2
Conditions:
  Field: type
  Comparison: Equals
  Value: Complaint
  
Assignment:
  Type: Least-Busy
  Team: Complaints Handling Team
  Target Field: assignedUser
```

### Rule 3: Inquiries to General Team

```
Name:           Inquiry Case Assignment
Entity Type:    Case
Is Active:      Yes
Order:          3
Conditions:
  Field: type
  Comparison: Equals
  Value: Inquiry
  
Assignment:
  Type: Round-Robin
  Team: General Inquiry Team
  Target Field: assignedUser
```

### Rule 4: Branch-Specific Assignment (Override)

```
Name:           Branch Case Assignment
Entity Type:    Case
Is Active:      Yes
Order:          0 (highest priority)
Conditions:
  Field: cBranchId
  Comparison: Is Not Empty
  
Assignment:
  Type: Round-Robin
  Team Field: cBranch.teamId
  Target Field: assignedUser
```

---

## 6. Teams Setup

Navigate to: **Administration → Teams**

### Required Teams

| Team Name | Role | Position Access |
|-----------|------|-----------------|
| Customer Service Team | Case Handler | Branch Staff |
| Complaints Handling Team | Case Handler | Branch Staff |
| General Inquiry Team | Case Handler | Branch Staff |
| Case Supervisors | Supervisor | Branch Manager |
| Case Managers | Manager | Regional Manager |
| Compliance Team | Compliance Officer | HQ Staff |

---

## 7. Scheduled Jobs

Navigate to: **Administration → Scheduled Jobs**

### Job 1: SLA Status Monitor

```
Name:           Case SLA Monitor
Job:            Custom script (see below)
Scheduling:     Every hour (0 * * * *)
Is Active:      Yes
```

**Custom Job Script** (requires custom code file):

Create file: `custom/Espo/Custom/Jobs/CaseSlaMonitor.php`

```php
<?php
namespace Espo\Custom\Jobs;

use Espo\Core\Job\Job;
use Espo\Core\Job\JobDataLess;
use Espo\ORM\EntityManager;

class CaseSlaMonitor implements Job, JobDataLess
{
    private EntityManager $entityManager;
    
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    
    public function run(): void
    {
        $cases = $this->entityManager
            ->getRDBRepository('Case')
            ->where([
                'status!=' => ['Resolved', 'Closed'],
            ])
            ->find();
            
        $now = new \DateTime();
        
        foreach ($cases as $case) {
            $dueDate = $case->get('cSlaResolutionDue');
            if (!$dueDate) continue;
            
            $due = new \DateTime($dueDate);
            $diff = $due->getTimestamp() - $now->getTimestamp();
            $hoursRemaining = $diff / 3600;
            
            if ($hoursRemaining < 0) {
                $case->set('cSlaStatus', 'Breached');
            } elseif ($hoursRemaining <= 8) {
                $case->set('cSlaStatus', 'At Risk');
            } else {
                $case->set('cSlaStatus', 'On Track');
            }
            
            $this->entityManager->saveEntity($case);
        }
    }
}
```

---

## 8. Email Templates

Navigate to: **Administration → Email Templates**

### Template 1: Case Acknowledgment

```
Name:           Case Acknowledgment
Subject:        Case #{cCaseNumber} - We've received your {type}

Body (HTML):
Dear {contactName},

Thank you for contacting Siyapatha Finance.

Your {type} has been received and assigned case number: <strong>{cCaseNumber}</strong>

<strong>Case Details:</strong>
- Category: {type}
- Priority: {priority}
- Expected Resolution: {cSlaResolutionDue}

We will update you as your case progresses.

Best regards,
Siyapatha Finance Customer Care
```

### Template 2: Case Resolution

```
Name:           Case Resolution
Subject:        Case #{cCaseNumber} has been resolved

Body (HTML):
Dear {contactName},

We're pleased to inform you that your case has been resolved.

<strong>Case:</strong> {cCaseNumber}
<strong>Resolution:</strong> {cResolutionType}
<strong>Resolved on:</strong> {cResolvedAt}

<strong>Resolution Details:</strong>
{resolution}

If you're not satisfied with this resolution, please reply to this email.

Thank you for choosing Siyapatha Finance.
```

### Template 3: SLA Warning (Internal)

```
Name:           SLA Warning
Subject:        ⚠️ SLA At Risk - Case #{cCaseNumber}

Body (HTML):
<strong>ALERT: Case approaching SLA breach</strong>

Case: {cCaseNumber} - {name}
Customer: {contactName}
Category: {type}
Priority: {priority}

<strong>Due Date:</strong> {cSlaResolutionDue}

Please take immediate action.
```

---

## 9. BPM Workflow Summary

Three workflows to import from `case_workflows_complete.csv`:

| # | Workflow Name | Trigger | Actions |
|---|---------------|---------|---------|
| 1 | Case Auto-Assignment & Acknowledgment | New case created | Apply assignment rule → Send acknowledgment email → Create follow-up task |
| 2 | Case Status Handler | Status changed | On Resolved: wait 7 days → auto-close; On Escalated: reassign to supervisor |
| 3 | Priority Alert | Priority = Urgent/High | Immediate notification to manager |

---

## 10. Filters & Reports

### Saved Filters

Navigate to: **Case List View → Filters (⋮) → Add Filter**

#### Filter 1: My Open Cases
```
Conditions:
  - assignedUser = Current User
  - status IN (Open, In Progress, Pending Customer)
```

#### Filter 2: SLA At Risk
```
Conditions:
  - cSlaStatus = At Risk
  - status NOT IN (Resolved, Closed)
```

#### Filter 3: SLA Breached
```
Conditions:
  - cSlaStatus = Breached
  - status NOT IN (Resolved, Closed)
```

#### Filter 4: Branch Cases (for Branch Managers)
```
Conditions:
  - cBranch = {Current User's Branch}
  - status NOT IN (Closed)
```

---

## 11. Implementation Checklist

### Day 1: Fields & Layouts
- [ ] Create 10 custom fields
- [ ] Modify `type` field options
- [ ] Modify `status` field options  
- [ ] Configure Detail layout
- [ ] Configure List layout
- [ ] Configure Create form layout
- [ ] Clear cache: Administration → Clear Cache

### Day 2: Logic & Automation
- [ ] Add Before Save formula script
- [ ] Create assignment rules (4 rules)
- [ ] Import BPM workflows from CSV
- [ ] Activate workflows

### Day 3: Templates & Jobs
- [ ] Create email templates (3 templates)
- [ ] Set up scheduled SLA monitor job
- [ ] Create saved filters (4 filters)
- [ ] Configure teams if not existing

### Day 4: Testing
- [ ] Create test cases for each category
- [ ] Verify SLA calculation
- [ ] Test assignment rules
- [ ] Verify workflow triggers
- [ ] Check email notifications

### Day 5: Portal & Go-Live
- [ ] Configure portal layouts
- [ ] Set field permissions for portal
- [ ] User training
- [ ] Go-live

---

## 12. Quick Reference

### Status Flow Diagram
```
Open → In Progress → Resolved → (7 days) → Closed
   ↘                  ↗
     Pending Customer
   ↘
     Escalated → (reassign) → In Progress
```

### SLA Matrix (Business Hours)

| Category | Urgent | High | Normal | Low |
|----------|--------|------|--------|-----|
| Complaint | 48h | 72h | 120h | 168h |
| Service | 24h | 48h | 72h | 120h |
| Inquiry | 8h | 24h | 48h | 72h |

### Business Hours Configuration
- Working hours: 8:30 AM - 5:00 PM
- Working days: Monday - Saturday
- Weekend: Sunday only
- Holidays: Configure in scheduled job

---

## Appendix A: Field Dependencies

Configure at: **Administration → Entity Manager → Case → Fields → [Field] → Dynamic Logic**

### Resolution Type Visibility
```json
{
  "visible": {
    "conditionGroup": [
      {
        "type": "in",
        "attribute": "status",
        "value": ["Resolved", "Closed"]
      }
    ]
  }
}
```

### Customer Satisfaction Visibility
```json
{
  "visible": {
    "conditionGroup": [
      {
        "type": "equals",
        "attribute": "status",
        "value": "Closed"
      }
    ]
  }
}
```

---

## Appendix B: Color Scheme

Siyapatha Finance Brand Colors:
- Primary: #1e3a5f (Navy)
- Accent: #d4af37 (Gold)
- Success: #28a745
- Warning: #ffc107
- Danger: #dc3545
- Info: #17a2b8

Apply via: **Administration → Settings → Theme**
