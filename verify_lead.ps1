$body = @{
    name                   = "Test Financial Lead"
    status                 = "New"
    monthlyIncome          = 100000
    additionalIncome       = 50000
    monthlyObligations     = 30000
    employmentType         = "Permanent"
    loanAmountRequested    = 500000
    primaryProductInterest = "Personal Loan"
    leadChannel            = "Website"
    nicNumber              = "123456789V"
} | ConvertTo-Json

$body | Out-File -Encoding ASCII payload.json

# Use --data instead of -d and quote the argument to avoid PowerShell parsing issues
curl.exe -X POST -u "admin:EspoCRM2025" -H "Content-Type: application/json" --data "@payload.json" https://espocrm-1050025521391.europe-west1.run.app/api/v1/Lead
