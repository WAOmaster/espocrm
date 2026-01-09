# Opportunity Stage Mapping Configuration Guide
## Siyapatha Finance - EspoCRM

---

## Overview

This document describes the dual-stage system implemented for Opportunity management. The system maintains standard CRM stages for Kanban visualization while tracking detailed loan processing stages internally.

**Purpose:** Display standard sales pipeline stages (Prospecting, Qualification, Proposal, Negotiation, Closed Won) on the Kanban board while tracking granular loan stages (Application Created, Document Collection, Credit Assessment, etc.) on each opportunity record.

---

## Stage Mapping Reference

| Loan Stage (cCLoanStage) | Kanban Column (stage) | Color |
|---|---|---|
| Application Created | Prospecting | Blue (info) |
| Document Collection | Qualification | Gray (default) |
| Credit Assessment | Qualification | Blue (primary) |
| Security Valuation | Proposal | Blue (primary) |
| Pending Approval - Branch | Proposal | Orange (warning) |
| Pending Approval - Regional | Proposal | Orange (warning) |
| Pending Approval - Committee | Proposal | Orange (warning) |
| Approved - Agreement Pending | Negotiation | Green (success) |
| Agreement Signed | Negotiation | Green (success) |
| Pending Disbursement | Negotiation | Green (success) |
| Disbursed - Won | Closed Won | Green (success) |
| Rejected - Lost | Closed Lost | Red (danger) |
| Cancelled - Lost | Closed Lost | Red (danger) |

---

## Configuration Components

### 1. Loan Stage Field (cCLoanStage)

**Location:** Administration → Entity Manager → Opportunity → Fields → Loan Stage

**Field Properties:**
- Type: Enum
- Name: cCLoanStage
- Label: Loan Stage
- Display As Label: Yes
- Audited: Yes

### 2. Auto-Mapping Workflow

**Location:** Administration → Workflows

**Workflow Name:** Opportunity: Map Loan Stage to Kanban Stage

**Configuration:**
- Entity Type: Opportunity
- Trigger: After Record Saved
- Condition: Loan Stage is not empty
- Action: Execute Formula (maps Loan Stage to standard Stage)

### 3. Layout Configuration

**Kanban Card Display:**
- Location: Administration → Entity Manager → Opportunity → Layouts → Kanban
- Add "Loan Stage" field to show detailed stage on each card

**Detail View:**
- Location: Administration → Entity Manager → Opportunity → Layouts → Detail
- Loan Stage appears in Overview panel
- Standard Stage moved to Side Panel (read-only reference)

---

## UI Configuration Procedures

### Procedure 1: Modify Loan Stage Options

1. Navigate to **Administration → Entity Manager**
2. Click **Opportunity**
3. Click **Fields** tab
4. Click **Loan Stage** (cCLoanStage)
5. Modify the **Options** list as needed
6. Click **Save**
7. Go to **Administration → Rebuild**

### Procedure 2: Change Stage Colors

1. Navigate to **Administration → Entity Manager**
2. Click **Opportunity**
3. Click **Fields** tab
4. Click **Loan Stage** (cCLoanStage)
5. In the Options section, each option has a color dropdown
6. Select appropriate color for each stage:
   - **default** (gray) - Neutral/waiting states
   - **info** (blue) - New/initial states
   - **primary** (dark blue) - In-progress states
   - **warning** (orange) - Pending/attention required
   - **success** (green) - Positive/completed states
   - **danger** (red) - Negative/failed states
7. Click **Save**
8. Go to **Administration → Rebuild**

### Procedure 3: Add Loan Stage to Kanban Cards

1. Navigate to **Administration → Entity Manager**
2. Click **Opportunity**
3. Click **Layouts**
4. Click **Kanban** in the left sidebar
5. Drag **Loan Stage** from Disabled to Enabled section
6. Arrange field order (recommended: Name, Loan Stage, Amount)
7. Click **Save**
8. Go to **Administration → Rebuild**

### Procedure 4: Modify Detail View Layout

1. Navigate to **Administration → Entity Manager**
2. Click **Opportunity**
3. Click **Layouts**
4. Click **Detail**
5. Drag fields to arrange:
   - Add **Loan Stage** to Overview panel
   - Move **Stage** to Side Panel Fields if needed
6. Click **Save**
7. Go to **Administration → Rebuild**

### Procedure 5: Update Stage Mapping Workflow

If loan stage options change, update the workflow formula:

1. Navigate to **Administration → Workflows**
2. Find **"Opportunity: Map Loan Stage to Kanban Stage"**
3. Click to edit
4. In Actions section, click the formula action
5. Update the mapping logic:
   ```
   $ls = cCLoanStage;
   
   ifThen($ls == 'Application Created', stage = 'Prospecting');
   ifThen($ls == 'Document Collection', stage = 'Qualification');
   // ... add/modify mappings as needed
   ```
6. Click **Save**

### Procedure 6: Configure Side Panel Fields

1. Navigate to **Administration → Entity Manager**
2. Click **Opportunity**
3. Click **Layouts**
4. Click **Side Panel Fields**
5. Add fields to display in right panel:
   - Stage
   - Probability, %
   - Assigned User
   - Created date
6. Click **Save**

---

## Workflow Formula Reference

The workflow uses EspoCRM formula syntax to map stages:

```
$ls = cCLoanStage;

ifThen($ls == 'Application Created', stage = 'Prospecting');
ifThen($ls == 'Document Collection', stage = 'Qualification');
ifThen($ls == 'Credit Assessment', stage = 'Qualification');
ifThen($ls == 'Security Valuation', stage = 'Proposal');
ifThen($ls == 'Pending Approval - Branch', stage = 'Proposal');
ifThen($ls == 'Pending Approval - Regional', stage = 'Proposal');
ifThen($ls == 'Pending Approval - Committee', stage = 'Proposal');
ifThen($ls == 'Approved - Agreement Pending', stage = 'Negotiation');
ifThen($ls == 'Agreement Signed', stage = 'Negotiation');
ifThen($ls == 'Pending Disbursement', stage = 'Negotiation');
ifThen($ls == 'Disbursed - Won', stage = 'Closed Won');
ifThen($ls == 'Rejected - Lost', stage = 'Closed Lost');
ifThen($ls == 'Cancelled - Lost', stage = 'Closed Lost');
```

**Adding New Loan Stage:**
1. Add option to cCLoanStage field (Procedure 1)
2. Add corresponding ifThen statement to workflow (Procedure 5)
3. Rebuild cache

---

## Color Reference

| Color Code | Appearance | Recommended Use |
|---|---|---|
| default | Gray | Neutral, waiting, or secondary states |
| info | Light Blue | New, informational states |
| primary | Dark Blue | Active, in-progress states |
| warning | Orange | Attention needed, pending approval |
| success | Green | Completed, approved, positive outcomes |
| danger | Red | Rejected, cancelled, negative outcomes |

---

## Troubleshooting

**Issue: Kanban board not showing Loan Stage**
- Verify Loan Stage added to Kanban layout (not List Small)
- Run Administration → Rebuild
- Clear browser cache and refresh

**Issue: Stage not auto-updating when Loan Stage changes**
- Check workflow is Active (Administration → Workflows)
- Verify workflow condition: cCLoanStage is not empty
- Check formula syntax in workflow action

**Issue: Colors not displaying correctly**
- Run Administration → Rebuild after any field changes
- Verify "Display As Label" is enabled on the field
- Clear browser cache

**Issue: Old numbered values still appearing**
- Update existing records by re-saving each opportunity
- Or use Mass Update to set Loan Stage values

---

## Important Notes

1. **Always Rebuild** - After any configuration change, go to Administration → Rebuild
2. **Workflow Dependency** - The standard Stage field is auto-managed; users should only edit Loan Stage
3. **Data Migration** - When changing option values, existing records need updating
4. **Kanban vs List Small** - Kanban layout controls Kanban cards; List Small is for popups and relationship panels

---

*Document Version: 1.0*  
*Last Updated: January 2026*  
*System: EspoCRM 9.x*
