---
description: Antigravity Development Instructions - EspoCRM Project
---

# Antigravity Development Instructions - EspoCRM Project

## MANDATORY PRE-DEVELOPMENT CHECKS

1. **Read Project Documentation First**
   - `/mnt/project/CLOUD_RUN_DEPLOYMENT.md` - Deployment procedures
   - `/mnt/project/EspoCRM_Technical_Documentation.docx` - System architecture
   - `/mnt/project/claude_code_prompt.json` - Development standards
   - `/mnt/project/financial_lead_management_enhancement.md` - Feature requirements

2. **Consult EspoCRM Official Docs**
   - https://docs.espocrm.com/development/metadata/ - Entity structure
   - https://docs.espocrm.com/development/orm/ - Database operations
   - https://docs.espocrm.com/development/api/ - API integration
   - https://docs.espocrm.com/development/services/ - Business logic
   - https://docs.espocrm.com/administration/bpm/ - Workflows

## DEVELOPMENT WORKFLOW

### 1. Code Development
- **ALL custom code goes in:** `custom/Espo/Custom/`
- **NEVER modify:** Core EspoCRM files in `application/`
- **Follow patterns:**
  - Entity definitions: JSON in `Resources/metadata/entityDefs/`
  - Services: PHP classes extending `Espo\Core\Services\Base`
  - Hooks: PHP classes in `Hooks/` directory
  - Client code: JavaScript in `client/custom/src/`

### 2. Quality Standards
- Test each component independently before integration
- Use Sri Lankan context (LKR currency, Asia/Colombo timezone, local formats)
- Implement proper error handling and validation
- Add security measures for sensitive data (NIC, bank accounts)
- Follow PSR-12 coding standards for PHP
- Write clear comments for complex logic

### 3. Git Deployment Process

```bash
# Commit changes
git add .
git commit -m "Clear description of changes"
git push origin main
```

**Cloud Build triggers automatically** via `cloudbuild.yaml`

### 4. Deployment Verification (12-20 minutes)

**Check build status:**
```bash
# View latest build
gcloud builds list --limit=5

# Stream logs of specific build
gcloud builds log <BUILD_ID> --stream
```

**Verify Cloud Run deployment:**
```bash
# Check service status
gcloud run services describe espocrm --region=europe-west1 --format="table(status.url,status.conditions)"

# View recent logs
gcloud run services logs read espocrm --region=europe-west1 --limit=50

# Check revision traffic
gcloud run revisions list --service=espocrm --region=europe-west1
```

**Wait 12-20 minutes** for complete deployment and container startup.

### 5. Testing Protocol

**Access:** https://espocrm-1050025521391.europe-west1.run.app/#

**Test checklist:**
1. Login with credentials from `/mnt/project/espocrm_access_instructions.md`
2. Verify new/modified functionality works
3. Check no existing features broken
4. Test with sample data (Sri Lankan context)
5. Verify database integrity
6. Check browser console for JavaScript errors
7. Monitor Cloud Run logs during testing

### 6. Troubleshooting

**If deployment fails:**
```bash
# Check build logs
gcloud builds log <BUILD_ID>

# Check container logs
gcloud run services logs read espocrm --region=europe-west1 --limit=100

# Rollback if needed
gcloud run services update-traffic espocrm --to-revisions=<PREVIOUS_REVISION>=100 --region=europe-west1
```

**Common issues:**
- PHP syntax errors → Check build logs
- Database connection → Verify env vars in Cloud Run
- Permission errors → Check file ownership in Dockerfile
- Missing dependencies → Verify composer.json

## DEVELOPMENT BEST PRACTICES (EspoCRM Specific)

1. **Entity Fields:**
   - Test in Entity Manager UI before coding JSON
   - Use proper field types (varchar, text, int, float, enum, currency)
   - Add validations and tooltips
   - Encrypt sensitive fields (nicNumber, bankAccountNumber)

2. **Business Logic:**
   - Use Formula engine for calculations (FOIR, DTI, EMI)
   - Implement hooks for event-driven automation
   - Use dependency injection for services
   - Follow EspoCRM's ORM patterns

3. **API Integration:**
   - Use HTTP client in custom services
   - Implement proper error handling
   - Log all external API calls
   - Cache responses where appropriate

4. **Workflows & BPM:**
   - Test workflows independently
   - Use proper conditions and actions
   - Avoid infinite loops
   - Monitor execution logs

5. **Frontend:**
   - Extend base views, don't override
   - Use EspoCRM's view system
   - Follow existing UI patterns
   - Test responsive design

## PROJECT CONTEXT

- **Platform:** EspoCRM 9.2.5 on Google Cloud Run
- **Region:** europe-west1
- **Database:** MySQL (Cloud SQL)
- **Industry:** Microfinance (Sri Lanka)
- **Network:** 63 branches, 74 users
- **Key Entities:** Lead, Case, Opportunity, CBranch
- **Compliance:** CBSL, PDPA, CRIB integration

## DEPLOYMENT CHECKLIST

Before pushing code:
- [ ] Read relevant project documentation
- [ ] Check EspoCRM docs for patterns
- [ ] Code in `custom/Espo/Custom/` only
- [ ] Test locally if possible
- [ ] Clear commit message
- [ ] No hardcoded credentials
- [ ] Proper error handling

After deployment:
- [ ] Monitor build logs (2-5 min)
- [ ] Wait full deployment (12-20 min)
- [ ] Check Cloud Run logs
- [ ] Test functionality via site
- [ ] Verify no breaking changes
- [ ] Document any configuration changes

## CRITICAL RULES

1. **ALWAYS** read project docs before coding
2. **NEVER** modify core EspoCRM files
3. **ALWAYS** test before committing
4. **ALWAYS** wait 12-20 min for full deployment
5. **ALWAYS** verify via site testing
6. **ALWAYS** check logs for errors
7. **NEVER** deploy without clear understanding
8. **ALWAYS** follow EspoCRM architecture patterns

## RESOURCES

- **Project Docs:** `/mnt/project/`
- **EspoCRM Docs:** https://docs.espocrm.com/
- **Site URL:** https://espocrm-1050025521391.europe-west1.run.app/#
- **Deployment:** Git push → Cloud Build → Cloud Run (europe-west1)
- **Logs:** `gcloud run services logs read espocrm --region=europe-west1`