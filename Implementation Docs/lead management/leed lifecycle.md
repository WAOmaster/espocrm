Looking at your images, I'll redesign the complete lead lifecycle workflow for Siyapatha Finance, incorporating all the status transitions and automation triggers.

## Lead Lifecycle Workflow - Siyapatha Finance

### Status Definitions

**Active States:**
- **New** - Form submission/inquiry received, awaiting validation
- **Validated** - Auto-validation passed (phone format, NIC, basic checks)
- **Assigned** - Assigned to field agent/relationship officer
- **Contacted** - First contact attempt made
- **Verified** - Interest confirmed, basic details verified
- **Nurturing** - Not ready now, scheduled for future follow-up
- **Qualified** - Passed financial qualification, ready for opportunity creation

**Terminal States:**
- **Converted** - Successfully converted to Opportunity (auto-closed)
- **Unresponsive** - No response after multiple attempts (closed)
- **Not Interested** - Customer explicitly declined (closed)
- **Disqualified** - Failed eligibility/compliance checks (closed)
- **Duplicate** - Duplicate lead identified (closed, merged)

### End-to-End Workflow

```
┌─────────────────────────────────────────────────────────────────┐
│ LEAD CAPTURE STAGE                                              │
└─────────────────────────────────────────────────────────────────┘
    │
    ▼
[New] ──Auto Validation──> [Validated] ──Assignment Rule──> [Assigned]
    │                           │                                │
    │ Invalid data              │ Duplicate found               │ No contact 24h
    ▼                           ▼                                ▼
[Disqualified]             [Duplicate]                    [Unresponsive]
    │                           │                                │
    └───────────────────────────┴────────────────────────────────┘
                                │
                                ▼
                            [Closed]

┌─────────────────────────────────────────────────────────────────┐
│ CONTACT & VERIFICATION STAGE                                    │
└─────────────────────────────────────────────────────────────────┘
    │
[Assigned] ──First Contact──> [Contacted] ──Interest Confirmed──> [Verified]
    │                              │                                  │
    │ 3 attempts, no response      │ Not interested                   │
    ▼                              ▼                                  │
[Unresponsive] ────────────> [Not Interested]                        │
                                                                      │
┌─────────────────────────────────────────────────────────────────┐
│ QUALIFICATION STAGE                                             │
└─────────────────────────────────────────────────────────────────┘
    │
[Verified] ──Qualification Call──> [Decision Point]
                                         │
                     ┌───────────────────┼───────────────────┐
                     │                   │                   │
                     ▼                   ▼                   ▼
               [Qualified]         [Nurturing]        [Disqualified]
                     │                   │                   │
                     │            Defer/Not Ready      Failed criteria
                     │                   │                   │
                     │            7/15/30 days              │
                     │                   │                   │
                     │                   ▼                   ▼
                     │            Re-engagement         [Closed]
                     │                   │
                     │                   └──────> [Contacted] (retry)
                     │
┌─────────────────────────────────────────────────────────────────┐
│ CONVERSION STAGE                                                │
└─────────────────────────────────────────────────────────────────┘
     │
[Qualified] ──Create Opportunity──> [Converted] ──Auto──> [Closed]
                                          │
                                    Creates:
                                    - Account
                                    - Contact  
                                    - Opportunity
```

### Automation Triggers

**On Lead Creation (New):**
- Auto-validate phone format, NIC format
- Duplicate detection (phone, NIC, email)
- If valid → Validated
- If duplicate → Duplicate (merge prompt)
- If invalid → Disqualified

**On Validated:**
- Run assignment rules (branch/territory)
- Send welcome SMS
- Create task "First Contact" (due: 2 hours)
- Status → Assigned

**On Assigned:**
- SLA timer starts (24 hours for first contact)
- If no contact in 24h → Unresponsive

**On First Contact Logged:**
- Status → Contacted
- Calculate lead score
- Log interaction in timeline

**On Interest Confirmed:**
- Status → Verified
- Trigger CRIB check (if configured)
- Create task "Qualification Call" (due: 24 hours)
- Send verification SMS

**On Qualification Call:**
- Calculate FOIR/DTI
- Auto-scoring evaluation
- If score ≥ threshold → Qualified
- If defer requested → Nurturing
- If failed criteria → Disqualified

**On Qualified:**
- Send "You're Qualified" SMS
- Create task "Create Opportunity"
- Notify relationship manager
- Flag for opportunity creation

**On Nurturing:**
- Create follow-up task (7/15/30 days)
- Add to nurture campaign
- When timeline reached → Contacted (retry)

**On Converted:**
- Create Account record
- Create Contact record
- Create Opportunity record
- Link all entities
- Auto-close lead
- Status → Closed

### Sample Customer Journeys

**Journey 1: Happy Path - Quick Conversion**
```
Day 1, 10:00 AM - New
- Customer submits web form for personal loan
- Auto-validation: ✓ Phone valid, NIC valid
- Duplicate check: None found
- Status → Validated

Day 1, 10:02 AM - Validated
- Assignment rule: Assigned to Field Agent "Kasun" (Galle branch)
- Welcome SMS sent: "Thank you! Our agent will contact you soon"
- Status → Assigned

Day 1, 11:30 AM - Assigned → Contacted
- Kasun calls customer
- Customer answers, interested in Rs. 500,000 loan
- Status → Contacted
- Lead score: 65/100

Day 1, 2:00 PM - Contacted → Verified
- Second call: Customer confirms employment, salary Rs. 75,000
- Basic details verified
- Status → Verified
- CRIB check requested

Day 2, 9:00 AM - Verified → Qualified
- CRIB report: Clean, score 720
- FOIR calculation: 35% (acceptable)
- DTI: 28% (good)
- Auto-qualification: ✓ Passed
- Status → Qualified
- SMS: "Congratulations! You're pre-qualified for loan up to Rs. 600,000"

Day 2, 10:30 AM - Qualified → Converted
- Branch officer creates opportunity
- Account created: "Nimal Perera"
- Contact created: +94771234567
- Opportunity created: "Personal Loan - Rs. 500,000"
- Status → Converted → Closed

Total time: 1.5 days
```

**Journey 2: Nurturing Path - Deferred Timeline**
```
Day 1, 3:00 PM - New → Validated → Assigned
- Walk-in inquiry at Kandy branch
- Manual lead creation by front desk
- Assigned to "Dilani" (Relationship Officer)

Day 1, 3:30 PM - Assigned → Contacted
- Dilani calls immediately (walk-in follow-up)
- Customer interested in vehicle loan
- Status → Contacted

Day 1, 4:00 PM - Contacted → Verified
- Qualification call completed
- Employment: Self-employed, 3 years
- Monthly income: Rs. 120,000
- Status → Verified

Day 2, 10:00 AM - Verified → Nurturing
- Customer: "I want to apply in 2 months after bonus"
- Defer reason: "Awaiting year-end bonus"
- Nurture timeline: 60 days
- Status → Nurturing
- Follow-up task created: 60 days

Day 62, 9:00 AM - Nurturing → Contacted
- Auto-task reminder to Dilani
- Dilani calls: "Hi, ready to proceed?"
- Customer: "Yes, received bonus, ready now"
- Status → Contacted (re-engagement)

Day 62, 2:00 PM - Contacted → Verified → Qualified
- Updated financials collected
- Bonus amount: Rs. 300,000 (down payment)
- FOIR: 32%, DTI: 25%
- Status → Qualified

Day 63 - Qualified → Converted
- Opportunity created: "Vehicle Loan - Rs. 2,500,000"
- Status → Converted → Closed

Total time: 63 days (with planned nurture period)
```

**Journey 3: Disqualification Path**
```
Day 1, 8:00 AM - New
- Online form submission
- Requested amount: Rs. 1,000,000 personal loan
- Auto-validation: ✓ Passed
- Status → Validated → Assigned

Day 1, 9:15 AM - Assigned → Contacted
- Agent "Priya" calls
- Customer answers
- Status → Contacted

Day 1, 9:30 AM - Contacted → Verified
- Interest confirmed
- Stated income: Rs. 45,000/month
- Status → Verified

Day 1, 2:00 PM - Verified → Disqualified
- Qualification call reveals:
  - Actual salary: Rs. 35,000
  - Existing loans: Rs. 25,000/month commitment
  - FOIR calculation: 71% (exceeds 60% limit)
  - DTI: 52% (exceeds 40% limit)
- Auto-disqualification triggered
- Status → Disqualified
- Disqualification reason: "FOIR exceeds policy limit"
- SMS: "Thank you for your interest. Unfortunately, we cannot proceed at this time."
- Status → Closed

Total time: 6 hours
```

**Journey 4: Unresponsive Path**
```
Day 1, 5:00 PM - New → Validated → Assigned
- Evening web form submission
- Assigned to "Chaminda" (call center)

Day 2, 9:00 AM - Assigned → Contacted (Attempt 1)
- Chaminda calls: No answer
- Left voicemail
- Status remains: Assigned
- Task created: "Follow-up call" (due: 4 hours)

Day 2, 2:00 PM - Attempted Contact 2
- Second call: No answer
- SMS sent: "We tried to reach you regarding your loan inquiry"
- Task created: "Final attempt" (due: tomorrow)

Day 3, 10:00 AM - Attempted Contact 3
- Third call: Phone switched off
- Email sent (if available)

Day 3, 6:00 PM - Assigned → Unresponsive
- Auto-status change (3 attempts, 24h+ elapsed)
- Status → Unresponsive → Closed
- Lead marked for potential remarketing campaign (90 days)

Total time: 2 days (closed as unresponsive)
```

**Journey 5: Duplicate Detection Path**
```
Day 1, 11:00 AM - New
- Web form submission
- Name: "Sunil Fernando"
- Phone: +94771234567
- NIC: 881234567V

Day 1, 11:00 AM - Duplicate Detected
- System finds existing lead:
  - Created: 15 days ago
  - Status: Nurturing
  - Same phone + NIC
- Auto-status: → Duplicate
- Alert to previous lead owner: "Duplicate inquiry detected"
- Merge recommendation shown
- New lead data appended to existing lead notes
- Status → Closed
- Original lead remains active

Total time: Instant (auto-merged)
```

This workflow ensures every lead follows a structured path from capture through conversion or closure, with appropriate automation at each stage while maintaining Sri Lankan financial services compliance requirements.