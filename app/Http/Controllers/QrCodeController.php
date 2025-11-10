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

            return response()->json([
                'success' => true,
                'message' => 'Données du QR code récupérées avec succès',
                'data' => array_merge($qrData, [
                    'qr_string' => json_encode($qrData)
                ])
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

            // Décoder les données du QR code
            $qrData = json_decode($request->qr_data, true);

            if (!$qrData || !isset($qrData['user_id']) || !isset($qrData['type'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR code invalide ou format incorrect'
                ], 400);
            }

            // Vérifier que c'est un QR code OM-Paie
            if ($qrData['type'] !== 'om_paie_user') {
                return response()->json([
                    'success' => false,
                    'message' => 'QR code non reconnu par OM-Paie'
                ], 400);
            }

            // Rechercher l'utilisateur
            $user = User::find($qrData['user_id']);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'QR code décodé avec succès',
                'data' => $qrData,
                'user_info' => [
                    'id' => $user->id,
                    'nom' => $user->nom,
                    'prenom' => $user->prenom,
                    'telephone' => $user->telephone
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du scan du QR code',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
