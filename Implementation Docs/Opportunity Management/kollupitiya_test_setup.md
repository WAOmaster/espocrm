# Kollupitiya Branch Test Setup

## Branch Details
- **Branch ID:** 695fa1b77a2e4d11b
- **Branch Name:** Kollupitiya
- **Branch Code:** BR-064
- **Region:** Western Province
- **Address:** 456 Galle Road, Colombo 00300
- **Phone:** 0112345678
- **Email:** kollupitiya@siyapatha.lk
- **Banking Hours:** Monday to Friday 8:30 AM - 4:30 PM

## Team Details
- **Team ID:** 695fa1a413888ef71
- **Team Name:** Kollupitiya Branch
- **Positions:** Manager, Assistant Manager, Operations Officer, Credit Officer, Executive Assistant

## User Accounts

### 1. Branch Manager
- **User ID:** 695fa1a6dfa439fa4
- **Username:** `klp.manager`
- **Password:** `Branch@2026`
- **Full Name:** Ranil Fernando
- **Title:** Branch Manager
- **Email:** ranil.fernando@siyapathafinance.lk
- **Phone:** +94712345001
- **Role:** Branch Manager (ID: 694ba0db34419c500)
- **Permissions:** Full management access, can create/edit/delete leads (team level), manage opportunities, cases

### 2. Assistant Manager
- **User ID:** 695fa1a7a48d61ff2
- **Username:** `klp.astmanager`
- **Password:** `Branch@2026`
- **Full Name:** Kamala Perera
- **Title:** Assistant Manager
- **Email:** kamala.perera@siyapathafinance.lk
- **Phone:** +94712345002
- **Role:** Branch Manager (ID: 694ba0db34419c500)
- **Permissions:** Same as Branch Manager

### 3. Operations Officer
- **User ID:** 695fa1a844a0e8e1f
- **Username:** `klp.operations`
- **Password:** `Branch@2026`
- **Full Name:** Nimal Silva
- **Title:** Operations Officer
- **Email:** nimal.silva@siyapathafinance.lk
- **Phone:** +94712345003
- **Role:** Branch Executive Officer (ID: 694cc8798d9f1f4b9)
- **Permissions:** Can create leads, read/edit team leads, create cases, read documents

### 4. Credit Officer
- **User ID:** 695fa1a8d521e2324
- **Username:** `klp.credit`
- **Password:** `Branch@2026`
- **Full Name:** Sanduni Jayawardena
- **Title:** Credit Officer
- **Email:** sanduni.jayawardena@siyapathafinance.lk
- **Phone:** +94712345004
- **Role:** Relationship Manager (ID: 694cc9f6c817514f9)
- **Permissions:** Can create/edit leads, create cases, read accounts

### 5. Executive Assistant
- **User ID:** 695fa1a96c9e872f9
- **Username:** `klp.assistant`
- **Password:** `Branch@2026`
- **Full Name:** Dilshan Rodrigo
- **Title:** Executive Assistant
- **Email:** dilshan.rodrigo@siyapathafinance.lk
- **Phone:** +94712345005
- **Role:** Branch Executive Officer (ID: 694cc8798d9f1f4b9)
- **Permissions:** Can create leads, read/edit team leads, create cases

## Testing Scenarios

### Test 1: Login as Each User
Test each user can login and see appropriate dashboard:

```bash
# Test URL
https://espocrm-dev-220642639797.us-central1.run.app/#User/login

# Login credentials
Username: klp.manager | Password: Branch@2026
Username: klp.astmanager | Password: Branch@2026
Username: klp.operations | Password: Branch@2026
Username: klp.credit | Password: Branch@2026
Username: klp.assistant | Password: Branch@2026
```

**Expected:** All users should successfully login and see role-appropriate interfaces.

### Test 2: Team-Based Lead Assignment
When a lead is created for Colombo city (within Kollupitiya jurisdiction):

1. Create a test lead via API:
```bash
curl -X POST "https://espocrm-dev-220642639797.us-central1.run.app/api/v1/Lead" \
  -u "admin:Q2fp120dbL3BafWK" \
  -H "Content-Type: application/json" \
  -d '{
    "firstName": "Test",
    "lastName": "Customer",
    "phoneNumber": "+94712223333",
    "cCity": "Colombo",
    "source": "Web"
  }'
```

**Expected:** Lead should be automatically assigned to Kollupitiya branch team based on city assignment rule.

2. Login as `klp.manager` and verify:
   - Lead appears in "My Leads" or "Team Leads"
   - Branch team members can see the lead
   - Assignment follows configured rules

### Test 3: Role-Based Permissions

**As klp.manager (Branch Manager):**
- Create new lead → Should work
- Edit any team lead → Should work
- Delete team lead → Should work
- View all opportunities → Should work
- Close cases → Should work

**As klp.operations (Operations Officer):**
- Create new lead → Should work
- Edit own lead → Should work
- Edit others' lead → Should work (team level)
- Delete any lead → Should work (team level)
- Create case → Should work

**As klp.credit (Credit Officer):**
- Create/edit leads → Should work
- View account details → Should work (read only)
- Create opportunities → Should work

### Test 4: Case Management
1. Login as `klp.operations`
2. Create a case:
```bash
curl -X POST "https://espocrm-dev-220642639797.us-central1.run.app/api/v1/Case" \
  -u "klp.operations:Branch@2026" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test Case - Document Request",
    "type": "Question",
    "priority": "Normal",
    "status": "New",
    "teamsIds": ["695fa1a413888ef71"]
  }'
```

**Expected:** Case created and visible to all Kollupitiya team members.

### Test 5: Team Visibility
1. Login as `klp.assistant`
2. Navigate to Leads list
3. Filter by Team = "Kollupitiya Branch"

**Expected:** Should see all leads assigned to Kollupitiya team, not leads from other branches.

## API Testing Commands

### Check Team Members
```bash
curl -X GET "https://espocrm-dev-220642639797.us-central1.run.app/api/v1/Team/695fa1a413888ef71" \
  -u "admin:Q2fp120dbL3BafWK" | python3 -m json.tool
```

### List Leads for Kollupitiya Team
```bash
curl -X GET "https://espocrm-dev-220642639797.us-central1.run.app/api/v1/Lead?where[0][type]=equals&where[0][attribute]=teamsIds&where[0][value]=695fa1a413888ef71" \
  -u "admin:Q2fp120dbL3BafWK" | python3 -m json.tool
```

### Check User Details
```bash
# Manager
curl -X GET "https://espocrm-dev-220642639797.us-central1.run.app/api/v1/User/695fa1a6dfa439fa4" \
  -u "admin:Q2fp120dbL3BafWK" | python3 -m json.tool

# Operations Officer  
curl -X GET "https://espocrm-dev-220642639797.us-central1.run.app/api/v1/User/695fa1a844a0e8e1f" \
  -u "admin:Q2fp120dbL3BafWK" | python3 -m json.tool
```

## Important Notes

1. **Team Assignment:** All 5 users are members of the "Kollupitiya Branch" team, ensuring team-based visibility and permissions work correctly.

2. **Branch Assignment:** The Kollupitiya branch is assigned to Ranil Fernando (klp.manager) as the branch manager.

3. **Password Security:** All users share the same test password `Branch@2026`. In production, each user should have unique strong passwords.

4. **Role Hierarchy:**
   - Manager/Assistant Manager: Full management rights
   - Operations Officer/Executive Assistant: Operational rights
   - Credit Officer: Focused on lead/credit management

5. **Workflow Testing:** The assignment rule should automatically route leads from Colombo to the Kollupitiya team based on the `cCity` field.

## Next Steps

1. **Test Assignment Rules:** Create leads with different city values to verify automatic assignment to Kollupitiya team
2. **Test Workflows:** Verify BPM workflows trigger correctly for team members
3. **Test SLA:** Create cases and verify SLA tracking works for Kollupitiya branch
4. **Test Reporting:** Generate reports filtered by Kollupitiya team
5. **Mobile Access:** Test if team members can access on mobile devices

## Troubleshooting

### Users Can't See Team Leads
- Verify user is member of team: Check `teamsIds` field in User entity
- Verify role permissions: Check role has "team" level access for Lead entity
- Clear cache: Administration > Clear Cache

### Assignment Rules Not Working
- Check if cBranch field exists on Lead entity
- Verify assignment rule conditions match the city/region
- Check workflow is active and not in draft mode

### Permission Issues
- Verify role assignments: User > Roles field should show correct role
- Check role permissions: Administration > Roles > [Role Name]
- Test with admin account to isolate permission vs data issues
