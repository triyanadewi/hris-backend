<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Package;
use App\Models\PackageBenefit;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PackageController extends Controller
{
    // Get packages with benefits
    public function getPackages()
    {
        try {
            Log::info('Getting packages with active benefits');
            
            $packages = Package::with(['packageBenefits' => function ($query) {
                $query->where('is_active', true)
                      ->orderBy('created_at', 'desc');
            }])
            ->whereHas('packageBenefits', function ($query) {
                $query->where('is_active', true);
            })
            ->get();

            $transformedPackages = $packages->map(function ($package) {
                return [
                    'id' => $package->id,
                    'name' => $package->name,
                    'price' => $package->price,
                    'description' => $package->description,
                    'benefits' => $package->packageBenefits->map(function ($benefit) {
                        return [
                            'id' => $benefit->id,
                            'package_id' => $benefit->package_id,
                            'max_branches' => $benefit->max_branches,
                            'max_employees' => $benefit->max_employees,
                            'access_duration_days' => $benefit->access_duration_days,
                            'is_active' => $benefit->is_active,
                            'created_at' => $benefit->created_at,
                            'updated_at' => $benefit->updated_at,
                        ];
                    })->toArray(),
                    'created_at' => $package->created_at,
                    'updated_at' => $package->updated_at,
                ];
            });

            return response()->json($transformedPackages);

        } catch (\Exception $e) {
            Log::error('Error getting packages:', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get packages',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // PERBAIKAN: Get company profile untuk checkout form
    public function getCompanyForCheckout()
    {
        try {
            $user = Auth::user() ?? User::find(1);

            if (!$user) {
                Log::error('No authenticated user found');
                return response()->json([
                    'success' => false,
                    'has_profile' => false,
                    'data' => [
                        'name' => '',
                        'email' => '',
                        'phone' => '',
                        'address' => '',
                    ]
                ]);
            }

            if (!$user->company_id) {
                Log::info('User has no company profile');
                return response()->json([
                    'success' => true,
                    'has_profile' => false,
                    'data' => [
                        'name' => '',
                        'email' => '',
                        'phone' => '',
                        'address' => '',
                    ]
                ]);
            }

            $company = Company::find($user->company_id);

            if (!$company) {
                Log::error('Company not found with ID: ' . $user->company_id);
                return response()->json([
                    'success' => false,
                    'has_profile' => false,
                    'data' => [
                        'name' => '',
                        'email' => '',
                        'phone' => '',
                        'address' => '',
                    ]
                ]);
            }

            // Return data yang sesuai dengan field di checkout form
            return response()->json([
                'success' => true,
                'has_profile' => true,
                'data' => [
                    'name' => $company->name,
                    'email' => $company->email,
                    'phone' => $company->head_office_phone,
                    'address' => $company->head_office_address,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting company for checkout:', $e->getMessage());
            return response()->json([
                'success' => false,
                'has_profile' => false,
                'data' => [
                    'name' => '',
                    'email' => '',
                    'phone' => '',
                    'address' => '',
                ]
            ], 500);
        }
    }

    // Create Order method dengan auto-fill company data
    public function createOrder(Request $request)
    {
        try {
            Log::info('Create order request:', $request->all());

            $validated = $request->validate([
                'package_benefits_id' => 'required|exists:package_benefits,id',
                'add_branch' => 'nullable|integer|min:0',
                'add_employees' => 'nullable|integer|min:0',
                'company_name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone_number' => 'required|string|max:20',
            ]);

            $benefit = PackageBenefit::with('package')
                ->where('id', $validated['package_benefits_id'])
                ->where('is_active', true)
                ->first();

            if (!$benefit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected package benefit is not available'
                ], 400);
            }

            $user = Auth::user() ?? User::find(1);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Jika user belum punya company, buat company baru
            if (!$user->company_id) {
                $company = Company::create([
                    'name' => $validated['company_name'],
                    'email' => $validated['email'],
                    'head_office_phone' => $validated['phone_number'],
                    'head_office_address' => 'Default Address'
                ]);

                $user->update(['company_id' => $company->id]);
                
                Log::info('Created new company for user:', [
                    'user_id' => $user->id,
                    'company_id' => $company->id
                ]);
            } else {
                $company = Company::find($user->company_id);
                
                // Update company data dengan data dari form (jika ada perubahan)
                $company->update([
                    'name' => $validated['company_name'],
                    'email' => $validated['email'],
                    'head_office_phone' => $validated['phone_number'],
                ]);
                
                Log::info('Updated existing company data:', [
                    'company_id' => $company->id
                ]);
            }

            // Calculate pricing
            $base_price = $benefit->package->price;
            $branch_price = ($validated['add_branch'] ?? 0) * 50000;
            $employee_price = ($validated['add_employees'] ?? 0) * 5000;
            $subtotal = $base_price + $branch_price + $employee_price;
            $tax = (int) round($subtotal * 0.1);
            $total = $subtotal + $tax;

            // Check existing pending order
            $existingOrder = Order::where('company_id', $company->id)
                ->where('status', 'pending')
                ->first();

            if ($existingOrder) {
                $existingOrder->update([
                    'package_benefits_id' => $benefit->id,
                    'add_branch' => $validated['add_branch'] ?? 0,
                    'add_employees' => $validated['add_employees'] ?? 0,
                    'company_name' => $validated['company_name'],
                    'email' => $validated['email'],
                    'phone_number' => $validated['phone_number'],
                    'duration_days' => $benefit->access_duration_days,
                    'subtotal' => $subtotal,
                    'tax' => $tax,
                    'total' => $total,
                ]);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'id' => $existingOrder->id,
                        'package_benefits_id' => $existingOrder->package_benefits_id,
                        'add_branch' => $existingOrder->add_branch,
                        'add_employees' => $existingOrder->add_employees,
                        'company_name' => $existingOrder->company_name,
                        'email' => $existingOrder->email,
                        'phone_number' => $existingOrder->phone_number,
                        'status' => $existingOrder->status,
                        'total_amount' => $existingOrder->total,
                        'created_at' => $existingOrder->created_at,
                        'updated_at' => $existingOrder->updated_at,
                    ]
                ]);
            }

            // Create new order
            $order = Order::create([
                'package_benefits_id' => $benefit->id,
                'company_id' => $company->id,
                'add_branch' => $validated['add_branch'] ?? 0,
                'add_employees' => $validated['add_employees'] ?? 0,
                'company_name' => $validated['company_name'],
                'email' => $validated['email'],
                'phone_number' => $validated['phone_number'],
                'duration_days' => $benefit->access_duration_days,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'status' => 'pending',
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $order->id,
                    'package_benefits_id' => $order->package_benefits_id,
                    'add_branch' => $order->add_branch,
                    'add_employees' => $order->add_employees,
                    'company_name' => $order->company_name,
                    'email' => $order->email,
                    'phone_number' => $order->phone_number,
                    'status' => $order->status,
                    'total_amount' => $order->total,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating order:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Method lainnya tetap sama...
    public function getOrderWithCompany($orderId)
    {
        try {
            $order = Order::with([
                'company',
                'packageBenefit.package'
            ])->findOrFail($orderId);

            $base_price = $order->packageBenefit->package->price;
            $branch_price = $order->add_branch * 50000;
            $employee_price = $order->add_employees * 5000;

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $order->id,
                    'package' => [
                        'id' => $order->packageBenefit->package->id,
                        'name' => $order->packageBenefit->package->name,
                        'description' => $order->packageBenefit->package->description,
                        'price' => $base_price,
                    ],
                    'benefit' => [
                        'id' => $order->packageBenefit->id,
                        'max_branches' => $order->packageBenefit->max_branches,
                        'max_employees' => $order->packageBenefit->max_employees,
                        'access_duration_days' => $order->packageBenefit->access_duration_days,
                    ],
                    'company' => [
                        'name' => $order->company_name,
                        'email' => $order->email,
                        'phone' => $order->phone_number,
                    ],
                    'addons' => [
                        'branches' => $order->add_branch,
                        'employees' => $order->add_employees,
                    ],
                    'pricing' => [
                        'base_price' => $base_price,
                        'branch_cost' => $branch_price,
                        'employee_cost' => $employee_price,
                        'subtotal' => $order->subtotal,
                        'tax' => $order->tax,
                        'total' => $order->total,
                    ],
                    'status' => $order->status,
                    'created_at' => $order->created_at,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting order:', $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }
    }

    public function createPayment(Request $request)
    {
        try {
            Log::info('Create payment request:', $request->all());

            $validated = $request->validate([
                'order_id' => 'nullable|exists:orders,id',
                'payment_method' => 'required|string',
                'amount' => 'required|numeric|min:0',
                'payment_reference' => 'nullable|string'
            ]);

            if (!empty($validated['order_id'])) {
                $order = Order::with(['packageBenefit', 'company'])->find($validated['order_id']);
            } else {
                $user = Auth::user() ?? User::find(1); // fallback ke user ID 1
                $order = Order::with(['packageBenefit', 'company'])
                    ->whereHas('company', function($query) use ($user) {
                        $query->where('id', $user->company_id);
                    })
                    ->where('status', 'pending')
                    ->orderBy('created_at', 'desc')
                    ->first();
            }

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            if ($order->status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Order has already been paid'
                ], 400);
            }

            if ($validated['amount'] != $order->total) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment amount does not match order total'
                ], 400);
            }

            $order->update([
                'payment_method' => $validated['payment_method'],
                'payment_reference' => $validated['payment_reference'] ?? 'PAY-' . time() . '-' . $order->id,
                'status' => 'paid',
                'paid_at' => now()
            ]);

            $this->createSubscription($order);

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully',
                'data' => [
                    'order_id' => $order->id,
                    'amount' => $order->total,
                    'payment_method' => $order->payment_method,
                    'payment_reference' => $order->payment_reference,
                    'status' => $order->status,
                    'paid_at' => $order->paid_at
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Payment processing failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process payment'
            ], 500);
        }
    }

    private function createSubscription($order)
    {
        try {
            Log::info('Creating subscription for paid order:', ['order_id' => $order->id]);

            $packageBenefit = $order->packageBenefit;
            $startDate = now()->toDateString();
            $endDate = now()->addDays($packageBenefit->access_duration_days)->toDateString();

            $existingSubscription = Subscription::where('company_id', $order->company_id)
                ->where('package_id', $packageBenefit->package_id)
                ->where('is_active', true)
                ->first();

            if ($existingSubscription) {
                $existingSubscription->update([
                    'extra_branch' => $existingSubscription->extra_branch + $order->add_branch,
                    'extra_employee' => $existingSubscription->extra_employee + $order->add_employees,
                    'ends_at' => $endDate
                ]);
            } else {
                Subscription::create([
                    'company_id' => $order->company_id,
                    'package_id' => $packageBenefit->package_id,
                    'extra_branch' => $order->add_branch,
                    'extra_employee' => $order->add_employees,
                    'starts_at' => $startDate,
                    'ends_at' => $endDate,
                    'is_active' => true
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to create subscription:', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}