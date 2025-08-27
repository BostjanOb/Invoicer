# Simple Invoice Management System

## Current Workflow

Currently, invoices are generated using a Google Sheets template and stored as PDFs on Google Drive. Each issued invoice is logged manually in a separate Google Sheet for tracking purposes.

## Goal

This application will provide a streamlined solution for generating, issuing, and tracking invoices within a single platform.

The project will be open-source. All user data must be stored in the application database, ensuring no personal information is persisted outside the system.

---

## Core Functionality

* **Invoice Numbering**

  * Invoices are tracked per year.
  * The counter resets annually.
  * Format: `001-2025` (sequential number + current year).
  * Drafts do not receive a number until issuance.

* **Invoice Issuance**

  * Once issued, the application generates a PDF.
  * PDFs are stored on Google Drive under the path: `year/number.pdf`.

* **Email Delivery**

  * Ability to send invoices directly to customers via different SMTP providers.
  * Custom message body configurable per email.
  * PDF invoice is automatically attached.

* **Recurring Invoices**

  * Support for creating recurring invoices with automatic dispatch.
  * Notifications sent to users after successful delivery.
  * Configurable option to skip weekends.

---

## Tech Stack

* **Backend Framework:** Laravel v12
* **Admin Panel / UI:** Filament v4

---

## Data Models

### User

Represents an account holder. Each user is also an invoice issuer.

**Schema:**

* `id`
* `email`
* `password`
* `name`
* `phone`
* `signature` (image)

**Company Details:**

* `company_name`
* `company_address`
* `company_city`
* `company_postcode`
* `company_country`
* `company_tax_number`
* `company_logo` (image)

**Bank Details:**

* `bank_iban`
* `bank_bic`
* `bank_name`

---

### Customer

Represents a client who receives invoices.

**Schema:**

* `id`
* `name`
* `address`
* `city`
* `postcode`
* `country`
* `tax_number`

---

### Invoice

Represents an invoice document issued to a customer.

**Schema:**

* `id`
* `customer_id` (FK → Customer)
* `number`
* `status` (`draft`, `issued`, `paid`)
* `issue_date`
* `payment_deadline`
* `service_text` (e.g., “Service period: from–to” or single date)

---

### Invoice Item

Represents a line item on an invoice.

**Schema:**

* `id`
* `invoice_id` (FK → Invoice)
* `title`
* `description`
* `price`
* `quantity`