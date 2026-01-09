# Lead Entity Formula Scripts - Phase 1
## Copy and Paste These Into EspoCRM

**Location:** Administration → Entity Manager → Lead → Formula (Before-Save Script)

**Instructions:**
1. Navigate to Administration → Entity Manager
2. Click on "Lead" entity
3. Click "Formula" tab
4. Copy the COMPLETE FORMULA below
5. Paste into the formula editor
6. Click "Save"
7. Test with a lead record

---

## COMPLETE LEAD FORMULA (Copy Everything Below This Line)

```javascript
// ═══════════════════════════════════════════════════════════════
// SIYAPATHA FINANCE - LEAD AUTO-CALCULATION FORMULAS
// Phase 1: EMI, FOIR/DTI, Lead Scoring, Auto-Qualification
// ═══════════════════════════════════════════════════════════════

// ───────────────────────────────────────────────────────────────
// 1. EMI CALCULATOR
// Calculate monthly installment based on loan amount and tenure
// ───────────────────────────────────────────────────────────────

ifThen(
    cDesiredLoanAmount && cPreferredTenure,
    
    // Variables
    $P = cDesiredLoanAmount;
    $annualRate = 18; // 18% p.a. default
    $r = $annualRate / 12 / 100;
    $n = cPreferredTenure;
    
    // EMI = P × r × (1+r)^n / ((1+r)^n - 1)
    $numerator = $P * $r * math\power(1 + $r, $n);
    $denominator = math\power(1 + $r, $n) - 1;
    $emi = $numerator / $denominator;
    
    // Store calculated EMI
    cCCalculatedEMI = math\round($emi, 2);
    cProposedEMI = cCCalculatedEMI;
);

// ───────────────────────────────────────────────────────────────
// 2. FOIR & DTI CALCULATOR
// Financial ratios for qualification assessment
// ───────────────────────────────────────────────────────────────

ifThen(
    cProposedEMI && cCMonthlyIncome && cExistingMonthlyObligations >= 0,
    
    // FOIR = New EMI / Income × 100
    cCFOIR = math\round((cProposedEMI / cCMonthlyIncome) * 100, 2);
    
    // DTI = Total Obligations / Income × 100
    $totalObligations = cExistingMonthlyObligations + cProposedEMI;
    cCDTI = math\round(($totalObligations / cCMonthlyIncome) * 100, 2);
    
    // Auto-qualification check
    ifThen(
        cCFOIR < 60 && cCDTI < 50,
        
        // PASS - Customer qualifies
        cCFinancialQualification = 'Pass';
        
        // Calculate maximum eligible amount
        $maxEMI = cCMonthlyIncome * 0.50; // 50% DTI limit
        $availableEMI = $maxEMI - cExistingMonthlyObligations;
        
        // Reverse EMI calculation for max loan amount
        // P = EMI × ((1+r)^n - 1) / (r × (1+r)^n)
        $r = 18 / 12 / 100;
        $n = cPreferredTenure;
        
        ifThen(
            $n > 0,
            $denom = $r * math\power(1 + $r, $n);
            $numer = math\power(1 + $r, $n) - 1;
            cCMaxEligibleAmount = math\round($availableEMI * $numer / $denom, 0);
        );
    ,
        // FAIL - Customer does not qualify
        cCFinancialQualification = 'Fail';
        cCMaxEligibleAmount = 0;
    );
);

// ───────────────────────────────────────────────────────────────
// 3. LEAD SCORING ALGORITHM
// Scores lead from 0-100 based on data completeness & quality
// ───────────────────────────────────────────────────────────────

$score = 0;

// CONTACT INFORMATION (30 points)
ifThen(phoneNumber, $score = $score + 15);
ifThen(emailAddress, $score = $score + 10);
ifThen(addressCity && addressState, $score = $score + 5);

// FINANCIAL INFORMATION (40 points)
ifThen(cDesiredLoanAmount, $score = $score + 10);
ifThen(cCMonthlyIncome, $score = $score + 15);
ifThen(cEmployeeType, $score = $score + 10);
ifThen(cExistingMonthlyObligations >= 0, $score = $score + 5);

// DOCUMENTATION (15 points)
ifThen(cNICNo, $score = $score + 10);
ifThen(cDateOfBirth, $score = $score + 5);

// SOURCE QUALITY (15 points)
ifThen(source == 'Web Site', $score = $score + 5);
ifThen(source == 'Existing Customer', $score = $score + 15);
ifThen(source == 'Partner', $score = $score + 10);
ifThen(source == 'Call', $score = $score + 3);

// BONUS: Good CRIB score
ifThen(cCreditScore >= 700, $score = $score + 10);

// Cap at 100
ifThen($score > 100, $score = 100);

// Store score
cLeadScore = $score;
cScoreLastUpdated = datetime\now();

// ───────────────────────────────────────────────────────────────
// 4. AUTO-QUALIFICATION LOGIC
// Automatically set status to Qualified when criteria met
// ───────────────────────────────────────────────────────────────

ifThen(
    status == 'Verified' && 
    cCreditScore >= 650 && 
    cCFinancialQualification == 'Pass' &&
    cCFOIR < 60 &&
    cCDTI < 50,
    
    // Set status to Qualified
    status = 'Qualified';
);

// ═══════════════════════════════════════════════════════════════
// END OF FORMULAS
// ═══════════════════════════════════════════════════════════════
```

---

## What These Formulas Do

### 1. EMI Calculator
**Triggers:** When loan amount and tenure are entered  
**Calculates:** Monthly installment using standard EMI formula  
**Result:** Stores in `cCCalculatedEMI` and `cProposedEMI`

**Example:**
- Loan: Rs. 500,000
- Tenure: 36 months
- Rate: 18% p.a.
- **EMI: Rs. 18,076**

---

### 2. FOIR & DTI Calculator
**Triggers:** When EMI, income, and existing obligations are present  
**Calculates:**
- FOIR = (Proposed EMI ÷ Income) × 100
- DTI = (All Obligations ÷ Income) × 100
- Max eligible loan amount

**Thresholds:**
- FOIR must be < 60%
- DTI must be < 50%
- If both pass → Qualification = Pass
- If either fails → Qualification = Fail

**Example (Journey 1):**
- Income: Rs. 75,000
- Existing: Rs. 15,000
- Proposed EMI: Rs. 18,076
- **FOIR: 24.1% ✓**
- **DTI: 44.1% ✓**
- **Status: PASS**

---

### 3. Lead Scoring
**Triggers:** On every save  
**Calculates:** Score from 0-100 based on:

| Category | Points | Criteria |
|----------|--------|----------|
| Contact Info | 30 | Phone, email, address |
| Financial Info | 40 | Loan amount, income, employment |
| Documentation | 15 | NIC, DOB |
| Source Quality | 15 | Web, existing customer, partner |
| CRIB Bonus | +10 | Score ≥ 700 |

**Example:**
- New lead with basic contact: ~30 points
- After first call with financials: ~65 points
- After CRIB check (good score): ~75 points

---

### 4. Auto-Qualification
**Triggers:** When status = Verified  
**Checks:**
- CRIB score ≥ 650
- Financial qualification = Pass
- FOIR < 60%
- DTI < 50%

**Action:** Automatically sets status to "Qualified"

---

## Testing the Formulas

### Test Case 1: Journey 1 (Happy Path)

Create/update a lead with these values:

```
First Name: Test
Last Name: Customer
Phone: +94771234567
Email: test@example.lk
Address City: Colombo
Address State: Western Province

Loan Amount (cDesiredLoanAmount): 500000
Tenure (cPreferredTenure): 36
Monthly Income (cCMonthlyIncome): 75000
Existing Obligations (cExistingMonthlyObligations): 15000
CRIB Score (cCreditScore): 720
Status: Verified
```

**Expected Results After Save:**
- ✓ cCCalculatedEMI = 18,076
- ✓ cCFOIR = 24.1
- ✓ cCDTI = 44.1
- ✓ cCFinancialQualification = Pass
- ✓ cCMaxEligibleAmount = ~600,000
- ✓ cLeadScore = 75+
- ✓ Status automatically changes to "Qualified"

---

### Test Case 2: Disqualified (High DTI)

```
Monthly Income: 50000
Existing Obligations: 20000
Proposed Loan: 500000 (36 months)
CRIB Score: 720
```

**Expected Results:**
- ✓ cCCalculatedEMI = 18,076
- ✓ cCFOIR = 36.2% (Pass)
- ✓ cCDTI = 76.2% (Fail - over 50% limit)
- ✓ cCFinancialQualification = Fail
- ✓ Status remains "Verified" (not auto-qualified)

---

### Test Case 3: Poor CRIB Score

```
Monthly Income: 75000
Existing Obligations: 10000
Proposed Loan: 300000
CRIB Score: 550 (below 650 threshold)
```

**Expected Results:**
- ✓ Financial calculations complete
- ✓ cCFinancialQualification may be Pass
- ✓ But status does NOT change to Qualified (CRIB too low)

---

## Verification Commands

After saving the formula, verify via API:

```bash
# Update a test lead with Journey 1 values
curl -X PUT 'https://espocrm-dev-220642639797.us-central1.run.app/api/v1/Lead/YOUR_LEAD_ID' \
  -u 'admin:Q2fp120dbL3BafWK' \
  -H 'Content-Type: application/json' \
  -d '{
    "cDesiredLoanAmount": 500000,
    "cPreferredTenure": 36,
    "cCMonthlyIncome": 75000,
    "cExistingMonthlyObligations": 15000,
    "cCreditScore": 720,
    "status": "Verified"
  }'

# Check if formulas worked
curl -s 'https://espocrm-dev-220642639797.us-central1.run.app/api/v1/Lead/YOUR_LEAD_ID' \
  -u 'admin:Q2fp120dbL3BafWK' | grep -E "cCCalculatedEMI|cCFOIR|cCDTI|cCFinancialQualification|cLeadScore|status"
```

---

## Common Issues & Solutions

### Issue 1: Formulas Not Executing
**Solution:** Clear cache and rebuild
- Administration → Clear Cache
- Administration → Rebuild

### Issue 2: Division by Zero Errors
**Solution:** Formula already has checks for zero values (e.g., `cCMonthlyIncome` must exist)

### Issue 3: Status Not Auto-Changing to Qualified
**Check:**
- Status must be exactly "Verified"
- CRIB score ≥ 650
- FOIR and DTI calculations completed
- Financial qualification = "Pass"

### Issue 4: Wrong EMI Calculation
**Check:**
- Loan amount and tenure are both populated
- Values are numeric (not text)
- Tenure is in months (not years)

---

## Next Steps After Formula Installation

Once formulas are saved and tested:

1. ✅ Create status progression workflows (if not done yet)
2. ✅ Update Lead layouts to show calculated fields
3. ✅ Test with Journey 1 sample data
4. ✅ Proceed to Phase 2 (Conversion automation)

---

**Formula Installation Complete?** Let me know and I'll verify the calculations via API!
