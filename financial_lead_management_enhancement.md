# Financial Services Lead Management Module for EspoCRM

## Executive Summary

This document specifies a comprehensive lead management enhancement for EspoCRM tailored to financial companies (banks, microfinance institutions, leasing companies). The enhancement addresses critical gaps in financial services lead tracking, including multi-channel capture, financial qualification, credit assessment, loan product matching, and regulatory compliance.

---

## 1. Current EspoCRM Lead Structure Analysis

### 1.1 Out-of-Box Lead Entity Fields

EspoCRM's default Lead entity includes:

**Basic Information:**
- `salutationName` - Title (Mr., Ms., etc.)
- `firstName` - First name
- `middleName` - Middle name
- `lastName` - Last name
- `emailAddress` - Email
- `phoneNumber` - Primary phone
- `website` - Company website

**Business Context:**
- `accountName` - Company name
- `title` - Job title
- `industry` - Industry type (references Account.industry)
- `description` - Notes/description
- `address` - Full address object (street, city, state, postal code, country)

**Lead Management:**
- `status` - Enum: New, Assigned, In Process, Converted, Recycled, Dead
- `source` - Enum: Call, Email, Existing Customer, Partner, Public Relations, Web Site, Campaign, Other
- `assignedUser` - Assigned sales person
- `teams` - Team assignments
- `createdAt` - Creation timestamp
- `modifiedAt` - Last modified timestamp

**Conversion Tracking:**
- `createdAccount` - Link to created Account
- `createdContact` - Link to created Contact
- `createdOpportunity` - Link to created Opportunity

### 1.2 Current Capabilities

**Strengths:**
- Multi-channel lead capture via Web-to-Lead API
- Lead assignment with Round-Robin and Least-Busy rules
- Basic workflow automation through Advanced Pack
- Lead conversion to Account/Contact/Opportunity
- Activity tracking (Calls, Meetings, Tasks, Emails)
- Campaign tracking integration
- Customizable status and source fields

**Critical Gaps for Financial Services:**
- No financial information capture (income, assets, liabilities)
- No credit assessment integration points
- No loan product interest tracking
- No financial qualification scoring
- No regulatory compliance fields (KYC status, consent tracking)
- No co-applicant/guarantor relationship tracking
- No document checklist management
- No multi-stage financial qualification workflow
- Limited channel attribution (missing field agent, broker, aggregator sources)

---

## 2. Financial Company Lead Context

### 2.1 What is a Financial Services Lead?

A financial services lead represents a **potential borrower or financial product customer** who has expressed interest through various channels. Unlike B2B SaaS leads, financial leads require:

1. **Financial qualification assessment** - Income, employment, creditworthiness
2. **Product fit matching** - Which loan products match their profile
3. **Regulatory compliance** - KYC/AML verification, consent capture
4. **Risk evaluation** - Credit bureau checks, fraud detection
5. **Multi-party tracking** - Co-applicants, guarantors, co-borrowers
6. **Document verification** - ID proofs, income documents, address proofs

### 2.2 Lead Origination Channels

**Digital Channels:**
- Company website inquiry forms
- Loan calculator submissions
- Mobile app registrations
- Social media lead ads (Facebook, Instagram, LinkedIn)
- Comparison/aggregator websites (LendingKart, BankBazaar equivalents)
- Google Ads / Search campaigns

**Direct Channels:**
- Phone calls to customer service
- Branch walk-ins
- Field agent/loan officer sourcing
- Existing customer referrals
- Partner referrals (car dealers, real estate brokers)

**Third-Party Channels:**
- DSA (Direct Sales Agent) network
- Broker submissions
- Corporate tie-ups (employer partnerships)
- Marketplace aggregators

### 2.3 Financial Lead Attributes

**Personal Information:**
- Full name, date of birth, gender, marital status
- Contact details (mobile, alternate phone, email, WhatsApp)
- Current address with proof type (Owned/Rented/Family)
- Permanent address
- ID proof details (NIC number, passport, driving license)

**Financial Profile:**
- Employment type (Salaried/Self-Employed/Business/Retired)
- Employer/Business name
- Monthly income / Annual turnover
- Existing monthly obligations (EMIs, rent, etc.)
- Bank account details
- CRIB/Credit score (if available)

**Loan Interest:**
- Interested product type (Personal Loan, Vehicle Loan, Gold Loan, Business Loan, Lease)
- Desired loan amount
- Preferred tenure
- Purpose of loan
- Property/vehicle details (for secured loans)

**Co-Applicant Information:**
- Co-applicant name and relationship
- Co-applicant income source
- Guarantor details (if applicable)

**Qualification Data:**
- FOIR (Fixed Obligation to Income Ratio)
- DTI (Debt-to-Income ratio)
- Credit score / bureau report status
- KYC completion status
- Document submission status
- Eligibility status

**Engagement Tracking:**
- Lead temperature (Hot/Warm/Cold)
- Last contact date and method
- Follow-up reminder date
- Number of touchpoints
- Response rate
- Stage in pipeline

---

## 3. Comprehensive Lead Management Module Design

### 3.1 Enhanced Lead Entity Structure

#### 3.1.1 New Custom Entities

**FinancialLead** (extends base Lead)
```json
{
  "entityType": "FinancialLead",
  "extends": "Lead",
  "fields": {
    // Financial Information
    "employmentType": {
      "type": "enum",
      "options": ["Salaried", "Self-Employed", "Business Owner", "Professional", "Retired", "Other"],
      "required": true
    },
    "employerName": {
      "type": "varchar",
      "maxLength": 255
    },
    "designation": {
      "type": "varchar",
      "maxLength": 100
    },
    "monthlyIncome": {
      "type": "currency",
      "required": true
    },
    "additionalIncome": {
      "type": "currency"
    },
    "monthlyObligations": {
      "type": "currency",
      "tooltip": "Existing EMIs, rent, and other fixed monthly expenses"
    },
    "bankName": {
      "type": "varchar",
      "maxLength": 100
    },
    "bankAccountNumber": {
      "type": "varchar",
      "maxLength": 50,
      "encrypted": true
    },
    
    // Personal Details
    "dateOfBirth": {
      "type": "date",
      "required": true
    },
    "gender": {
      "type": "enum",
      "options": ["Male", "Female", "Other", "Prefer not to say"]
    },
    "maritalStatus": {
      "type": "enum",
      "options": ["Single", "Married", "Divorced", "Widowed"]
    },
    "numberOfDependents": {
      "type": "int",
      "min": 0
    },
    "nationality": {
      "type": "varchar",
      "maxLength": 100,
      "default": "Sri Lankan"
    },
    "nicNumber": {
      "type": "varchar",
      "maxLength": 20,
      "encrypted": true,
      "pattern": "^([0-9]{9}[xXvV]|[0-9]{12})$"
    },
    "passportNumber": {
      "type": "varchar",
      "maxLength": 20,
      "encrypted": true
    },
    "drivingLicenseNumber": {
      "type": "varchar",
      "maxLength": 20
    },
    
    // Address Information
    "currentAddress": {
      "type": "address"
    },
    "residenceType": {
      "type": "enum",
      "options": ["Owned", "Rented", "Parents/Family", "Company Provided"]
    },
    "yearsAtCurrentAddress": {
      "type": "int"
    },
    "permanentAddressSameAsCurrent": {
      "type": "bool",
      "default": true
    },
    "permanentAddress": {
      "type": "address"
    },
    
    // Loan Interest
    "interestedProducts": {
      "type": "multiEnum",
      "options": ["Personal Loan", "Vehicle Loan", "Gold Loan", "Home Loan", "Business Loan", "Leasing", "Credit Card"]
    },
    "primaryProductInterest": {
      "type": "enum",
      "optionsReference": "LoanProduct.name"
    },
    "loanAmountRequested": {
      "type": "currency",
      "required": true
    },
    "loanTenureRequested": {
      "type": "int",
      "tooltip": "Requested tenure in months"
    },
    "loanPurpose": {
      "type": "text"
    },
    "assetDescription": {
      "type": "text",
      "tooltip": "Vehicle details, property details, etc."
    },
    
    // Qualification & Scoring
    "leadTemperature": {
      "type": "enum",
      "options": ["Hot", "Warm", "Cold"],
      "default": "Warm",
      "style": {
        "Hot": "danger",
        "Warm": "warning",
        "Cold": "primary"
      }
    },
    "leadScore": {
      "type": "int",
      "min": 0,
      "max": 100,
      "readOnly": true,
      "tooltip": "Auto-calculated lead score based on profile"
    },
    "creditScore": {
      "type": "int",
      "min": 300,
      "max": 900,
      "readOnly": true
    },
    "creditBureauStatus": {
      "type": "enum",
      "options": ["Not Checked", "Pending", "Retrieved", "Failed"],
      "default": "Not Checked"
    },
    "foir": {
      "type": "float",
      "readOnly": true,
      "tooltip": "Fixed Obligation to Income Ratio (%)"
    },
    "dti": {
      "type": "float",
      "readOnly": true,
      "tooltip": "Debt-to-Income ratio (%)"
    },
    "eligibilityStatus": {
      "type": "enum",
      "options": ["Not Evaluated", "Pre-Qualified", "Qualified", "Not Qualified", "Conditional"],
      "default": "Not Evaluated",
      "style": {
        "Qualified": "success",
        "Pre-Qualified": "warning",
        "Not Qualified": "danger",
        "Conditional": "primary"
      }
    },
    "eligibilityRemarks": {
      "type": "text"
    },
    "maxEligibleAmount": {
      "type": "currency",
      "readOnly": true
    },
    "recommendedTenure": {
      "type": "int",
      "readOnly": true
    },
    
    // Enhanced Channel Attribution
    "leadChannel": {
      "type": "enum",
      "options": [
        "Website Form",
        "Mobile App",
        "Phone Call",
        "Walk-in",
        "Field Agent",
        "DSA/Broker",
        "Partner Referral",
        "Existing Customer",
        "Social Media",
        "Email Campaign",
        "Aggregator",
        "Corporate Tie-up",
        "Event/Exhibition",
        "Other"
      ],
      "required": true
    },
    "sourceDetail": {
      "type": "varchar",
      "maxLength": 255,
      "tooltip": "Campaign name, agent code, partner name, etc."
    },
    "referredBy": {
      "type": "link",
      "entity": "Contact",
      "tooltip": "Existing customer who referred this lead"
    },
    "fieldAgentCode": {
      "type": "varchar",
      "maxLength": 50
    },
    "dsaBrokerCode": {
      "type": "varchar",
      "maxLength": 50
    },
    "utmSource": {
      "type": "varchar",
      "maxLength": 100
    },
    "utmMedium": {
      "type": "varchar",
      "maxLength": 100
    },
    "utmCampaign": {
      "type": "varchar",
      "maxLength": 100
    },
    
    // Compliance & KYC
    "kycStatus": {
      "type": "enum",
      "options": ["Not Started", "In Progress", "Pending Documents", "Completed", "Failed"],
      "default": "Not Started",
      "required": true
    },
    "kycVerifiedBy": {
      "type": "link",
      "entity": "User"
    },
    "kycVerifiedDate": {
      "type": "datetime"
    },
    "dataConsentGiven": {
      "type": "bool",
      "default": false,
      "required": true,
      "tooltip": "Consent for data processing as per PDPA"
    },
    "dataConsentDate": {
      "type": "datetime"
    },
    "creditBureauConsentGiven": {
      "type": "bool",
      "default": false
    },
    "marketingConsentGiven": {
      "type": "bool",
      "default": false
    },
    "fraudCheckStatus": {
      "type": "enum",
      "options": ["Not Checked", "Clear", "Alert", "Blocked"],
      "default": "Not Checked"
    },
    "isDuplicate": {
      "type": "bool",
      "default": false,
      "readOnly": true
    },
    "duplicateOf": {
      "type": "link",
      "entity": "FinancialLead"
    },
    
    // Document Tracking
    "documentChecklistComplete": {
      "type": "bool",
      "default": false,
      "readOnly": true
    },
    "missingDocuments": {
      "type": "text",
      "readOnly": true
    },
    
    // Engagement Tracking
    "lastContactDate": {
      "type": "date"
    },
    "lastContactMethod": {
      "type": "enum",
      "options": ["Phone", "Email", "SMS", "WhatsApp", "In-Person", "Video Call"]
    },
    "nextFollowUpDate": {
      "type": "date"
    },
    "followUpPriority": {
      "type": "enum",
      "options": ["Low", "Normal", "High", "Urgent"],
      "default": "Normal"
    },
    "touchpointCount": {
      "type": "int",
      "default": 0,
      "readOnly": true
    },
    "responseRate": {
      "type": "float",
      "readOnly": true,
      "tooltip": "% of attempts that received response"
    },
    "daysInPipeline": {
      "type": "int",
      "readOnly": true,
      "formula": "datetime\\diff(createdAt, datetime\\now(), 'days')"
    },
    
    // Loss Reasons
    "lossReason": {
      "type": "enum",
      "options": [
        "Not Qualified",
        "High Interest Rate",
        "Competitor Offer",
        "No Longer Interested",
        "Unreachable",
        "Documentation Issues",
        "Credit Issues",
        "Amount Mismatch",
        "Other"
      ]
    },
    "lossRemarks": {
      "type": "text"
    }
  },
  
  "links": {
    "coApplicant": {
      "type": "hasOne",
      "entity": "CoApplicant",
      "foreign": "lead"
    },
    "guarantor": {
      "type": "hasOne",
      "entity": "Guarantor",
      "foreign": "lead"
    },
    "documents": {
      "type": "hasMany",
      "entity": "LeadDocument",
      "foreign": "lead"
    },
    "creditReports": {
      "type": "hasMany",
      "entity": "CreditReport",
      "foreign": "lead"
    },
    "qualificationChecks": {
      "type": "hasMany",
      "entity": "QualificationCheck",
      "foreign": "lead"
    },
    "loanProduct": {
      "type": "belongsTo",
      "entity": "LoanProduct"
    }
  }
}
```

#### 3.1.2 Supporting Entities

**CoApplicant Entity**
```json
{
  "entityType": "CoApplicant",
  "fields": {
    "name": "varchar",
    "relationship": {
      "type": "enum",
      "options": ["Spouse", "Parent", "Child", "Sibling", "Business Partner", "Other"]
    },
    "nicNumber": "varchar",
    "employmentType": "enum",
    "monthlyIncome": "currency",
    "contactNumber": "phone",
    "email": "email",
    "lead": {
      "type": "link",
      "entity": "FinancialLead"
    }
  }
}
```

**Guarantor Entity**
```json
{
  "entityType": "Guarantor",
  "fields": {
    "name": "varchar",
    "relationshipToApplicant": "varchar",
    "nicNumber": "varchar",
    "address": "address",
    "employmentDetails": "text",
    "contactNumber": "phone",
    "email": "email",
    "propertyDetails": "text",
    "consentGiven": "bool",
    "lead": {
      "type": "link",
      "entity": "FinancialLead"
    }
  }
}
```

**LeadDocument Entity**
```json
{
  "entityType": "LeadDocument",
  "fields": {
    "name": "varchar",
    "documentType": {
      "type": "enum",
      "options": [
        "NIC Front",
        "NIC Back",
        "Passport",
        "Driving License",
        "Salary Slip",
        "Bank Statement",
        "Income Tax Return",
        "Business Registration",
        "Utility Bill",
        "Employment Letter",
        "Property Documents",
        "Vehicle Registration",
        "Other"
      ]
    },
    "documentStatus": {
      "type": "enum",
      "options": ["Pending", "Submitted", "Verified", "Rejected"],
      "default": "Pending"
    },
    "isMandatory": "bool",
    "attachment": {
      "type": "attachment",
      "maxCount": 5
    },
    "verifiedBy": {
      "type": "link",
      "entity": "User"
    },
    "verifiedDate": "datetime",
    "rejectionReason": "text",
    "lead": {
      "type": "link",
      "entity": "FinancialLead"
    }
  }
}
```

**CreditReport Entity**
```json
{
  "entityType": "CreditReport",
  "fields": {
    "bureauName": {
      "type": "enum",
      "options": ["CRIB", "Other"]
    },
    "reportDate": "datetime",
    "creditScore": "int",
    "reportReference": "varchar",
    "reportSummary": "text",
    "numberOfActiveLoans": "int",
    "totalOutstandingAmount": "currency",
    "defaultHistory": "bool",
    "reportFile": {
      "type": "attachment",
      "maxCount": 1
    },
    "lead": {
      "type": "link",
      "entity": "FinancialLead"
    }
  }
}
```

**QualificationCheck Entity**
```json
{
  "entityType": "QualificationCheck",
  "fields": {
    "checkDate": "datetime",
    "checkType": {
      "type": "enum",
      "options": ["Pre-Qualification", "Full Qualification", "Re-Qualification"]
    },
    "eligibilityResult": {
      "type": "enum",
      "options": ["Qualified", "Not Qualified", "Conditional"]
    },
    "maxLoanAmount": "currency",
    "recommendedTenure": "int",
    "interestRateOffered": "float",
    "foirCalculated": "float",
    "dtiCalculated": "float",
    "qualificationRemarks": "text",
    "performedBy": {
      "type": "link",
      "entity": "User"
    },
    "lead": {
      "type": "link",
      "entity": "FinancialLead"
    }
  }
}
```

**LoanProduct Entity**
```json
{
  "entityType": "LoanProduct",
  "fields": {
    "name": "varchar",
    "productCode": "varchar",
    "productType": {
      "type": "enum",
      "options": ["Personal Loan", "Vehicle Loan", "Gold Loan", "Home Loan", "Business Loan", "Leasing", "Credit Card"]
    },
    "description": "text",
    "minLoanAmount": "currency",
    "maxLoanAmount": "currency",
    "minTenure": "int",
    "maxTenure": "int",
    "interestRateMin": "float",
    "interestRateMax": "float",
    "processingFeePercentage": "float",
    "minCreditScore": "int",
    "minMonthlyIncome": "currency",
    "maxFOIR": "float",
    "eligibilityCriteria": "text",
    "requiredDocuments": "array",
    "isActive": "bool",
    "displayOrder": "int"
  }
}
```

### 3.2 Enhanced Lead Pipeline Stages

```json
{
  "leadStatus": {
    "type": "enum",
    "options": [
      "New",
      "Contact Attempted",
      "Contacted",
      "Information Gathering",
      "Document Collection",
      "Credit Check",
      "Qualification In Progress",
      "Qualified",
      "Awaiting Decision",
      "Converted to Application",
      "Lost",
      "Nurturing"
    ],
    "default": "New",
    "style": {
      "New": "primary",
      "Contact Attempted": "default",
      "Contacted": "success",
      "Information Gathering": "warning",
      "Document Collection": "warning",
      "Credit Check": "info",
      "Qualification In Progress": "info",
      "Qualified": "success",
      "Awaiting Decision": "warning",
      "Converted to Application": "success",
      "Lost": "danger",
      "Nurturing": "default"
    }
  }
}
```

### 3.3 Formula-Based Calculations

**Lead Score Calculation**
```javascript
// Formula for leadScore field
$score = 0;

// Income-based scoring (0-25 points)
if(monthlyIncome >= 150000) $score = $score + 25;
else if(monthlyIncome >= 100000) $score = $score + 20;
else if(monthlyIncome >= 75000) $score = $score + 15;
else if(monthlyIncome >= 50000) $score = $score + 10;
else if(monthlyIncome >= 25000) $score = $score + 5;

// Employment type (0-15 points)
if(employmentType == 'Salaried') $score = $score + 15;
else if(employmentType == 'Self-Employed') $score = $score + 12;
else if(employmentType == 'Business Owner') $score = $score + 10;
else if(employmentType == 'Professional') $score = $score + 13;

// Credit score (0-30 points)
if(creditScore >= 750) $score = $score + 30;
else if(creditScore >= 700) $score = $score + 25;
else if(creditScore >= 650) $score = $score + 20;
else if(creditScore >= 600) $score = $score + 15;
else if(creditScore >= 550) $score = $score + 10;

// Document completeness (0-15 points)
if(documentChecklistComplete) $score = $score + 15;
else if(kycStatus == 'Completed') $score = $score + 10;
else if(kycStatus == 'In Progress') $score = $score + 5;

// Engagement (0-15 points)
if(touchpointCount >= 5) $score = $score + 15;
else if(touchpointCount >= 3) $score = $score + 10;
else if(touchpointCount >= 1) $score = $score + 5;

// Responsiveness bonus
if(responseRate >= 80) $score = $score + 5;

$score;
```

**FOIR Calculation**
```javascript
// Formula for foir field
if(monthlyIncome > 0) {
  $totalIncome = monthlyIncome + ifThenElse(additionalIncome, additionalIncome, 0);
  $obligations = ifThenElse(monthlyObligations, monthlyObligations, 0);
  math\round(($obligations / $totalIncome) * 100, 2);
} else {
  0;
}
```

**DTI Calculation**
```javascript
// Formula for dti field - similar to FOIR
if(monthlyIncome > 0) {
  $totalIncome = monthlyIncome + ifThenElse(additionalIncome, additionalIncome, 0);
  $obligations = ifThenElse(monthlyObligations, monthlyObligations, 0);
  math\round(($obligations / $totalIncome) * 100, 2);
} else {
  0;
}
```

**Days in Pipeline**
```javascript
// Formula for daysInPipeline field
datetime\diff(createdAt, datetime\now(), 'days');
```

---

## 4. API Integrations Architecture

### 4.1 Credit Bureau Integration (CRIB)

**Purpose:** Retrieve credit reports and scores for applicants

**Implementation Approach:**
```php
<?php
namespace Espo\Custom\Services;

class CreditBureauService extends \Espo\Core\Services\Base
{
    public function retrieveCreditReport($leadId)
    {
        $lead = $this->getEntityManager()->getEntity('FinancialLead', $leadId);
        
        if (!$lead) {
            throw new \Exception('Lead not found');
        }
        
        // Check consent
        if (!$lead->get('creditBureauConsentGiven')) {
            throw new \Exception('Credit bureau consent not given');
        }
        
        // Call CRIB API
        $cribService = $this->getServiceFactory()->create('CribApi');
        $reportData = $cribService->fetchCreditReport([
            'nicNumber' => $lead->get('nicNumber'),
            'fullName' => $lead->get('firstName') . ' ' . $lead->get('lastName'),
            'dateOfBirth' => $lead->get('dateOfBirth')
        ]);
        
        // Create CreditReport entity
        $creditReport = $this->getEntityManager()->createEntity('CreditReport', [
            'leadId' => $leadId,
            'bureauName' => 'CRIB',
            'reportDate' => date('Y-m-d H:i:s'),
            'creditScore' => $reportData['score'],
            'reportReference' => $reportData['referenceNumber'],
            'reportSummary' => json_encode($reportData['summary']),
            'numberOfActiveLoans' => $reportData['activeLoans'],
            'totalOutstandingAmount' => $reportData['totalOutstanding'],
            'defaultHistory' => $reportData['hasDefaults']
        ]);
        
        // Update lead
        $lead->set('creditScore', $reportData['score']);
        $lead->set('creditBureauStatus', 'Retrieved');
        $this->getEntityManager()->saveEntity($lead);
        
        // Trigger qualification check
        $this->runQualificationCheck($leadId);
        
        return $creditReport;
    }
}
```

### 4.2 SMS Gateway Integration

**Purpose:** Send SMS notifications for lead nurturing and updates

```php
<?php
namespace Espo\Custom\Services;

class SmsService extends \Espo\Core\Services\Base
{
    private $providers = [
        'dialog' => [
            'apiUrl' => 'https://api.dialog.lk/sms',
            'apiKey' => 'CONFIG_DIALOG_API_KEY'
        ],
        'mobitel' => [
            'apiUrl' => 'https://api.mobitel.lk/sms',
            'apiKey' => 'CONFIG_MOBITEL_API_KEY'
        ]
    ];
    
    public function sendSms($phoneNumber, $message, $provider = 'dialog')
    {
        $config = $this->providers[$provider];
        
        $client = new \GuzzleHttp\Client();
        $response = $client->post($config['apiUrl'], [
            'json' => [
                'apiKey' => $config['apiKey'],
                'to' => $phoneNumber,
                'message' => $message
            ]
        ]);
        
        // Log SMS in activity stream
        $this->getEntityManager()->createEntity('SmsLog', [
            'phoneNumber' => $phoneNumber,
            'message' => $message,
            'provider' => $provider,
            'status' => 'Sent',
            'sentAt' => date('Y-m-d H:i:s')
        ]);
        
        return json_decode($response->getBody(), true);
    }
    
    public function sendLeadWelcomeSms($leadId)
    {
        $lead = $this->getEntityManager()->getEntity('FinancialLead', $leadId);
        
        $message = "Dear {$lead->get('firstName')}, Thank you for your interest in our loan products. "
                 . "Your reference number is {$lead->get('number')}. "
                 . "Our representative will contact you shortly. - {COMPANY_NAME}";
        
        return $this->sendSms($lead->get('phoneNumber'), $message);
    }
}
```

### 4.3 Document Verification API

**Purpose:** Integrate with OCR and verification services for document validation

```php
<?php
namespace Espo\Custom\Services;

class DocumentVerificationService extends \Espo\Core\Services\Base
{
    public function verifyNicDocument($attachmentId)
    {
        $attachment = $this->getEntityManager()->getEntity('Attachment', $attachmentId);
        
        if (!$attachment) {
            throw new \Exception('Attachment not found');
        }
        
        // Call OCR service (example: Google Vision API)
        $ocrService = $this->getServiceFactory()->create('OcrApi');
        $extractedData = $ocrService->extractTextFromImage($attachment);
        
        // Parse NIC number and name
        $nicNumber = $this->parseNicNumber($extractedData['text']);
        $fullName = $this->parseNameFromNic($extractedData['text']);
        
        return [
            'nicNumber' => $nicNumber,
            'fullName' => $fullName,
            'extractedText' => $extractedData['text'],
            'confidence' => $extractedData['confidence']
        ];
    }
    
    private function parseNicNumber($text)
    {
        // Regex for Sri Lankan NIC (old: 9 digits + V/X, new: 12 digits)
        preg_match('/\b(\d{9}[VXvx]|\d{12})\b/', $text, $matches);
        return $matches[1] ?? null;
    }
}
```

### 4.4 Loan Product Recommendation Engine

**Purpose:** Match leads with suitable loan products based on profile

```php
<?php
namespace Espo\Custom\Services;

class LoanProductRecommendationService extends \Espo\Core\Services\Base
{
    public function recommendProducts($leadId)
    {
        $lead = $this->getEntityManager()->getEntity('FinancialLead', $leadId);
        
        if (!$lead) {
            throw new \Exception('Lead not found');
        }
        
        // Get all active loan products
        $products = $this->getEntityManager()
            ->getRepository('LoanProduct')
            ->where([
                'isActive' => true
            ])
            ->find();
        
        $recommendations = [];
        
        foreach ($products as $product) {
            $score = $this->calculateProductMatchScore($lead, $product);
            
            if ($score > 0) {
                $recommendations[] = [
                    'productId' => $product->get('id'),
                    'productName' => $product->get('name'),
                    'matchScore' => $score,
                    'maxEligibleAmount' => $this->calculateMaxEligibleAmount($lead, $product),
                    'estimatedEmi' => $this->calculateEmi($lead, $product)
                ];
            }
        }
        
        // Sort by match score descending
        usort($recommendations, function($a, $b) {
            return $b['matchScore'] - $a['matchScore'];
        });
        
        return $recommendations;
    }
    
    private function calculateProductMatchScore($lead, $product)
    {
        $score = 0;
        
        // Check minimum income
        if ($lead->get('monthlyIncome') >= $product->get('minMonthlyIncome')) {
            $score += 30;
        } else {
            return 0; // Not eligible
        }
        
        // Check credit score
        if ($lead->get('creditScore') >= $product->get('minCreditScore')) {
            $score += 30;
        } else {
            return 0; // Not eligible
        }
        
        // Check FOIR
        if ($lead->get('foir') <= $product->get('maxFOIR')) {
            $score += 20;
        } else {
            $score += 10; // Conditional
        }
        
        // Check loan amount fit
        $requestedAmount = $lead->get('loanAmountRequested');
        if ($requestedAmount >= $product->get('minLoanAmount') && 
            $requestedAmount <= $product->get('maxLoanAmount')) {
            $score += 20;
        }
        
        return $score;
    }
    
    private function calculateMaxEligibleAmount($lead, $product)
    {
        $monthlyIncome = $lead->get('monthlyIncome');
        $existingObligations = $lead->get('monthlyObligations') ?? 0;
        $maxFoir = $product->get('maxFOIR');
        $tenure = $product->get('maxTenure');
        $interestRate = $product->get('interestRateMin');
        
        // Calculate maximum EMI based on FOIR
        $maxEmi = ($monthlyIncome * $maxFoir / 100) - $existingObligations;
        
        // Calculate loan amount from EMI using standard formula
        $monthlyRate = $interestRate / 12 / 100;
        $loanAmount = $maxEmi * ((pow(1 + $monthlyRate, $tenure) - 1) / 
                                  ($monthlyRate * pow(1 + $monthlyRate, $tenure)));
        
        return min($loanAmount, $product->get('maxLoanAmount'));
    }
    
    private function calculateEmi($lead, $product)
    {
        $loanAmount = $lead->get('loanAmountRequested');
        $tenure = $lead->get('loanTenureRequested') ?? $product->get('maxTenure');
        $interestRate = $product->get('interestRateMin');
        
        // EMI formula: P * r * (1+r)^n / ((1+r)^n - 1)
        $monthlyRate = $interestRate / 12 / 100;
        $emi = $loanAmount * $monthlyRate * pow(1 + $monthlyRate, $tenure) / 
               (pow(1 + $monthlyRate, $tenure) - 1);
        
        return round($emi, 2);
    }
}
```

---

## 5. Business Process Workflows

### 5.1 New Lead Processing Workflow (BPM)

```xml
<bpmn:process id="NewLeadProcessing" name="New Lead Processing">
  <bpmn:startEvent id="LeadCreated" name="Lead Created">
    <bpmn:outgoing>Flow_1</bpmn:outgoing>
  </bpmn:startEvent>
  
  <!-- Auto-assign based on territory or round-robin -->
  <bpmn:serviceTask id="AutoAssign" name="Auto-assign Lead">
    <bpmn:incoming>Flow_1</bpmn:incoming>
    <bpmn:outgoing>Flow_2</bpmn:outgoing>
  </bpmn:serviceTask>
  
  <!-- Send welcome SMS -->
  <bpmn:serviceTask id="SendWelcomeSms" name="Send Welcome SMS">
    <bpmn:incoming>Flow_2</bpmn:incoming>
    <bpmn:outgoing>Flow_3</bpmn:outgoing>
  </bpmn:serviceTask>
  
  <!-- Create follow-up task -->
  <bpmn:serviceTask id="CreateFollowUpTask" name="Create Follow-up Task">
    <bpmn:incoming>Flow_3</bpmn:incoming>
    <bpmn:outgoing>Flow_4</bpmn:outgoing>
  </bpmn:serviceTask>
  
  <!-- Check for duplicates -->
  <bpmn:serviceTask id="CheckDuplicates" name="Check for Duplicates">
    <bpmn:incoming>Flow_4</bpmn:incoming>
    <bpmn:outgoing>Flow_5</bpmn:outgoing>
  </bpmn:serviceTask>
  
  <bpmn:exclusiveGateway id="IsDuplicate" name="Is Duplicate?">
    <bpmn:incoming>Flow_5</bpmn:incoming>
    <bpmn:outgoing>Flow_6_Yes</bpmn:outgoing>
    <bpmn:outgoing>Flow_6_No</bpmn:outgoing>
  </bpmn:exclusiveGateway>
  
  <!-- If duplicate, mark and notify -->
  <bpmn:serviceTask id="MarkDuplicate" name="Mark as Duplicate">
    <bpmn:incoming>Flow_6_Yes</bpmn:incoming>
    <bpmn:outgoing>Flow_7</bpmn:outgoing>
  </bpmn:serviceTask>
  
  <!-- If not duplicate, proceed normally -->
  <bpmn:serviceTask id="UpdateStatus" name="Update Status to 'Contact Attempted'">
    <bpmn:incoming>Flow_6_No</bpmn:incoming>
    <bpmn:outgoing>Flow_8</bpmn:outgoing>
  </bpmn:serviceTask>
  
  <bpmn:endEvent id="End">
    <bpmn:incoming>Flow_7</bpmn:incoming>
    <bpmn:incoming>Flow_8</bpmn:incoming>
  </bpmn:endEvent>
</bpmn:process>
```

### 5.2 Lead Qualification Workflow

```xml
<bpmn:process id="LeadQualification" name="Lead Qualification Process">
  <bpmn:startEvent id="InfoGathered" name="Information Gathered">
    <bpmn:outgoing>Flow_1</bpmn:outgoing>
  </bpmn:startEvent>
  
  <!-- Check KYC status -->
  <bpmn:exclusiveGateway id="CheckKyc" name="KYC Completed?">
    <bpmn:incoming>Flow_1</bpmn:incoming>
    <bpmn:outgoing>Flow_2_Yes</bpmn:outgoing>
    <bpmn:outgoing>Flow_2_No</bpmn:outgoing>
  </bpmn:exclusiveGateway>
  
  <!-- If KYC not complete, wait -->
  <bpmn:userTask id="CompleteKyc" name="Request KYC Documents">
    <bpmn:incoming>Flow_2_No</bpmn:incoming>
    <bpmn:outgoing>Flow_3</bpmn:outgoing>
  </bpmn:userTask>
  
  <!-- Run credit check -->
  <bpmn:serviceTask id="RunCreditCheck" name="Retrieve Credit Report">
    <bpmn:incoming>Flow_2_Yes</bpmn:incoming>
    <bpmn:incoming>Flow_3</bpmn:incoming>
    <bpmn:outgoing>Flow_4</bpmn:outgoing>
  </bpmn:serviceTask>
  
  <!-- Calculate FOIR/DTI -->
  <bpmn:serviceTask id="CalculateRatios" name="Calculate FOIR/DTI">
    <bpmn:incoming>Flow_4</bpmn:incoming>
    <bpmn:outgoing>Flow_5</bpmn:outgoing>
  </bpmn:serviceTask>
  
  <!-- Get product recommendations -->
  <bpmn:serviceTask id="RecommendProducts" name="Recommend Loan Products">
    <bpmn:incoming>Flow_5</bpmn:incoming>
    <bpmn:outgoing>Flow_6</bpmn:outgoing>
  </bpmn:serviceTask>
  
  <!-- Check qualification result -->
  <bpmn:exclusiveGateway id="CheckQualification" name="Qualified?">
    <bpmn:incoming>Flow_6</bpmn:incoming>
    <bpmn:outgoing>Flow_7_Qualified</bpmn:outgoing>
    <bpmn:outgoing>Flow_7_NotQualified</bpmn:outgoing>
  </bpmn:exclusiveGateway>
  
  <!-- If qualified, update status and notify -->
  <bpmn:serviceTask id="UpdateQualified" name="Update to 'Qualified'">
    <bpmn:incoming>Flow_7_Qualified</bpmn:incoming>
    <bpmn:outgoing>Flow_8</bpmn:outgoing>
  </bpmn:serviceTask>
  
  <bpmn:serviceTask id="SendQualifiedEmail" name="Send Qualification Email">
    <bpmn:incoming>Flow_8</bpmn:incoming>
    <bpmn:outgoing>Flow_9</bpmn:outgoing>
  </bpmn:serviceTask>
  
  <!-- If not qualified, update and close -->
  <bpmn:serviceTask id="UpdateNotQualified" name="Update to 'Not Qualified'">
    <bpmn:incoming>Flow_7_NotQualified</bpmn:incoming>
    <bpmn:outgoing>Flow_10</bpmn:outgoing>
  </bpmn:serviceTask>
  
  <bpmn:endEvent id="End">
    <bpmn:incoming>Flow_9</bpmn:incoming>
    <bpmn:incoming>Flow_10</bpmn:incoming>
  </bpmn:endEvent>
</bpmn:process>
```

### 5.3 Lead Nurturing Workflow

**Email Drip Campaign:**
- Day 0: Welcome email with loan calculator link
- Day 2: Educational content on loan process
- Day 5: Product comparison guide
- Day 7: Customer testimonials
- Day 10: Special offer (if not responded)
- Day 14: Last touch - "Still interested?"

**SMS Reminders:**
- Day 3: Reminder to complete KYC
- Day 7: Reminder about pending documents
- Day 14: Re-engagement message

---

## 6. User Interface Enhancements

### 6.1 Lead Capture Forms

**Web-to-Lead Form (Embedded on Website)**

```html
<!DOCTYPE html>
<html>
<head>
    <title>Quick Loan Application</title>
    <style>
        .loan-form { max-width: 600px; margin: 0 auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 8px; }
        .submit-btn { background: #007bff; color: white; padding: 12px 30px; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <div class="loan-form">
        <h2>Apply for a Loan in Minutes</h2>
        <form id="loanApplicationForm">
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="firstName" placeholder="First Name" required>
                <input type="text" name="lastName" placeholder="Last Name" required>
            </div>
            
            <div class="form-group">
                <label>Mobile Number *</label>
                <input type="tel" name="phoneNumber" pattern="[0-9]{10}" required>
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="emailAddress">
            </div>
            
            <div class="form-group">
                <label>Loan Type *</label>
                <select name="primaryProductInterest" required>
                    <option value="">-- Select --</option>
                    <option value="Personal Loan">Personal Loan</option>
                    <option value="Vehicle Loan">Vehicle Loan</option>
                    <option value="Gold Loan">Gold Loan</option>
                    <option value="Business Loan">Business Loan</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Loan Amount Required (LKR) *</label>
                <input type="number" name="loanAmountRequested" min="50000" step="10000" required>
            </div>
            
            <div class="form-group">
                <label>Monthly Income (LKR) *</label>
                <input type="number" name="monthlyIncome" min="25000" step="5000" required>
            </div>
            
            <div class="form-group">
                <label>Employment Type *</label>
                <select name="employmentType" required>
                    <option value="">-- Select --</option>
                    <option value="Salaried">Salaried</option>
                    <option value="Self-Employed">Self-Employed</option>
                    <option value="Business Owner">Business Owner</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="dataConsentGiven" value="1" required>
                    I consent to the processing of my personal data *
                </label>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="creditBureauConsentGiven" value="1">
                    I consent to credit bureau check
                </label>
            </div>
            
            <button type="submit" class="submit-btn">Submit Application</button>
        </form>
    </div>
    
    <script>
        document.getElementById('loanApplicationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            var formData = new FormData(this);
            var payload = {
                firstName: formData.get('firstName'),
                lastName: formData.get('lastName'),
                phoneNumber: formData.get('phoneNumber'),
                emailAddress: formData.get('emailAddress'),
                primaryProductInterest: formData.get('primaryProductInterest'),
                loanAmountRequested: parseFloat(formData.get('loanAmountRequested')),
                monthlyIncome: parseFloat(formData.get('monthlyIncome')),
                employmentType: formData.get('employmentType'),
                dataConsentGiven: formData.get('dataConsentGiven') === '1',
                creditBureauConsentGiven: formData.get('creditBureauConsentGiven') === '1',
                leadChannel: 'Website Form',
                status: 'New',
                utmSource: new URLSearchParams(window.location.search).get('utm_source'),
                utmMedium: new URLSearchParams(window.location.search).get('utm_medium'),
                utmCampaign: new URLSearchParams(window.location.search).get('utm_campaign')
            };
            
            // Submit to EspoCRM Lead Capture API
            fetch('https://your-espocrm.com/api/v1/LeadCapture/YOUR_API_KEY', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(response => response.json())
            .then(data => {
                alert('Application submitted successfully! We will contact you shortly.');
                this.reset();
            })
            .catch(error => {
                alert('Error submitting application. Please try again.');
                console.error(error);
            });
        });
    </script>
</body>
</html>
```

### 6.2 Custom Lead Detail View Layout

**Custom Layout Configuration (JSON)**

```json
{
  "FinancialLead": {
    "detail": {
      "layout": [
        {
          "label": "Overview",
          "rows": [
            [
              {"name": "firstName", "span": 1},
              {"name": "lastName", "span": 1}
            ],
            [
              {"name": "phoneNumber", "span": 1},
              {"name": "emailAddress", "span": 1}
            ],
            [
              {"name": "status", "span": 1},
              {"name": "leadTemperature", "span": 1}
            ],
            [
              {"name": "leadScore", "span": 1},
              {"name": "leadChannel", "span": 1}
            ],
            [
              {"name": "assignedUser", "span": 1},
              {"name": "createdAt", "span": 1}
            ]
          ]
        },
        {
          "label": "Financial Information",
          "rows": [
            [
              {"name": "employmentType", "span": 1},
              {"name": "monthlyIncome", "span": 1}
            ],
            [
              {"name": "additionalIncome", "span": 1},
              {"name": "monthlyObligations", "span": 1}
            ],
            [
              {"name": "foir", "span": 1},
              {"name": "dti", "span": 1}
            ],
            [
              {"name": "employerName", "span": 2}
            ],
            [
              {"name": "bankName", "span": 1},
              {"name": "bankAccountNumber", "span": 1}
            ]
          ]
        },
        {
          "label": "Loan Requirements",
          "rows": [
            [
              {"name": "primaryProductInterest", "span": 1},
              {"name": "loanAmountRequested", "span": 1}
            ],
            [
              {"name": "loanTenureRequested", "span": 1},
              {"name": "loanPurpose", "span": 1}
            ],
            [
              {"name": "assetDescription", "span": 2}
            ]
          ]
        },
        {
          "label": "Qualification Status",
          "rows": [
            [
              {"name": "eligibilityStatus", "span": 1},
              {"name": "creditScore", "span": 1}
            ],
            [
              {"name": "maxEligibleAmount", "span": 1},
              {"name": "recommendedTenure", "span": 1}
            ],
            [
              {"name": "creditBureauStatus", "span": 1},
              {"name": "kycStatus", "span": 1}
            ],
            [
              {"name": "eligibilityRemarks", "span": 2}
            ]
          ]
        },
        {
          "label": "Personal Details",
          "rows": [
            [
              {"name": "dateOfBirth", "span": 1},
              {"name": "gender", "span": 1}
            ],
            [
              {"name": "maritalStatus", "span": 1},
              {"name": "numberOfDependents", "span": 1}
            ],
            [
              {"name": "nicNumber", "span": 1},
              {"name": "nationality", "span": 1}
            ],
            [
              {"name": "currentAddress", "span": 2}
            ],
            [
              {"name": "residenceType", "span": 1},
              {"name": "yearsAtCurrentAddress", "span": 1}
            ]
          ]
        },
        {
          "label": "Compliance & Consent",
          "rows": [
            [
              {"name": "dataConsentGiven", "span": 1},
              {"name": "dataConsentDate", "span": 1}
            ],
            [
              {"name": "creditBureauConsentGiven", "span": 1},
              {"name": "marketingConsentGiven", "span": 1}
            ],
            [
              {"name": "fraudCheckStatus", "span": 1},
              {"name": "isDuplicate", "span": 1}
            ],
            [
              {"name": "kycVerifiedBy", "span": 1},
              {"name": "kycVerifiedDate", "span": 1}
            ]
          ]
        },
        {
          "label": "Engagement",
          "rows": [
            [
              {"name": "touchpointCount", "span": 1},
              {"name": "responseRate", "span": 1}
            ],
            [
              {"name": "lastContactDate", "span": 1},
              {"name": "lastContactMethod", "span": 1}
            ],
            [
              {"name": "nextFollowUpDate", "span": 1},
              {"name": "followUpPriority", "span": 1}
            ],
            [
              {"name": "daysInPipeline", "span": 1}
            ]
          ]
        },
        {
          "label": "Channel Attribution",
          "rows": [
            [
              {"name": "source", "span": 1},
              {"name": "sourceDetail", "span": 1}
            ],
            [
              {"name": "referredBy", "span": 1},
              {"name": "campaign", "span": 1}
            ],
            [
              {"name": "utmSource", "span": 1},
              {"name": "utmMedium", "span": 1}
            ],
            [
              {"name": "utmCampaign", "span": 2}
            ]
          ]
        }
      ]
    }
  }
}
```

### 6.3 Custom Dashboards for Lead Management

**Sales Manager Dashboard**

Dashlets to include:
1. **Lead Funnel Chart** - Conversion by stage
2. **Lead Source Performance** - Leads by channel with conversion %
3. **Top Performing Loan Officers** - Leaderboard by conversions
4. **Average Lead Score Trend** - Time series
5. **Qualification Rate** - % of leads qualified vs. total
6. **Document Completion Status** - Pie chart
7. **Credit Score Distribution** - Histogram
8. **Hot Leads Requiring Attention** - List view

**Loan Officer Dashboard**

Dashlets to include:
1. **My Leads Pipeline** - Kanban view by status
2. **Today's Follow-ups** - Task list
3. **Qualified Leads Awaiting Conversion** - List view
4. **Recent Credit Reports** - Timeline
5. **Document Checklist Pending** - Count
6. **My Conversion Rate This Month** - KPI
7. **Average Response Time** - KPI

---

## 7. Reporting & Analytics

### 7.1 Standard Reports

**Lead Performance Report**
- Total leads by period
- Leads by channel
- Conversion rate by channel
- Average days to conversion
- Lead score distribution
- Qualified vs. Not Qualified breakdown

**Channel Attribution Report**
- Cost per lead by channel
- Conversion rate by channel
- ROI by marketing campaign
- UTM parameter analysis

**Loan Officer Performance Report**
- Leads assigned vs. converted
- Average lead score
- Average response time
- Touchpoint efficiency
- Document completion rate

**Qualification Metrics Report**
- Qualification rate (% of leads qualified)
- Average credit score of qualified leads
- FOIR/DTI distribution
- Rejection reasons breakdown
- Time to qualification

**Financial Analysis Report**
- Average loan amount requested
- Average monthly income of applicants
- Product preference trends
- Loan amount vs. income correlation

### 7.2 Custom Report Filters

```json
{
  "filters": [
    {
      "name": "dateRange",
      "type": "dateRange",
      "field": "createdAt"
    },
    {
      "name": "leadChannel",
      "type": "multiEnum",
      "field": "leadChannel"
    },
    {
      "name": "status",
      "type": "multiEnum",
      "field": "status"
    },
    {
      "name": "eligibilityStatus",
      "type": "multiEnum",
      "field": "eligibilityStatus"
    },
    {
      "name": "assignedUser",
      "type": "link",
      "field": "assignedUser"
    },
    {
      "name": "leadScoreRange",
      "type": "intRange",
      "field": "leadScore"
    },
    {
      "name": "loanAmountRange",
      "type": "currencyRange",
      "field": "loanAmountRequested"
    },
    {
      "name": "productInterest",
      "type": "enum",
      "field": "primaryProductInterest"
    }
  ]
}
```

---

## 8. Mobile Responsiveness

### 8.1 Mobile-Optimized Lead Capture

Key considerations:
- Single-column layout for easy thumb navigation
- Progressive disclosure (show fields as user progresses)
- Auto-formatting for phone numbers (e.g., 077-XXX-XXXX)
- Loan calculator integration
- One-tap document upload via camera
- SMS OTP verification for security

### 8.2 Loan Officer Mobile App Requirements

**Must-have features:**
- Lead list with quick filters (Hot/Qualified/Follow-up Due)
- One-tap call/SMS/WhatsApp actions
- Document capture and upload
- Quick note entry
- Task completion
- Offline mode with sync
- GPS check-in for field visits

---

## 9. Security & Compliance

### 9.1 Data Protection

**Field-Level Encryption:**
- NIC number
- Bank account number
- Passport number

**Role-Based Access Control:**

```json
{
  "roles": {
    "loanOfficer": {
      "permissions": {
        "FinancialLead": {
          "read": "team",
          "create": "yes",
          "edit": "own",
          "delete": "no"
        },
        "creditScore": {
          "read": "yes",
          "edit": "no"
        },
        "bankAccountNumber": {
          "read": "no"
        }
      }
    },
    "seniorManager": {
      "permissions": {
        "FinancialLead": {
          "read": "all",
          "create": "yes",
          "edit": "all",
          "delete": "yes"
        },
        "creditScore": {
          "read": "yes",
          "edit": "no"
        },
        "bankAccountNumber": {
          "read": "yes"
        }
      }
    }
  }
}
```

### 9.2 Audit Trail

All changes to financial leads should be logged:
- Who made the change
- What was changed (before/after values)
- When the change occurred
- IP address and device information

### 9.3 Consent Management

Implement consent tracking for:
- Data processing consent (mandatory)
- Credit bureau consent
- Marketing communications consent
- Third-party data sharing consent

Record:
- Consent given date/time
- Consent method (web form, verbal, SMS)
- Consent version (track consent text changes)
- Withdrawal date (if applicable)

---

## 10. Implementation Roadmap

### Phase 1: Core Entity Structure (Weeks 1-3)
- Create FinancialLead entity with custom fields
- Create supporting entities (CoApplicant, Guarantor, LeadDocument, CreditReport, QualificationCheck, LoanProduct)
- Implement relationships
- Configure field validations
- Set up formula calculations (FOIR, DTI, Lead Score)

### Phase 2: UI & Layouts (Weeks 4-5)
- Design custom detail view layout
- Create list view with custom filters
- Build Kanban board for pipeline visualization
- Design web-to-lead capture form
- Create custom dashboards

### Phase 3: Workflows & Automation (Weeks 6-8)
- Implement new lead processing workflow
- Set up auto-assignment rules
- Create lead nurturing email sequences
- Build SMS notification triggers
- Configure follow-up automation

### Phase 4: API Integrations (Weeks 9-11)
- Integrate CRIB credit bureau API
- Connect SMS gateways (Dialog, Mobitel)
- Implement document OCR service
- Build loan product recommendation engine
- Set up duplicate detection

### Phase 5: Advanced Features (Weeks 12-14)
- Develop lead scoring algorithm refinement
- Build advanced reporting dashboards
- Implement fraud detection rules
- Create mobile-responsive interfaces
- Set up compliance & audit logging

### Phase 6: Testing & Deployment (Weeks 15-16)
- Unit testing of all custom components
- Integration testing with external APIs
- User acceptance testing (UAT)
- Performance optimization
- Documentation and training
- Production deployment

---

## 11. Key Success Metrics

**Lead Management KPIs:**
- Lead response time < 15 minutes
- Lead conversion rate > 15%
- Average days in pipeline < 21 days
- Document completion rate > 80%
- Duplicate lead rate < 5%

**Quality Metrics:**
- Lead score accuracy > 85%
- Qualification accuracy > 90%
- Credit check success rate > 95%
- Form abandonment rate < 30%

**Engagement Metrics:**
- Average touchpoints to conversion: 5-7
- Response rate > 60%
- Follow-up completion rate > 90%
- SMS delivery rate > 98%

---

## 12. Conclusion

This comprehensive Financial Services Lead Management Module transforms EspoCRM into a purpose-built solution for financial companies in Sri Lanka. The enhancement addresses critical gaps in the standard lead entity while maintaining compatibility with EspoCRM's core architecture.

**Key Differentiators:**
1. **Financial-specific data capture** - Income, assets, employment, credit scoring
2. **Multi-party relationship tracking** - Co-applicants, guarantors
3. **Regulatory compliance** - KYC, consent management, data protection
4. **Intelligent qualification** - Automated credit checks, FOIR/DTI calculation, product matching
5. **Multi-channel attribution** - Comprehensive tracking of digital and physical channels
6. **Document management** - Structured checklist with verification workflows

**Implementation Considerations:**
- Estimated development effort: 12-16 weeks
- Required skills: PHP (EspoCRM backend), JavaScript (frontend), API integration
- Licensing: EspoCRM Advanced Pack required for BPM workflows
- Infrastructure: Database encryption support, secure API hosting

This module positions EspoCRM as a competitive alternative to specialized financial CRM platforms while leveraging its open-source flexibility and customization capabilities.
