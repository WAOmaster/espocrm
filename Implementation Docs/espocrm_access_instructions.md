# EspoCRM Access Instructions - Siyapatha Finance

## System Access Details

**Environment:** Development
# **URL:** https://espocrm-1050025521391.europe-west1.run.app/
**Username:** admin
**Password:** EspoCRM2025

## Web Interface Access

Navigate to: https://espocrm-1050025521391.europe-west1.run.app/#
Login with credentials above.

## API Access

### Authentication Method
EspoCRM uses HTTP Basic Authentication for API requests.

### Base API Endpoint
```
https://espocrm-1050025521391.europe-west1.run.app/api/v1/
```

### API Authentication
```bash
curl -u 'admin:EspoCRM2025' \
  -H 'Content-Type: application/json' \
  https://espocrm-1050025521391.europe-west1.run.app/api/v1/
```

## Common API Operations

### Create Account (Customer)
```bash
curl -X POST 'https://espocrm-1050025521391.europe-west1.run.app/api/v1/Account' \
  -u 'admin:EspoCRM2025' \
  -H 'Content-Type: application/json' \
  -d '{
    "name": "Customer Name",
    "type": "Customer",
    "phoneNumber": "+94XXXXXXXXX",
    "emailAddress": "email@example.com",
    "billingAddressStreet": "Street Address",
    "billingAddressCity": "City",
    "billingAddressState": "Province",
    "billingAddressCountry": "Sri Lanka",
    "billingAddressPostalCode": "XXXXX",
    "description": "Customer description"
  }'
```

### List Accounts
```bash
curl -X GET 'https://espocrm-1050025521391.europe-west1.run.app/api/v1/Account?maxSize=20&orderBy=createdAt&order=desc' \
  -u 'admin:EspoCRM2025' \
  -H 'Content-Type: application/json'
```

### Get Specific Account
```bash
curl -X GET 'https://espocrm-1050025521391.europe-west1.run.app/api/v1/Account/{ACCOUNT_ID}' \
  -u 'admin:EspoCRM2025' \
  -H 'Content-Type: application/json'
```

### Update Account
```bash
curl -X PUT 'https://espocrm-1050025521391.europe-west1.run.app/api/v1/Account/{ACCOUNT_ID}' \
  -u 'admin:EspoCRM2025' \
  -H 'Content-Type: application/json' \
  -d '{
    "description": "Updated description"
  }'
```

### Delete Account
```bash
curl -X DELETE 'https://espocrm-1050025521391.europe-west1.run.app/api/v1/Account/{ACCOUNT_ID}' \
  -u 'admin:EspoCRM2025' \
  -H 'Content-Type: application/json'
```

## Sri Lankan Context

### Standard Phone Format
+94XXXXXXXXX (Mobile: +947XXXXXXXX, Landline: +9411XXXXXXX)

### Province List
- Western Province
- Central Province
- Southern Province
- Northern Province
- Eastern Province
- North Western Province
- North Central Province
- Uva Province
- Sabaragamuwa Province

### Major Cities by Province
- **Western:** Colombo, Negombo, Gampaha, Kalutara
- **Central:** Kandy, Matale, Nuwara Eliya
- **Southern:** Galle, Matara, Hambantota
- **Northern:** Jaffna, Kilinochchi, Mannar
- **Eastern:** Trincomalee, Batticaloa, Ampara
- **North Western:** Kurunegala, Puttalam
- **North Central:** Anuradhapura, Polonnaruwa
- **Uva:** Badulla, Monaragala
- **Sabaragamuwa:** Ratnapura, Kegalle

## API Response Format

Successful creation returns:
```json
{
  "id": "694ff2d1305612d40",
  "name": "Customer Name",
  "type": "Customer",
  "phoneNumber": "+94XXXXXXXXX",
  "emailAddress": "email@example.com",
  "createdAt": "2025-12-27 14:53:05",
  ...
}
```

Error response returns:
```json
{
  "messageTranslation": {
    "label": "validationFailure",
    "data": {
      "field": "fieldName",
      "type": "valid"
    }
  }
}
```

## Quick Testing Commands

### Verify API Connection
```bash
curl -X GET 'https://espocrm-1050025521391.europe-west1.run.app/api/v1/' \
  -u 'admin:EspoCRM2025'
```

Expected response: `"EspoCRM REST API"`

### Count Total Accounts
```bash
curl -X GET 'https://espocrm-1050025521391.europe-west1.run.app/api/v1/Account?maxSize=1' \
  -u 'admin:EspoCRM2025' -s | grep -o '"total":[0-9]*'
```

## Security Notes

- This is a development environment
- Credentials are hardcoded for development purposes only
- Production environment will require token-based authentication
- Always use HTTPS for API requests
- Store credentials securely and never commit to version control


google cloud IAM admin 

How to Use This Service Account

  Option 1: Authenticate in Current Shell

  # Authenticate using the service account key
  gcloud auth activate-service-account espocrm-admin@studio-2246322462-31ba0.iam.gserviceaccount.com \
    --key-file=/home/sashizero91/espocrm-keys/espocrm-admin-key.json \
    --project=studio-2246322462-31ba0

  # Verify authentication
  gcloud auth list

  Option 2: Use with Application Default Credentials

  # Set the key as application default credentials
  export GOOGLE_APPLICATION_CREDENTIALS="/home/sashizero91/espocrm-keys/espocrm-admin-key.json"

  # Now SDKs will automatically use this service account

  Option 3: Download Key for Local Use

  To use this service account on your local machine, you can download the key:
  # The key is located at:
  /home/sashizero91/espocrm-keys/espocrm-admin-key.json

  Security Warning

  - This service account has Owner role with full project access
  - Keep the key file secure and never commit it to version control
  - For production, use more restricted roles with least privilege principle
  - Consider rotating keys regularly