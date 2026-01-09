# Manual Field Creation Required - Phase 1
## Lead Entity Custom Fields for Financial Calculations

**Status:** ❌ Cannot be created via API  
**Reason:** EspoCRM Entity Manager is not exposed through REST API  
**Action Required:** Manual creation through Admin UI  
**Estimated Time:** 15-20 minutes

---

## Fields to Create

Navigate to: **Administration** → **Entity Manager** → **Lead** → **Fields** → **Add Field**

---

### 1. Lead Score

**Field Name:** `leadScore`  
**Type:** Integer  
**Label:** Lead Score  
**Min Value:** 0  
**Max Value:** 100  
**Default Value:** 0  
**Required:** No  
**Read Only:** Yes (calculated by formula)  
**Audited:** No  
**Tooltip:** Auto-calculated lead quality score (0-100)

---

### 2. Score Last Updated

**Field Name:** `scoreLastUpdated`  
**Type:** DateTime  
**Label:** Score Last Updated  
**Required:** No  
**Read Only:** Yes  
**Audited:** No

---

### 3. Monthly Income

**Field Name:** `cMonthlyIncome`  
**Type:** Currency  
**Label:** Monthly Income  
**Required:** No  
**Read Only:** No  
**Audited:** Yes  
**Tooltip:** Customer's gross monthly income

**NOTE:** Check if this field already exists before creating

---

### 4. FOIR (Fixed Obligation to Income Ratio)

**Field Name:** `cFOIR`  
**Type:** Float  
**Label:** FOIR (%)  
**Decimal Places:** 2  
**Min Value:** 0  
**Max Value:** 100  
**Required:** No  
**Read Only:** Yes (calculated by formula)  
**Audited:** No  
**Tooltip:** Fixed Obligation to Income Ratio - New EMI / Income × 100

---

### 5. DTI (Debt-to-Income Ratio)

**Field Name:** `cDTI`  
**Type:** Float  
**Label:** DTI (%)  
**Decimal Places:** 2  
**Min Value:** 0  
**Max Value:** 100  
**Required:** No  
**Read Only:** Yes (calculated by formula)  
**Audited:** No  
**Tooltip:** Debt-to-Income Ratio - Total Obligations / Income × 100

---

### 6. Proposed EMI

**Field Name:** `cProposedEMI`  
**Type:** Currency  
**Label:** Proposed Monthly EMI  
**Required:** No  
**Read Only:** Yes (calculated by formula)  
**Audited:** No  
**Tooltip:** Calculated monthly installment based on loan amount and tenure

---

### 7. Calculated EMI

**Field Name:** `cCalculatedEMI`  
**Type:** Currency  
**Label:** Calculated EMI  
**Required:** No  
**Read Only:** Yes (calculated by formula)  
**Audited:** No

---

### 8. Financial Qualification

**Field Name:** `cFinancialQualification`  
**Type:** Enum  
**Label:** Financial Qualification  
**Options:**
- Pass
- Fail
- Pending

**Required:** No  
**Read Only:** Yes (calculated by formula)  
**Audited:** Yes  
**Default:** Pending  
**Tooltip:** Auto-calculated based on FOIR/DTI thresholds

---

### 9. Max Eligible Amount

**Field Name:** `cMaxEligibleAmount`  
**Type:** Currency  
**Label:** Max Eligible Loan Amount  
**Required:** No  
**Read Only:** Yes (calculated by formula)  
**Audited:** No  
**Tooltip:** Maximum loan amount customer qualifies for based on income

---

### 10. CRIB Status

**Field Name:** `cCRIBStatus`  
**Type:** Enum  
**Label:** CRIB Status  
**Options:**
- Clean
- Defaults
- Pending
- Not Checked

**Required:** No  
**Read Only:** No  
**Audited:** Yes  
**Default:** Not Checked

---

### 11. CRIB Last Checked

**Field Name:** `cCRIBLastChecked`  
**Type:** DateTime  
**Label:** CRIB Last Checked  
**Required:** No  
**Read Only:** No  
**Audited:** Yes

---

### 12. Existing Loans Detail

**Field Name:** `cExistingLoans`  
**Type:** Text  
**Label:** Existing Loans (CRIB Details)  
**Rows:** 4  
**Required:** No  
**Read Only:** No  
**Audited:** No  
**Tooltip:** Details of existing loans from CRIB report

---

## Verification Checklist

After creating all fields:

- [ ] All 12 fields created successfully
- [ ] Field names exactly match (case-sensitive)
- [ ] Field types correct
- [ ] Read-only fields marked as calculated
- [ ] Enum options entered correctly
- [ ] Currency fields use default currency (LKR)
- [ ] No errors in Entity Manager

---

## Quick Verification via API

After creating fields, run this command to verify:

```bash
curl -s -X GET 'https://espocrm-dev-220642639797.us-central1.run.app/api/v1/Lead/describe' \
  -u 'admin:Q2fp120dbL3BafWK' | jq '.fields | keys[] | select(. == "leadScore" or . == "cFOIR" or . == "cDTI" or . == "cFinancialQualification")'
```

Expected output: All field names should appear

---

## Layout Recommendations

After field creation, add to Lead Detail Layout:

**Panel: Lead Scoring**
- leadScore (with badge/color coding)
- scoreLastUpdated

**Panel: Financial Assessment**
- cMonthlyIncome
- cExistingMonthlyObligations (already exists)
- cProposedEMI
- cFOIR
- cDTI
- cFinancialQualification (with badge)
- cMaxEligibleAmount

**Panel: Credit Bureau**
- cCreditScore (already exists)
- cCRIBStatus
- cCRIBLastChecked
- cExistingLoans

---

## After Completion

Once you've created all fields, **let me know** and I will:
1. Verify fields via API
2. Create the formula scripts for auto-calculation
3. Continue with BPM/workflow creation
4. Complete Phase 1 implementation

**Estimated Time to Complete:** 15-20 minutes
