<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\VideoBookDetailResource;
use App\Models\User;
use App\Models\UserPurchasedEVideoBooks;
use App\Models\VideoBook;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Stripe\Account;
use Stripe\Charge;
use Stripe\Customer;
use Stripe\Stripe;
use Stripe\Token;

class PaymentController extends Controller
{
    /**
     * Purchase Video Series
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function purchaseVideoSeries(Request $request, $id)
    {
        try {
            $user = $request->user();

            $videoSeries = VideoBook::withCount(['users' => function ($query) use ($user) {
                $query->where('users.id', $user->id);
            }])->where('e_video_book.id', $id)->first();

            if (!$videoSeries){
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.VIDEO_BOOK_NOT_FOUND')
                ]);
            }

            if ($user->id == $videoSeries->user_id) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.AUTHOR_NOT_PURCHASE')
                ]);
            }

            if ($videoSeries->users_count > 0) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.ALREADY_PURCHASED')
                ]);
            }

            // set stripe key
            Stripe::setApiKey(Config::get('services.stripe.secret'));
            // get all cards
            $cards = [];
            if ($user->stripe_customer_id !== null) {
                // get all cards
                $cards = Customer::retrieve($user->stripe_customer_id)->sources->all([
                    'limit' => 10,
                    'object' => 'card'
                ])['data'];
            }


            return response()->json([
                'status' => 1,
                'message' => trans('api-message.SERIES_DATA_GET_SUCCESSFULLY_FOR_PURCHASE'),
                'data' => [
                    'seriesDetail' => new VideoBookDetailResource($videoSeries),
                    'cards' => $cards,
                ]
            ]);

        } catch (\Stripe\Error\RateLimit $e) {
            // Too many requests made to the API too quickly
            Log::error(strtr(trans('log-messages.DEFAULT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.STRIPE_DEFAULT_ERROR_MESSAGE'),
                'error' => $e->getMessage()
            ], 429);
        } catch (\Stripe\Error\InvalidRequest $e) {
            // Invalid parameters were supplied to Stripe's API
            Log::error(strtr(trans('log-messages.DEFAULT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.STRIPE_DEFAULT_ERROR_MESSAGE'),
                'error' => $e->getMessage()
            ], 400);
        } catch (\Stripe\Error\Authentication $e) {
            // Authentication with Stripe's API failed
            // (maybe you changed API keys recently)
            Log::error(strtr(trans('log-messages.DEFAULT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.STRIPE_DEFAULT_ERROR_MESSAGE'),
                'error' => $e->getMessage()
            ], 401);
        } catch (\Stripe\Error\ApiConnection $e) {
            // Network communication with Stripe failed
            Log::error(strtr(trans('log-messages.DEFAULT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.STRIPE_DEFAULT_ERROR_MESSAGE'),
                'error' => $e->getMessage()
            ], 500);
        } catch (\Stripe\Error\Base $e) {
            // Display a very generic error to the user, and maybe send
            // yourself an email
            Log::error(strtr(trans('log-messages.DEFAULT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.STRIPE_DEFAULT_ERROR_MESSAGE'),
                'error' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            // Log Message
            Log::error(strtr(trans('log-messages.DEFAULT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.DEFAULT_ERROR_MESSAGE'),
            ], 500);
        }
    }

    /**
     * Payment
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function payment(Request $request)
    {
        try {
            // rule validation - start
            $rule = [
                'video_series_id' => 'required|integer|min:1',
                'amount' => 'required|regex:/^[0-9]+(\.[0-9][0-9]?)?$/',
                'source' => 'required',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ]);
            }
            // rule validation - End

            // series Validation
            $user = $request->user();

            $videoSeries = VideoBook::withCount(['users' => function ($query) use ($user) {
                $query->where('users.id', $user->id);
            }])->where('e_video_book.id', $request->video_series_id)
                ->first();

            if (!$videoSeries){
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.VIDEO_BOOK_NOT_FOUND')
                ]);
            }
            $author = User::find($videoSeries->user_id);

            if ($user->id == $videoSeries->user_id) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.AUTHOR_NOT_PURCHASE')
                ]);
            }

            if ($videoSeries->users_count > 0) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.ALREADY_PURCHASED')
                ]);
            }

            if ($videoSeries->price !== $request->amount) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.INSUFFICIENT_AMOUNT')
                ]);
            }
            // set stripe key
            Stripe::setApiKey(Config::get('services.stripe.secret'));

            // check account is exists
            if ($user->stripe_customer_id == null) {
                $customer = Customer::create([
                    'email' => $user->email,
                    'description' => 'Account For ' . $user->name
                ]);
                $customer_id = $customer['id'];
                $user->stripe_customer_id = $customer_id;
                $user->save();
            } else {
                $customer_id = $user->stripe_customer_id;
                $customer = Customer::retrieve($customer_id);
            }

            if (isset($request->saved) and $request->saved == 0){
                // check card is already exists
                $source = Token::retrieve($request->source);
                $fingerprint = $source->card->fingerprint;

                $card_exists = false;
                foreach ($customer->sources->data as $card) {
                    if ($card->fingerprint == $fingerprint) {
                        $card_exists = true;
                        $card_id = $card->id;
                    }
                }

                // save card
                if ($card_exists == false) {
                    $card = $customer->sources->create([
                        'source' => $source->id
                    ]);
                    $card_id = $card->id;
                }

                $customer->save();

                $source = $card_id;
            } else{
                $source = $request->source;
            }

            if ($author->hasRole(Config::get('constant.SUPER_ADMIN_SLUG')) || $videoSeries->author_profit_in_percentage == 0.00){
                $charge = Charge::create([
                    'customer' => $customer,
                    'source' => $source,
                    'currency' => 'USD',
                    'amount' => ($request->amount * 100),
                    'description' => 'Payment By ' . $user->full_name,
                    'receipt_email' => $user->email
                ]);
            } else{
                // create charge
                $charge = Charge::create([
                    'customer' => $customer,
                    'source' => $source,
                    'currency' => 'USD',
                    'amount' => ($request->amount * 100),
                    'description' => 'Payment By ' . $user->full_name,
                    'receipt_email' => $user->email,
                    "destination" => [
                        'amount' => intval(number_format(($request->amount * $videoSeries->author_profit_in_percentage) / 100, 2, '.', '') * 100),
                        "account" => $author->stripe_account_id,
                    ],
                ]);
            }

            if ($charge['status'] == 'succeeded') {
                $purchaseSeries = new UserPurchasedEVideoBooks();
                $purchaseSeries->user_id = $user->id;
                $purchaseSeries->e_video_book_id = $videoSeries->id;
                $purchaseSeries->price = $request->amount;
                $purchaseSeries->author_profit_in_percentage = $videoSeries->author_profit_in_percentage;
                $purchaseSeries->author_price = number_format(($request->amount * $videoSeries->author_profit_in_percentage) / 100, 2, '.', '');
                $purchaseSeries->purchase_transaction_id = $charge['balance_transaction'];
                $purchaseSeries->payout_transaction_id = isset($charge['transfer']) ? $charge['transfer'] : null;

                $purchaseSeries->save();
            }
            $videoSeries->users_count = 1;

            return response()->json([
                'status' => 1,
                'message' => trans('api-message.PAYMENT_SUCCESS'),
                'data' => [
                    'seriesDetail' => new VideoBookDetailResource($videoSeries),
                    'transactionDetail' => [
                        'status' => $charge['status'],
                        'amount' => $request->amount,
                        'transactionId' => $charge['balance_transaction'],
                        'createdAt' => (string) $purchaseSeries->created_at
                    ]
                ]
            ]);

        } catch (\Stripe\Error\RateLimit $e) {
            // Too many requests made to the API too quickly
            Log::error(strtr(trans('log-messages.DEFAULT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.STRIPE_DEFAULT_ERROR_MESSAGE'),
                'error' => $e->getMessage()
            ], 429);
        } catch (\Stripe\Error\InvalidRequest $e) {
            // Invalid parameters were supplied to Stripe's API
            Log::error(strtr(trans('log-messages.DEFAULT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.STRIPE_DEFAULT_ERROR_MESSAGE'),
                'error' => $e->getMessage()
            ], 400);
        } catch (\Stripe\Error\Authentication $e) {
            // Authentication with Stripe's API failed
            // (maybe you changed API keys recently)
            Log::error(strtr(trans('log-messages.DEFAULT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.STRIPE_DEFAULT_ERROR_MESSAGE'),
                'error' => $e->getMessage()
            ], 401);
        } catch (\Stripe\Error\ApiConnection $e) {
            // Network communication with Stripe failed
            Log::error(strtr(trans('log-messages.DEFAULT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.STRIPE_DEFAULT_ERROR_MESSAGE'),
                'error' => $e->getMessage()
            ], 500);
        } catch (\Stripe\Error\Base $e) {
            // Display a very generic error to the user, and maybe send
            // yourself an email
            Log::error(strtr(trans('log-messages.DEFAULT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.STRIPE_DEFAULT_ERROR_MESSAGE'),
                'error' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            // Log Message
            Log::error(strtr(trans('log-messages.DEFAULT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.DEFAULT_ERROR_MESSAGE'),
            ], 500);
        }
    }

    /**
     * Delete Card
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteCard(Request $request)
    {
        try {
            $rule = [
                'source' => 'required'
            ];
            $validator = Validator::make($request->all(), $rule);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }


            // set stripe key
            Stripe::setApiKey(Config::get('services.stripe.secret'));

            //Get User
            $user = $request->user();
            $customer_id = $user->stripe_customer_id;
            $customer = Customer::retrieve($customer_id);

            // Delete Card
            $card = $customer->sources->retrieve($request->source)->delete();

            $customer->save();

            return response()->json([
                'status' => 1,
                'message' => trans('api-message.CARD_DELETE_SUCCESS'),
            ]);
        } catch (\Stripe\Error\RateLimit $e) {
            // Too many requests made to the API too quickly
            Log::error(strtr(trans('log-messages.DEFAULT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.STRIPE_DEFAULT_ERROR_MESSAGE'),
                'error' => $e->getMessage()
            ], 429);
        } catch (\Stripe\Error\InvalidRequest $e) {
            // Invalid parameters were supplied to Stripe's API
            Log::error(strtr(trans('log-messages.DEFAULT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.STRIPE_DEFAULT_ERROR_MESSAGE'),
                'error' => $e->getMessage()
            ], 400);
        } catch (\Stripe\Error\Authentication $e) {
            // Authentication with Stripe's API failed
            // (maybe you changed API keys recently)
            Log::error(strtr(trans('log-messages.DEFAULT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.STRIPE_DEFAULT_ERROR_MESSAGE'),
                'error' => $e->getMessage()
            ], 401);
        } catch (\Stripe\Error\ApiConnection $e) {
            // Network communication with Stripe failed
            Log::error(strtr(trans('log-messages.DEFAULT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.STRIPE_DEFAULT_ERROR_MESSAGE'),
                'error' => $e->getMessage()
            ], 500);
        } catch (\Stripe\Error\Base $e) {
            // Display a very generic error to the user, and maybe send
            // yourself an email
            Log::error(strtr(trans('log-messages.DEFAULT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.STRIPE_DEFAULT_ERROR_MESSAGE'),
                'error' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            // Log Message
            Log::error(strtr(trans('log-messages.DEFAULT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.DEFAULT_ERROR_MESSAGE'),
            ], 500);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveAccountDetails(Request $request){
        try{
            $user = $request->user();
            $dob = Carbon::parse($user->birth_date)->format('Y-m-d');
            $age = Carbon::parse($user->birth_date)->diffInYears();
            if ($age <= 13){
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.BELOW_AGE_FOR_STRIPE_ACCOUNT')
                ]);
            }
            $dob = explode('-', $dob);

            if (!empty($user->stripe_account_id)){
                return response()->json([
                    'status' => 0,
                    'message' => trans('api-message.ALREADY_ADD_PAYMENT_DETAILS')
                ]);
            }

            // set stripe key
            Stripe::setApiKey(Config::get('services.stripe.secret'));

            $account = Account::create([
                "country" => "US",
                "type" => "custom",
                "external_account" => $request->token,
                "tos_acceptance" => [
                    "date" => time(),
                    "ip" => $request->ip(),
                ],
                "legal_entity" => [
                    'first_name' => explode(' ', $user->full_name)[0],
                    'last_name' => isset(explode(' ', $user->full_name)[1]) ? explode(' ', $user->full_name)[1] : '_',
                    'dob' => [
                        'day' => $dob[2],
                        'month' => $dob[1],
                        'year' => $dob[0],
                    ],
                    'type' => "individual"
                ]
            ]);

            $user->stripe_account_id = $account['id'];
            $user->save();

            return response()->json([
                'status' => 1,
                'message' => trans('api-message.ACCOUNT_ADD_SUCCESS'),
                'data' => [
                    'card' => $account['external_accounts']['data']
                ]
            ]);
        } catch (\Stripe\Error\RateLimit $e) {
            // Too many requests made to the API too quickly
            Log::error(strtr(trans('log-messages.DEFAULT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.STRIPE_DEFAULT_ERROR_MESSAGE'),
                'error' => $e->getMessage()
            ], 429);
        } catch (\Stripe\Error\InvalidRequest $e) {
            // Invalid parameters were supplied to Stripe's API
            Log::error(strtr(trans('log-messages.DEFAULT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.STRIPE_DEFAULT_ERROR_MESSAGE'),
                'error' => $e->getMessage()
            ], 400);
        } catch (\Stripe\Error\Authentication $e) {
            // Authentication with Stripe's API failed
            // (maybe you changed API keys recently)
            Log::error(strtr(trans('log-messages.DEFAULT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.STRIPE_DEFAULT_ERROR_MESSAGE'),
                'error' => $e->getMessage()
            ], 401);
        } catch (\Stripe\Error\ApiConnection $e) {
            // Network communication with Stripe failed
            Log::error(strtr(trans('log-messages.DEFAULT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.STRIPE_DEFAULT_ERROR_MESSAGE'),
                'error' => $e->getMessage()
            ], 500);
        } catch (\Stripe\Error\Base $e) {
            // Display a very generic error to the user, and maybe send
            // yourself an email
            Log::error(strtr(trans('log-messages.DEFAULT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.STRIPE_DEFAULT_ERROR_MESSAGE'),
                'error' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            // Log Message
            Log::error(strtr(trans('log-messages.DEFAULT_ERROR_MESSAGE'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('api-message.DEFAULT_ERROR_MESSAGE'),
            ], 500);
        }
    }
}
