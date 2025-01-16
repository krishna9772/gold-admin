<?php

namespace Modules\Frontend\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Modules\Subscriptions\Models\Plan;
use Modules\Subscriptions\Models\SubscriptionTransactions;
use Modules\Subscriptions\Models\Subscription;
use Modules\Subscriptions\Trait\SubscriptionTrait;
use Modules\Tax\Models\Tax;
use GuzzleHttp\Client;
use PayPal\Api\Payment;
use Stripe\StripeClient;
use PayPal\Api\PaymentExecution;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;
use Midtrans\Snap;
use Midtrans\Config;
use  Modules\Subscriptions\Transformers\SubscriptionResource;
use Modules\Subscriptions\Transformers\PlanResource;
use Modules\Subscriptions\Transformers\PlanlimitationMappingResource;
use App\Mail\SubscriptionDetail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
class PaymentController extends Controller
{
    use SubscriptionTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('frontend::index');
    }

    public function selectPlan(Request $request)
    {
        $planId = $request->input('plan_id');
        $planName = $request->input('plan_name');
        $plan= Plan::all();

        $plans = PlanResource::collection($plan);

        $activeSubscriptions = Subscription::where('user_id', auth()->id())->where('status', 'active')->where('end_date', '>', now())->orderBy('id','desc')->first();
        $currentPlanId = $activeSubscriptions ? $activeSubscriptions->plan_id : null;


        $planId = $planId ?? $currentPlanId ?? Plan::first()->id ?? null;

        $view = view('frontend::subscriptionPayment', compact('plans','planId','currentPlanId'))->render();
        return response()->json(['success' => true, 'view' => $view]);
    }

    public function processPayment(Request $request)
    {
        $paymentMethod = $request->input('payment_method');
        $price = $request->input('price');

        $paymentHandlers = [
            'stripe' => 'StripePayment',
            'razorpay' => 'RazorpayPayment',
            'paystack' => 'PaystackPayment',
            'paypal' => 'PayPalPayment',
            'flutterwave' => 'FlutterwavePayment',
            'cinet' => 'CinetPayment',
            'sadad' => 'SadadPayment',
            'airtel' => 'AirtelPayment',
            'phonepe' => 'PhonePePayment',
            'midtrans' => 'MidtransPayment',
        ];

        if (array_key_exists($paymentMethod, $paymentHandlers)) {
            return $this->{$paymentHandlers[$paymentMethod]}($request, $price);
        }

        return redirect()->back()->withErrors('Invalid payment method.');
    }


    protected function StripePayment(Request $request)
    {
        $baseURL = env('APP_URL');
        $stripe = new StripeClient('sk_test_CG2JhAIXvVWDeFUFqtUizO4N00zmvm7o8J');
        $price = $request->input('price'); // Get the price from the request
        $plan_id=$request->input('plan_id');
        $priceInCents = $price * 100;
        $checkout_session = $stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'Subscription Plan',
                    ],
                    'unit_amount' => $priceInCents,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
        'metadata' => [
                            'plan_id' => $plan_id,
                        ],
            'success_url'=> $baseURL .'/payment/success?gateway=stripe&session_id={CHECKOUT_SESSION_ID}'
        ]);
        return response()->json(['redirect' => $checkout_session->url]);
    }

    protected function RazorpayPayment(Request $request, $price)
{
    $baseURL = env('APP_URL');
    $razorpayKey = 'rzp_test_CLw7tH3O3P5eQM';
    $razorpaySecret = 'rzp_test_CLw7tH3O3P5eQM';
    $plan_id = $request->input('plan_id');
    $priceInPaise = $price * 100;

    try {
        $api = new \Razorpay\Api\Api($razorpayKey, $razorpaySecret);
        $orderData = [
            'receipt'         => 'rcptid_' . time(),
            'amount'          => $priceInPaise,
            'currency'        => 'INR',
            'payment_capture' => 1
        ];

        $razorpayOrder = $api->order->create($orderData);
        session(['razorpay_order_id' => $razorpayOrder['id']]);

        return view('razorpay.payment', [
            'order_id' => $razorpayOrder['id'],
            'amount' => $priceInPaise,
            'plan_id' => $plan_id,
            'key' => $razorpayKey,
            'currency' => 'INR',
            'name' => 'Subscription Plan',
            'description' => 'Payment for subscription plan'
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 400);    }
}

    protected function PaystackPayment(Request $request)
    {
        $baseURL = env('APP_URL');
        $paystackSecretKey = 'sk_test_9b5bf65070d9773c7a2b3aa7dd8d41310c5fc03c';
        $price = $request->input('price');
        $plan_id = $request->input('plan_id');
        $priceInKobo = $price * 100; // Paystack uses kobo

        // Create a new Paystack payment
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $paystackSecretKey,
        ])->post('https://api.paystack.co/transaction/initialize', [
            'email' => auth()->user()->email, // Get user email from authenticated user
            'amount' => $priceInKobo,
            'currency' => 'NGN',
            'callback_url' => $baseURL . '/payment/success?gateway=paystack',
            'metadata' => [
                'plan_id' => $plan_id,
            ],
        ]);

        $responseBody = $response->json();

        if ($responseBody['status']) {
            return response()->json([
                'success' => true,
                'redirect' => $responseBody['data']['authorization_url'],
            ]);
        } else {
            return response()->json(['error' => 'something went wrong choose different method'], 400);
        }
    }

    protected function PayPalPayment(Request $request)
    {
        $baseURL = env('APP_URL');
        $price = $request->input('price');
        $plan_id = $request->input('plan_id');

        // Validate price
        if (!is_numeric($price) || $price <= 0) {
            return redirect()->back()->withErrors('Invalid price value.');
        }

        try {
            // Get Access Token
            $accessToken = $this->getAccessToken();

            // Create Payment
            $payment = $this->createPayment($accessToken, $price, $plan_id);

            if (isset($payment['links'])) {
                foreach ($payment['links'] as $link) {
                    if ($link['rel'] === 'approval_url') {
                        return response()->json(['success' => true, 'redirect' => $link['href']]);

                    }
                }
            }

            return redirect()->back()->withErrors('Payment creation failed.');
        } catch (\Exception $ex) {
            return redirect()->back()->withErrors('Payment processing failed: ' . $ex->getMessage());
        }
    }

    protected function FlutterwavePayment(Request $request)
    {
        $baseURL = env('APP_URL');
        $flutterwaveKey = 'FLWSECK_TEST-76e58fc4d85dd2c3fc01ea7ef5b9e2bb-X';
        $price = $request->input('price');
        $plan_id = $request->input('plan_id');
        $priceInKobo = $price * 100;

        $data = [
            'tx_ref' => 'txn_' . time(),
            'email' => auth()->user()->email,
            'amount' => $priceInKobo,
            "currency" => "GHS",
            "payment_type" => "mobilemoneyghana",
            'callback_url' => $baseURL . '/payment/success?gateway=flutterwave',
            'metadata' => [
                'plan_id' => $plan_id,
            ],
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $flutterwaveKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.flutterwave.com/v3/charges?type=mobilemoneyghana', $data);

        $responseBody = $response->json();

        // Check if the response is valid and has expected keys
        if ($response->successful() && isset($responseBody['status'])) {
            if ($responseBody['status'] === 'success') {
                return response()->json(['success' => true, 'redirect' => $responseBody['data']['link']]);


            } else {
                // return redirect()->back()->withErrors('Payment initialization failed: ' . ($responseBody['message'] ?? 'Unknown error'));
                return response()->json(['error' => 'Payment initialization failed: ' . ($responseBody['message'] ?? 'Unknown error')],400);;

            }
        }
    }

    protected function CinetPayment(Request $request)
    {
        $baseURL = env('APP_URL');
        $cinetApiKey = 'YOUR_CINET_API_KEY';
        $price = $request->input('price');
        $plan_id = $request->input('plan_id');
        $priceInCents = $price * 100;

        $data = [
            'amount' => $priceInCents,
            'currency' => 'USD',
            'plan_id' => $plan_id,
            'callback_url' => $baseURL . '/payment/success?gateway=cinet',
            'user_email' => auth()->user()->email,
        ];
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $cinetApiKey,
        ])->post('https://api.cinet.com/payment', $data);

        $responseBody = $response->json();

        if ($response->successful() && isset($responseBody['payment_url'])) {
            return redirect($responseBody['payment_url']);
        } else {
            return redirect()->back()->withErrors('Payment initialization failed: ' . ($responseBody['message'] ?? 'Unknown error'));
        }
    }

    protected function SadadPayment(Request $request)
    {
        $baseURL = env('APP_URL');
        $price = $request->input('price');
        $plan_id = $request->input('plan_id');
        $response = $this->makeSadadPaymentRequest($price, $plan_id);
        if ($response->isSuccessful()) {
            return redirect($response->redirect_url);
        } else {
            return redirect()->back()->withErrors('Payment initiation failed: ' . $response->message);
        }
    }

    protected function AirtelPayment(Request $request)
    {
        $baseURL = env('APP_URL');
        $price = $request->input('price');
        $plan_id = $request->input('plan_id');

        $response = $this->makeAirtelPaymentRequest($price, $plan_id);

        if ($response->isSuccessful()) {
            return redirect($response->redirect_url);
        } else {
            return redirect()->back()->withErrors('Payment initiation failed: ' . $response->message);
        }
    }

    protected function PhonePePayment(Request $request)
    {
        $baseURL = env('APP_URL');
        $price = $request->input('price');
        $plan_id = $request->input('plan_id');

        $response = $this->makePhonePePaymentRequest($price, $plan_id);

        if ($response->isSuccessful()) {
            return redirect($response->payment_url);
        } else {
            return redirect()->back()->withErrors('Payment initiation failed: ' . $response->message);
        }
    }

    protected function MidtransPayment(Request $request)
    {
        Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        Config::$isProduction = env('MIDTRANS_IS_PRODUCTION');

        $price = $request->input('price');
        $plan_id = $request->input('plan_id');
        $transactionDetails = [
            'order_id' => uniqid(),
            'gross_amount' => $price,
        ];

        $customerDetails = [
            'first_name' => auth()->user()->name,
            'email' => auth()->user()->email,
        ];

        $transaction = [
            'transaction_details' => $transactionDetails,
            'customer_details' => $customerDetails,
        ];

        try {
            $snapToken = Snap::getSnapToken($transaction);
            return response()->json(['snapToken' => $snapToken]);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors('Payment initiation failed: ' . $e->getMessage());
        }
    }

    private function getAccessToken()
    {
        $clientId = 'Aec0WfRHUKNVEQWRedFhD5S7OvBdaugQ7MmY7xTuhHjwZMZaHg6e62gH_3MjkfSCw7C4WBG4-er-ICLI';
        $clientSecret = 'EAqgmm659_iD9RagRIQCV6-cupQqZjUdW9VAAC4HnNuEM4zo1ZBShSw82irmtAICjJMA5CE7H2J6Hl2A';

        $client = new Client();
        $response = $client->post('https://api.sandbox.paypal.com/v1/oauth2/token', [
            'auth' => [$clientId, $clientSecret],
            'form_params' => [
                'grant_type' => 'client_credentials',
            ],
        ]);

        $data = json_decode($response->getBody(), true);
        return $data['access_token'];
    }

    private function createPayment($accessToken, $price, $planId)
    {
        $baseURL = env('APP_URL');
        $client = new Client();
        $response = $client->post('https://api.sandbox.paypal.com/v1/payments/payment', [
            'headers' => [
                'Authorization' => "Bearer $accessToken",
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'intent' => 'sale',
                'payer' => [
                    'payment_method' => 'paypal',
                ],
                'transactions' => [[
                    'amount' => [
                        'total' => $price,
                        'currency' => 'USD',
                    ],
                    'description' => 'Payment for plan ID: ' . $planId,
                ]],
                'redirect_urls' => [
                    'return_url' => $baseURL . '/payment/success?gateway=paypal',
                    'cancel_url' => $baseURL . '/payment/cancel',
                ],
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    public function paymentSuccess(Request $request)
    {
        $gateway = $request->input('gateway');

        switch ($gateway) {
            case 'stripe':
                return $this->handleStripeSuccess($request);
            case 'razorpay':
                return $this->handleRazorpaySuccess($request);
            case 'paystack':
                return $this->handlePaystackSuccess($request);
            case 'paypal':
                return $this->handlePayPalSuccess($request);
            case 'flutterwave':
                return $this->handleFlutterwaveSuccess($request);
            case 'cinet':
                return $this->handleCinetSuccess($request);
            case 'sadad':
                return $this->handleSadadSuccess($request);
            case 'airtel':
                return $this->handleAirtelSuccess($request);
            case 'phonepe':
                return $this->handlePhonePeSuccess($request);
            case 'midtrans':
                return $this->MidtransPayment($request);
            default:
                return redirect('/')->with('error', 'Invalid payment gateway.');
        }
    }

    protected function handlePaymentSuccess($plan_id, $amount, $payment_type, $transaction_id)
    {
        $plan = Plan::findOrFail($plan_id);
       $limitation_data = PlanlimitationMappingResource::collection($plan->planLimitation);

       $user=Auth::user();

        $start_date = now();
        $end_date = $this->get_plan_expiration_date($start_date, $plan->duration, $plan->duration_value);
        $taxes = Tax::active()->get();
        $totalTax = 0;
        foreach ($taxes as $tax) {
            if (strtolower($tax->type) == 'fixed') {
                $totalTax += $tax->value;
            } elseif (strtolower($tax->type) == 'percentage') {
                $totalTax += ($plan->price * $tax->value) / 100;
            }
        }
        // Create the subscription
        $subscription = Subscription::create([
            'plan_id' => $plan_id,
            'user_id' => auth()->id(),
            'device_id' => 1,
            'start_date' => now(),
            'end_date' => $end_date,
            'status' => 'active',
            'amount' =>$plan->price,
            'discount_percentage'=>$plan->discount_percentage,
            'tax_amount' => $totalTax ,
            'total_amount' => $amount,
            'name' => $plan->name,
            'identifier' => $plan->identifier,
            'type' => $plan->duration,
            'duration' => $plan->duration_value,
            'level' => $plan->level,
            'plan_type' => $limitation_data ? json_encode( $limitation_data) : null,
            'payment_id' => null,
        ]);

        // Create a subscription transaction
        SubscriptionTransactions::create([
            'user_id' => auth()->id(),
            'amount' => $amount,
            'payment_type' => $payment_type,
            'payment_status' => 'paid',
            'tax_data'=> $taxes->isEmpty() ? null : json_encode($taxes),
            'transaction_id' => $transaction_id,
            'subscriptions_id' => $subscription->id,
        ]);


       $response = new SubscriptionResource($subscription);

       $this->sendNotificationOnsubscription('new_subscription', $response);
       if (isSmtpConfigured()) {
           if ($user) {
               try {
                   Mail::to($user->email)->send(new SubscriptionDetail($response));

               } catch (\Exception $e) {
                   Log::error('Failed to send email to ' . $user->email . ': ' . $e->getMessage());
               }
           } else {
               Log::info('User object is not set. Email not sent.');
           }
       } else {
           Log::error('SMTP configuration is not set correctly. Email not sent.');
       }



        auth()->user()->update(['is_subscribe' => 1]);

        return redirect('/')->with('success', 'Payment completed successfully!');
    }

    protected function handleStripeSuccess(Request $request)
    {
        $sessionId = $request->input('session_id');
        $stripe = new StripeClient('sk_test_CG2JhAIXvVWDeFUFqtUizO4N00zmvm7o8J');

        try {
            $session = $stripe->checkout->sessions->retrieve($sessionId);
            return $this->handlePaymentSuccess($session->metadata->plan_id, $session->amount_total / 100, 'stripe', $session->payment_intent);
        } catch (\Exception $e) {
            return redirect('/')->with('error', 'Payment failed: ' . $e->getMessage());
        }
    }

    protected function handleRazorpaySuccess(Request $request)
{
    $paymentId = $request->input('razorpay_payment_id');
    $razorpayOrderId = session('razorpay_order_id');
    $plan_id = $request->input('plan_id');

    $razorpayKey = 'rzp_test_CLw7tH3O3P5eQM';
    $razorpaySecret = 'rzp_test_CLw7tH3O3P5eQM';
    $api = new \Razorpay\Api\Api($razorpayKey, $razorpaySecret);
    $payment = $api->payment->fetch($paymentId);

    if ($payment['status'] == 'captured') {
        return $this->handlePaymentSuccess($plan_id, $payment['amount'] / 100, 'razorpay', $paymentId);
    } else {
        return redirect('/')->with('error', 'Payment failed: ' . $payment['error_description']);
    }
}

   protected function handlePaystackSuccess(Request $request)
    {
        $reference = $request->input('reference');
        $paystackSecretKey = 'sk_test_9b5bf65070d9773c7a2b3aa7dd8d41310c5fc03c';

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $paystackSecretKey,
        ])->get("https://api.paystack.co/transaction/verify/{$reference}");

        $responseBody = $response->json();

        if ($responseBody['status']) {
            return $this->handlePaymentSuccess($responseBody['data']['metadata']['plan_id'], $responseBody['data']['amount'] / 100, 'paystack', $responseBody['data']['id']);
        } else {
            return redirect('/')->with('error', 'Payment verification failed: ' . $responseBody['message']);
        }
    }

   protected function handlePayPalSuccess(Request $request)
    {
        $paymentId = $request->input('paymentId');
        $payerId = $request->input('PayerID');

        $apiContext = new ApiContext(
            new OAuthTokenCredential(
                'Aec0WfRHUKNVEQWRedFhD5S7OvBdaugQ7MmY7xTuhHjwZMZaHg6e62gH_3MjkfSCw7C4WBG4-er-ICLI',
                'EAqgmm659_iD9RagRIQCV6-cupQqZjUdW9VAAC4HnNuEM4zo1ZBShSw82irmtAICjJMA5CE7H2J6Hl2A'
            )
        );

        try {
            $payment = Payment::get($paymentId, $apiContext);
            $execution = new PaymentExecution();
            $execution->setPayerId($payerId);
            $result = $payment->execute($execution, $apiContext);

            if ($result->getState() == 'approved') {
                $plan_id = $result->transactions[0]->item_list->items[0]->sku;
                return $this->handlePaymentSuccess($plan_id, $result->transactions[0]->amount->total, 'paypal', $paymentId);
            } else {
                return redirect('/')->with('error', 'Payment not approved.');
            }
        } catch (\Exception $e) {
            return redirect('/')->with('error', 'Payment verification failed: ' . $e->getMessage());
        }
    }

    protected function handleFlutterwaveSuccess(Request $request)
    {
        $tx_ref = $request->input('tx_ref');
        $flutterwaveKey = env('FLUTTERWAVE_SECRET_KEY');

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $flutterwaveKey,
        ])->get("https://api.flutterwave.com/v3/transactions/{$tx_ref}/verify");

        $responseBody = $response->json();

        if ($responseBody['status'] === 'success') {
            return $this->handlePaymentSuccess($responseBody['data']['metadata']['plan_id'], $responseBody['data']['amount'] / 100, 'flutterwave', $responseBody['data']['id']);
        } else {
            return redirect('/')->with('error', 'Payment verification failed: ' . $responseBody['message']);
        }
    }
    protected function handleCinetSuccess(Request $request)
    {
        $transactionId = $request->input('transaction_id');
        $paymentStatus = $request->input('status');
        $planId = $request->input('plan_id');

        if ($paymentStatus !== 'success') {
            return redirect('/')->with('error', 'Payment failed: Invalid payment status.');
        }

        return $this->handlePaymentSuccess($planId, $request->input('amount'), 'cinet', $transactionId);
    }

    protected function handleSadadSuccess(Request $request)
    {
        $transactionId = $request->input('transaction_id');
        $paymentStatus = $request->input('status');
        $plan_id = $request->input('plan_id');

        if ($paymentStatus !== 'success') {
            return redirect('/')->with('error', 'Payment failed: Invalid payment status.');
        }

        return $this->handlePaymentSuccess($plan_id, $request->input('amount'), 'sadad', $transactionId);
    }

   public function midtransNotification(Request $request)
    {
        $payload = json_decode($request->getContent(), true);

        if ($payload['transaction_status'] === 'settlement') {
            $transactionId = $payload['order_id'];
            $plan_id = $payload['item_details'][0]['id'];
            $amount = $payload['gross_amount'];

            return $this->handlePaymentSuccess($plan_id, $amount, 'midtrans', $transactionId);
        }

        return response()->json(['status' => 'success']);
    }

    protected function makeSadadPaymentRequest($price, $plan_id)
    {
        $url = 'https://api.sadad.com/payment';
        $data = [
            'amount' => $price,
            'plan_id' => $plan_id,
            'callback_url' => env('APP_URL') . '/payment/success?gateway=sadad',
        ];

        $client = new \GuzzleHttp\Client();
        $response = $client->post($url, [
            'json' => $data,
            'headers' => [
                'Authorization' => 'Bearer ' . env('SADAD_API_KEY'),
            ]
        ]);

        return json_decode($response->getBody());
    }

    protected function handleAirtelSuccess(Request $request)
    {
        $transactionId = $request->input('transaction_id');
        $paymentStatus = $request->input('status');
        $planId = $request->input('plan_id');

        if ($paymentStatus !== 'success') {
            return redirect('/')->with('error', 'Payment failed: Invalid payment status.');
        }

        return $this->handlePaymentSuccess($planId, $request->input('amount'), 'airtel', $transactionId);
    }

     protected function handlePhonePeSuccess(Request $request)
    {
        $transactionId = $request->input('transaction_id');
        $paymentStatus = $request->input('status');
        $planId = $request->input('plan_id');

        if ($paymentStatus !== 'success') {
            return redirect('/')->with('error', 'Payment failed: Invalid payment status.');
        }

        return $this->handlePaymentSuccess($planId, $request->input('amount'), 'phonepe', $transactionId);
    }

    protected function makePhonePePaymentRequest($price, $plan_id)
    {
        $url = 'https://api.phonepe.com/apis/hermes/pg/v1/pay';
        $data = [
            'amount' => $price,
            'plan_id' => $plan_id,
            'callbackUrl' => env('APP_URL') . '/payment/success?gateway=phonepe',
            'currency' => 'INR',
        ];
        $client = new Client();
        $response = $client->post($url, [
            'json' => $data,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-VERIFY-TOKEN' => env('PHONEPE_VERIFY_TOKEN'),
            ]
        ]);

        return json_decode($response->getBody());
    }
    protected function makeAirtelPaymentRequest($price, $plan_id)
    {
        $url = 'https://api.airtel.com/payment';
        $data = [
            'amount' => $price,
            'plan_id' => $plan_id,
            'callback_url' => env('APP_URL') . '/payment/success?gateway=airtel',
        ];

        $client = new Client();
        $response = $client->post($url, [
            'json' => $data,
            'headers' => [
                'Authorization' => 'Bearer ' . env('AIRTEL_API_KEY'),
            ]
        ]);

        return json_decode($response->getBody());
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('frontend::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        //
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('frontend::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('frontend::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}
