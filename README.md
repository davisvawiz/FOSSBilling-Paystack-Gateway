Respiratory – FOSSBilling Paystack Gateway

<p align="center">
  <img src="https://tukuz.com/wp-content/uploads/2020/10/paystack-logo-vector.png" alt="DaviHost" width="150">
</p><p align="center">
  <strong>A modern Paystack payment gateway for FOSSBilling.</strong>
  <br>
  Secure payments. Automated invoice settlement. Beautiful checkout experience.
</p><p align="center">
  <img src="https://img.shields.io/badge/License-Apache%202.0-blue?style=for-the-badge" alt="Apache 2.0">
  <img src="https://img.shields.io/badge/PHP-8.0%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.0+">
  <img src="https://img.shields.io/badge/FOSSBilling-0.8.9-success?style=for-the-badge" alt="FOSSBilling 0.8.9">
</p><p align="center">
  <img src="https://img.shields.io/badge/Status-Working-brightgreen?style=for-the-badge" alt="Working">
  <img src="https://img.shields.io/badge/Paystack-Integrated-00C3F7?style=for-the-badge&logo=paystack&logoColor=white" alt="Paystack">
  <img src="https://img.shields.io/badge/Contributions-Welcome-orange?style=for-the-badge" alt="Contributions welcome">
</p>---

📖 About

Respiratory is a Paystack payment gateway integration developed for FOSSBilling.

It brings Paystack's secure payment infrastructure into FOSSBilling, allowing customers to pay invoices conveniently through Paystack's checkout popup.

The integration handles server-side transaction verification, automatic invoice settlement, transaction validation, and customer redirects.

Built with reliability, security, and customer experience in mind.

✨ Features

- 💳 Paystack popup checkout integration.
- 🔐 Secure server-side transaction verification.
- ⚡ Automatic invoice payment processing.
- ✅ Automatic invoice status updates after successful payment.
- 🔄 Automatic customer redirection after payment.
- 🎨 Beautiful animated payment confirmation screen.
- 🛡️ Duplicate transaction protection.
- 💰 Invoice amount verification.
- 🌍 Currency validation.
- 🧾 FOSSBilling-native transaction processing.
- 🧪 Separate test and live API credentials.
- 📱 Responsive payment confirmation experience.

🎬 See It In Action

Want to see how the integration works?

Visit our live demonstration:

<p align="center">
  <a href="https://my.davihost.com/">
    <img src="https://img.shields.io/badge/LIVE%20DEMO-See%20Paystack%20Integration-00C3F7?style=for-the-badge" alt="Live Demo">
  </a>
</p>https://my.davihost.com/

Experience the FOSSBilling invoice payment workflow and Paystack checkout integration.

🔄 How It Works

The payment workflow is designed to provide a seamless experience from checkout to invoice settlement.

Customer opens an invoice
          |
          v
      Clicks Pay Now
          |
          v
   Paystack popup opens
          |
          v
 Customer completes payment
          |
          v
 Server verifies transaction
          |
          v
 FOSSBilling receives IPN
          |
          v
 Transaction is validated
          |
          v
 Invoice is automatically paid
          |
          v
 Animated confirmation screen
          |
          v
 Customer returns to invoice

Successful popup completion alone does not mark an invoice as paid. Payment must first be verified and processed by FOSSBilling.

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
Configure API Keys
    |
Enable

5. Clear the cache

Clear your FOSSBilling cache after installing or updating the gateway.

Paystack should now be available as a payment option during invoice checkout.

⚙️ Configuration

Configure your Paystack API credentials inside the FOSSBilling administrator panel.

Setting| Description
Live Public Key| Paystack live public key
Live Secret Key| Paystack live secret key
Test Public Key| Paystack test public key
Test Secret Key| Paystack test secret key

Recommended: Start with test credentials and complete a full invoice payment before switching to live mode.

Never expose your Paystack secret key in frontend JavaScript or public repositories.

📋 Requirements

- FOSSBilling 0.8.9
- PHP 8.0 or newer
- A Paystack merchant account
- Valid Paystack API credentials
- PHP cURL extension enabled

🔐 Security and Reliability

Payment security and accurate invoice settlement are essential.

Respiratory incorporates:

- Server-side Paystack transaction verification.
- Secret-key-based API authentication.
- Invoice amount validation.
- Currency validation.
- Transaction reference validation.
- Duplicate transaction protection.
- FOSSBilling-native payment processing.
- Controlled payment confirmation and redirects.

The gateway does not rely solely on the browser's payment-success callback to establish payment completion.

🛠️ Troubleshooting

Paystack popup does not open

- Check whether Paystack's JavaScript library loads.
- Check your browser console for JavaScript errors.
- Verify your public API key.

Payment succeeds but invoice remains unpaid

- Check FOSSBilling transaction records.
- Inspect the payment verification response.
- Check FOSSBilling application logs.
- Confirm that the notification/IPN request is processed successfully.

Customer is not redirected

- Check browser console errors.
- Verify the FOSSBilling notification endpoint.
- Check the invoice return URL.

For bugs or technical assistance, please open an issue in this repository.

🗺️ Roadmap

Potential future enhancements:

- [ ] Enhanced webhook integration.
- [ ] Subscription payment support.
- [ ] Refund management.
- [ ] Advanced transaction logging.
- [ ] Expanded currency support.
- [ ] Additional gateway configuration options.
- [ ] Further checkout experience improvements.

🤝 Contributing

Contributions are welcome!

Whether you are fixing bugs, improving security, enhancing the checkout experience, or adding new functionality, your contributions are appreciated.

You can contribute by:

- Reporting bugs.
- Suggesting improvements.
- Submitting pull requests.
- Improving documentation.
- Testing compatibility with newer FOSSBilling releases.

Together, we can make Paystack integration better for the FOSSBilling community.

👨‍💻 Author

Davis Vawiz

Developer and maintainer of Respiratory – FOSSBilling Paystack Gateway.

- 🌐 Website: https://davisvawiz.space/
- 🐙 GitHub: https://github.com/davisvawiz

🌐 Recommended Hosting – DaviHost

Looking for reliable hosting for your website or business?

Check out DaviHost, a hosting service by Davis Vawiz.

<p align="center">
  <a href="https://davihost.com/">
    <img src="https://davihost.com/logo.png" alt="DaviHost Logo" width="190">
  </a>
  <br>
  <strong>Build. Host. Grow.</strong>
  <br>
  <a href="https://davihost.com/">Visit DaviHost →</a>
</p>📄 License

This project is licensed under the Apache License 2.0.

See the "LICENSE" (LICENSE) file for details.

---

<p align="center">
  Made with ❤️ by <a href="https://davisvawiz.space/">Davis Vawiz</a>
  <br>
  Built for the FOSSBilling ecosystem.
</p>
