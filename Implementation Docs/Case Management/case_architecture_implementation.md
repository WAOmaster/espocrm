# Case Management Implementation - Current Architecture Analysis

## 1. CURRENT ARCHITECTURE ANALYSIS (Via API)

### Existing Case Entity Structure

```json
{
  "id": "695120ea6a0f25b75",
  "number": 5,                          // Auto-increment case number
  "name": "Issue 01",                   // Case subject
  "status": "New",                      // Current: New, Assigned, Pending, Closed, Rejected, Duplicate
  "priority": "Normal",                 // Current: Low, Normal, High, Urgent
  "type": "Inquiry",                    // Current: Question, Incident, Problem
  "description": "test issue 0001",     // Case description
  "contactId": "6950207486d1c5836",    // Customer link
  "contactName": "Nimal Perera",        // Customer name (from link)
  "assignedUserId": "6948c55708e2d700e", // Assigned officer
  "assignedUserName": "Admin",
  "createdAt": "2025-12-28 12:22:02",
  "modifiedAt": "2025-12-28 12:22:02",
  "cCaseNumber": "C2812202500001"       // Custom case number (already exists!)
}
```

### Existing Contact (Customer) Structure

```json
{
  "id": "6950207486d1c5836",
  "name": "Nimal Perera",
  "firstName": "Nimal",
  "lastName": "Perera",
  "emailAddress": "nimal.perera@siyapatha.lk",
  "phoneNumber": "+94771858863",
  "addressStreet": "No. 125, Wakwella Road",
  "addressCity": "Galle",
  "addressState": "Southern Province",
  "addressCountry": "Sri Lanka",
  "cDateOfBirth": "1985-03-15",
  "cNicNumber": "85078863V",
  "cGender": "Male",
  "cBranchId": null,                     // Branch link exists!
  "cBranchName": null
}
```

### Required vs Existing Fields Mapping

| Required Field | Existing Field | Status | Action Needed |
|----------------|----------------|---------|---------------|
| Case ID | `number` + `cCaseNumber` | ✅ Exists | Use existing |
| Customer Name | `contactName` | ✅ Exists | Use existing link |
| Customer Type | - | ❌ Missing | **CREATE NEW** |
| Product Type | - | ❌ Missing | **CREATE NEW** |
| Case Category | `type` | ⚠️ Modify | Update enum values |
| Case Status | `status` | ⚠️ Modify | Update enum values |
| Priority | `priority` | ✅ Exists | Use existing |
| Branch | - | ❌ Missing | **CREATE NEW link** |
| Assigned Officer | `assignedUserId` | ✅ Exists | Use existing |

---

## 2. FIELD REQUIREMENTS DEFINITION

### Field 1: Customer Type (NEW)
```json
{
  "name": "cCustomerType",
  "type": "enum",
  "required": false,
  "options": [
    "Individual",
    "Corporate",
    "SME",
    "Micro Finance"
  ],
  "default": "Individual",
  "audited": true,
  "tooltip": "Type of customer submitting the case"
}
```

**API Creation:**
```bash
curl -X POST 'https://espocrm-dev-220642639797.us-central1.run.app/api/v1/Case/action/createField' \
  -u 'admin:Q2fp120dbL3BafWK' \
  -H 'Content-Type: application/json' \
  -d '{
    "name": "cCustomerType",
    "type": "enum",
    "params": {
      "required": false,
      "options": ["Individual", "Corporate", "SME", "Micro Finance"],
      "default": "Individual",
      "audited": true,
      "tooltip": "Type of customer submitting the case"
    }
  }'
```

### Field 2: Product Type (NEW)
```json
{
  "name": "cProductType",
  "type": "enum",
  "required": false,
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
    "Other"
  ],
  "default": null,
  "audited": true,
  "tooltip": "Product related to this case (if applicable)",
  "isCustom": true
}
```

**Entity Manager UI Creation:**
1. Administration → Entity Manager → Case → Fields → Create Field
2. Name: `cProductType`
3. Label: `Product Type`
4. Type: Enum
5. Options: [as listed above]
6. Tooltip: "Product related to this case (if applicable)"
7. Audited: Yes
8. Save

### Field 3: Case Category (MODIFY EXISTING)
```json
{
  "name": "type",
  "type": "enum",
  "required": true,
  "options": [
    "Service",
    "Complaint",
    "Inquiry"
  ],
  "default": "Inquiry",
  "audited": true,
  "style": {
    "Service": "primary",
    "Complaint": "danger",
    "Inquiry": "default"
  }
}
```

**Update via Entity Manager:**
1. Administration → Entity Manager → Case → Fields → type
2. Update Options to: Service, Complaint, Inquiry
3. Update translations in Administration → Label Manager → Case → options → type

### Field 4: Case Status (MODIFY EXISTING)
```json
{
  "name": "status",
  "type": "enum",
  "required": true,
  "options": [
    "Open",
    "In Progress",
    "Pending Customer",
    "Escalated",
    "Resolved",
    "Closed"
  ],
  "default": "Open",
  "audited": true,
  "style": {
    "Open": "primary",
    "In Progress": "warning",
    "Pending Customer": "default",
    "Escalated": "danger",
    "Resolved": "success",
    "Closed": "default"
  }
}
```

**Update via Entity Manager:**
1. Administration → Entity Manager → Case → Fields → status
2. Update Options to: Open, In Progress, Pending Customer, Escalated, Resolved, Closed
3. Update default to "Open"

### Field 5: Branch (NEW LINK)
```json
{
  "name": "cBranch",
  "type": "link",
  "entity": "Branch",
  "required": false,
  "audited": true,
  "tooltip": "Branch handling this case"
}
```

**Create Relationship:**
1. Administration → Entity Manager → Case → Relationships → Create
2. Relationship Type: Many-to-One
3. Link Name: `cBranch`
4. Foreign Entity: Branch
5. Foreign Link Name: `cases`
6. Save

**Note:** Branch entity already exists based on Contact.cBranchId

---

## 3. COMPLETE FIELD DEFINITION TABLE

| # | Field Name | Display Label | Type | Required | Options/Link | Default | Description |
|---|------------|---------------|------|----------|--------------|---------|-------------|
| 1 | `number` | Case ID | Auto-increment | Yes | - | Auto | System-generated case number |
| 2 | `cCaseNumber` | Case Number | Varchar | No | - | Auto | Custom formatted case number |
| 3 | `name` | Subject | Varchar(249) | Yes | - | - | Brief description of case |
| 4 | `contactId` | Customer Name | Link | No | Contact | - | Customer who reported case |
| 5 | `cCustomerType` | Customer Type | Enum | No | Individual, Corporate, SME, Micro Finance | Individual | Type of customer |
| 6 | `cProductType` | Product Type | Enum | No | Personal Loan, Vehicle Loan, etc. | - | Related product (if applicable) |
| 7 | `type` | Case Category | Enum | Yes | Service, Complaint, Inquiry | Inquiry | Category of case |
| 8 | `status` | Case Status | Enum | Yes | Open, In Progress, Pending, Escalated, Resolved, Closed | Open | Current status |
| 9 | `priority` | Priority | Enum | Yes | Low, Normal, High, Urgent | Normal | Priority level |
| 10 | `cBranchId` | Branch | Link | No | Branch | - | Branch handling case |
| 11 | `assignedUserId` | Assigned Officer | Link | No | User | - | Officer assigned to case |
| 12 | `description` | Description | Text | No | - | - | Detailed case description |
| 13 | `createdAt` | Created Date | Datetime | Auto | - | Auto | When case was created |
| 14 | `modifiedAt` | Modified Date | Datetime | Auto | - | Auto | Last modification date |

---

## 4. ASSIGNMENT RULES

### Assignment Rule 1: Branch-Based Assignment
**Trigger:** Case created with Branch selected

**Logic:**
```javascript
// Formula (Before Save)
if(!entity\isNew()) return;

$branchId = entity\attribute('cBranchId');

if($branchId) {
  // Get branch and its assigned manager/officer
  $branchManager = record\attribute('Branch', $branchId, 'assignedUserId');
  
  if($branchManager) {
    entity\setAttribute('assignedUserId', $branchManager);
  }
}
```

**BPM Implementation:**
- Trigger: After Record Created
- Condition: `cBranchId` is not empty
- Action: Apply Assignment Rule → Round-Robin among branch team members

### Assignment Rule 2: Category-Based Assignment
**Trigger:** Case created without Branch

**Logic by Category:**
- **Service Requests** → Customer Service Team (Round-Robin)
- **Complaints** → Complaints Handling Team (Least-Busy)
- **Inquiries** → General Inquiry Team (Round-Robin)

**BPM Flow:**
```
Case Created 
  → Check Branch?
    → Yes: Assign to Branch Team
    → No: Check Category
      → Service: Round-Robin (CS Team)
      → Complaint: Least-Busy (Complaints Team)
      → Inquiry: Round-Robin (Inquiry Team)
```

### Assignment Rule 3: Escalation Assignment
**Trigger:** Status changed to "Escalated"

**Logic:**
```javascript
// Formula (After Save)
$statusChanged = entity\isAttributeChanged('status');
$isEscalated = entity\attribute('status') == 'Escalated';

if($statusChanged && $isEscalated) {
  // Get current assignee's supervisor
  $currentUserId = entity\attribute('assignedUserId');
  $supervisor = record\attribute('User', $currentUserId, 'superiorId');
  
  if($supervisor) {
    entity\setAttribute('assignedUserId', $supervisor);
  }
}
```

---

## 5. BPM WORKFLOW DEFINITIONS

### Workflow 1: Case Acknowledgment & Auto-Assignment

**Name:** Case Acknowledgment & Auto-Assignment  
**Target Entity:** Case  
**Trigger:** After Record Created  
**Is Active:** Yes

**Flowchart Definition:**

```json
{
  "name": "Case Acknowledgment & Auto-Assignment",
  "targetType": "Case",
  "isActive": true,
  "flowchartData": {
    "elements": [
      {
        "id": "start",
        "type": "eventStartConditional",
        "position": {"x": 60, "y": 140},
        "triggerType": "afterRecordCreated",
        "conditions": {
          "all": [
            {
              "type": "equals",
              "attribute": "status",
              "value": "Open"
            }
          ]
        }
      },
      {
        "id": "check_branch",
        "type": "gatewayExclusive",
        "position": {"x": 200, "y": 140},
        "label": "Branch Selected?"
      },
      {
        "id": "assign_branch",
        "type": "task",
        "position": {"x": 340, "y": 80},
        "label": "Assign to Branch Team",
        "actions": [
          {
            "type": "applyAssignmentRule",
            "target": "round-robin",
            "teamId": "formula:record\\attribute('Branch', entity\\attribute('cBranchId'), 'teamId')"
          }
        ]
      },
      {
        "id": "assign_category",
        "type": "gatewayExclusive",
        "position": {"x": 340, "y": 200},
        "label": "Check Category"
      },
      {
        "id": "assign_service",
        "type": "task",
        "position": {"x": 480, "y": 140},
        "label": "Assign to CS Team",
        "actions": [
          {
            "type": "applyAssignmentRule",
            "target": "round-robin",
            "teamId": "CUSTOMER_SERVICE_TEAM_ID"
          }
        ]
      },
      {
        "id": "assign_complaint",
        "type": "task",
        "position": {"x": 480, "y": 200},
        "label": "Assign to Complaints Team",
        "actions": [
          {
            "type": "applyAssignmentRule",
            "target": "least-busy",
            "teamId": "COMPLAINTS_TEAM_ID"
          }
        ]
      },
      {
        "id": "assign_inquiry",
        "type": "task",
        "position": {"x": 480, "y": 260},
        "label": "Assign to Inquiry Team",
        "actions": [
          {
            "type": "applyAssignmentRule",
            "target": "round-robin",
            "teamId": "INQUIRY_TEAM_ID"
          }
        ]
      },
      {
        "id": "send_ack_email",
        "type": "taskSendMessage",
        "position": {"x": 620, "y": 140},
        "label": "Send Acknowledgment Email",
        "messageType": "Email",
        "from": "system",
        "to": "targetEntity.contact",
        "template": "case_acknowledgment"
      },
      {
        "id": "send_ack_sms",
        "type": "task",
        "position": {"x": 760, "y": 140},
        "label": "Send Acknowledgment SMS",
        "actions": [
          {
            "type": "runScript",
            "formula": "// SMS sending logic via API integration"
          }
        ]
      },
      {
        "id": "update_status",
        "type": "task",
        "position": {"x": 900, "y": 140},
        "label": "Update Status",
        "actions": [
          {
            "type": "updateTargetEntity",
            "fields": {
              "status": "In Progress"
            }
          }
        ]
      },
      {
        "id": "end",
        "type": "eventEnd",
        "position": {"x": 1040, "y": 140}
      }
    ],
    "flows": [
      {"from": "start", "to": "check_branch"},
      {"from": "check_branch", "to": "assign_branch", "condition": "cBranchId != null", "label": "Yes"},
      {"from": "check_branch", "to": "assign_category", "condition": "cBranchId == null", "label": "No"},
      {"from": "assign_category", "to": "assign_service", "condition": "type == 'Service'", "label": "Service"},
      {"from": "assign_category", "to": "assign_complaint", "condition": "type == 'Complaint'", "label": "Complaint"},
      {"from": "assign_category", "to": "assign_inquiry", "condition": "type == 'Inquiry'", "label": "Inquiry"},
      {"from": "assign_branch", "to": "send_ack_email"},
      {"from": "assign_service", "to": "send_ack_email"},
      {"from": "assign_complaint", "to": "send_ack_email"},
      {"from": "assign_inquiry", "to": "send_ack_email"},
      {"from": "send_ack_email", "to": "send_ack_sms"},
      {"from": "send_ack_sms", "to": "update_status"},
      {"from": "update_status", "to": "end"}
    ]
  }
}
```

### Workflow 2: Case Status Management

**Name:** Case Status Management  
**Target Entity:** Case  
**Trigger:** After Record Updated (status changed)  
**Is Active:** Yes

**Flowchart Definition:**

```json
{
  "name": "Case Status Management",
  "targetType": "Case",
  "isActive": true,
  "flowchartData": {
    "elements": [
      {
        "id": "start",
        "type": "eventStartConditional",
        "position": {"x": 60, "y": 140},
        "triggerType": "afterRecordUpdated",
        "conditions": {
          "all": [
            {
              "type": "wasEqual",
              "attribute": "status",
              "not": true
            }
          ]
        }
      },
      {
        "id": "check_status",
        "type": "gatewayExclusive",
        "position": {"x": 200, "y": 140},
        "label": "New Status?"
      },
      {
        "id": "handle_escalated",
        "type": "task",
        "position": {"x": 340, "y": 60},
        "label": "Handle Escalation",
        "actions": [
          {
            "type": "updateTargetEntity",
            "formula": "$currentUserId = entity\\attribute('assignedUserId'); $supervisor = record\\attribute('User', $currentUserId, 'superiorId'); if($supervisor) { entity\\setAttribute('assignedUserId', $supervisor); }"
          }
        ]
      },
      {
        "id": "notify_escalation",
        "type": "taskSendMessage",
        "position": {"x": 480, "y": 60},
        "label": "Notify Supervisor",
        "messageType": "Email",
        "from": "system",
        "to": "targetEntity.assignedUser",
        "template": "case_escalation"
      },
      {
        "id": "handle_resolved",
        "type": "task",
        "position": {"x": 340, "y": 140},
        "label": "Record Resolution Time",
        "actions": [
          {
            "type": "updateTargetEntity",
            "formula": "$now = datetime\\now(); entity\\setAttribute('cResolvedAt', $now);"
          }
        ]
      },
      {
        "id": "send_resolution_email",
        "type": "taskSendMessage",
        "position": {"x": 480, "y": 140},
        "label": "Send Resolution Email",
        "messageType": "Email",
        "from": "system",
        "to": "targetEntity.contact",
        "template": "case_resolution"
      },
      {
        "id": "wait_7days",
        "type": "eventIntermediateTimerCatch",
        "position": {"x": 620, "y": 140},
        "timerShift": 7,
        "timerShiftUnits": "days",
        "label": "Wait 7 Days"
      },
      {
        "id": "auto_close",
        "type": "task",
        "position": {"x": 760, "y": 140},
        "label": "Auto-Close Case",
        "actions": [
          {
            "type": "updateTargetEntity",
            "fields": {
              "status": "Closed"
            }
          }
        ]
      },
      {
        "id": "handle_closed",
        "type": "taskSendMessage",
        "position": {"x": 340, "y": 220},
        "label": "Send Closure Notification",
        "messageType": "Email",
        "from": "system",
        "to": "targetEntity.contact",
        "template": "case_closure"
      },
      {
        "id": "end",
        "type": "eventEnd",
        "position": {"x": 900, "y": 140}
      }
    ],
    "flows": [
      {"from": "start", "to": "check_status"},
      {"from": "check_status", "to": "handle_escalated", "condition": "status == 'Escalated'", "label": "Escalated"},
      {"from": "check_status", "to": "handle_resolved", "condition": "status == 'Resolved'", "label": "Resolved"},
      {"from": "check_status", "to": "handle_closed", "condition": "status == 'Closed'", "label": "Closed"},
      {"from": "handle_escalated", "to": "notify_escalation"},
      {"from": "notify_escalation", "to": "end"},
      {"from": "handle_resolved", "to": "send_resolution_email"},
      {"from": "send_resolution_email", "to": "wait_7days"},
      {"from": "wait_7days", "to": "auto_close"},
      {"from": "auto_close", "to": "end"},
      {"from": "handle_closed", "to": "end"}
    ]
  }
}
```

### Workflow 3: Priority-Based Notification

**Name:** Priority-Based Notification  
**Target Entity:** Case  
**Trigger:** After Record Created OR Priority Changed  
**Is Active:** Yes

**Simplified Flow:**
```
Start (High/Urgent Priority)
  → Notify Assigned Officer (Email + SMS)
  → Notify Team Lead (Email)
  → Create High-Priority Task
  → End
```

---

## 6. API IMPLEMENTATION SCRIPTS

### Script 1: Create Case via API

```bash
#!/bin/bash
# create_case.sh

API_URL="https://espocrm-dev-220642639797.us-central1.run.app/api/v1"
AUTH="admin:Q2fp120dbL3BafWK"

# Create new case
curl -X POST "${API_URL}/Case" \
  -u "${AUTH}" \
  -H 'Content-Type: application/json' \
  -d '{
    "name": "Internet banking access issue",
    "type": "Service",
    "priority": "High",
    "contactId": "6950207486d1c5836",
    "cCustomerType": "Individual",
    "cProductType": "Savings Account",
    "cBranchId": "BRANCH_ID_HERE",
    "description": "Customer unable to login to internet banking portal. Error message: Invalid credentials.",
    "status": "Open"
  }'
```

### Script 2: Update Case Status

```bash
#!/bin/bash
# update_case_status.sh

CASE_ID=$1
NEW_STATUS=$2

curl -X PUT "${API_URL}/Case/${CASE_ID}" \
  -u "${AUTH}" \
  -H 'Content-Type: application/json' \
  -d "{
    \"status\": \"${NEW_STATUS}\"
  }"
```

### Script 3: Bulk Case Assignment

```python
#!/usr/bin/env python3
# bulk_assign_cases.py

import requests
from requests.auth import HTTPBasicAuth

API_URL = "https://espocrm-dev-220642639797.us-central1.run.app/api/v1"
AUTH = HTTPBasicAuth('admin', 'Q2fp120dbL3BafWK')

def get_unassigned_cases():
    """Get all cases with status=Open and no assigned user"""
    response = requests.get(
        f"{API_URL}/Case",
        auth=AUTH,
        params={
            'where[0][type]': 'isNull',
            'where[0][attribute]': 'assignedUserId',
            'where[1][type]': 'equals',
            'where[1][attribute]': 'status',
            'where[1][value]': 'Open',
            'maxSize': 50
        }
    )
    return response.json()['list']

def assign_case_to_team(case_id, team_id, assignment_rule='round-robin'):
    """Assign case using assignment rule"""
    # This would typically be done through BPM
    # or custom API endpoint for assignment
    pass

# Example usage
unassigned = get_unassigned_cases()
print(f"Found {len(unassigned)} unassigned cases")

for case in unassigned:
    print(f"Case #{case['number']}: {case['name']}")
    # Assign based on category
    if case['type'] == 'Complaint':
        assign_case_to_team(case['id'], 'COMPLAINTS_TEAM_ID')
```

---

## 7. FORMULA SCRIPTS

### Formula 1: Auto-Generate Case Number
**Location:** Administration → Entity Manager → Case → Formula (Before Save)

```javascript
// Auto-generate custom case number: CDDMMYYYYNNNNN
if(entity\isNew()) {
  $date = datetime\today();
  $day = datetime\day($date);
  $month = datetime\month($date);
  $year = datetime\year($date);
  
  $dayStr = string\pad(string\concatenate($day), 2, '0');
  $monthStr = string\pad(string\concatenate($month), 2, '0');
  $yearStr = string\concatenate($year);
  
  $number = entity\attribute('number');
  $numberStr = string\pad(string\concatenate($number), 5, '0');
  
  $caseNumber = string\concatenate(
    'C',
    $dayStr,
    $monthStr,
    $yearStr,
    $numberStr
  );
  
  entity\setAttribute('cCaseNumber', $caseNumber);
}
```

### Formula 2: Auto-Set Customer Type from Contact
**Location:** Administration → Entity Manager → Case → Formula (Before Save)

```javascript
// Auto-populate customer type from contact if not set
$contactId = entity\attribute('contactId');

if($contactId && !entity\attribute('cCustomerType')) {
  // Check if contact is linked to account
  $accountId = record\attribute('Contact', $contactId, 'accountId');
  
  if($accountId) {
    $accountType = record\attribute('Account', $accountId, 'type');
    
    if($accountType == 'Customer') {
      entity\setAttribute('cCustomerType', 'Individual');
    } else {
      if($accountType == 'Partner') {
        entity\setAttribute('cCustomerType', 'Corporate');
      }
    }
  } else {
    entity\setAttribute('cCustomerType', 'Individual');
  }
}
```

### Formula 3: Escalation Logic
**Location:** Administration → Entity Manager → Case → Formula (After Save)

```javascript
// Auto-escalate if status changed to Escalated
$statusChanged = entity\isAttributeChanged('status');
$isEscalated = entity\attribute('status') == 'Escalated';

if($statusChanged && $isEscalated) {
  // Get current assignee's supervisor
  $currentUserId = entity\attribute('assignedUserId');
  
  if($currentUserId) {
    $supervisor = record\attribute('User', $currentUserId, 'superiorId');
    
    if($supervisor) {
      entity\setAttribute('assignedUserId', $supervisor);
      
      // Send notification
      ext\email\send(
        $supervisor,
        null,
        'case_escalation',
        record\attribute('User', $supervisor, 'emailAddress'),
        entity\attribute('id')
      );
    }
  }
}
```

---

## 8. IMPLEMENTATION CHECKLIST

### Phase 1: Field Setup (Day 1)
- [ ] Create `cCustomerType` field (Enum)
- [ ] Create `cProductType` field (Enum)
- [ ] Modify `type` field options to Service/Complaint/Inquiry
- [ ] Modify `status` field options to Open/In Progress/Pending/Escalated/Resolved/Closed
- [ ] Create `cBranch` relationship (Many-to-One with Branch)
- [ ] Verify `cCaseNumber` field exists (already created)
- [ ] Test field creation via API

### Phase 2: Layout Configuration (Day 1-2)
- [ ] Update Detail Layout with all fields
- [ ] Update List Layout with key columns
- [ ] Update Filters to include new fields
- [ ] Configure Portal layouts (if applicable)
- [ ] Set field access permissions per role

### Phase 3: Formula Scripts (Day 2)
- [ ] Add Before-Save formula for case number generation
- [ ] Add Before-Save formula for customer type auto-population
- [ ] Add After-Save formula for escalation logic
- [ ] Test all formulas with sample data

### Phase 4: BPM Workflows (Day 2-3)
- [ ] Create Workflow 1: Case Acknowledgment & Auto-Assignment
- [ ] Create Workflow 2: Case Status Management
- [ ] Create Workflow 3: Priority-Based Notification
- [ ] Test each workflow individually
- [ ] Test combined workflow scenarios

### Phase 5: Teams & Assignment Rules (Day 3)
- [ ] Create Customer Service Team
- [ ] Create Complaints Handling Team
- [ ] Create Inquiry Team
- [ ] Configure Round-Robin assignment rule
- [ ] Configure Least-Busy assignment rule
- [ ] Test assignment logic

### Phase 6: Email Templates (Day 3-4)
- [ ] Create "Case Acknowledgment" template
- [ ] Create "Case Escalation" template
- [ ] Create "Case Resolution" template
- [ ] Create "Case Closure" template
- [ ] Test email sending

### Phase 7: Testing (Day 4-5)
- [ ] Test case creation (manual)
- [ ] Test case creation (API)
- [ ] Test assignment rules
- [ ] Test status transitions
- [ ] Test escalation flow
- [ ] Test email notifications
- [ ] End-to-end testing with real scenarios

### Phase 8: Training & Documentation (Day 5)
- [ ] Create user guide
- [ ] Train branch staff
- [ ] Train customer service team
- [ ] Document API integration points
- [ ] Create troubleshooting guide

---

## 9. API TESTING COMMANDS

### Test 1: Create Case with All Fields
```bash
curl -X POST 'https://espocrm-dev-220642639797.us-central1.run.app/api/v1/Case' \
  -u 'admin:Q2fp120dbL3BafWK' \
  -H 'Content-Type: application/json' \
  -d '{
    "name": "Test Case - Complete Fields",
    "type": "Complaint",
    "priority": "High",
    "contactId": "6950207486d1c5836",
    "cCustomerType": "Individual",
    "cProductType": "Personal Loan",
    "description": "Testing complete case creation with all fields",
    "status": "Open"
  }'
```

### Test 2: Get Case with Custom Fields
```bash
curl -X GET 'https://espocrm-dev-220642639797.us-central1.run.app/api/v1/Case/CASE_ID' \
  -u 'admin:Q2fp120dbL3BafWK' \
  -H 'Content-Type: application/json'
```

### Test 3: Update Case Status
```bash
curl -X PUT 'https://espocrm-dev-220642639797.us-central1.run.app/api/v1/Case/CASE_ID' \
  -u 'admin:Q2fp120dbL3BafWK' \
  -H 'Content-Type: application/json' \
  -d '{
    "status": "Escalated"
  }'
```

### Test 4: Filter Cases by Category
```bash
curl -X GET 'https://espocrm-dev-220642639797.us-central1.run.app/api/v1/Case' \
  -u 'admin:Q2fp120dbL3BafWK' \
  -H 'Content-Type: application/json' \
  -G \
  --data-urlencode 'where[0][type]=equals' \
  --data-urlencode 'where[0][attribute]=type' \
  --data-urlencode 'where[0][value]=Complaint'
```

---

## 10. NEXT STEPS

1. **Immediate Actions (Today)**
   - Create custom fields via Entity Manager
   - Update status/type enum options
   - Test basic case creation

2. **Short-term (This Week)**
   - Implement BPM workflows
   - Configure assignment rules
   - Create email templates
   - Complete end-to-end testing

3. **Medium-term (Next Week)**
   - Train users
   - Deploy to production
   - Monitor performance
   - Gather feedback

4. **Long-term Enhancements**
   - SLA tracking
   - Customer portal integration
   - SMS notifications
   - Reporting dashboards
   - CRIB integration for complaints
