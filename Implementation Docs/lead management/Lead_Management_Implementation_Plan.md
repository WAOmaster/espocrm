# Financial Lead Management - Implementation Plan
**Epic:** Lead Management Enhancement for Sri Lankan Financial Services  
**Platform:** EspoCRM with Advanced Pack  
**Target:** Microfinance, Banks, Leasing Companies (Sri Lanka)

---

## 1. PROCESS FLOW DIAGRAM

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                          LEAD CAPTURE (Multi-Channel)                        │
│  Web Form │ Mobile App │ Branch Walk-in │ Field Agent │ Partner/DSA │ Call  │
└────────┬─────────────────────────────────────────────────────────────────────┘
         │
         ▼
┌────────────────────────┐
│    NEW LEAD CREATED    │ ◄── Auto: Welcome SMS + Ref Number
│  Status: New           │ ◄── Auto: Duplicate Detection (NIC/Mobile)
│  Temperature: Warm     │ ◄── Auto: Territory-based Assignment
└──────────┬─────────────┘
           │
           ▼
┌──────────────────────────────┐
│  CONTACT ATTEMPTED           │ ◄── Manual: First call/visit attempt
│  Status: Contact Attempted   │ ◄── Auto: Log attempt in timeline
│  SLA: < 15 minutes          │ ◄── Auto: Escalation if 24hrs no contact
└──────────┬───────────────────┘
           │
           ▼
┌──────────────────────────────┐
│  CONTACTED (First Touch)     │ ◄── Manual: Conversation logged
│  Status: Contacted           │ ◄── Auto: Create initial assessment task
│  Temperature: Hot/Warm/Cold  │ ◄── Auto: Send follow-up SMS
└──────────┬───────────────────┘
           │
           ▼
┌──────────────────────────────┐
│  INFORMATION GATHERING       │ ◄── Manual: Collect employment, income data
│  Status: Info Gathering      │ ◄── Manual: Update financial fields
│  Fields: 40+ custom fields   │ ◄── Auto: Request document list via SMS
└──────────┬───────────────────┘
           │
           ▼
┌──────────────────────────────┐
│  DOCUMENT COLLECTION         │ ◄── Manual: Upload docs (NIC, Salary, Bank)
│  Status: Doc Collection      │ ◄── Auto: OCR extraction & validation
│  Checklist: 8-12 documents   │ ◄── Auto: Document completion % tracking
└──────────┬───────────────────┘
           │
           ├─── All Docs Complete?
           │
           ▼
┌──────────────────────────────┐
│  CREDIT CHECK                │ ◄── Auto: CRIB API call (with consent)
│  Status: Credit Check        │ ◄── Auto: Create CreditReport entity
│  CRIB Integration            │ ◄── Auto: Extract credit score
└──────────┬───────────────────┘
           │
           ▼
┌──────────────────────────────┐
│  QUALIFICATION IN PROGRESS   │ ◄── Auto: Calculate FOIR/DTI (Formula)
│  Status: Qualification       │ ◄── Auto: Run lead scoring algorithm
│  Auto-calculation            │ ◄── Auto: Product matching logic
└──────────┬───────────────────┘
           │
           ├─── Score > 60?
           │
           ▼                    ▼
    ┌─────────────┐      ┌──────────────┐
    │  QUALIFIED  │      │  NOT QUALIFIED│
    │  Score: 60+ │      │  Score: < 60  │
    └──────┬──────┘      └──────┬────────┘
           │                     │
           ▼                     ▼
┌──────────────────────┐  ┌──────────────────┐
│ AWAITING DECISION    │  │  NURTURING       │
│ Status: Awaiting     │  │  Status: Nurture │
│ RM: Offer presented  │  │  Monthly touch   │
└──────┬───────────────┘  └──────────────────┘
       │
       ├─── Customer Decision?
       │
       ▼                     ▼
┌──────────────────┐  ┌──────────────┐
│ CONVERTED        │  │  LOST        │
│ Create Opp       │  │  Log Reason  │
│ → Loan Process   │  │  → Nurture   │
└──────────────────┘  └──────────────┘
```

### Workflow Actors & Responsibilities

**Field Agent** (Stages: New → Contacted)
- First contact < 15 min, Physical verification, Initial document collection
- Tools: Mobile CRM, GPS check-in, Camera

**Branch Officer** (Stages: Doc Collection → Qualification)
- Complete document checklist, Trigger credit checks, Financial ratio calculation
- Tools: Desktop CRM, Scanner, CRIB access

**Relationship Manager** (Stages: Qualified → Converted)
- Present offers, Negotiate terms, Close application
- Tools: CRM Dashboard, Loan Calculator, Proposal Generator

### Key Automation Rules (EspoCRM Advanced Pack BPM)

1. Lead Created → Auto-assign by territory (Round-Robin/Least-Busy)
2. Lead Created → Send welcome SMS (Dialog/Mobitel API)
3. Lead Created → Duplicate check (Formula: NIC/Mobile match)
4. No Contact 24hrs → Create escalation task for supervisor
5. Docs Complete → Trigger CRIB credit check (API call)
6. Credit Report Received → Auto-calculate FOIR/DTI (Formula)
7. Lead Score > 60 → Status = Qualified, Notify RM
8. No Decision 7 days → Send follow-up email + SMS
9. Converted → Create Opportunity record, Start loan workflow
10. Lost → Add to nurture campaign (email drip)

---

## 2. CLICKUP TASK BREAKDOWN (16 Tasks, 80 Story Points)

### TASK 1: Entity & Field Structure Setup ⭐
**Story Points:** 8 | **Priority:** Critical | **Type:** Backend

Create FinancialLead entity with 40+ custom fields across 7 categories:
- Financial fields (employment, income, obligations, banking)
- Personal/identity fields (NIC with encryption, DOB, marital status)
- Address fields (current, permanent, residence type)
- Loan interest fields (products, amount, tenure, purpose)
- Qualification fields (temperature, score, FOIR, DTI, eligibility)
- Channel fields (lead source, referrals, UTM params)
- Compliance fields (KYC, consents, PDPA)

**Deliverable:** FinancialLead entity configured in Entity Manager

---

### TASK 2: Supporting Entity Creation ⭐
**Story Points:** 5 | **Priority:** High | **Type:** Backend

Create 6 related entities:
- CoApplicant (1:Many relationship)
- Guarantor (1:Many relationship)
- LeadDocument (1:Many, with verification workflow)
- CreditReport (1:1, from CRIB API)
- QualificationCheck (1:1, auto-generated)
- LoanProduct (product catalog)

**Deliverable:** Entity relationships established

---

### TASK 3: Formula & Calculation Configuration ⭐
**Story Points:** 5 | **Priority:** High | **Type:** Backend

Implement 5 calculated fields:
- FOIR calculation: `(monthlyObligations / monthlyIncome) * 100`
- DTI calculation: `((obligations + loanEMI) / income) * 100`
- Lead scoring algorithm (0-100 based on income, employment, FOIR, credit score, docs)
- Duplicate detection (NIC/mobile match)
- Document completion percentage

**Deliverable:** Real-time formula calculations working

---

### TASK 4: UI Layout & View Configuration
**Story Points:** 8 | **Priority:** High | **Type:** Frontend

Configure 5 optimized views:
- Detail view (7 collapsible panels)
- List view (6 columns + 4 custom filters + color coding)
- Kanban board (12 stages, drag-and-drop)
- Create/Edit form (tabbed interface, conditional fields)
- Mobile-responsive layout (single column, quick actions)

**Deliverable:** Professional UX for all device types

---

### TASK 5: BPM Workflow - Lead Auto-Assignment ⭐
**Story Points:** 3 | **Priority:** Critical | **Type:** Workflow

Configure 4 assignment rules:
- Territory-based (field agents by district)
- Round-robin (web leads, exclude overloaded officers)
- Product specialist (home/business loans)
- Escalation (unassigned after 5 min)

**Deliverable:** BPM flowchart with assignment automation

---

### TASK 6: BPM Workflow - Lead Nurturing & Notifications ⭐
**Story Points:** 5 | **Priority:** High | **Type:** Workflow

Implement 7 automated workflows:
- Welcome SMS on creation
- Initial contact task (due in 15 min)
- 24hr no-contact escalation
- Document request SMS
- Credit check notification
- Qualification success (SMS + RM task)
- Lost lead nurture campaign

**Deliverable:** SMS/email integration active

---

### TASK 7: BPM Workflow - Financial Calculations
**Story Points:** 3 | **Priority:** High | **Type:** Workflow

Automate 3 calculation triggers:
- FOIR/DTI recalculation on income change
- Auto-run qualification when docs + credit complete
- Product matching logic (amount + FOIR criteria)

**Deliverable:** Financial calculations automated

---

### TASK 8: Integration - CRIB Credit Bureau API ⭐
**Story Points:** 8 | **Priority:** High | **Type:** API Integration

Integrate CRIB for credit checks:
- API credential configuration (SSL/TLS)
- Manual + automatic trigger (on consent)
- Response parsing (score, accounts, debt)
- Error handling (invalid NIC, timeouts)
- Lead score update on credit report

**Deliverable:** CRIB integration functional (requires credentials)

---

### TASK 9: Integration - SMS Gateway (Dialog/Mobitel) ⭐
**Story Points:** 5 | **Priority:** High | **Type:** API Integration

Sri Lankan SMS gateway integration:
- Provider configuration (Dialog/Mobitel)
- Template manager (4 templates)
- sendSMS() function with consent check
- Delivery status tracking
- Activity timeline logging

**Deliverable:** SMS automation with 4 templates

---

### TASK 10: Dashboard & Reports Configuration
**Story Points:** 5 | **Priority:** Medium | **Type:** Analytics

Create 3 role-specific dashboards:
- Loan Officer: Kanban, follow-ups, hot leads, conversion rate
- Manager: Team pipeline, officer performance, response time
- Executive: Total leads, conversion, days to close, revenue potential

Plus 4 standard reports with filters

**Deliverable:** Analytics dashboards + reports

---

### TASK 11: Role-Based Access Control (RBAC) ⭐
**Story Points:** 3 | **Priority:** Critical | **Type:** Security

Configure 4 user roles:
- Field Agent (team access, no sensitive fields)
- Branch Officer (team access, view credit scores)
- Relationship Manager (all access, full visibility)
- Supervisor/Manager (admin-level permissions)

Field-level encryption for NIC, bank account, passport

**Deliverable:** RBAC configured with encryption

---

### TASK 12: Document Management & Checklist
**Story Points:** 5 | **Priority:** High | **Type:** Feature

Build document system:
- 8 document types (NIC, salary slips, bank statements, etc.)
- Multi-file upload UI (drag-and-drop)
- Checklist panel (completion percentage)
- Verification workflow (approve/reject)
- Optional: OCR integration (Google Vision/Tesseract)

**Deliverable:** Document checklist operational

---

### TASK 13: Duplicate Detection & Merge
**Story Points:** 3 | **Priority:** Medium | **Type:** Feature

Implement duplicate detection:
- Formula-based detection (NIC/phone/email match)
- Auto-flag on lead creation
- Duplicate review dashboard
- Lead merge functionality (preserve history)

**Deliverable:** Duplicate detection + merge

---

### TASK 14: Mobile Optimization & Field App
**Story Points:** 8 | **Priority:** Medium | **Type:** Mobile

Optimize for field agents:
- Mobile portal layout (single column, large buttons)
- Offline mode (PWA, sync on reconnect)
- GPS check-in (500m radius validation)
- Photo capture (camera integration)
- Quick actions (call, SMS, WhatsApp, directions)

**Deliverable:** Mobile-optimized CRM with offline support

---

### TASK 15: Testing, Documentation & Training ⭐
**Story Points:** 5 | **Priority:** High | **Type:** QA

Comprehensive testing:
- Unit testing (formulas, validations, relationships)
- Workflow testing (10 BPM flows end-to-end)
- Integration testing (CRIB, SMS, OCR)
- UAT (20 sample leads, 5 users, sign-off)
- User documentation (4 PDFs + 1 video)
- Training sessions (4 sessions, 2-4 hrs each)

**Deliverable:** Tested system with trained users

---

### TASK 16: Production Deployment & Go-Live ⭐
**Story Points:** 3 | **Priority:** Critical | **Type:** Deployment

Production deployment:
- Environment setup (PostgreSQL on GCP, SSL)
- Data migration (from legacy system)
- Go-live checklist (workflows, integrations, permissions)
- Monitoring (uptime, errors, backups)
- Support plan (daily check-ins week 1, on-call weeks 2-4)

**Deliverable:** Live production system

---

## 3. IMPLEMENTATION SUMMARY

**Timeline:** 12-14 weeks (2-person team)  
**Critical Path:** Tasks 1 → 5 → 6 → 15 → 16  
**Total Effort:** 80 story points

### Prerequisites
- EspoCRM Advanced Pack license (for BPM workflows)
- CRIB API credentials (for credit bureau integration)
- Dialog/Mobitel SMS API credentials
- PostgreSQL database with encryption support
- GCP environment (Cloud SQL, Cloud Run)

### Key Success Metrics
- 95% lead response time < 15 minutes
- 85% lead score accuracy
- 28% overall conversion rate
- 100% document completion for qualified leads
- Zero data security incidents

### Risk Mitigation
1. Test CRIB API in sandbox before production
2. Start with manual SMS, enable automation after validation
3. Pilot with 5 users before full rollout
4. Daily check-ins during first week post-launch

### EspoCRM-Specific Capabilities Used
✅ Entity Manager (custom fields, relationships)  
✅ Formula Engine (FOIR, DTI, lead scoring)  
✅ BPM Workflows (10 automated processes)  
✅ Layout Manager (custom views, panels)  
✅ Role-Based Access Control  
✅ Advanced Pack features (workflows, campaigns)  
✅ Webhooks (SMS, CRIB API integration)  
✅ Portal (mobile optimization)

---

## 4. ENTITY STRUCTURE REFERENCE

```
FinancialLead (Main Entity - extends Lead)
    ├── CoApplicant (1:Many)
    │   └── Fields: name, relationship, NIC, income
    ├── Guarantor (1:Many)
    │   └── Fields: name, NIC, assets
    ├── LeadDocument (1:Many)
    │   └── Fields: type, file, verificationStatus
    ├── CreditReport (1:1)
    │   └── Fields: score, accounts, debt, CRIB report
    ├── QualificationCheck (1:1)
    │   └── Fields: FOIR, DTI, eligibility, matchedProducts
    └── LoanProduct (Many:1)
        └── Fields: name, minAmount, maxAmount, rate
```

**Field Categories in FinancialLead:**
- Employment: 5 fields (type, employer, designation, income, obligations)
- Personal: 7 fields (DOB, gender, marital status, dependents, NIC, passport, license)
- Address: 6 fields (current, permanent, residence type, years)
- Loan Interest: 6 fields (products, amount, tenure, purpose, asset)
- Qualification: 7 fields (temperature, score, FOIR, DTI, credit score, eligibility)
- Channel: 8 fields (source, agent, campaign, UTM params, contact tracking)
- Compliance: 5 fields (KYC status, consents, PDPA)

**Total:** 44 custom fields + default Lead fields

---

*Generated for EspoCRM implementation - Sri Lankan Financial Services CRM*  
*All tasks achievable with EspoCRM + Advanced Pack*
