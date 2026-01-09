# Kollupitiya Branch - Quick Test Reference

## Login Credentials (All users same password)

| Username | Password | Role | Name |
|----------|----------|------|------|
| klp.manager | Branch@2026 | Branch Manager | Ranil Fernando |
| klp.astmanager | Branch@2026 | Assistant Manager | Kamala Perera |
| klp.operations | Branch@2026 | Operations Officer | Nimal Silva |
| klp.credit | Branch@2026 | Credit Officer | Sanduni Jayawardena |
| klp.assistant | Branch@2026 | Executive Assistant | Dilshan Rodrigo |

## Quick Test Commands

### 1. Create Test Lead (Auto-assign to Kollupitiya)
```bash
curl -X POST "https://espocrm-dev-220642639797.us-central1.run.app/api/v1/Lead" \
  -u "admin:Q2fp120dbL3BafWK" \
  -H "Content-Type: application/json" \
  -d '{
    "firstName": "Priya",
    "lastName": "Wijesinghe",
    "phoneNumber": "+94714445555",
    "cCity": "Colombo",
    "cAddressStreet": "123 Galle Road",
    "cNic": "199012345678",
    "source": "Walk-In"
  }'
```

### 2. Create Test Lead as Operations Officer
```bash
curl -X POST "https://espocrm-dev-220642639797.us-central1.run.app/api/v1/Lead" \
  -u "klp.operations:Branch@2026" \
  -H "Content-Type: application/json" \
  -d '{
    "firstName": "Nuwan",
    "lastName": "Bandara",
    "phoneNumber": "+94716667777",
    "cCity": "Colombo",
    "source": "Referral",
    "teamsIds": ["695fa1a413888ef71"]
  }'
```

### 3. Check Team's Leads
```bash
curl -s "https://espocrm-dev-220642639797.us-central1.run.app/api/v1/Lead?where[0][type]=linkedWith&where[0][attribute]=teams&where[0][value][]=695fa1a413888ef71&maxSize=10" \
  -u "klp.manager:Branch@2026" | python3 -m json.tool
```

### 4. Create Case for Branch
```bash
curl -X POST "https://espocrm-dev-220642639797.us-central1.run.app/api/v1/Case" \
  -u "klp.operations:Branch@2026" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Loan Documentation Missing",
    "type": "Question",
    "priority": "High",
    "status": "New",
    "description": "Customer needs assistance with loan documentation",
    "teamsIds": ["695fa1a413888ef71"]
  }'
```

## Key IDs for Reference

- **Branch ID:** 695fa1b77a2e4d11b
- **Team ID:** 695fa1a413888ef71
- **Manager User ID:** 695fa1a6dfa439fa4

## Web UI Testing URLs

- **Login:** https://espocrm-dev-220642639797.us-central1.run.app/#User/login
- **Leads:** https://espocrm-dev-220642639797.us-central1.run.app/#Lead
- **Cases:** https://espocrm-dev-220642639797.us-central1.run.app/#Case
- **Branch:** https://espocrm-dev-220642639797.us-central1.run.app/#CBranch/view/695fa1b77a2e4d11b
- **Team:** https://espocrm-dev-220642639797.us-central1.run.app/#Team/view/695fa1a413888ef71

## Test Scenarios

✓ **Login Test:** Login with each username and verify access
✓ **Team View:** Manager should see all 5 team members
✓ **Lead Creation:** Operations officer creates lead, manager should see it
✓ **Permission Test:** Executive assistant tries to delete lead (should fail based on role)
✓ **Branch Assignment:** Create lead with Colombo city, verify it routes to Kollupitiya team
✓ **Case Management:** Create case, assign to team member, verify SLA tracking
