<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     title="OM-Paie API",
 *     version="2.0.0",
 *     description="API de paiement mobile et transfert d'argent - OM-Paie avec MongoDB",
 *     @OA\Contact(
 *         email="support@om-paie.sn"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 *
 ** @OA\Server(
 *     url="https://tinkin-transfer.onrender.com/api",
 *     description="Serveur de production sur Render"
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000/api",
 *     description="Serveur de développement"
 * )
 *
 *
 * @OA\SecurityScheme(
 *     securityScheme="passport",
 *     type="oauth2",
 *     description="OAuth2 avec Laravel Passport",
 *     @OA\Flow(
 *         flow="password",
 *         tokenUrl="/oauth/token",
 *         scopes={}
 *     )
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Bearer Token (JWT) obtenu après connexion"
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="Gestion de l'authentification des utilisateurs"
 * )
 *
 * @OA\Tag(
 *     name="Transfer",
 *     description="Transferts d'argent entre utilisateurs"
 * )
 *
 * @OA\Tag(
 *     name="Payment",
 *     description="Paiements vers les marchands"
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
