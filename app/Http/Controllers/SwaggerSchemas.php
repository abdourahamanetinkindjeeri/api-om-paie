<?php

namespace App\Http\Controllers;

/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     title="Utilisateur",
 *     description="Modèle d'utilisateur",
 *     @OA\Property(property="id", type="string", example="64f7b1a2c5d4e123456789ab"),
 *     @OA\Property(property="nom", type="string", example="Diop"),
 *     @OA\Property(property="prenom", type="string", example="Mamadou"),
 *     @OA\Property(property="telephone", type="string", example="+221771234567"),
 *     @OA\Property(property="email", type="string", format="email", example="mamadou@example.com"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Wallet",
 *     type="object",
 *     title="Portefeuille",
 *     description="Modèle de portefeuille utilisateur",
 *     @OA\Property(property="id", type="string", example="64f7b1a2c5d4e123456789ac"),
 *     @OA\Property(property="user_id", type="string", example="64f7b1a2c5d4e123456789ab"),
 *     @OA\Property(property="balance", type="number", format="float", example=25000.50),
 *     @OA\Property(property="currency", type="string", example="XOF"),
 *     @OA\Property(property="status", type="string", enum={"active", "blocked", "suspended"}, example="active"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Transaction",
 *     type="object",
 *     title="Transaction",
 *     description="Modèle de transaction",
 *     @OA\Property(property="id", type="string", example="64f7b1a2c5d4e123456789ad"),
 *     @OA\Property(property="type", type="string", enum={"transfer", "payment"}, example="transfer"),
 *     @OA\Property(property="montant", type="number", format="float", example=5000),
 *     @OA\Property(property="currency", type="string", example="XOF"),
 *     @OA\Property(property="expediteur_telephone", type="string", example="+221771234567"),
 *     @OA\Property(property="destinataire_telephone", type="string", example="+221781234567"),
 *     @OA\Property(property="status", type="string", enum={"pending", "completed", "failed", "cancelled"}, example="completed"),
 *     @OA\Property(property="metadata", type="object", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Payment",
 *     type="object",
 *     title="Paiement",
 *     description="Modèle de paiement",
 *     @OA\Property(property="id", type="string", example="64f7b1a2c5d4e123456789ae"),
 *     @OA\Property(property="type", type="string", example="payment"),
 *     @OA\Property(property="montant", type="number", format="float", example=2500),
 *     @OA\Property(property="currency", type="string", example="XOF"),
 *     @OA\Property(property="expediteur_telephone", type="string", example="+221771234567"),
 *     @OA\Property(property="destinataire_telephone", type="string", example="+221338901234"),
 *     @OA\Property(property="status", type="string", enum={"pending", "completed", "failed", "cancelled"}, example="completed"),
 *     @OA\Property(property="metadata", type="object",
 *         @OA\Property(property="code_marchand", type="string", example="BOUT001"),
 *         @OA\Property(property="description", type="string", example="Achat produits")
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Merchant",
 *     type="object",
 *     title="Marchand",
 *     description="Modèle de marchand",
 *     @OA\Property(property="id", type="string", example="64f7b1a2c5d4e123456789ae"),
 *     @OA\Property(property="name", type="string", example="Boutique Sandaga"),
 *     @OA\Property(property="code", type="string", example="SND001"),
 *     @OA\Property(property="telephone", type="string", example="+221338901234"),
 *     @OA\Property(property="email", type="string", format="email", example="boutique@sandaga.sn"),
 *     @OA\Property(property="status", type="string", enum={"active", "inactive"}, example="active"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *     title="Réponse d'erreur",
 *     description="Réponse standard en cas d'erreur",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Message d'erreur"),
 *     @OA\Property(property="error", type="string", example="error_code", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="ValidationErrorResponse",
 *     type="object",
 *     title="Erreur de validation",
 *     description="Réponse en cas d'erreur de validation",
 *     @OA\Property(property="message", type="string", example="The given data was invalid."),
 *     @OA\Property(property="errors", type="object",
 *         @OA\Property(property="field_name", type="array",
 *             @OA\Items(type="string", example="Le champ field_name est requis.")
 *         )
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="SuccessResponse",
 *     type="object",
 *     title="Réponse de succès",
 *     description="Réponse standard en cas de succès",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Opération réussie"),
 *     @OA\Property(property="data", type="object", nullable=true)
 * )
 */
class SwaggerSchemas extends Controller
{
    // Cette classe contient uniquement les schémas Swagger
}
