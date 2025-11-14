<?php

namespace App\Services;

use App\Models\Passport\Token;
use App\Models\Passport\Client;
use App\Models\Passport\PersonalAccessClient;
use App\Models\User;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\ValidAt;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use Lcobucci\Clock\SystemClock;

class MongoPassportService
{
    public function createPersonalAccessToken(User $user, string $name = 'Personal Access Token', array $scopes = [])
    {
        // Utiliser un ID de client fixe pour simplifier
        $clientId = '9e974ca6-da07-4217-9a2d-ac5c780eab17'; // ID du client créé précédemment

        // Access token: 15 minutes
        $accessExpiresAt = Carbon::now()->addMinutes(15);

        // Refresh token: 30 jours (valeur retournée en clair, stockée en hash)
        $refreshTokenPlain = rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
        $refreshExpiresAt = Carbon::now()->addDays(30);

        // Créer le token - laisser HasUuids générer l'ID automatiquement
        $token = new Token([
            'user_id' => $user->id,
            'client_id' => $clientId,
            'name' => $name,
            'scopes' => $scopes,
            'revoked' => false,
            'expires_at' => $accessExpiresAt,
            'refresh_token_hash' => hash('sha256', $refreshTokenPlain),
            'refresh_expires_at' => $refreshExpiresAt,
        ]);
        $token->save();

        // Générer l'access token (JWT-like)
        $accessToken = base64_encode(json_encode([
            'token_id' => $token->id,
            'user_id' => $user->id,
            'client_id' => $clientId,
            'scopes' => $scopes,
            'issued_at' => now()->timestamp,
            'expires_at' => $accessExpiresAt->timestamp,
        ]));

        return (object) [
            'accessToken' => $accessToken,
            'token' => $token,
            'refreshToken' => $refreshTokenPlain,
            'refresh_expires_at' => $refreshExpiresAt,
        ];
    }

    public function getPersonalAccessClient()
    {
        $personalAccessClient = PersonalAccessClient::first();

        if ($personalAccessClient && $personalAccessClient->client_id) {
            return Client::where('id', $personalAccessClient->client_id)->first();
        }

        return null;
    }

    public function createPersonalAccessClient()
    {
        // Créer le client
        $client = Client::create([
            'id' => Str::uuid(),
            'name' => 'Personal Access Client',
            'secret' => null,
            'provider' => null,
            'redirect' => 'http://localhost',
            'personal_access_client' => true,
            'password_client' => false,
            'revoked' => false,
        ]);

        // Créer l'enregistrement personal access client
        PersonalAccessClient::create([
            'client_id' => $client->id,
        ]);

        return $client;
    }

    public function validateToken(string $accessToken)
    {
        try {
            $decoded = json_decode(base64_decode($accessToken), true);

            if (!$decoded || !isset($decoded['token_id'])) {
                return null;
            }

            $token = Token::where('id', $decoded['token_id'])
                ->where('revoked', false)
                ->first();

            if (!$token || $token->expires_at < now()) {
                return null;
            }

            return $token;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function revokeToken(string $accessToken)
    {
        $token = $this->validateToken($accessToken);

        if ($token) {
            $token->update([
                'revoked' => true,
                'refresh_expires_at' => now(),
                'refresh_token_hash' => null,
            ]);
            return true;
        }

        return false;
    }

    /**
     * Rafraîchir l'access token à partir d'un refresh token JWT valide.
     * - Valide le JWT refresh token signé avec HS256
     * - Rotation du refresh token (nouveau JWT retourné)
     * - Prolonge l'expiration de l'access token à 15 minutes
     * - Prolonge le refresh token de 30 jours
     *
     * @return array|null
     */
    public function refreshAccessToken(string $refreshTokenJwt)
    {
        try {
            // Décoder le JWT manuellement pour extraire les claims
            $parts = explode('.', $refreshTokenJwt);
            if (count($parts) !== 3) {
                return null;
            }

            $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
            if (!$payload || !isset($payload['user_id']) || !isset($payload['token_id'])) {
                return null;
            }

            // Vérifier l'expiration
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return null;
            }

            $userId = $payload['user_id'];
            $tokenId = $payload['token_id'];

            // Valider la signature avec Lcobucci
            $config = Configuration::forSymmetricSigner(
                new Sha256(),
                InMemory::plainText(config('passport.secret'))
            );

            $token = $config->parser()->parse($refreshTokenJwt);
            if (!$config->validator()->validate($token, new SignedWith($config->signer(), $config->signingKey()))) {
                return null;
            }

            /** @var \App\Models\Passport\Token|null $tokenRecord */
            $tokenRecord = Token::where('id', $tokenId)
                ->where('user_id', $userId)
                ->where('revoked', false)
                ->first();

            if (!$tokenRecord) {
                return null;
            }

            /** @var \App\Models\User|null $user */
            $user = User::find($userId);
            if (!$user) {
                return null;
            }

            $clientId = $tokenRecord->client_id;

            // Nouvelle expiration d'access token (15 min)
            $accessExpiresAt = Carbon::now()->addMinutes(15);

            // Générer un nouveau refresh token JWT (30 jours)
            $now = new \DateTimeImmutable();
            $refreshExpires = $now->modify('+30 days');

            $newRefreshToken = $config->builder()
                ->issuedBy(config('app.url'))
                ->permittedFor(config('app.url'))
                ->identifiedBy($tokenRecord->id . '-refresh')
                ->issuedAt($now)
                ->expiresAt($refreshExpires)
                ->withClaim('user_id', $user->id)
                ->withClaim('token_id', $tokenRecord->id)
                ->getToken($config->signer(), $config->signingKey());

            // Mettre à jour le token en base (optionnel, puisque le JWT est auto-suffisant)
            $tokenRecord->expires_at = $accessExpiresAt;
            $tokenRecord->refresh_expires_at = $refreshExpires;
            $tokenRecord->save();

            // Re-générer l'access token encodé
            $accessToken = base64_encode(json_encode([
                'token_id' => $tokenRecord->id,
                'user_id' => $user->id,
                'client_id' => $clientId,
                'scopes' => $tokenRecord->scopes ?? [],
                'issued_at' => now()->timestamp,
                'expires_at' => $accessExpiresAt->timestamp,
            ]));

            return [
                'user' => $user,
                'access_token' => $accessToken,
                'expires_at' => $accessExpiresAt,
                'expires_in' => 15 * 60,
                'refresh_token' => $newRefreshToken->toString(),
                'refresh_expires_at' => $refreshExpires,
            ];
        } catch (RequiredConstraintsViolated | \Exception $e) {
            return null;
        }
    }
}
