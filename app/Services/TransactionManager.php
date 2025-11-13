<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Gestionnaire de transactions MongoDB avec support des replica sets
 * Fournit une abstraction pour les opérations transactionnelles ACID
 */
class TransactionManager
{
    /**
     * Exécute une fonction dans une vraie transaction MongoDB multi-document
     *
     * @param callable $callback Fonction à exécuter dans la transaction
     * @param array $options Options de transaction MongoDB
     * @return mixed Résultat de la fonction callback
     * @throws Exception Si la transaction échoue
     */
    public function executeInTransaction(callable $callback, array $options = [])
    {
        // Vérifier si nous sommes sur un replica set
        $isReplicaSet = $this->isReplicaSetAvailable();

        if (!$isReplicaSet) {
            Log::warning('Replica set non disponible, exécution sans transaction ACID');
            return $callback(null);
        }

        // Options par défaut pour la transaction MongoDB native
        $defaultOptions = [
            'readConcern' => 'majority',
            'writeConcern' => ['w' => 'majority', 'j' => true, 'wtimeout' => 10000],
            'readPreference' => 'primary',
            'maxCommitTimeMS' => 10000
        ];

        $transactionOptions = array_merge($defaultOptions, $options);

        // Obtenir le client MongoDB natif
        $client = DB::getMongoClient();

        // Démarrer une session MongoDB
        $session = $client->startSession();

        try {
            // Démarrer la transaction
            $session->startTransaction($transactionOptions);

            Log::info('Transaction MongoDB démarrée', [
                'session_id' => $session->getLogicalSessionId()->getId(),
                'options' => $this->sanitizeOptionsForLogging($transactionOptions)
            ]);

            // Exécuter la logique métier avec la session
            $result = $callback($session);

            // Commit de la transaction
            $session->commitTransaction();

            Log::info('Transaction MongoDB committée avec succès', [
                'session_id' => $session->getLogicalSessionId()->getId()
            ]);

            return $result;

        } catch (Exception $e) {
            // Gestion des erreurs (MongoDB ou autres)
            $isMongoError = str_contains($e->getMessage(), 'MongoDB') ||
                           method_exists($e, 'getCode') && $e->getCode() > 0;

            Log::error($isMongoError ? 'Erreur MongoDB lors de la transaction' : 'Erreur lors de la transaction', [
                'error' => $e->getMessage(),
                'code' => method_exists($e, 'getCode') ? $e->getCode() : null,
                'session_id' => method_exists($session, 'getLogicalSessionId') ?
                    $session->getLogicalSessionId()->getId() ?? null : null
            ]);

            try {
                if (method_exists($session, 'abortTransaction')) {
                    $session->abortTransaction();
                    Log::info('Transaction MongoDB annulée suite à une erreur');
                }
            } catch (Exception $abortException) {
                Log::error('Erreur lors de l\'annulation de la transaction', [
                    'error' => $abortException->getMessage()
                ]);
            }

            throw new Exception('Erreur de transaction : ' . $e->getMessage());

        } catch (Exception $e) {
            // Gestion des autres erreurs
            Log::error('Erreur lors de l\'exécution de la transaction MongoDB', [
                'error' => $e->getMessage(),
                'session_id' => $session->getLogicalSessionId()->getId() ?? null,
                'options' => $this->sanitizeOptionsForLogging($transactionOptions)
            ]);

            try {
                $session->abortTransaction();
                Log::info('Transaction MongoDB annulée suite à une erreur');
            } catch (Exception $abortException) {
                Log::error('Erreur lors de l\'annulation de la transaction', [
                    'error' => $abortException->getMessage()
                ]);
            }

            throw new Exception('Erreur de transaction : ' . $e->getMessage());
        } finally {
            $session->endSession();
        }
    }

    /**
     * Vérifie si un replica set MongoDB est disponible
     *
     * @return bool
     */
    private function isReplicaSetAvailable(): bool
    {
        try {
            $client = DB::getMongoClient();
            $isMaster = $client->admin->command(['isMaster' => 1])->toArray()[0];

            // Vérifier si nous sommes dans un replica set
            return isset($isMaster['setName']) && !empty($isMaster['setName']);
        } catch (Exception $e) {
            Log::warning('Impossible de vérifier le statut du replica set', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Exécute une opération de paiement avec transaction
     *
     * @param callable $paymentCallback Fonction contenant la logique de paiement
     * @return mixed
     */
    public function executePaymentTransaction(callable $paymentCallback)
    {
        return $this->executeInTransaction($paymentCallback, [
            'writeConcern' => ['w' => 'majority', 'j' => true],
            'maxCommitTimeMS' => 10000 // 10 secondes timeout
        ]);
    }

    /**
     * Exécute une opération de transfert avec transaction
     *
     * @param callable $transferCallback Fonction contenant la logique de transfert
     * @return mixed
     */
    public function executeTransferTransaction(callable $transferCallback)
    {
        return $this->executeInTransaction($transferCallback, [
            'writeConcern' => ['w' => 'majority', 'j' => true],
            'maxCommitTimeMS' => 15000 // 15 secondes timeout pour les transferts
        ]);
    }

    /**
     * Exécute une opération critique avec les garanties les plus strictes
     *
     * @param callable $criticalCallback Fonction contenant la logique critique
     * @return mixed
     */
    public function executeCriticalTransaction(callable $criticalCallback)
    {
        return $this->executeInTransaction($criticalCallback, [
            'readConcern' => 'majority',
            'writeConcern' => ['w' => 'majority', 'j' => true, 'wtimeout' => 5000],
            'maxCommitTimeMS' => 20000 // 20 secondes timeout pour les opérations critiques
        ]);
    }

    /**
     * Nettoie les options de transaction pour le logging
     *
     * @param array $options
     * @return array
     */
    private function sanitizeOptionsForLogging(array $options): array
    {
        // Retirer les objets complexes qui ne peuvent pas être loggés
        $sanitized = [];
        foreach ($options as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $value;
            } elseif (is_string($value) || is_numeric($value) || is_bool($value)) {
                $sanitized[$key] = $value;
            } else {
                $sanitized[$key] = gettype($value);
            }
        }
        return $sanitized;
    }
}
