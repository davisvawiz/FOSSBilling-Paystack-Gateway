# Respiratory – FOSSBilling Paystack Gateway

![License](https://img.shields.io/badge/License-Apache%202.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-%3E%3D8.0-777BB4)
![FOSSBilling](https://img.shields.io/badge/FOSSBilling-Compatible-success)
![Status](https://img.shields.io/badge/Status-Working-success)
![Maintenance](https://img.shields.io/badge/Maintained-Yes-brightgreen)
![Contributions](https://img.shields.io/badge/Contributions-Welcome-orange)
![Paystack](https://img.shields.io/badge/Paystack-Integrated-00C3F7)

A Paystack payment gateway integration for FOSSBilling providing secure payment processing, transaction verification, automated invoice handling, and seamless payment workflows.

---

## Features

- One-time invoice payments
- Paystack popup checkout integration
- Automatic payment verification
- Automatic invoice payment processing
- Redirects users back to invoice page after payment
- Duplicate transaction protection
- Amount verification
- Currency verification
- Transaction validation
- FOSSBilling-compatible workflow

---

## Current Status

**Status:** Stable and working

Verified functionality:

- ✅ Invoice creation
- ✅ Payment processing
- ✅ Transaction verification
- ✅ Automatic invoice payment
- ✅ Redirect to paid invoice page
- ✅ Callback processing

Current implementation has been tested and is functioning correctly.

This project will continue receiving updates and improvements to enhance reliability, functionality, security, and overall payment workflow experience.

---

## Payment Flow

```text
Customer clicks Pay Now
        ↓
Paystack checkout popup opens
        ↓
Customer completes payment
        ↓
Paystack verifies payment
        ↓
FOSSBilling processes transaction
        ↓
Invoice automatically marked as paid
        ↓
Customer redirected back to invoice page
```

---

## Installation

### 1. Clone repository

```bash
git clone https://github.com/davisvawiz/fossbilling-paystack.git
```

### 2. Copy gateway file

Move:

```text
Paystack.php
```

To:

```text
/src/library/Payment/Adapter/
```

### 3. Copy logo

Move:

```text
paystack.png
```

To:

```text
/html/assets/img/gateways/
```

### 4. Enable gateway

Inside FOSSBilling Admin Panel:

```text
System Settings
    ↓
Payment Gateways
    ↓
Paystack
    ↓
Configure API Keys
    ↓
Enable
```

---

## Configuration

Configure the following keys:

| Setting | Description |
|----------|-------------|
| Public Key | Paystack public key |
| Secret Key | Paystack secret key |
| Test Public Key | Paystack test public key |
| Test Secret Key | Paystack test secret key |

---

## Requirements

- PHP 8.0+
- FOSSBilling
- Paystack account
- Paystack API keys

---

## Planned Improvements

Future enhancements may include:

- Webhook support
- Signature verification
- Subscription support
- Refund support
- Better transaction logging
- Improved error handling
- Additional currency handling
- Configuration enhancements

---

## Contributing

Contributions, improvements, suggestions, bug reports, and pull requests are welcome.

Ways to contribute:

- Report issues
- Suggest improvements
- Submit pull requests
- Improve documentation
- Enhance payment handling
- Add new features

Community contributions help improve the project and make it more reliable.

---

## Author

**Davis Vawiz**

Website: https://davisvawiz.space

GitHub: https://github.com/davisvawiz

---

## License

Licensed under the Apache License 2.0.

See the `LICENSE` file for details.

---

Built for the FOSSBilling ecosystem using Paystack payment services.
