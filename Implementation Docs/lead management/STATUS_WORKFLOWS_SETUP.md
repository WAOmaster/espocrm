# Status Progression Workflows - Manual Setup
## Create These 2 Workflows in EspoCRM

**Total Time:** 10-15 minutes  
**Location:** Administration → Workflows

---

## WORKFLOW 1: New → Contacted

### Basic Configuration

**Name:** Status: New to Contacted  
**Entity Type:** Lead  
**Type/Trigger:** After record updated  
**Active:** ✓ Yes

### Conditions

**Use Formula Condition:**
```javascript
status == 'New' && record\count('Call', 'parentId=', id, 'parentType=', 'Lead', 'status=', 'Held') > 0
```

**What this means:** Progress from New to Contacted when first call is completed.

### Actions

**Action Type:** Update Record

**Fields to Update:**
- **Field:** status
- **Value:** Contacted

### Save and Activate

Click "Save" and ensure workflow is **Active**

---

## WORKFLOW 2: Contacted → Verified

### Basic Configuration

**Name:** Status: Contacted to Verified  
**Entity Type:** Lead  
**Type/Trigger:** After record updated  
**Active:** ✓ Yes

### Conditions

**Use Formula Condition:**
```javascript
status == 'Contacted' && record\count('Call', 'parentId=', id, 'parentType=', 'Lead', 'status=', 'Held') >= 2
```

**What this means:** Progress from Contacted to Verified after 2+ calls completed.

### Actions

**Action Type:** Update Record

**Fields to Update:**
- **Field:** status
- **Value:** Verified

### Save and Activate

Click "Save" and ensure workflow is **Active**

---

## Step-by-Step Instructions

### Creating Workflow 1:

1. **Go to Workflows**
   - Administration → Workflows
   - Click "Create Workflow"

2. **Basic Info Tab**
   - Name: `Status: New to Contacted`
   - Entity Type: Select "Lead"
   - Type: Select "After record updated"
   - Check "Active"

3. **Conditions Tab**
   - Click "Add Formula"
   - Paste: `status == 'New' && record\count('Call', 'parentId=', id, 'parentType=', 'Lead', 'status=', 'Held') > 0`
   - Click outside formula box to save

4. **Actions Tab**
   - Click "Add Action"
   - Type: Select "Update Record"
   - In "Fields" section:
     - Click "Add Field"
     - Select "Status"
     - Set value to "Contacted"

5. **Save**
   - Click "Save" button
   - Verify workflow appears in list as "Active"

---

### Creating Workflow 2:

1. **Create New Workflow**
   - Click "Create Workflow" again

2. **Basic Info Tab**
   - Name: `Status: Contacted to Verified`
   - Entity Type: Select "Lead"
   - Type: Select "After record updated"
   - Check "Active"

3. **Conditions Tab**
   - Click "Add Formula"
   - Paste: `status == 'Contacted' && record\count('Call', 'parentId=', id, 'parentType=', 'Lead', 'status=', 'Held') >= 2`

4. **Actions Tab**
   - Click "Add Action"
   - Type: "Update Record"
   - Fields:
     - Add Field → "Status"
     - Value → "Verified"

5. **Save and Verify Active**

---

## Complete Status Flow After Setup

With formulas + these 2 workflows, the complete automation will be:

```
New (Manual entry)
  ↓ [After 1st call completed]
Contacted (Auto via Workflow 1)
  ↓ [After 2nd call completed]
Verified (Auto via Workflow 2)
  ↓ [When CRIB ≥650 + FOIR/DTI pass]
Qualified (Auto via Formula)
  ↓ [Manual conversion]
Converted
```

---

## Testing the Workflows

### Test Case: Journey 1 Flow

1. **Create a lead:**
   - Set status = "New"
   - Save

2. **Add first call:**
   - Create a Call activity
   - Link to the lead (Parent Type: Lead)
   - Set status = "Held"
   - Save the call
   - **Check:** Lead status should auto-change to "Contacted"

3. **Add second call:**
   - Create another Call activity
   - Link to same lead
   - Set status = "Held"
   - Save the call
   - **Check:** Lead status should auto-change to "Verified"

4. **Add financial data:**
   - Edit the lead
   - Add: Monthly Income, Existing Obligations, Loan Amount, Tenure
   - Add: CRIB Score ≥ 650
   - Save
   - **Check:** Formula should calculate FOIR/DTI
   - **Check:** If qualification criteria met, status → "Qualified"

---

## Verification via API

After creating workflows, verify they're active:

```bash
curl -s -X GET 'https://espocrm-dev-220642639797.us-central1.run.app/api/v1/Workflow?where[0][type]=equals&where[0][attribute]=entityType&where[0][value]=Lead&where[1][type]=equals&where[1][attribute]=isActive&where[1][value]=true' \
  -u 'admin:Q2fp120dbL3BafWK' | grep -E '"name"|"id"'
```

Should show your 2 new workflows plus existing ones.

---

## What These Workflows Do

### Workflow 1: New → Contacted
**Purpose:** Automatically progress lead when first contact is made  
**Trigger:** Any update to a lead  
**Logic:** 
- IF status is "New"
- AND at least 1 call with status "Held" exists
- THEN set status to "Contacted"

**Journey 1 Example:**
- Day 1, 10:00 AM: Lead created (status: New)
- Day 1, 11:30 AM: First call logged as "Held"
- **Result:** Status automatically becomes "Contacted"

---

### Workflow 2: Contacted → Verified
**Purpose:** Progress lead after verification call completed  
**Trigger:** Any update to a lead  
**Logic:**
- IF status is "Contacted"
- AND 2 or more calls with status "Held" exist
- THEN set status to "Verified"

**Journey 1 Example:**
- Day 1, 11:30 AM: First call (status now: Contacted)
- Day 1, 02:00 PM: Second call logged as "Held"
- **Result:** Status automatically becomes "Verified"

---

## Common Issues

### Issue 1: Workflow Not Triggering
**Check:**
- Workflow is Active (green checkmark)
- Conditions formula is correct (no syntax errors)
- Call status is exactly "Held" (not "Planned" or other)
- Lead status matches condition exactly

### Issue 2: Multiple Status Changes
**Solution:** This is normal - workflows trigger on each save
- Save lead → workflows run
- If conditions met → status changes
- Status change triggers another save
- But conditions no longer met, so stops

### Issue 3: Status Changes Back
**Check:** No other workflows conflicting
- Only these 2 workflows should affect status
- Check existing workflows don't override

---

## Integration with Formulas

These workflows work together with the formulas:

| Stage | Automation | Method |
|-------|------------|--------|
| New → Contacted | After 1st call | Workflow 1 |
| Contacted → Verified | After 2nd call | Workflow 2 |
| Verified → Qualified | CRIB + Financial pass | Formula (in Lead entity) |

The formula handles Verified → Qualified automatically when:
1. CRIB score ≥ 650
2. Financial qualification = Pass (FOIR < 60%, DTI < 50%)

---

## After Workflow Setup

Once both workflows are created and tested:

1. ✅ Test complete flow with sample lead
2. ✅ Verify status progressions automatic
3. ✅ Update layouts to show new fields
4. ✅ Train staff on new automation
5. ✅ Move to Phase 2 (Conversion automation)

---

**Questions or Issues?**
- Test with a sample lead first
- Check workflow logs if not working
- Verify call status is "Held" not "Planned"
- Ensure workflows are Active

**Let me know when workflows are created and I'll verify via API!**
