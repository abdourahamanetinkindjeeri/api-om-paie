<?php

namespace App\Services;

use App\Models\Passport\Token;
use App\Models\Passport\Client;
use App\Models\Passport\PersonalAccessClient;
use App\Models\User;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MongoPassportService
{
    public function createPersonalAccessToken(User $user, string $name = 'Personal Access Token', array $scopes = [])
    {
        // Utiliser un ID de client fixe pour simplifier
        $clientId = '9e974ca6-da07-4217-9a2d-ac5c780eab17'; // ID du client créé précédemment

        // Créer le token - laisser HasUuids générer l'ID automatiquement
        $token = new Token([
            'user_id' => $user->id,
            'client_id' => $clientId,
            'name' => $name,
            'scopes' => $scopes,
            'revoked' => false,
            'expires_at' => Carbon::now()->addYear(), // Token expire dans 1 an
        ]);
        $token->save();

        // Générer l'access token (JWT-like)
        $accessToken = base64_encode(json_encode([
            'token_id' => $token->id,
            'user_id' => $user->id,
            'client_id' => $clientId,
            'scopes' => $scopes,
            'issued_at' => now()->timestamp,
            'expires_at' => $token->expires_at->timestamp,
        ]));

        return (object) [
            'accessToken' => $accessToken,
            'token' => $token,
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
            $token->update(['revoked' => true]);
            return true;
        }

        return false;
    }
}
