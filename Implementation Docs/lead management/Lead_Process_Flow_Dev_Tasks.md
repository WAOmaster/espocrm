# Lead Process Flow - Development Subtasks
**Source:** User Stories from BA Analysis  
**Focus:** Lead Assignment → Financial Calculation → Scoring → SMS → Auto-Qualification → Credit Check  
**Format:** Development-Ready Tasks with Clear Outcomes

---

## WORKFLOW 1: Lead Auto-Assignment Flow

**User Story Source:** Epic 1 - Instant Lead Assignment Experience  
**Business Value:** Zero manual assignment delays, fair distribution, no lost leads

---

### Task 1.1: Territory-Based Assignment System
**Story:** Field Agent Receives Territory-Based Leads (Story 1.1)  
**Outcome:** When lead created via "Field Agent" or "Branch Walk-in", auto-assign to agent based on address district

#### Subtasks:

**1.1.1 Create Territory Management Entity**
- [ ] Create Territory entity with fields:
  - `name` (text) - e.g., "Colombo North"
  - `district` (dropdown) - Colombo, Gampaha, Kandy, etc.
  - `assignedUser` (user link) - Field agent
- [ ] Add 10+ Sri Lankan districts to dropdown
- [ ] Create admin interface: Territory list + create/edit forms
- [ ] Seed initial data: Map 3-5 territories to test users

**Acceptance:**
- Admin can create territory: "Colombo Central" → District: Colombo → Assign to: Agent A
- Territory list shows all configured territories with assigned agents

---

**1.1.2 Build District Extraction Logic**
- [ ] Create background process that triggers when lead is saved
- [ ] Check: If `leadChannel` = "Field Agent" OR "Branch Walk-in"
- [ ] Extract district from `addressCity` field
  - Use keyword matching: "Colombo" in city → District = "Colombo"
  - Build mapping table: Mount Lavinia → Colombo, Negombo → Gampaha, etc.
- [ ] Find territory where `district` matches extracted district
- [ ] Get `assignedUser` from matched territory
- [ ] Set lead's `assignedUserId` = territory's `assignedUser`
- [ ] Save lead

**Acceptance:**
- Lead created: Address City = "Colombo Fort", Channel = "Field Agent"
- Result: Lead auto-assigned to agent mapped to Colombo district within 5 seconds

---

**1.1.3 Auto-Create First Contact Task**
- [ ] After assigning lead, create Task record:
  - Name: "First Contact - [Lead Name]"
  - Assigned to: Same user as lead
  - Related to: This lead
  - Due date: Current time + 15 minutes
  - Status: "Not Started"
  - Priority: "High"
- [ ] Task appears in agent's task list immediately

**Acceptance:**
- Lead assigned at 10:00 AM → Task created with due time 10:15 AM
- Agent sees task in "My Tasks" widget

---

**1.1.4 Show Assignment Notification**
- [ ] When lead assigned, create in-app notification:
  - Message: "📍 New [District] Lead: [Name]"
  - Link to lead detail page
  - Mark as unread
- [ ] If mobile app exists, send push notification
- [ ] Email notification (optional, configurable)

**Acceptance:**
- Lead assigned → Agent sees notification bell with (1) badge
- Click notification → Opens lead detail page

---

### Task 1.2: Round-Robin Assignment for Web Leads
**Story:** Loan Officer Receives Web Leads Fairly (Story 1.2)  
**Outcome:** Web/mobile/social leads distributed evenly across Sales team, respecting 20-lead capacity

#### Subtasks:

**1.2.1 Build Sales Team Workload Checker**
- [ ] Create background process for web lead channels
- [ ] Check: If `leadChannel` = "Web Form" OR "Mobile App" OR "Social Media"
- [ ] Get all users in "Sales" team
- [ ] For each user, count their open leads:
  - Where `assignedUserId` = user
  - AND `status` IN ("New", "Contacted", "Info Gathering")
- [ ] Build list of available users (open leads < 20)

**Acceptance:**
- Query returns: User A (12 leads), User B (18 leads), User C (20 leads)
- Available users: [User A, User B]

---

**1.2.2 Implement Round-Robin Selection**
- [ ] Store last assigned user index in system settings
  - Setting name: "lastAssignedIndex"
  - Initial value: 0
- [ ] Get next user: `(lastIndex + 1) % availableUsers.length`
- [ ] Assign lead to selected user
- [ ] Update setting: `lastAssignedIndex` = new index
- [ ] Create first contact task for assigned user

**Acceptance:**
- Lead 1 → Assigned to User A, index = 0
- Lead 2 → Assigned to User B, index = 1
- Lead 3 → Assigned to User A, index = 0 (wrapped around)

---

**1.2.3 Handle Edge Case: All Users Full**
- [ ] If all users have ≥20 open leads:
  - Get user with oldest lead (least recently assigned)
  - Assign anyway
  - Create note: "⚠️ All officers at capacity - assigned to [User]"
  - Flag for supervisor review

**Acceptance:**
- All users have 20+ leads → Lead still assigned (not left unassigned)
- Supervisor sees alert in dashboard

---

**1.2.4 Build Team Distribution Dashboard Widget**
- [ ] Create widget: "Lead Distribution This Week"
- [ ] Show bar chart: Each team member + their lead count
- [ ] Show each user's capacity: "12/20" with progress bar
- [ ] Click bar → Filter to that user's leads
- [ ] Auto-refresh every 30 seconds

**Acceptance:**
- Dashboard shows: User A (12), User B (18), User C (15)
- Visual indicates User B near capacity (yellow/orange)

---

### Task 1.3: Unassigned Lead Escalation
**Story:** Supervisor Catches Unassigned Leads (Story 1.3)  
**Outcome:** Leads unassigned after 5 minutes auto-escalate to supervisor

#### Subtasks:

**1.3.1 Create Scheduled Job for Escalation**
- [ ] Create job that runs every 5 minutes
- [ ] Find leads where:
  - `assignedUserId` IS NULL
  - `createdAt` < (current time - 5 minutes)
  - `status` != "Assignment Failed"
- [ ] For each unassigned lead:
  - Get default supervisor ID from settings
  - Set lead's `assignedUserId` = supervisor
  - Set lead's `status` = "Assignment Failed"
  - Save lead

**Acceptance:**
- Lead created at 10:00 AM, still unassigned at 10:05 AM
- Job runs at 10:05 AM → Lead assigned to supervisor

---

**1.3.2 Create Escalation Task for Supervisor**
- [ ] When lead escalated, create Task:
  - Name: "🚨 URGENT: Unassigned Lead - [Name]"
  - Assigned to: Supervisor
  - Related to: Lead
  - Due: Immediate (current time)
  - Priority: "Urgent"
- [ ] Add note to lead timeline:
  - "Lead assignment failed. Possible reasons: No territory match, all agents full."

**Acceptance:**
- Supervisor sees urgent task appear in task list
- Task links directly to unassigned lead

---

**1.3.3 Build Assignment Failure Dashboard**
- [ ] Create widget: "⚠️ Assignment Failures"
- [ ] Show count of leads with status = "Assignment Failed"
- [ ] Click count → Opens filtered list showing:
  - Lead name
  - Created date/time
  - Failure reason
  - "Assign to..." dropdown (quick action)

**Acceptance:**
- Widget shows: "⚠️ Assignment Failures (2)"
- Click → See list with 2 failed leads and reasons

---

### Task 1.4: Assignment Transparency Dashboard
**Story:** Everyone Sees Assignment Transparency (Story 1.4)  
**Outcome:** Team sees fair distribution stats in real-time

#### Subtasks:

**1.4.1 Build Assignment Stats Widget**
- [ ] Create widget: "Assignment Stats Today"
- [ ] Calculate and display:
  - Total leads created today: COUNT where createdAt = today
  - Auto-assigned: COUNT where assignedUserId NOT NULL AND status != "Assignment Failed"
  - Failed: COUNT where status = "Assignment Failed"
  - Average assignment time: AVG(assignedAt - createdAt)
- [ ] Refresh data every 30 seconds

**Acceptance:**
- Widget shows: Total: 47, Auto-assigned: 45, Failed: 2, Avg time: 23 sec

---

**1.4.2 Build Leads by Channel Chart**
- [ ] Create bar chart widget: "Leads by Channel"
- [ ] Group leads created today by `leadChannel`
- [ ] Display count for each channel
- [ ] Click channel → Filter lead list to that channel

**Acceptance:**
- Chart shows: Web Form (20), Field Agent (15), Branch Walk-in (10), Social Media (2)

---

**1.4.3 Build Leads by Officer Chart**
- [ ] Create bar chart widget: "Leads by Officer"
- [ ] Show each Sales team member + count of leads assigned today
- [ ] Click officer → Filter to their leads

**Acceptance:**
- Chart shows: User A (12), User B (15), User C (20)

---

## WORKFLOW 2: Financial Calculation Flow

**User Story Source:** Epic 2 - Instant Financial Qualification Visibility  
**Business Value:** Instant eligibility determination without manual calculation

---

### Task 2.1: Auto-Calculate FOIR & DTI
**Story:** Officer Sees FOIR/DTI Automatically (Story 2.1)  
**Outcome:** FOIR/DTI calculate automatically when income/obligations/loan amount entered

#### Subtasks:

**2.1.1 Create Calculated Fields**
- [ ] Add read-only fields to Lead:
  - `foirRatio` (number, 2 decimals, display as %)
  - `dtiRatio` (number, 2 decimals, display as %)
- [ ] Add to detail view layout: Financial Summary panel
- [ ] Fields show as "—" if not yet calculated

**Acceptance:**
- New lead shows: FOIR: — , DTI: —
- After entering data: FOIR: 35.50%, DTI: 42.30%

---

**2.1.2 Implement FOIR Calculation Logic**
- [ ] Create background process that triggers before lead save
- [ ] Calculate FOIR:
  - Formula: `(monthlyObligations / monthlyIncome) * 100`
  - Handle division by zero: If income = 0, set FOIR = 0
  - Round to 2 decimals
- [ ] Store result in `foirRatio` field

**Test Cases:**
- Income: 100,000, Obligations: 30,000 → FOIR: 30.00%
- Income: 50,000, Obligations: 40,000 → FOIR: 80.00%
- Income: 0, Obligations: 10,000 → FOIR: 0.00%

**Acceptance:**
- Officer enters income & obligations → FOIR calculates on save

---

**2.1.3 Implement DTI Calculation Logic**
- [ ] Calculate proposed EMI (simplified):
  - Formula: `loanAmountRequested / loanTenureRequested`
  - If tenure = 0 or null, use default 12 months
- [ ] Calculate DTI:
  - Formula: `((monthlyObligations + proposedEMI) / monthlyIncome) * 100`
  - Handle division by zero
  - Round to 2 decimals
- [ ] Store result in `dtiRatio` field

**Test Cases:**
- Income: 100K, Obligations: 20K, Loan: 600K, Tenure: 60
  - Proposed EMI: 10K
  - DTI: (20K + 10K) / 100K * 100 = 30.00%

**Acceptance:**
- Officer enters loan amount & tenure → DTI calculates on save

---

**2.1.4 Build FOIR/DTI Display Component**
- [ ] Create Financial Summary panel on detail page
- [ ] Display format:
  ```
  Monthly Income:      Rs. 85,000
  Monthly Obligations: Rs. 25,000
  ─────────────────────────────────
  FOIR: 29.41% [progress bar]
  DTI:  35.29% [progress bar]
  ```
- [ ] Color-code progress bars:
  - Green: <40%
  - Yellow: 40-60%
  - Red: >60%
- [ ] Add tooltip on hover: Explain formula

**Acceptance:**
- Panel shows financial data with color-coded indicators
- Tooltip shows: "FOIR = (Obligations ÷ Income) × 100"

---

**2.1.5 Add Manual Recalculate Button**
- [ ] Add "⟳ Recalculate" button to Financial Summary panel
- [ ] Click button → Trigger recalculation of FOIR/DTI
- [ ] Show loading spinner during calculation
- [ ] Update display with new values

**Acceptance:**
- Officer changes income → Clicks recalculate → FOIR/DTI update

---

### Task 2.2: Auto-Set Eligibility Status
**Story:** Officer Sees Eligibility Status Badge (Story 2.2)  
**Outcome:** Clear badge shows Eligible/Pending/Not Eligible based on criteria

#### Subtasks:

**2.2.1 Implement Eligibility Status Logic**
- [ ] Create background process that runs after FOIR calculation
- [ ] Check conditions:
  - If FOIR = null OR income = null → "Pending Review"
  - If FOIR > 60% → "Not Eligible"
  - If income < 25,000 → "Not Eligible"
  - If creditScore < 600 (when available) → "Not Eligible"
  - If FOIR ≤ 40% AND income ≥ 50,000 → "Eligible"
  - Otherwise → "Pending Review"
- [ ] Set `eligibilityStatus` field

**Acceptance:**
- FOIR: 35%, Income: 60K → Status: "Eligible"
- FOIR: 70% → Status: "Not Eligible"
- FOIR: 50%, Income: 40K → Status: "Pending Review"

---

**2.2.2 Create Eligibility Badge Display**
- [ ] Add badge to top of lead detail page
- [ ] Badge styles:
  - ✓ ELIGIBLE (green background)
  - ⚠ PENDING REVIEW (orange background)
  - ✗ NOT ELIGIBLE (red background)
- [ ] Badge also appears in list view next to lead name
- [ ] Make badge clickable

**Acceptance:**
- Detail page shows prominent eligibility badge
- List view shows same badge next to each lead

---

**2.2.3 Build Eligibility Breakdown Modal**
- [ ] Click badge → Opens modal showing criteria breakdown:
  ```
  ✓ Income: Rs. 85,000 (≥ Rs. 50,000 required)
  ✓ FOIR: 29% (≤ 60% required)
  ⚠ Credit Score: Not yet checked
  
  Result: ELIGIBLE (pending credit check)
  ```
- [ ] Each criterion shows checkmark or X
- [ ] Show requirement thresholds

**Acceptance:**
- Click badge → Modal shows why lead is eligible/not eligible

---

### Task 2.3: High-Risk Lead Alerts
**Story:** Officer Gets Alerted for High-Risk Leads (Story 2.3)  
**Outcome:** Automatic task created when FOIR/DTI exceeds thresholds

#### Subtasks:

**2.3.1 Create High-Risk Detection Logic**
- [ ] After calculating FOIR/DTI, check thresholds:
  - If FOIR > 60% OR DTI > 70% → Trigger alert
- [ ] Create Task:
  - Name: "Review High FOIR/DTI Lead - [Name]"
  - Assigned to: Lead's assigned user
  - Priority: "High"
  - Due: Today
- [ ] Add note to lead timeline:
  - "⚠️ Auto-flagged: FOIR 68%, DTI 75%"

**Acceptance:**
- Lead saved with FOIR = 65% → Task created for officer
- Timeline shows alert with specific percentages

---

**2.3.2 Send Manager Email Notification**
- [ ] Get assigned user's manager (from team hierarchy)
- [ ] Send email:
  - Subject: "High-Risk Lead Alert: [Lead Name]"
  - Body: FOIR/DTI values, link to lead
  - Include customer info
- [ ] Log email send in timeline

**Acceptance:**
- High-risk lead saved → Manager receives email within 1 minute

---

**2.3.3 Show Warning Banner on Detail Page**
- [ ] If FOIR > 60%, show warning at top of detail page:
  ```
  ⚠️ HIGH RISK: FOIR is 68% (exceeds 60% threshold)
  Customer may struggle with repayment.
  [ Review with Manager ] [ Proceed Anyway ]
  ```
- [ ] Click "Review with Manager" → Creates task for manager
- [ ] Click "Proceed Anyway" → Adds override note to timeline

**Acceptance:**
- High-risk lead → Warning banner visible
- Actions work as expected

---

**2.3.4 Add Warning Indicator to List View**
- [ ] In lead list, show ⚠️ icon next to high-risk leads
- [ ] Color row background light red
- [ ] Hover tooltip: "High FOIR: 68%"

**Acceptance:**
- List view clearly shows high-risk leads with visual indicator

---

## WORKFLOW 3: Lead Scoring & Prioritization Flow

**User Story Source:** Epic 3 - Smart Lead Scoring & Prioritization  
**Business Value:** Automatic quality scoring helps officers prioritize high-conversion leads

---

### Task 3.1: Implement Lead Scoring Algorithm
**Story:** Officer Sees Lead Score at a Glance (Story 3.1)  
**Outcome:** Every lead has 0-100 score based on 5 factors

#### Subtasks:

**3.1.1 Create Lead Score Field**
- [ ] Add field to Lead: `leadScore` (integer, 0-100, read-only)
- [ ] Add to list view as sortable column
- [ ] Add to detail view in sidebar widget

**Acceptance:**
- New lead shows: Score: —
- After data entered: Score: 75

---

**3.1.2 Build Scoring Calculation Logic**
- [ ] Create process that runs after lead save
- [ ] Calculate score components:

**Income Level (20 points):**
- [ ] If income ≥ 100,000 → +20 pts
- [ ] Else if income ≥ 50,000 → +15 pts
- [ ] Else if income ≥ 30,000 → +10 pts
- [ ] Else → +0 pts

**Employment Type (15 points):**
- [ ] If "Salaried" or "Professional" → +15 pts
- [ ] If "Business Owner" → +12 pts
- [ ] If "Self-Employed" → +10 pts
- [ ] Else → +5 pts

**FOIR Ratio (20 points):**
- [ ] If FOIR < 30% → +20 pts
- [ ] Else if FOIR < 40% → +15 pts
- [ ] Else if FOIR < 60% → +10 pts
- [ ] Else → +0 pts

**Credit Score (30 points):**
- [ ] If creditScore = null → +15 pts (assume average)
- [ ] Else if creditScore ≥ 750 → +30 pts
- [ ] Else if creditScore ≥ 650 → +20 pts
- [ ] Else if creditScore ≥ 600 → +10 pts
- [ ] Else → +0 pts

**Document Completion (15 points):**
- [ ] Calculate % of verified documents
- [ ] If 100% → +15 pts
- [ ] Else if ≥80% → +12 pts
- [ ] Else if ≥50% → +8 pts
- [ ] Else → +0 pts

- [ ] Sum all components (max 100)
- [ ] Store in `leadScore` field

**Test Cases:**
- Income: 120K, Salaried, FOIR: 25%, Credit: 800, Docs: 100% → Score: 100
- Income: 60K, Self-Employed, FOIR: 35%, Credit: 700, Docs: 80% → Score: 72

**Acceptance:**
- Officer enters/updates data → Score recalculates automatically

---

**3.1.3 Build Score Badge for List View**
- [ ] In lead list, show score as colored badge:
  - [85] with green background if score ≥75
  - [62] with orange background if score 50-74
  - [38] with red background if score <50
- [ ] Make column sortable by score

**Acceptance:**
- List shows: "[85] 🔥" for hot lead, "[62] ⚡" for warm, "[38] ❄️" for cold

---

**3.1.4 Create Score Widget for Detail View**
- [ ] Add circular score indicator to sidebar:
  ```
       ╭───────╮
       │  85   │  🔥 HOT LEAD
       │ /100  │  
       ╰───────╯
  ```
- [ ] Color widget border based on score
- [ ] Update in real-time when score changes

**Acceptance:**
- Detail page shows prominent score widget
- Widget color matches score level

---

**3.1.5 Build Score Breakdown Tooltip**
- [ ] Click score widget → Show breakdown modal:
  ```
  Score Breakdown:
  ─────────────────
  Income:      20/20 ✓
  Employment:  15/15 ✓
  FOIR:        20/20 ✓
  Credit:      15/30 ⚠️ (not checked)
  Documents:   15/15 ✓
  ─────────────────
  Total:       85/100
  ```
- [ ] Each component shows points earned vs. possible
- [ ] Show icon: ✓ for full points, ⚠️ for partial, ✗ for zero

**Acceptance:**
- Click widget → Modal shows detailed scoring breakdown

---

### Task 3.2: Auto-Set Lead Temperature
**Story:** Officer Sees Lead Temperature Classification (Story 3.2)  
**Outcome:** Leads automatically tagged as Hot/Warm/Cold based on score

#### Subtasks:

**3.2.1 Implement Temperature Logic**
- [ ] After calculating score, set temperature:
  - If score ≥ 75 → `leadTemperature` = "Hot"
  - Else if score ≥ 50 → `leadTemperature` = "Warm"
  - Else → `leadTemperature` = "Cold"
- [ ] Temperature updates automatically with score

**Acceptance:**
- Score changes from 72 to 76 → Temperature changes from Warm to Hot

---

**3.2.2 Add Temperature Icons to List View**
- [ ] Display icon next to lead name:
  - 🔥 for Hot leads
  - ⚡ for Warm leads
  - ❄️ for Cold leads
- [ ] Icon color matches: Red, Orange, Blue

**Acceptance:**
- List shows temperature icon for each lead

---

**3.2.3 Build Temperature Filter Dashboard Widget**
- [ ] Create widget: "My Leads by Temperature"
- [ ] Show counts:
  ```
  🔥 Hot (8)   →  Contact within 1 hour
  ⚡ Warm (15) →  Contact today
  ❄️ Cold (12) →  Contact this week
  ```
- [ ] Click each → Filter lead list to that temperature
- [ ] Show recommended action timeframe

**Acceptance:**
- Dashboard shows temperature distribution
- Click "Hot (8)" → List filters to 8 hot leads

---

**3.2.4 Sort List by Temperature (Default)**
- [ ] Set default list sort: Temperature (Hot first), then Created Date
- [ ] Hot leads always appear at top
- [ ] Within same temperature, newest first

**Acceptance:**
- Open lead list → Hot leads at top, then Warm, then Cold

---

### Task 3.3: Hot Lead Instant Alerts
**Story:** Relationship Manager Gets Hot Lead Alerts (Story 3.3)  
**Outcome:** RMs get instant notification when hot lead (score ≥80) assigned

#### Subtasks:

**3.3.1 Create Hot Lead Detection**
- [ ] After calculating score, check conditions:
  - If score ≥ 80 AND status = "New" → Trigger alert
- [ ] Create notification:
  - Message: "🚨 Hot Lead Assigned: [Name] (Score: 85)"
  - Link to lead detail page
  - Mark as "Urgent"

**Acceptance:**
- Lead score becomes 82 → Notification created immediately

---

**3.3.2 Create Priority Contact Task**
- [ ] Create Task for assigned user:
  - Name: "Priority Contact - Hot Lead [Name]"
  - Due: Current time + 5 minutes
  - Priority: "Urgent"
  - Status: "Not Started"
- [ ] Task appears at top of task list

**Acceptance:**
- Hot lead assigned at 2:00 PM → Task due at 2:05 PM

---

**3.3.3 Set Lead Priority to High**
- [ ] Automatically set `priority` field = "High"
- [ ] High priority leads show with ⚡ icon in list
- [ ] Can be sorted/filtered by priority

**Acceptance:**
- Hot lead automatically marked as high priority

---

**3.3.4 Build Hot Leads Dashboard Widget**
- [ ] Create widget: "🔥 Hot Leads Awaiting Contact"
- [ ] Show count of hot leads where:
  - Temperature = "Hot"
  - Status = "New" or "Contacted"
  - Last contact > 1 hour ago
- [ ] List shows lead name, score, time since creation
- [ ] Click lead → Open detail page

**Acceptance:**
- Dashboard shows: "🔥 Hot Leads Awaiting Contact (3)"
- List shows 3 hot leads with time since assignment

---

### Task 3.4: Score Distribution Analytics
**Story:** Manager Reviews Score Distribution (Story 3.4)  
**Outcome:** Dashboard shows lead quality distribution

#### Subtasks:

**3.4.1 Build Score Distribution Chart**
- [ ] Create widget: "Lead Score Distribution"
- [ ] Horizontal bar chart with ranges:
  - 0-25 (Cold)
  - 26-50 (Cold)
  - 51-75 (Warm)
  - 76-100 (Hot)
- [ ] Show count for each range
- [ ] Click bar → Filter list to that range

**Acceptance:**
- Chart shows: 0-25 (12), 26-50 (8), 51-75 (16), 76-100 (22)

---

**3.4.2 Add Time Period Filter**
- [ ] Add dropdown to widget: "This Week" / "This Month" / "All Time"
- [ ] Chart updates based on selected period
- [ ] Default: "This Week"

**Acceptance:**
- Select "This Month" → Chart shows last 30 days distribution

---

**3.4.3 Show Conversion Rate by Score**
- [ ] Below chart, show conversion statistics:
  ```
  Conversion Rate by Score:
  76-100: 42% (18 converted / 43 total)
  51-75:  28% (12 converted / 43 total)
  0-50:   8% (3 converted / 37 total)
  ```
- [ ] Update weekly

**Acceptance:**
- Chart shows conversion rate for each score range

---

## WORKFLOW 4: Customer SMS Communication Flow

**User Story Source:** Epic 4 - Instant Customer Communication  
**Business Value:** Automatic customer acknowledgment, manual SMS capability

---

### Task 4.1: Welcome SMS Automation
**Story:** Customer Receives Welcome SMS Automatically (Story 4.1)  
**Outcome:** Customer gets instant SMS confirmation when lead created

#### Subtasks:

**4.1.1 Create SMS Sending Function**
- [ ] Build SMS service that integrates with Dialog/Mobitel API
- [ ] Function inputs:
  - Phone number
  - Message text
- [ ] Validate Sri Lankan phone format:
  - Accept: 077XXXXXXX, +94771234567
  - Convert 077... to +9477...
- [ ] Send POST request to SMS API
- [ ] Return success/failure result

**Acceptance:**
- Call function with 0771234567 → Sends SMS to +94771234567

---

**4.1.2 Implement Auto-Send on Lead Creation**
- [ ] Create process that triggers after lead saved
- [ ] Check conditions:
  - Lead is new (just created)
  - `phoneNumber` is not empty
  - `dataProcessingConsent` = true
- [ ] If conditions met:
  - Format message: "Dear [FirstName], Thank you for your interest. Ref: [LeadId]. We'll contact you within 15 minutes."
  - Send SMS using function from 4.1.1
- [ ] If conditions not met, skip (no error)

**Acceptance:**
- Lead created with phone + consent → SMS sent within 30 seconds
- Lead created without consent → No SMS sent, no error

---

**4.1.3 Log SMS to Timeline**
- [ ] After sending SMS, create note in lead timeline:
  - Type: "SMS Sent"
  - Message: "✓ SMS sent to +94771234567"
  - Timestamp: Send time
  - Status: "Delivered" (if API confirms)
- [ ] If SMS fails:
  - Message: "✗ SMS failed: Invalid number"
  - Status: "Failed"

**Acceptance:**
- Timeline shows: "📱 SMS sent to +94771234567" with timestamp
- Failed SMS shows error reason

---

**4.1.4 Add Retry Button for Failed SMS**
- [ ] If SMS failed, show "Retry" button in timeline entry
- [ ] Click retry → Resend SMS using same message
- [ ] Update timeline entry with new attempt

**Acceptance:**
- Failed SMS shows [ Retry ] button
- Click retry → SMS sends again

---

**4.1.5 Update Last Contact Date**
- [ ] After successful SMS send:
  - Set `lastContactDate` = current timestamp
  - Save lead
- [ ] This field used for follow-up tracking

**Acceptance:**
- SMS sent → Last contact date updates to current time

---

### Task 4.2: Manual SMS with Templates
**Story:** Officer Sends SMS from Lead Screen (Story 4.2)  
**Outcome:** Officers can send template-based SMS without leaving CRM

#### Subtasks:

**4.2.1 Create SMS Template Entity**
- [ ] Create entity: `SmsTemplate`
- [ ] Fields:
  - `name` (text) - "Welcome SMS"
  - `code` (text) - "welcome" (unique)
  - `body` (long text) - Message template
  - `variables` (multi-select) - Available placeholders
- [ ] Seed 4 templates:
  - Welcome SMS
  - Document Request
  - Follow-up Reminder
  - Qualified Congratulations

**Acceptance:**
- Admin can create/edit SMS templates
- Template list shows 4 default templates

---

**4.2.2 Build "Send SMS" Button & Modal**
- [ ] Add "📱 Send SMS" button to lead detail page
- [ ] Click button → Opens modal
- [ ] Modal shows:
  - To: [Phone number] [Lead name]
  - Template dropdown (select from templates)
  - Preview area (auto-updates)
  - Character count (160 = 1 SMS unit)
  - [ Cancel ] [ Send SMS ] buttons

**Acceptance:**
- Click "Send SMS" → Modal opens with template selector

---

**4.2.3 Implement Template Variable Replacement**
- [ ] When template selected, render preview:
  - Replace `{firstName}` with lead's first name
  - Replace `{leadId}` with lead ID
  - Replace `{productName}` with primary product
  - Replace `{portalLink}` with customer portal URL
- [ ] Preview updates in real-time
- [ ] Show character count (warn if >160)

**Acceptance:**
- Select "Document Request" template
- Preview shows: "Dear Rajesh, Documents needed: NIC, Salary slips..."

---

**4.2.4 Send Manual SMS**
- [ ] Click "Send SMS" button:
  - Validate phone number
  - Send SMS using function from 4.1.1
  - Show loading spinner
  - On success: Close modal, show "✓ SMS sent"
  - On failure: Show error, keep modal open
- [ ] Log to timeline like auto-SMS

**Acceptance:**
- Click "Send SMS" → SMS sends → Modal closes → Timeline updated

---

### Task 4.3: SMS History View
**Story:** Officer Sees SMS History (Story 4.3)  
**Outcome:** All SMS communications visible in timeline

#### Subtasks:

**4.3.1 Display SMS in Timeline**
- [ ] Timeline shows each SMS as entry:
  ```
  📱 SMS sent to +94771234567
     "Dear Rajesh, Thank you for..."
     Sent: Today at 10:35 AM
     Status: Delivered ✓
  ```
- [ ] Show first 50 chars of message, click to expand full text
- [ ] Show delivery status icon

**Acceptance:**
- Timeline shows all SMS communications with timestamps

---

**4.3.2 Add Timeline SMS Filter**
- [ ] Add filter button: "Show SMS Only"
- [ ] Click → Timeline shows only SMS entries
- [ ] Count badge: "SMS (5)"

**Acceptance:**
- Click "Show SMS Only" → Timeline filters to SMS entries

---

**4.3.3 Show Delivery Status**
- [ ] For each SMS entry, show status:
  - ✓ Delivered (green)
  - ⏳ Sent (gray)
  - ✗ Failed (red)
- [ ] If API supports delivery webhook, update status when received

**Acceptance:**
- SMS entry shows current delivery status

---

## WORKFLOW 5: Auto-Qualification Flow

**User Story Source:** Epic 5 - Automatic Qualification & Status Updates  
**Business Value:** Automatic qualification when criteria met, transparent rejections

---

### Task 5.1: Automatic Lead Qualification
**Story:** Lead Qualifies Automatically (Story 5.1)  
**Outcome:** Status changes to "Qualified" when all criteria met

#### Subtasks:

**5.1.1 Create Document Completion Checker**
- [ ] Build function to calculate document completion:
  - Get all LeadDocument records for lead
  - Count documents where `verificationStatus` = "Verified"
  - Calculate percentage: (verified / total) * 100
  - Return percentage
- [ ] Store result in `documentCompletionPercentage` field

**Acceptance:**
- Lead has 8 documents, 5 verified → Completion: 62.5%

---

**5.1.2 Build Qualification Criteria Checker**
- [ ] Create process that runs after lead save
- [ ] Check ALL conditions:
  - `documentCompletionPercentage` = 100
  - `creditScore` is not null
  - `leadScore` ≥ 60
  - `foirRatio` ≤ 60
- [ ] If ALL true → Trigger qualification
- [ ] If ANY false → Skip

**Acceptance:**
- All criteria met → Qualification triggered
- Any criterion missing → No qualification

---

**5.1.3 Execute Qualification Actions**
- [ ] When qualification triggered:
  - Set `status` = "Qualified"
  - Set `eligibilityStatus` = "Eligible"
  - Set `qualifiedDate` = current timestamp
  - Save lead

**Acceptance:**
- Criteria met → Status changes to "Qualified"

---

**5.1.4 Create QualificationCheck Record**
- [ ] Create new record in QualificationCheck entity:
  - `leadId` = current lead
  - `checkDate` = current timestamp
  - `foirCalculated` = lead's FOIR
  - `dtiCalculated` = lead's DTI
  - `leadScoreAtCheck` = lead's score
  - `eligibilityResult` = "Eligible"
- [ ] This creates audit trail of qualification

**Acceptance:**
- QualificationCheck record created with all metrics

---

**5.1.5 Create RM Task**
- [ ] Create Task:
  - Name: "Present Offer - [Lead Name]"
  - Assigned to: Relationship Manager (from team)
  - Related to: Lead
  - Due: Tomorrow (next business day)
  - Priority: "High"

**Acceptance:**
- RM sees new task in their list

---

**5.1.6 Send Congratulations SMS**
- [ ] Send SMS to customer:
  - Template: "Congratulations! You're qualified for [Product]. Our RM will contact you soon."
  - Log to timeline

**Acceptance:**
- Customer receives congratulations SMS

---

**5.1.7 Add Visual Confirmation**
- [ ] Update detail page:
  - Status badge changes to "✓ Qualified" (green)
  - Show subtle confetti animation (optional)
  - Add timeline entry: "🎉 Lead Auto-Qualified"
- [ ] Show criteria checkmarks:
  ```
  ✓ Documents: 100% complete
  ✓ Credit Score: 720 (Good)
  ✓ Lead Score: 75 (Warm)
  ✓ FOIR: 35% (Acceptable)
  
  Result: QUALIFIED
  ```

**Acceptance:**
- Detail page shows qualification confirmation

---

### Task 5.2: Transparent Rejection Handling
**Story:** Lead Rejection is Transparent (Story 5.2)  
**Outcome:** Clear reasons shown when lead doesn't qualify

#### Subtasks:

**5.2.1 Build Rejection Reason Generator**
- [ ] Create function that checks rejection reasons:
  - If `foirRatio` > 60 → Add "FOIR too high (XX%, max 60%)"
  - If `monthlyIncome` < 25000 → Add "Income below minimum"
  - If `creditScore` < 600 → Add "Credit score too low"
  - If `leadScore` < 60 → Add "Overall score insufficient"
- [ ] Return array of reasons

**Acceptance:**
- FOIR=68%, Income=20K → Returns 2 reasons

---

**5.2.2 Auto-Reject When Criteria Fail**
- [ ] After lead save, if score < 60 OR FOIR > 60:
  - Set `eligibilityStatus` = "Not Eligible"
  - Generate rejection reasons
  - Store in `rejectionReason` field
  - Save lead

**Acceptance:**
- Score=52 → Status becomes "Not Eligible"

---

**5.2.3 Log Rejection to Timeline**
- [ ] Create timeline note:
  ```
  Not qualified:
  • FOIR too high (68%, max 60%)
  • Overall score insufficient (52, min 60)
  ```
- [ ] Include timestamp

**Acceptance:**
- Timeline shows rejection with specific reasons

---

**5.2.4 Create Alternative Discussion Task**
- [ ] Create Task for assigned officer:
  - Name: "Discuss Alternatives - [Lead Name]"
  - Due: Today
  - Note: Rejection reasons included

**Acceptance:**
- Officer receives task to discuss alternatives

---

**5.2.5 Build Rejection Reasons Display**
- [ ] Detail page shows expandable "Not Eligible" section:
  ```
  ✗ NOT ELIGIBLE
  
  Reasons:
  • FOIR: 68% (exceeds 60% threshold)
    Current obligations too high for requested loan
  
  • Lead Score: 52 (below 60 minimum)
    Contributing factors:
    - Income: Rs. 45,000 (below ideal Rs. 50,000)
    - Credit Score: 620 (below ideal 650)
  
  Options:
  [ Review Smaller Loan ] [ Request Manager Override ]
  ```
- [ ] Show detailed explanation for each reason
- [ ] Provide action buttons

**Acceptance:**
- Not eligible leads show clear, actionable rejection info

---

### Task 5.3: Manager Override Capability
**Story:** Manager Can Override Qualification (Story 5.3)  
**Outcome:** Managers can manually qualify leads with documented reason

#### Subtasks:

**5.3.1 Add Override Fields to Lead**
- [ ] Add fields:
  - `manualOverride` (checkbox, default false)
  - `overrideReason` (long text)
  - `overrideByUser` (user link)
  - `overrideDate` (datetime)

**Acceptance:**
- Fields available on lead record

---

**5.3.2 Build Override Button (Managers Only)**
- [ ] Add "Override Qualification" button to detail page
- [ ] Show only if:
  - Current user is Manager role
  - Lead eligibility status = "Not Eligible" OR "Pending Review"
- [ ] Hide button for Field Agents and Branch Officers

**Acceptance:**
- Manager sees override button on not-eligible lead
- Officer doesn't see button

---

**5.3.3 Create Override Modal**
- [ ] Click "Override Qualification" → Opens modal:
  ```
  Manual Qualification Override
  
  Current Status: Not Eligible (Score: 55)
  
  Reason for Override:
  [Textarea - required, min 20 chars]
  
  [Cancel]  [Approve Override]
  ```
- [ ] Require reason (cannot be empty)

**Acceptance:**
- Modal opens with current status shown
- Cannot submit without reason

---

**5.3.4 Process Override**
- [ ] Click "Approve Override":
  - Set `manualOverride` = true
  - Set `overrideReason` = entered text
  - Set `overrideByUser` = current user
  - Set `overrideDate` = current timestamp
  - Set `status` = "Qualified"
  - Set `eligibilityStatus` = "Eligible"
  - Save lead
- [ ] Add timeline entry:
  - "Manual override by [Manager Name]: [Reason]"

**Acceptance:**
- After override, lead status = "Qualified"
- Timeline shows override with reason

---

**5.3.5 Show Override Badge**
- [ ] On detail page, if `manualOverride` = true:
  - Show badge: "⚠️ Manager Override"
  - Badge links to override details modal
  - Modal shows: Who, when, why
- [ ] Override cannot be undone (audit trail)

**Acceptance:**
- Override badge visible on manually qualified leads

---

## WORKFLOW 6: Credit Check Integration Flow

**User Story Source:** Epic 8 - Credit Check Integration UI  
**Business Value:** One-click credit reports, automatic score updates

---

### Task 6.1: One-Click Credit Check Request
**Story:** Officer Requests Credit Check with One Click (Story 8.1)  
**Outcome:** Credit report requested and received within CRM

#### Subtasks:

**6.1.1 Build CRIB API Integration**
- [ ] Create CRIB service class
- [ ] Configure API credentials (from settings)
- [ ] Function: `requestCreditReport(nicNumber, fullName)`
- [ ] API call:
  - Endpoint: CRIB credit report API
  - Auth: Username/password (from config)
  - Body: NIC number, full name, report type
- [ ] Parse response:
  - Credit score
  - Number of loan accounts
  - Total outstanding debt
  - Number of inquiries
  - Report PDF (base64)
- [ ] Return structured data

**Acceptance:**
- Call function → Returns credit report data from CRIB

---

**6.1.2 Add "Request Credit Check" Button**
- [ ] Add button to lead detail page
- [ ] Enable button only if:
  - `creditBureauConsent` = true
  - `nicNumber` is not empty
  - No recent credit report (<7 days old)
- [ ] If consent missing, show "⚠️ Consent Required" (disabled)

**Acceptance:**
- Button enabled when criteria met
- Disabled with tooltip when criteria not met

---

**6.1.3 Build Credit Check Confirmation Modal**
- [ ] Click "Request Credit Check" → Opens modal:
  ```
  Request Credit Report
  
  Provider: CRIB (Credit Information Bureau)
  Customer: Rajesh Kumar
  NIC: 901234567V
  
  ⚠️ This will deduct 1 credit from your account
  ✓ Customer consent obtained (Jan 27, 2024)
  
  [Cancel]  [Request Report]
  ```
- [ ] Show cost warning
- [ ] Show consent confirmation

**Acceptance:**
- Modal shows customer details and warnings

---

**6.1.4 Process Credit Check Request**
- [ ] Click "Request Report":
  - Show loading spinner: "⟳ Requesting Credit Report..."
  - Call CRIB API function
  - Wait for response (up to 30 seconds)
  - If success → Create CreditReport record
  - If failure → Show error message

**Acceptance:**
- Request succeeds → Report received within 30 seconds

---

**6.1.5 Create CreditReport Record**
- [ ] Create record in CreditReport entity:
  - `leadId` = current lead
  - `reportProvider` = "CRIB"
  - `reportDate` = current timestamp
  - `creditScore` = from API response
  - `numberOfLoanAccounts` = from response
  - `totalOutstandingDebt` = from response
  - `numberOfInquiries` = from response
  - `reportStatus` = "Completed"
- [ ] Save PDF as attachment to report

**Acceptance:**
- CreditReport record created with all data

---

**6.1.6 Update Lead Credit Score**
- [ ] After report received:
  - Set lead's `creditScore` = report's credit score
  - Save lead
- [ ] This triggers:
  - Lead score recalculation (includes credit score)
  - Eligibility status recalculation
  - Possible auto-qualification

**Acceptance:**
- Lead credit score field updates to CRIB score
- Lead score recalculates

---

**6.1.7 Add Timeline Entry**
- [ ] Add note to timeline:
  - "Credit report received - Score: 720"
  - Link to view full report

**Acceptance:**
- Timeline shows credit check completion

---

### Task 6.2: In-CRM Credit Report Viewer
**Story:** Officer Views Credit Report in CRM (Story 8.2)  
**Outcome:** Credit report details visible without leaving CRM

#### Subtasks:

**6.2.1 Build Credit Report Summary Panel**
- [ ] After credit check completes, show panel on detail page:
  ```
  CREDIT REPORT (CRIB)
  Received: Jan 27, 2024 at 2:45 PM
  
  Credit Score: 720 (Good)
  ●●●●●●●○○○
  
  Active Loan Accounts: 2
  Total Outstanding Debt: Rs. 450,000
  Recent Inquiries (6 months): 3
  
  [ View Full Report PDF ]
  ```
- [ ] Color-code score:
  - Green: ≥700 (Good)
  - Yellow: 600-699 (Fair)
  - Red: <600 (Poor)

**Acceptance:**
- Panel shows credit report summary

---

**6.2.2 Add Credit Score Indicator**
- [ ] Show credit score with visual indicator:
  - Progress bar (0-850 range)
  - Color based on score level
  - Label: "Excellent" / "Good" / "Fair" / "Poor"

**Acceptance:**
- Score shows with visual indicator

---

**6.2.3 Build PDF Viewer Link**
- [ ] "View Full Report PDF" button
- [ ] Click → Opens PDF in new tab
- [ ] PDF retrieved from CreditReport attachment

**Acceptance:**
- Click button → PDF opens in new browser tab

---

**6.2.4 Show Warning for Low Scores**
- [ ] If credit score < 600:
  - Show warning banner: "⚠️ Low credit score - high risk"
  - Add to high-risk alert workflow
- [ ] If score > 750:
  - Show checkmark: "✓ Excellent credit history"

**Acceptance:**
- Low score shows warning
- High score shows positive indicator

---

### Task 6.3: Credit Check History
**Story:** Officer Sees Credit Check History (Story 8.3)  
**Outcome:** All credit checks for customer visible in chronological order

#### Subtasks:

**6.3.1 Build Credit History Section**
- [ ] Add "Credit History" section to detail page
- [ ] Show all CreditReport records for this lead:
  ```
  Credit Check History
  
  Report #1 - Jan 27, 2024
  Score: 720 | Requested by: You
  [ View Report ]
  
  Report #2 - Sep 15, 2023
  Score: 695 | Requested by: Kumar P.
  (From previous loan application)
  [ View Report ]
  ```
- [ ] Order: Newest first
- [ ] Show who requested each report

**Acceptance:**
- Section shows all credit reports chronologically

---

**6.3.2 Highlight Most Recent Report**
- [ ] Latest report shown with emphasis:
  - Larger font
  - Green highlight border
  - "CURRENT" badge
- [ ] Older reports collapsed by default

**Acceptance:**
- Most recent report prominently displayed

---

**6.3.3 Link to Previous Lead Records**
- [ ] If credit report from previous lead (same customer):
  - Show link: "(From previous loan application)"
  - Click → Opens previous lead record
  - Requires matching by NIC or customer entity

**Acceptance:**
- Previous loan applications linked

---

## IMPLEMENTATION SUMMARY

**6 Major Workflows, 43 Development Tasks**

| Workflow | Tasks | Focus |
|----------|-------|-------|
| 1. Auto-Assignment | 14 subtasks | Territory routing, round-robin, escalation, dashboards |
| 2. Financial Calculation | 15 subtasks | FOIR/DTI auto-calc, eligibility status, high-risk alerts |
| 3. Lead Scoring | 14 subtasks | 100-point algorithm, temperature, hot lead alerts, analytics |
| 4. SMS Communication | 11 subtasks | Auto-welcome, templates, manual send, history |
| 5. Auto-Qualification | 16 subtasks | Criteria checking, qualification, rejection, override |
| 6. Credit Check | 13 subtasks | CRIB integration, one-click request, report viewer, history |

**Each Task Includes:**
✅ Clear outcome statement  
✅ Step-by-step subtasks  
✅ Acceptance criteria  
✅ Test cases where applicable  
✅ UI behavior descriptions  
✅ Edge case handling

**Next Steps:**
1. Assign tasks to developers
2. Set up development environment
3. Configure API integrations (SMS, CRIB)
4. Build in sprints following priority order
5. Test each workflow end-to-end

*Ready for sprint planning and developer assignment*
