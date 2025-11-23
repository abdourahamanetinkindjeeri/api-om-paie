<?php

namespace App\Http\Controllers;

use App\Models\User;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class QrCodeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/user/qrcode",
     *     summary="Générer le QR code de l'utilisateur connecté",
     *     tags={"QR Code"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="QR code généré avec succès",
     *         @OA\MediaType(
     *             mediaType="image/svg+xml",
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié"
     *     )
     * )
     */
    public function generateUserQrCode(Request $request)
    {
        try {
            /** @var User $user */
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié'
                ], 401);
            }

            // Données à encoder dans le QR code
            $qrData = [
                'user_id' => $user->id,
                'telephone' => $user->telephone,
                'nom_complet' => $user->prenom . ' ' . $user->nom,
                'type' => 'om_paie_user',
                'timestamp' => now()->toISOString()
            ];

            // Créer le QR code avec BaconQrCode et SVG backend (pas besoin d'Imagick)
            $renderer = new ImageRenderer(
                new RendererStyle(300, 10),
                new SvgImageBackEnd()
            );
            $writer = new Writer($renderer);
            $qrCodeString = $writer->writeString(json_encode($qrData));

            return response($qrCodeString)
                ->header('Content-Type', 'image/svg+xml')
                ->header('Content-Disposition', 'inline; filename="qrcode-' . $user->id . '.svg"');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du QR code',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/user/qrcode/data",
     *     summary="Obtenir les données du QR code de l'utilisateur connecté",
     *     tags={"QR Code"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Données du QR code récupérées avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Données du QR code récupérées avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="user_id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="telephone", type="string", example="+221771234567"),
     *                 @OA\Property(property="nom_complet", type="string", example="John Doe"),
     *                 @OA\Property(property="type", type="string", example="om_paie_user"),
     *                 @OA\Property(property="timestamp", type="string", format="date-time"),
     *                 @OA\Property(property="qr_string", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié"
     *     )
     * )
     */
    public function getUserQrCodeData(Request $request)
    {
        try {
            /** @var User $user */
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié'
                ], 401);
            }

            // Données à encoder dans le QR code
            $qrData = [
                'user_id' => $user->id,
                'telephone' => $user->telephone,
                'nom_complet' => $user->prenom . ' ' . $user->nom,
                'type' => 'om_paie_user',
                'timestamp' => now()->toISOString()
            ];

            // Format ultra-simplifié : seulement le numéro de téléphone
            $qrString = $user->telephone;

            return response()->json([
                'success' => true,
                'message' => 'Données du QR code récupérées avec succès',
                'data' => [
                    'qr_data' => $qrData,
                    'qr_string' => $qrString
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des données',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/qrcode/scan",
     *     summary="Scanner et décoder un QR code",
     *     tags={"QR Code"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="qr_data", type="string", description="Données du QR code scanné")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="QR code décodé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="user_id", type="string"),
     *                 @OA\Property(property="telephone", type="string"),
     *                 @OA\Property(property="nom_complet", type="string"),
     *                 @OA\Property(property="type", type="string")
     *             ),
     *             @OA\Property(
     *                 property="user_info",
     *                 type="object",
     *                 @OA\Property(property="id", type="string"),
     *                 @OA\Property(property="nom", type="string"),
     *                 @OA\Property(property="prenom", type="string"),
     *                 @OA\Property(property="telephone", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="QR code invalide"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Utilisateur non trouvé"
     *     )
     * )
     */
    public function scanQrCode(Request $request)
    {
        try {
            $request->validate([
                'qr_data' => 'required|string'
            ]);

            $qrData = $request->qr_data;

            // Vérifier si c'est un numéro de téléphone sénégalais (+221XXXXXXXXX)
            if (preg_match('/^\+221\d{9}$/', $qrData)) {
                // Format ultra-simplifié : seulement le numéro de téléphone
                $telephone = $qrData;

                // Rechercher l'utilisateur par téléphone
                $user = User::where('telephone', $telephone)->first();

                if (!$user) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Utilisateur non trouvé'
                    ], 404);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'QR code décodé avec succès',
                    'data' => [
                        'user_id' => $user->id,
                        'telephone' => $user->telephone,
                        'nom_complet' => $user->prenom . ' ' . $user->nom,
                        'type' => 'om_paie_user',
                        'timestamp' => now()->toISOString()
                    ],
                    'user_info' => [
                        'id' => $user->id,
                        'nom' => $user->nom,
                        'prenom' => $user->prenom,
                        'telephone' => $user->telephone
                    ]
                ]);
            }

            // Vérifier l'ancien format OM_PAY:telephone:user_id
            if (str_starts_with($qrData, 'OM_PAY:')) {
                $parts = explode(':', $qrData);
                if (count($parts) === 3) {
                    $telephone = $parts[1];
                    $userId = $parts[2];

                    $user = User::where('telephone', $telephone)
                               ->where('id', $userId)
                               ->first();

                    if (!$user) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Utilisateur non trouvé'
                        ], 404);
                    }

                    return response()->json([
                        'success' => true,
                        'message' => 'QR code décodé avec succès',
                        'data' => [
                            'user_id' => $user->id,
                            'telephone' => $user->telephone,
                            'nom_complet' => $user->prenom . ' ' . $user->nom,
                            'type' => 'om_paie_user',
                            'timestamp' => now()->toISOString()
                        ],
                        'user_info' => [
                            'id' => $user->id,
                            'nom' => $user->nom,
                            'prenom' => $user->prenom,
                            'telephone' => $user->telephone
                        ]
                    ]);
                }
            }

            // Fallback: essayer l'ancien format JSON
            $jsonData = json_decode($qrData, true);
            if ($jsonData && isset($jsonData['user_id']) && isset($jsonData['type'])) {
                if ($jsonData['type'] !== 'om_paie_user') {
                    return response()->json([
                        'success' => false,
                        'message' => 'QR code non reconnu par OM-Paie'
                    ], 400);
                }

                $user = User::find($jsonData['user_id']);
                if (!$user) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Utilisateur non trouvé'
                    ], 404);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'QR code décodé avec succès',
                    'data' => $jsonData,
                    'user_info' => [
                        'id' => $user->id,
                        'nom' => $user->nom,
                        'prenom' => $user->prenom,
                        'telephone' => $user->telephone
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Format de QR code non reconnu'
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du scan du QR code',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
