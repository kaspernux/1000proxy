<?php

namespace App\Http\Controllers;

use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class QrCodeController extends Controller
{
    public function __construct(
        private QrCodeService $qrCodeService
    ) {}

    /**
     * Generate a branded QR code
     */
    public function generate(Request $request): Response|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'data' => 'required|string|max:4296',
            'size' => 'integer|min:50|max:1000',
            'format' => 'string|in:png,svg,eps,pdf',
            'style' => 'string|in:square,dot,round',
            'eye' => 'string|in:square,circle',
            'color_scheme' => 'string|in:primary,secondary,dark,inverse',
            'margin' => 'integer|min:0|max:50',
            'error_correction' => 'string|in:L,M,Q,H'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid parameters',
                'details' => $validator->errors()
            ], 400);
        }

        $data = $request->input('data');
        $size = $request->input('size', 300);
        $format = $request->input('format', 'png');
        
        $options = [
            'style' => $request->input('style', 'dot'),
            'eye' => $request->input('eye', 'circle'),
            'colorScheme' => $request->input('color_scheme', 'primary'),
            'margin' => $request->input('margin', 15),
            'errorCorrection' => $request->input('error_correction', 'M'),
        ];

        try {
            // Validate QR data
            if (!$this->qrCodeService->validateQrData($data)) {
                return response()->json([
                    'error' => 'Invalid QR code data'
                ], 400);
            }

            $qrCode = $this->qrCodeService->generateBrandedQrCode($data, $size, $format, $options);

            $mimeType = match($format) {
                'svg' => 'image/svg+xml',
                'eps' => 'application/postscript',
                'pdf' => 'application/pdf',
                default => 'image/png'
            };

            return response($qrCode)
                ->header('Content-Type', $mimeType)
                ->header('Content-Disposition', 'inline; filename="qrcode.' . $format . '"')
                ->header('Cache-Control', 'public, max-age=3600');

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate QR code',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate client configuration QR code
     */
    public function generateClient(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_link' => 'required|url',
            'size' => 'integer|min:50|max:1000',
            'color_scheme' => 'string|in:primary,secondary,dark,inverse'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid parameters',
                'details' => $validator->errors()
            ], 400);
        }

        $clientLink = $request->input('client_link');
        $size = $request->input('size', 200);
        $colorScheme = $request->input('color_scheme', 'primary');

        try {
            $qrCodeBase64 = $this->qrCodeService->generateClientQrCode($clientLink, [
                'colorScheme' => $colorScheme
            ]);

            return response()->json([
                'qr_code' => $qrCodeBase64,
                'format' => 'base64_png'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate client QR code',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate subscription QR code
     */
    public function generateSubscription(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'subscription_link' => 'required|url',
            'size' => 'integer|min:50|max:1000',
            'color_scheme' => 'string|in:primary,secondary,dark,inverse'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid parameters',
                'details' => $validator->errors()
            ], 400);
        }

        $subscriptionLink = $request->input('subscription_link');
        $size = $request->input('size', 250);
        $colorScheme = $request->input('color_scheme', 'secondary');

        try {
            $qrCodeBase64 = $this->qrCodeService->generateSubscriptionQrCode($subscriptionLink, [
                'colorScheme' => $colorScheme
            ]);

            return response()->json([
                'qr_code' => $qrCodeBase64,
                'format' => 'base64_png'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate subscription QR code',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate download QR code
     */
    public function generateDownload(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'download_url' => 'required|url',
            'size' => 'integer|min:50|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid parameters',
                'details' => $validator->errors()
            ], 400);
        }

        $downloadUrl = $request->input('download_url');
        $size = $request->input('size', 180);

        try {
            $qrCodeBase64 = $this->qrCodeService->generateDownloadQrCode($downloadUrl);

            return response()->json([
                'qr_code' => $qrCodeBase64,
                'format' => 'base64_png'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate download QR code',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate multiple QR codes for client configuration set
     */
    public function generateClientSet(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_link' => 'required|url',
            'subscription_link' => 'url',
            'json_subscription_link' => 'url'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid parameters',
                'details' => $validator->errors()
            ], 400);
        }

        $links = $request->only(['client_link', 'subscription_link', 'json_subscription_link']);

        try {
            $qrCodes = $this->qrCodeService->generateClientQrCodeSet($links);

            return response()->json([
                'qr_codes' => $qrCodes,
                'format' => 'base64_png'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate QR code set',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate mobile app deep link QR code
     */
    public function generateMobileApp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'deep_link' => 'required|string',
            'size' => 'integer|min:50|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid parameters',
                'details' => $validator->errors()
            ], 400);
        }

        $deepLink = $request->input('deep_link');
        $size = $request->input('size', 280);

        try {
            $qrCodeBase64 = $this->qrCodeService->generateMobileAppQrCode($deepLink);

            return response()->json([
                'qr_code' => $qrCodeBase64,
                'format' => 'base64_png'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate mobile app QR code',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get optimal QR code size for given data
     */
    public function getOptimalSize(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'data' => 'required|string|max:4296'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid parameters',
                'details' => $validator->errors()
            ], 400);
        }

        $data = $request->input('data');
        $optimalSize = $this->qrCodeService->getOptimalSize($data);

        return response()->json([
            'optimal_size' => $optimalSize,
            'data_length' => strlen($data)
        ]);
    }

    /**
     * Validate QR code data
     */
    public function validateData(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'data' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid parameters',
                'details' => $validator->errors()
            ], 400);
        }

        $data = $request->input('data');
        $isValid = $this->qrCodeService->validateQrData($data);

        return response()->json([
            'valid' => $isValid,
            'data_length' => strlen($data),
            'max_length' => 4296
        ]);
    }
}
