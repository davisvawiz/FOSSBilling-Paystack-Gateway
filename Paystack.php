<?php
/**
 * Respiratory - FOSSBilling Paystack Gateway
 *
 * Paystack payment gateway integration
 * for FOSSBilling.
 *
 * @author Davis Vawiz
 * @link https://github.com/davisvawiz
 * @link https://davisvawiz.space
 *
 * @copyright 2026 Davis Vawiz
 * @license Apache-2.0
 * SPDX-License-Identifier: Apache-2.0
 */

class Payment_Adapter_Paystack implements FOSSBilling\InjectionAwareInterface
{
    protected ?Pimple\Container $di = null;

    private string $secretKey;
    private string $publicKey;

    public function setDi(Pimple\Container $di): void
    {
        $this->di = $di;
    }

    public function getDi(): ?Pimple\Container
    {
        return $this->di;
    }

    public function __construct(private $config)
    {
        if ($config['test_mode']) {

            $this->secretKey =
                $config['test_secret_key'] ?? '';

            $this->publicKey =
                $config['test_public_key'] ?? '';

        } else {

            $this->secretKey =
                $config['secret_key'] ?? '';

            $this->publicKey =
                $config['public_key'] ?? '';
        }

        if (!$this->secretKey) {
            throw new Payment_Exception(
                'Paystack Secret Key missing'
            );
        }

        if (!$this->publicKey) {
            throw new Payment_Exception(
                'Paystack Public Key missing'
            );
        }
    }

    public static function getConfig()
    {
        return [

            'supports_one_time_payments'=>true,

            'description'=>
                'Paystack payment gateway integration',

            'logo'=>[
                'logo'=>'paystack.png',
                'height'=>'40px',
                'width'=>'150px'
            ],

            'form'=>[

                'public_key'=>[
                    'text',
                    [
                        'label'=>'Live Public Key'
                    ]
                ],

                'secret_key'=>[
                    'text',
                    [
                        'label'=>'Live Secret Key'
                    ]
                ],

                'test_public_key'=>[
                    'text',
                    [
                        'label'=>'Test Public Key',
                        'required'=>false
                    ]
                ],

                'test_secret_key'=>[
                    'text',
                    [
                        'label'=>'Test Secret Key',
                        'required'=>false
                    ]
                ]
            ]
        ];
    }

    public function getHtml(
        $api_admin,
        $invoice_id,
        $subscription
    )
    {
        $invoice =
            $this->di['db']->load(
                'Invoice',
                $invoice_id
            );

        return $this->_generateForm(
            $invoice
        );
    }

    protected function getAmount(
        Model_Invoice $invoice
    )
    {
        $invoiceService =
            $this->di['mod_service'](
                'Invoice'
            );

        return
            $invoiceService
            ->getTotalWithTax(
                $invoice
            ) * 100;
    }

    public function processTransaction(
        $api_admin,
        $id,
        $data,
        $gateway_id
    )
    {
        $tx =
            $this->di['db']
            ->getExistingModelById(
                'Transaction',
                $id
            );

        if (
            $tx->status
            ==
            'processed'
        ) {
            return;
        }

        try {

            $reference =
                $data['get']['reference'];

            $invoice =
                $this->di['db']
                ->getExistingModelById(
                    'Invoice',
                    $tx->invoice_id
                );

            $curl = curl_init();

            curl_setopt_array(
                $curl,
                [

                    CURLOPT_URL =>
                    "https://api.paystack.co/transaction/verify/"
                    .$reference,

                    CURLOPT_RETURNTRANSFER=>true,

                    CURLOPT_HTTPHEADER=>[
                        "Authorization: Bearer "
                        .$this->secretKey
                    ]

                ]
            );

            $response =
                curl_exec(
                    $curl
                );

            curl_close(
                $curl
            );

            $result =
                json_decode(
                    $response,
                    true
                );

            error_log(
                print_r(
                    $result,
                    true
                )
            );

            if(
                !isset(
                    $result['status']
                )
                ||
                !$result['status']
            ){
                throw new Payment_Exception(
                    'Payment verification failed'
                );
            }

            $payment =
                $result['data'];

            /*
            Prevent duplicate transactions
            */

            if(
                $tx->status
                ==
                'processed'
            ){
                return;
            }

            /*
            Verify amount
            */

            $expectedAmount =
                $this->getAmount(
                    $invoice
                );

            if(
                $payment['amount']
                !=
                $expectedAmount
            ){

                throw new Payment_Exception(
                    'Amount mismatch'
                );
            }

            /*
            Verify currency
            */

            if(
                strtoupper(
                    $payment['currency']
                )
                !=
                strtoupper(
                    $invoice->currency
                )
            ){

                throw new Payment_Exception(
                    'Currency mismatch'
                );
            }

            /*
            Verify status
            */

            if(
                $payment['status']
                !=
                'success'
            ){

                throw new Payment_Exception(
                    'Payment not successful'
                );
            }

            $tx->txn_id =
                $payment['reference'];

            $tx->txn_status =
                $payment['status'];

            $tx->amount =
                $payment['amount']/100;

            $tx->currency =
                $payment['currency'];

            $tx->status =
                'processed';

            $tx->updated_at =
                date(
                    'Y-m-d H:i:s'
                );

            $this->di['db']
                ->store(
                    $tx
                );

            $client =
                $this->di['db']
                ->getExistingModelById(
                    'Client',
                    $invoice->client_id
                );

            $clientService =
                $this->di['mod_service'](
                    'client'
                );

            $invoiceService =
                $this->di['mod_service'](
                    'Invoice'
                );

            $bd=[

                'amount'=>
                $tx->amount,

                'description'=>
                'Paystack payment '
                .$payment['reference'],

                'type'=>
                'transaction',

                'rel_id'=>
                $tx->id
            ];

            $clientService
                ->addFunds(
                    $client,
                    $tx->amount,
                    $bd['description'],
                    $bd
                );

            $invoiceService
                ->payInvoiceWithCredits(
                    $invoice
                );

        }
        catch(Exception $e){

            $tx->status =
                'error';

            $tx->error =
                $e->getMessage();

            $this->di['db']
                ->store(
                    $tx
                );

            throw new Payment_Exception(
                $e->getMessage()
            );
        }
    }

    protected function _generateForm(
        Model_Invoice $invoice
    )
    {
        $reference =
            uniqid(
                'FB_'
            );

        $tx =
            $this->di['db']
            ->dispense(
                'Transaction'
            );

        $tx->invoice_id =
            $invoice->id;

        $tx->txn_id =
            $reference;

        $tx->status =
            'received';

        $tx->created_at =
            date(
                'Y-m-d H:i:s'
            );

        $tx->updated_at =
            date(
                'Y-m-d H:i:s'
            );

        $this->di['db']
            ->store(
                $tx
            );

        $payGateway =
            $this->di['db']
            ->findOne(
                'PayGateway',
                'gateway="Paystack"'
            );

        $gatewayService =
            $this->di['mod_service'](
                'Invoice',
                'PayGateway'
            );

        $callback =
            $gatewayService
            ->getCallbackUrl(
                $payGateway,
                $invoice
            );

        $redirect =
            $this->di['tools']
            ->url(
                'invoice/'.
                $invoice->hash
            );

        return '

<script src="https://js.paystack.co/v1/inline.js"></script>

<button
class="btn btn-primary"
onclick="payWithPaystack()"
type="button"
>
Pay Now
</button>

<script>

function payWithPaystack(){

let handler=
PaystackPop.setup({

key:"'.$this->publicKey.'",

email:"'.$invoice->buyer_email.'",

amount:'.$this->getAmount(
$invoice
).',

currency:"'.$invoice->currency.'",

ref:"'.$reference.'",

callback:function(response){

fetch(
"'.$callback.
'&reference="
+
response.reference
)

.then(
response=>response.json()
)

.then(data=>{

window.location=
"'.$redirect.'";

})

.catch(error=>{

window.location=
"'.$redirect.'";

});

}

});

handler.openIframe();

}

</script>';

    }

}
