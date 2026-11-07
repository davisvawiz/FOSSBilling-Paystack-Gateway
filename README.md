Respiratory – FOSSBilling Paystack Gateway

"License" (https://img.shields.io/badge/License-Apache%202.0-blue.svg)
"PHP" (https://img.shields.io/badge/PHP-%3E%3D8.0-777BB4)
"FOSSBilling" (https://img.shields.io/badge/FOSSBilling-0.8.9-success)
"Status" (https://img.shields.io/badge/Status-Working-brightgreen)
"Maintenance" (https://img.shields.io/badge/Maintained-Yes-brightgreen)
"Contributions" (https://img.shields.io/badge/Contributions-Welcome-orange)
"Paystack" (https://img.shields.io/badge/Paystack-Integrated-00C3F7)

A modern Paystack payment gateway for FOSSBilling, designed to deliver secure transactions, reliable invoice processing, and a smooth customer checkout experience.

Respiratory brings Paystack payments directly into FOSSBilling, allowing customers to pay invoices through Paystack's secure checkout while FOSSBilling handles transaction verification, payment confirmation, and invoice settlement.

---

✨ Features

- Paystack Checkout: Convenient popup-based payment experience.
- Secure Payment Verification: Server-side verification against the Paystack API.
- Automatic Invoice Settlement: Automatically processes successful payments and updates invoice status.
- Smart Redirects: Returns customers to their invoice after payment confirmation.
- Beautiful Confirmation Experience: Modern, animated payment confirmation screen with a premium visual design.
- Duplicate Payment Protection: Helps prevent repeated transaction processing.
- Amount Validation: Verifies that the received payment matches the expected invoice amount.
- Currency Validation: Checks transaction currency against the invoice currency.
- Test and Live Modes: Configure separate Paystack API credentials for testing and production.
- FOSSBilling Integration: Uses FOSSBilling's native payment-processing workflow.

---

🚀 Compatibility

Component| Requirement
FOSSBilling| 0.8.9
PHP| 8.0 or newer
Payment Provider| Paystack
Payment Type| One-time payments
Checkout| Paystack Popup
License| Apache 2.0

---

💳 See How It Works

Want to see the integration in action?

Visit our live demonstration:

"https://my.davihost.com/" (https://my.davihost.com/)

Experience the invoice payment workflow and see how Paystack integrates into the FOSSBilling customer experience.

---

🔄 Payment Workflow

Customer opens an unpaid invoice
             |
             v
       Clicks Pay Now
             |
             v
    Paystack checkout popup
             |
             v
    Customer completes payment
             |
             v
   Paystack transaction verification
             |
             v
   FOSSBilling IPN processing
             |
             v
    Transaction validation
             |
             v
      Invoice settlement
             |
             v
    Invoice marked as PAID
             |
             v
   Animated confirmation screen
             |
             v
    Redirect to paid invoice

The integration uses server-side payment verification rather than trusting the browser's success callback alone.

---

📦 Installation

1. Clone the repository

git clone https://github.com/davisvawiz/FOSSBilling-Paystack-Gateway.git

2. Install the payment adapter

Copy:

Paystack.php

Into your FOSSBilling installation:

/src/library/Payment/Adapter/

3. Install the Paystack logo

Copy:

paystack.png

Into:

/html/assets/img/gateways/

4. Enable Paystack

Log in to your FOSSBilling administrator panel.

Navigate to:

System Settings
    |
Payment Gateways
    |
Paystack
    |
Configure API Credentials
    |
Enable

5. Clear cache

Clear your FOSSBilling cache after installing or updating the adapter.

Your Paystack gateway should now be available to customers during invoice checkout.

---

⚙️ Configuration

The gateway supports separate credentials for test and live environments.

Configuration| Description
Live Public Key| Paystack live public key
Live Secret Key| Paystack live secret key
Test Public Key| Paystack test public key
Test Secret Key| Paystack test secret key

Recommended: Start with Paystack test credentials and complete a full invoice payment before switching to live mode.

Never expose your Paystack secret key in frontend JavaScript or public repositories.

---

🔐 Security and Reliability

Payment security and accurate invoice settlement are essential.

Respiratory incorporates:

- Server-side transaction verification.
- Secret-key-based API authentication.
- Invoice amount validation.
- Currency validation.
- Transaction reference verification.
- Duplicate transaction protection.
- FOSSBilling-native payment processing.
- Controlled payment confirmation and redirects.

Successful popup completion alone does not mark an invoice as paid. The transaction must pass server-side verification and FOSSBilling payment processing.

---

🛠️ Troubleshooting

Payment popup does not open

- Confirm that Paystack's JavaScript library loads.
- Check browser console errors.
- Ensure your public key is correctly configured.

Payment succeeds but invoice remains unpaid

- Check FOSSBilling transaction records.
- Inspect the payment verification response.
- Check FOSSBilling application logs.
- Confirm that the notification/IPN request is being processed successfully.

Customer is not redirected

- Check the browser console.
- Verify that the FOSSBilling notification endpoint responds correctly.
- Check the invoice return URL.

For bugs and technical assistance, please open an issue in this repository.

---

🗺️ Roadmap

Potential future improvements:

- [ ] Enhanced webhook integration.
- [ ] Subscription payment support.
- [ ] Refund management.
- [ ] Advanced transaction logging.
- [ ] Expanded currency support.
- [ ] Additional gateway configuration options.
- [ ] Further checkout experience improvements.

---

🤝 Contributing

Contributions are welcome!

Whether you're fixing bugs, improving security, enhancing the checkout experience, or adding new functionality, your contributions are appreciated.

You can contribute by:

- Reporting bugs.
- Suggesting improvements.
- Submitting pull requests.
- Improving documentation.
- Testing compatibility with newer FOSSBilling releases.

Together, we can make Paystack integration better for the FOSSBilling community.

---

👨‍💻 Author

Davis Vawiz

Developer and maintainer of Respiratory – FOSSBilling Paystack Gateway.

- Website: "davisvawiz.space" (https://davisvawiz.space/)
- GitHub: "github.com/davisvawiz" (https://github.com/davisvawiz)

---

🌐 Recommended: DaviHost

Looking for reliable hosting for your website or business?

Check out DaviHost, a hosting service by Davis Vawiz.

<p align="center">
  <a href="https://davihost.com/">
    <img src="https://davihost.com/logo.png" alt="DaviHost Logo" width="190">
  </a>
</p><p align="center">
  <strong>Build. Host. Grow.</strong>
  <br>
  <a href="https://davihost.com/">Visit DaviHost →</a>
</p>---

📄 License

This project is licensed under the Apache License 2.0.

See the "LICENSE" (LICENSE) file for details.

---

<p align="center">
  Made with ❤️ by <a href="https://davisvawiz.space/">Davis Vawiz</a>
  <br>
  Built for the FOSSBilling ecosystem.
</p>
