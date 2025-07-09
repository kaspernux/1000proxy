<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PaymentGatewayService;
use App\Services\GeographicExpansionService;
use App\Services\PartnershipService;
use App\Services\CustomerSuccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BusinessGrowthController extends Controller
{
    protected $paymentGatewayService;
    protected $geographicExpansionService;
    protected $partnershipService;
    protected $customerSuccessService;
    
    public function __construct(
        PaymentGatewayService $paymentGatewayService,
        GeographicExpansionService $geographicExpansionService,
        PartnershipService $partnershipService,
        CustomerSuccessService $customerSuccessService
    ) {
        $this->paymentGatewayService = $paymentGatewayService;
        $this->geographicExpansionService = $geographicExpansionService;
        $this->partnershipService = $partnershipService;
        $this->customerSuccessService = $customerSuccessService;
    }
    
    /**
     * Display business growth dashboard
     */
    public function dashboard()
    {
        $data = [
            'payment_gateways' => $this->paymentGatewayService->getGatewayStats(),
            'geographic_expansion' => $this->geographicExpansionService->getExpansionStats(),
            'partnerships' => $this->partnershipService->generatePartnershipReport(),
            'customer_success' => $this->customerSuccessService->generateReport(),
        ];
        
        return view('admin.business-growth.dashboard', $data);
    }
    
    /**
     * Payment Gateway Management
     */
    public function paymentGateways()
    {
        $gateways = $this->paymentGatewayService->getAvailableGateways();
        return view('admin.business-growth.payment-gateways', compact('gateways'));
    }
    
    public function configurePaymentGateway(Request $request, string $gateway)
    {
        $request->validate([
            'enabled' => 'boolean',
            'priority' => 'integer|min:1|max:100',
            'credentials' => 'array',
        ]);
        
        try {
            $this->paymentGatewayService->configureGateway($gateway, $request->all());
            
            return back()->with('success', 'Payment gateway configured successfully');
        } catch (\Exception $e) {
            Log::error('Payment gateway configuration error: ' . $e->getMessage());
            return back()->with('error', 'Failed to configure payment gateway');
        }
    }
    
    /**
     * Geographic Expansion Management
     */
    public function geographicExpansion()
    {
        $expansion = $this->geographicExpansionService->getExpansionOverview();
        return view('admin.business-growth.geographic-expansion', compact('expansion'));
    }
    
    public function updateRegionalPricing(Request $request)
    {
        $request->validate([
            'country' => 'required|string|size:2',
            'plan' => 'required|string',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
        ]);
        
        try {
            $this->geographicExpansionService->updateRegionalPricing(
                $request->country,
                $request->plan,
                $request->price,
                $request->currency
            );
            
            return back()->with('success', 'Regional pricing updated successfully');
        } catch (\Exception $e) {
            Log::error('Regional pricing update error: ' . $e->getMessage());
            return back()->with('error', 'Failed to update regional pricing');
        }
    }
    
    public function updateGeographicRestrictions(Request $request)
    {
        $request->validate([
            'restrictions' => 'required|array',
            'restrictions.*.country' => 'required|string|size:2',
            'restrictions.*.allowed' => 'required|boolean',
            'restrictions.*.reason' => 'nullable|string',
        ]);
        
        try {
            $this->geographicExpansionService->updateGeographicRestrictions($request->restrictions);
            
            return back()->with('success', 'Geographic restrictions updated successfully');
        } catch (\Exception $e) {
            Log::error('Geographic restrictions update error: ' . $e->getMessage());
            return back()->with('error', 'Failed to update geographic restrictions');
        }
    }
    
    /**
     * Partnership Management
     */
    public function partnerships()
    {
        $partnerships = $this->partnershipService->getAvailablePartnerships();
        return view('admin.business-growth.partnerships', compact('partnerships'));
    }
    
    public function integratePartnership(Request $request, string $service)
    {
        $request->validate([
            'credentials' => 'required|array',
            'credentials.api_key' => 'required|string',
        ]);
        
        try {
            $success = $this->partnershipService->integrateWithService($service, $request->credentials);
            
            if ($success) {
                return back()->with('success', 'Partnership integrated successfully');
            } else {
                return back()->with('error', 'Failed to integrate partnership');
            }
        } catch (\Exception $e) {
            Log::error('Partnership integration error: ' . $e->getMessage());
            return back()->with('error', 'Partnership integration failed');
        }
    }
    
    public function affiliateProgram()
    {
        $stats = $this->partnershipService->generatePartnershipReport();
        return view('admin.business-growth.affiliate-program', compact('stats'));
    }
    
    public function resellerProgram()
    {
        $stats = $this->partnershipService->generatePartnershipReport();
        return view('admin.business-growth.reseller-program', compact('stats'));
    }
    
    /**
     * Customer Success Management
     */
    public function customerSuccess()
    {
        $report = $this->customerSuccessService->generateReport();
        return view('admin.business-growth.customer-success', compact('report'));
    }
    
    public function runAutomation(Request $request)
    {
        try {
            $this->customerSuccessService->runAutomation();
            
            return back()->with('success', 'Customer success automation completed');
        } catch (\Exception $e) {
            Log::error('Customer success automation error: ' . $e->getMessage());
            return back()->with('error', 'Automation failed');
        }
    }
    
    public function updateHealthScores(Request $request)
    {
        try {
            $this->customerSuccessService->updateHealthScores();
            
            return back()->with('success', 'Health scores updated successfully');
        } catch (\Exception $e) {
            Log::error('Health score update error: ' . $e->getMessage());
            return back()->with('error', 'Failed to update health scores');
        }
    }
    
    /**
     * Analytics and Reporting
     */
    public function analytics()
    {
        $data = [
            'payment_analytics' => $this->paymentGatewayService->getAnalytics(),
            'geographic_analytics' => $this->geographicExpansionService->getAnalytics(),
            'partnership_analytics' => $this->partnershipService->generatePartnershipReport(),
            'customer_analytics' => $this->customerSuccessService->generateReport(),
        ];
        
        return view('admin.business-growth.analytics', $data);
    }
    
    public function exportReport(Request $request)
    {
        $request->validate([
            'type' => 'required|in:payment,geographic,partnership,customer_success',
            'period' => 'required|in:weekly,monthly,quarterly,yearly',
            'format' => 'required|in:csv,pdf,excel',
        ]);
        
        try {
            $report = $this->generateReport($request->type, $request->period);
            
            return $this->downloadReport($report, $request->format);
        } catch (\Exception $e) {
            Log::error('Report export error: ' . $e->getMessage());
            return back()->with('error', 'Failed to export report');
        }
    }
    
    private function generateReport(string $type, string $period): array
    {
        switch ($type) {
            case 'payment':
                return $this->paymentGatewayService->generateReport($period);
            case 'geographic':
                return $this->geographicExpansionService->generateReport($period);
            case 'partnership':
                return $this->partnershipService->generatePartnershipReport($period);
            case 'customer_success':
                return $this->customerSuccessService->generateReport($period);
            default:
                throw new \InvalidArgumentException('Invalid report type');
        }
    }
    
    private function downloadReport(array $report, string $format)
    {
        // Implementation would depend on chosen reporting library
        // For now, return JSON response
        return response()->json($report)
            ->header('Content-Disposition', 'attachment; filename="report.' . $format . '"');
    }
}
