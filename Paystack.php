<?php
/**
 * Respiratory - FOSSBilling Paystack Gateway
 *
 * Secure Paystack payment gateway integration
 * for FOSSBilling.
 *
 * Compatible with FOSSBilling 0.8.9 payment adapter API.
 *
 * @author Davis Vawiz
 * @link https://github.com/davisvawiz
 * @link https://davisvawiz.space
 *
 * @version 1.2.0
 * @copyright 2026 Davis Vawiz
 * @license Apache-2.0
 * SPDX-License-Identifier: Apache-2.0
 */

class Payment_Adapter_Paystack implements FOSSBilling\InjectionAwareInterface
{
    protected ?Pimple\Container $di = null;

    private string $secretKey;
    private string $publicKey;

    public function __construct(private $config)
    {
        $testMode = !empty($this->config['test_mode']);

        $this->secretKey = (string) ($testMode
            ? ($this->config['test_secret_key'] ?? '')
            : ($this->config['secret_key'] ?? ''));

        $this->publicKey = (string) ($testMode
            ? ($this->config['test_public_key'] ?? '')
            : ($this->config['public_key'] ?? ''));

        if ($this->secretKey === '' || $this->publicKey === '') {
            throw new Payment_Exception(
                'Paystack is not fully configured. Please check the public and secret keys.'
            );
        }
    }

    public function setDi(Pimple\Container $di): void
    {
        $this->di = $di;
    }

    public function getDi(): ?Pimple\Container
    {
        return $this->di;
    }

    /** FOSSBilling must render this adapter's HTML/JavaScript directly. */
    public function getType(): string
    {
        return Payment_AdapterAbstract::TYPE_HTML;
    }

    /**
     * Resolve the invoice from the IPN query/post payload. FOSSBilling 0.8.9
     * can provide the request nested under get/post, so support both shapes.
     */
    public function getInvoiceId($data): ?int
    {
        if (!is_array($data)) {
            return null;
        }

        $invoiceId = $data['get']['invoice_id']
            ?? $data['post']['invoice_id']
            ?? $data['invoice_id']
            ?? null;

        return is_numeric($invoiceId) && (int) $invoiceId > 0
            ? (int) $invoiceId
            : null;
    }

    public static function getConfig(): array
    {
        return [
            'supports_one_time_payments' => true,
            'supports_subscriptions' => false,
            'description' => 'Accept invoice payments securely through Paystack.',
            'logo' => [
                'logo' => 'paystack.png',
                'height' => '40px',
                'width' => '150px',
            ],
            'form' => [
                'public_key' => [
                    'text',
                    ['label' => 'Live Public Key'],
                ],
                'secret_key' => [
                    'password',
                    ['label' => 'Live Secret Key', 'secret' => true],
                ],
                'test_public_key' => [
                    'text',
                    ['label' => 'Test Public Key', 'required' => false],
                ],
                'test_secret_key' => [
                    'password',
                    ['label' => 'Test Secret Key', 'required' => false, 'secret' => true],
                ],
            ],
        ];
    }

    /**
     * FOSSBilling 0.8.9 uses the same browser-return/IPN flow as the bundled
     * Stripe adapter: the provider returns through notify_url with redirect
     * and invoice_hash parameters. No PHP session restoration is required.
     */
    public function getHtml(FOSSBilling\Api\Proxy $api_admin, int $invoice_id, bool $subscription): string
    {
        if ($subscription) {
            throw new Payment_Exception('Paystack does not support subscription payments.');
        }

        // Match the bundled Stripe adapter: use the invoice model and the
        // Invoice service's tax-inclusive total rather than trusting browser data.
        $invoice = $this->di['db']->load('Invoice', $invoice_id);
        if (!$invoice) {
            throw new Payment_Exception('Unable to load the invoice for Paystack payment.');
        }

        if (strtolower((string) $invoice->status) === 'paid') {
            return '<div class="alert alert-success">This invoice has already been paid.</div>';
        }

        $amount = $this->toSmallestCurrencyUnit(
            (float) $this->di['mod_service']('Invoice')->getTotalWithTax($invoice),
            (string) $invoice->currency
        );
        if ($amount < 1) {
            throw new Payment_Exception('The invoice amount is invalid for Paystack.');
        }

        $email = (string) $invoice->buyer_email;
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Payment_Exception('A valid client email address is required for Paystack.');
        }

        $notifyUrl = (string) ($this->config['notify_url'] ?? '');
        if ($notifyUrl === '') {
            throw new Payment_Exception('FOSSBilling did not provide a payment notification URL.');
        }

        $returnUrl = (string) ($this->config['return_url'] ?? '');
        if ($returnUrl === '') {
            throw new Payment_Exception('FOSSBilling did not provide a browser return URL.');
        }

        // FOSSBilling 0.8.9 separates the browser return from IPN processing:
        // Paystack's callback_url is the native invoice return route, while
        // the same-origin browser callback below invokes notify_url to run IPN.
        $callbackUrl = $returnUrl;

        $reference = 'FB_' . bin2hex(random_bytes(16));
        $payload = [
            'email' => $email,
            'amount' => (string) $amount,
            'currency' => strtoupper((string) $invoice->currency),
            'reference' => $reference,
            'callback_url' => $callbackUrl,
            'metadata' => [
                'invoice_id' => (string) $invoice->id,
                'gateway_id' => (string) ($this->config['gateway_id'] ?? ''),
                'source' => 'FOSSBilling',
            ],
        ];

        $initialized = $this->initializeTransaction($payload);
        $accessCode = (string) ($initialized['access_code'] ?? '');
        $providerReference = (string) ($initialized['reference'] ?? '');

        if ($accessCode === '' || $providerReference !== $reference) {
            throw new Payment_Exception('Paystack returned an invalid transaction initialization response.');
        }

        $accessCodeJs = json_encode($accessCode, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
        $notifyUrlJs = json_encode($notifyUrl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
        $returnUrlJs = json_encode($returnUrl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
        if (!is_string($accessCodeJs) || !is_string($notifyUrlJs) || !is_string($returnUrlJs)) {
            throw new Payment_Exception('Could not prepare Paystack checkout.');
        }

        return <<<HTML
<div class="paystack-checkout">
    <button class="btn btn-primary" id="paystack-pay-button" type="button">Pay with Paystack</button>
    <div id="paystack-payment-error" class="alert alert-danger" role="alert" style="display:none;margin-top:10px;"></div>
</div>
<style>
#paystack-confirm-overlay{position:fixed;inset:0;z-index:2147483000;display:none;align-items:center;justify-content:center;padding:24px;background:radial-gradient(ellipse at 50% 35%,rgba(38,89,150,.48),transparent 52%),linear-gradient(145deg,#071426,#101d35 55%,#080f1d);color:#f7fbff;font-family:inherit;opacity:0;transition:opacity .28s ease}
#paystack-confirm-overlay.is-visible{display:flex;opacity:1}
#paystack-confirm-overlay .pc-card{width:min(440px,100%);padding:38px 30px 32px;text-align:center;border:1px solid rgba(255,255,255,.15);border-radius:26px;background:linear-gradient(145deg,rgba(255,255,255,.13),rgba(255,255,255,.045));box-shadow:0 28px 90px rgba(0,0,0,.48),inset 1px 1px 0 rgba(255,255,255,.16);backdrop-filter:blur(18px);transform:perspective(900px) rotateX(5deg) translateY(12px);transition:transform .45s cubic-bezier(.2,.8,.2,1)}
#paystack-confirm-overlay.is-visible .pc-card{transform:perspective(900px) rotateX(0) translateY(0)}
#paystack-confirm-overlay .pc-scene{height:128px;display:grid;place-items:center;perspective:600px;margin:0 auto 18px}
#paystack-confirm-overlay .pc-orb{position:relative;width:88px;height:88px;border-radius:26px;display:grid;place-items:center;background:linear-gradient(145deg,#45e0b1,#087d86);box-shadow:0 18px 35px rgba(0,220,180,.25),inset 5px 5px 12px rgba(255,255,255,.4),inset -7px -8px 15px rgba(0,35,65,.35);transform:rotateX(16deg) rotateY(-22deg);animation:pc-float 3s ease-in-out infinite}
#paystack-confirm-overlay .pc-orb:before{content:"";position:absolute;inset:-13px;border:1px solid rgba(91,238,211,.38);border-radius:31px;transform:rotate(35deg);animation:pc-spin 8s linear infinite}
#paystack-confirm-overlay .pc-check{font-size:43px;font-weight:800;line-height:1;color:#fff;text-shadow:0 3px 8px rgba(0,0,0,.2)}
#paystack-confirm-overlay h3{margin:8px 0 10px;color:#fff;font-size:23px;font-weight:750;letter-spacing:-.4px}
#paystack-confirm-overlay p{margin:0;color:rgba(235,245,255,.76);font-size:14px;line-height:1.65}
#paystack-confirm-overlay .pc-progress{height:5px;margin:25px 0 0;overflow:hidden;border-radius:9px;background:rgba(255,255,255,.12)}
#paystack-confirm-overlay .pc-progress span{display:block;width:38%;height:100%;border-radius:9px;background:linear-gradient(90deg,#40dfb0,#6ebdff);box-shadow:0 0 14px rgba(64,223,176,.65);animation:pc-progress 1.6s ease-in-out infinite}
#paystack-confirm-overlay .pc-secure{display:inline-flex;align-items:center;gap:7px;margin-top:19px;color:rgba(235,245,255,.56);font-size:11px;letter-spacing:.5px;text-transform:uppercase}
@keyframes pc-float{0%,100%{transform:translateY(0) rotateX(16deg) rotateY(-22deg)}50%{transform:translateY(-9px) rotateX(23deg) rotateY(18deg)}}
@keyframes pc-spin{to{transform:rotate(395deg)}}
@keyframes pc-progress{0%{transform:translateX(-120%)}100%{transform:translateX(290%)}}
@media(prefers-reduced-motion:reduce){#paystack-confirm-overlay *,#paystack-confirm-overlay *:before{animation:none!important;transition:none!important}}
</style>
<div id="paystack-confirm-overlay" role="status" aria-live="polite" aria-hidden="true">
  <div class="pc-card">
    <div class="pc-scene"><div class="pc-orb"><span class="pc-check">✓</span></div></div>
    <h3>Payment received</h3>
    <p>We're securely confirming your payment and updating your invoice. Please keep this page open for a moment.</p>
    <div class="pc-progress"><span></span></div>
    <div class="pc-secure">◆ &nbsp; Secure payment confirmation</div>
  </div>
</div>
<script>
(function () {
    'use strict';
    var button = document.getElementById('paystack-pay-button');
    var errorBox = document.getElementById('paystack-payment-error');
    var overlay = document.getElementById('paystack-confirm-overlay');
    if (!button || button.dataset.paystackBound === '1') return;
    button.dataset.paystackBound = '1';

    var accessCode = {$accessCodeJs};
    var notifyUrl = {$notifyUrlJs};
    var returnUrl = {$returnUrlJs};

    function showError(message) {
        errorBox.textContent = message;
        errorBox.style.display = 'block';
        overlay.classList.remove('is-visible');
        overlay.setAttribute('aria-hidden', 'true');
        button.disabled = false;
    }

    function loadPaystackV2() {
        return new Promise(function (resolve, reject) {
            if (typeof window.PaystackPop === 'function') {
                resolve();
                return;
            }

            var existing = document.querySelector('script[data-paystack-inline-v2]');
            if (existing) {
                existing.addEventListener('load', resolve, { once: true });
                existing.addEventListener('error', reject, { once: true });
                return;
            }

            var script = document.createElement('script');
            script.src = 'https://js.paystack.co/v2/inline.js';
            script.async = true;
            script.dataset.paystackInlineV2 = '1';
            script.onload = resolve;
            script.onerror = function () {
                reject(new Error('Unable to load Paystack Checkout. Check your connection or content-security policy.'));
            };
            document.head.appendChild(script);
        });
    }

    button.addEventListener('click', function () {
        errorBox.style.display = 'none';
        button.disabled = true;

        loadPaystackV2().then(function () {
            if (typeof window.PaystackPop !== 'function') {
                throw new Error('Paystack Checkout did not initialize. Please reload the invoice.');
            }

            var popup = new window.PaystackPop();
            if (typeof popup.resumeTransaction !== 'function') {
                throw new Error('This Paystack InlineJS version does not support resuming initialized transactions.');
            }

            // InlineJS V2 supports resumeTransaction(accessCode, callbacks).
            // The success callback explicitly invokes FOSSBilling IPN; closing
            // the popup alone must never be treated as a successful payment.
            popup.resumeTransaction(accessCode, {
                onSuccess: function (transaction) {
                    var reference = transaction && (transaction.reference || transaction.trxref);
                    if (!reference) {
                        showError('Paystack completed but did not return a transaction reference. Please contact support before retrying.');
                        return;
                    }

                    overlay.setAttribute('aria-hidden', 'false');
                    overlay.classList.add('is-visible');
                    button.disabled = true;
                    var separator = notifyUrl.indexOf('?') === -1 ? '?' : '&';
                    var ipnUrl = notifyUrl + separator + 'reference=' + encodeURIComponent(reference);

                    // Same-origin fetch retains the customer's FOSSBilling
                    // session cookie. ipn.php verifies the reference server-side,
                    // creates/processes the transaction, then we use the native
                    // 0.8.9 invoice return URL for top-level navigation.
                    fetch(ipnUrl, {
                        method: 'GET',
                        credentials: 'same-origin',
                        cache: 'no-store',
                        headers: { 'Accept': 'application/json' },
                        redirect: 'follow'
                    }).then(function (response) {
                        var contentType = response.headers.get('content-type') || '';
                        if (!response.ok) {
                            return response.text().then(function (body) {
                                throw new Error('FOSSBilling IPN returned HTTP ' + response.status + (body ? ': ' + body.slice(0, 250) : ''));
                            });
                        }
                        if (!contentType.toLowerCase().includes('application/json')) {
                            throw new Error('FOSSBilling IPN did not return JSON. Check the gateway notification URL.');
                        }
                        return response.json();
                    }).then(function (result) {
                        if (!result || result.error || typeof result.result === 'undefined' || result.result === null) {
                            var detail = result && result.error && result.error.message;
                            throw new Error(detail || 'FOSSBilling did not confirm the payment.');
                        }
                        window.location.replace(returnUrl);
                    }).catch(function (error) {
                        showError('Paystack reports success, but FOSSBilling could not finish invoice processing. Do not pay again. ' + (error && error.message ? error.message : 'Please contact support.'));
                    });
                },
                onCancel: function () {
                    button.disabled = false;
                },
                onError: function (error) {
                    showError(error && error.message ? error.message : 'Paystack checkout could not be completed.');
                }
            });
        }).catch(function (error) {
            showError(error && error.message ? error.message : 'Unable to open Paystack Checkout.');
        });
    });
})();
</script>
HTML;
    }

    /**
     * Initialize a transaction on Paystack's server. The secret key never
     * leaves PHP; only the returned access_code is exposed to InlineJS.
     */
    private function initializeTransaction(array $payload): array
    {
        $curl = curl_init();
        if ($curl === false) {
            throw new Payment_Exception('Could not initialize the Paystack connection.');
        }

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.paystack.co/transaction/initialize',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_THROW_ON_ERROR),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->secretKey,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
        ]);

        $response = curl_exec($curl);
        $curlError = curl_error($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($response === false) {
            throw new Payment_Exception('Could not initialize payment with Paystack: ' . $curlError);
        }

        $result = json_decode($response, true);
        if (
            $httpCode < 200
            || $httpCode >= 300
            || !is_array($result)
            || empty($result['status'])
            || !is_array($result['data'] ?? null)
        ) {
            $message = is_array($result) ? (string) ($result['message'] ?? '') : '';
            throw new Payment_Exception(
                $message !== '' ? 'Paystack initialization failed: ' . $message : 'Paystack initialization failed.'
            );
        }

        return $result['data'];
    }

    /**
     * Verify the payment directly with Paystack before crediting an invoice.
     */
    public function processTransaction(FOSSBilling\Api\Proxy $api_admin, int $id, array $data, int $gateway_id): void
    {
        $tx = $this->di['db']->getExistingModelById('Transaction', $id);

        // FOSSBilling may call the same callback more than once (browser return
        // plus a provider retry). A completed transaction must never be credited twice.
        if ($tx->status === 'processed' && empty($tx->error)) {
            return;
        }

        $reference = $data['get']['reference']
            ?? $data['post']['reference']
            ?? ($data['post']['data']['reference'] ?? null);

        if (!is_string($reference) || $reference === '') {
            throw new Payment_Exception('Paystack payment reference was not provided.');
        }

        try {
            $payment = $this->verifyReference($reference);

            // Paystack's top-level "status" only means the API request worked.
            // The transaction's own data.status must be "success".
            if (($payment['status'] ?? '') !== 'success') {
                throw new Payment_Exception('Paystack reports that this payment was not successful.');
            }

            if (!hash_equals($reference, (string) ($payment['reference'] ?? ''))) {
                throw new Payment_Exception('Paystack transaction reference mismatch.');
            }

            // Bind the verified Paystack payment to this exact FOSSBilling
            // gateway and invoice. Never trust IDs supplied by the browser.
            if ((int) $tx->gateway_id !== $gateway_id) {
                throw new Payment_Exception('Transaction does not belong to this Paystack gateway.');
            }

            $invoice = $this->di['db']->getExistingModelById('Invoice', (int) $tx->invoice_id);
            $invoiceService = $this->di['mod_service']('Invoice');

            $metadataInvoiceId = $payment['metadata']['invoice_id'] ?? null;
            if ($metadataInvoiceId === null || (string) $metadataInvoiceId !== (string) $invoice->id) {
                throw new Payment_Exception('Paystack transaction is not associated with this invoice.');
            }

            $metadataGatewayId = $payment['metadata']['gateway_id'] ?? null;
            if ($metadataGatewayId !== null && (string) $metadataGatewayId !== (string) $gateway_id) {
                throw new Payment_Exception('Paystack transaction belongs to a different payment gateway.');
            }

            $expectedMinor = $this->toSmallestCurrencyUnit(
                (float) $invoiceService->getTotalWithTax($invoice),
                (string) $invoice->currency
            );
            if ((int) ($payment['amount'] ?? -1) !== $expectedMinor) {
                throw new Payment_Exception('Paystack payment amount does not match the invoice total.');
            }

            if (strtoupper((string) ($payment['currency'] ?? '')) !== strtoupper((string) $invoice->currency)) {
                throw new Payment_Exception('Paystack payment currency does not match the invoice currency.');
            }

            $expectedDomain = !empty($this->config['test_mode']) ? 'test' : 'live';
            if (isset($payment['domain']) && strtolower((string) $payment['domain']) !== $expectedDomain) {
                throw new Payment_Exception('Paystack transaction environment does not match the configured gateway mode.');
            }

            // Reload current invoice state before applying funds. This avoids
            // using a stale invoice object when the customer returns late.
            $freshInvoice = $this->di['db']->getExistingModelById('Invoice', (int) $invoice->id);
            if (strtolower((string) $freshInvoice->status) === 'paid') {
                $tx->txn_id = $reference;
                $tx->txn_status = 'success';
                $tx->status = 'processed';
                $tx->updated_at = date('Y-m-d H:i:s');
                $this->di['db']->store($tx);
                return;
            }

            // Use FOSSBilling's transaction claim mechanism like its Stripe
            // adapter. This prevents the browser return and a webhook/retry
            // from applying the same payment concurrently.
            $transactionService = $this->di['mod_service']('Invoice', 'Transaction');
            if (!$transactionService->claimForProcessing((int) $tx->id)) {
                return;
            }

            $amount = $this->fromSmallestCurrencyUnit($expectedMinor, (string) $invoice->currency);
            $description = 'Paystack transaction ' . $reference;

            $tx->txn_id = $reference;
            $tx->txn_status = 'success';
            $tx->amount = $amount;
            $tx->currency = strtoupper((string) $payment['currency']);
            $tx->status = 'processing';
            $tx->updated_at = date('Y-m-d H:i:s');
            $this->di['db']->store($tx);

            $client = $this->di['db']->getExistingModelById('Client', (int) $invoice->client_id);
            $clientService = $this->di['mod_service']('Client');

            $clientService->addFunds($client, $amount, $description, [
                'amount' => $amount,
                'description' => $description,
                'type' => 'transaction',
                'rel_id' => $tx->id,
            ]);

            // This is the same invoice application sequence used by the
            // bundled FOSSBilling 0.8.9 Stripe adapter.
            if ($tx->invoice_id && !$invoiceService->isInvoiceTypeDeposit($freshInvoice)) {
                if (!$freshInvoice->approved) {
                    $invoiceService->approveInvoice($freshInvoice, ['use_credits' => false]);
                }
                $invoiceService->payInvoiceWithCredits($freshInvoice);
            } elseif ($tx->invoice_id && $invoiceService->isInvoiceTypeDeposit($freshInvoice)) {
                $invoiceService->markAsPaid($freshInvoice);
            }

            $tx->status = 'processed';
            $tx->error = null;
            $tx->updated_at = date('Y-m-d H:i:s');
            $this->di['db']->store($tx);
        } catch (Throwable $e) {
            $tx->status = 'error';
            $tx->error = $e->getMessage();
            $tx->updated_at = date('Y-m-d H:i:s');
            $this->di['db']->store($tx);

            if ($e instanceof Payment_Exception) {
                throw $e;
            }

            throw new Payment_Exception('Paystack payment processing failed: ' . $e->getMessage());
        }
    }

    private function verifyReference(string $reference): array
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.paystack.co/transaction/verify/' . rawurlencode($reference),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->secretKey,
                'Accept: application/json',
            ],
        ]);

        $response = curl_exec($curl);
        $curlError = curl_error($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($response === false) {
            throw new Payment_Exception('Could not contact Paystack: ' . $curlError);
        }

        $result = json_decode($response, true);

        if ($httpCode !== 200 || !is_array($result) || empty($result['status']) || empty($result['data'])) {
            throw new Payment_Exception('Paystack could not verify this transaction.');
        }

        return $result['data'];
    }

    /**
     * Paystack expects the amount in the currency's smallest unit.
     * FOSSBilling invoice totals are normally represented with two decimals.
     */
    private function toSmallestCurrencyUnit(float $amount, string $currency): int
    {
        $fractionDigits = $this->getCurrencyFractionDigits($currency);

        return (int) round($amount * (10 ** $fractionDigits));
    }

    private function fromSmallestCurrencyUnit(int $amount, string $currency): float
    {
        return $amount / (10 ** $this->getCurrencyFractionDigits($currency));
    }

    private function getCurrencyFractionDigits(string $currency): int
    {
        $zeroDecimalCurrencies = ['BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'];

        return in_array(strtoupper($currency), $zeroDecimalCurrencies, true) ? 0 : 2;
    }
}
