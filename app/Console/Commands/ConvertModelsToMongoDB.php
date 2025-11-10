<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ConvertModelsToMongoDB extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'convert:models {--path=app/Models : Chemin vers les modèles} {--dry-run : Afficher les changements sans les appliquer}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convertir automatiquement les modèles Laravel SQL vers MongoDB';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $path = $this->option('path');
        $dryRun = $this->option('dry-run');

        $this->info('🔄 Démarrage de la conversion des modèles vers MongoDB...');

        if ($dryRun) {
            $this->warn('Mode DRY-RUN activé - Aucun fichier ne sera modifié');
        }

        $modelFiles = $this->getModelFiles($path);

        if (empty($modelFiles)) {
            $this->error("Aucun fichier modèle trouvé dans {$path}");
            return;
        }

        $this->info("📁 Trouvé " . count($modelFiles) . " fichiers modèles");

        $converted = 0;
        $skipped = 0;

        foreach ($modelFiles as $filePath) {
            if ($this->convertModel($filePath, $dryRun)) {
                $converted++;
            } else {
                $skipped++;
            }
        }

        $this->info("✅ Conversion terminée : {$converted} modèles convertis, {$skipped} ignorés");
    }

    /**
     * Obtenir la liste des fichiers modèles
     */
    private function getModelFiles(string $path): array
    {
        $files = [];

        if (!File::exists($path)) {
            return $files;
        }

        $items = File::allFiles($path);

        foreach ($items as $item) {
            if ($item->getExtension() === 'php') {
                $files[] = $item->getPathname();
            }
        }

        return $files;
    }

    /**
     * Convertir un modèle spécifique
     */
    private function convertModel(string $filePath, bool $dryRun): bool
    {
        $content = File::get($filePath);
        $originalContent = $content;

        // Vérifier si c'est déjà un modèle MongoDB
        if (strpos($content, 'MongoDB\\Laravel\\Eloquent\\Model') !== false) {
            $this->line("⏭️  Modèle déjà converti : " . basename($filePath));
            return false;
        }

        // Vérifier si c'est un modèle Eloquent standard
        if (strpos($content, 'Illuminate\\Database\\Eloquent\\Model') === false) {
            $this->line("⏭️  Pas un modèle Eloquent : " . basename($filePath));
            return false;
        }

        $this->line("🔄 Conversion de : " . basename($filePath));

        // Remplacer l'import du modèle
        $content = str_replace(
            'use Illuminate\Database\Eloquent\Model;',
            'use MongoDB\Laravel\Eloquent\Model;',
            $content
        );

        // Ajouter la connexion MongoDB si elle n'existe pas
        if (strpos($content, 'protected $connection') === false) {
            // Trouver la classe et ajouter la connexion après la déclaration de classe
            $pattern = '/class\s+\w+\s+extends\s+Model\s*\{/';
            if (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                $insertPos = $matches[0][1] + strlen($matches[0][0]);
                $insertText = "\n    protected \$connection = 'mongodb';\n";
                $content = substr_replace($content, $insertText, $insertPos, 0);
            }
        }

        // Ajouter la collection si elle n'existe pas
        if (strpos($content, 'protected $collection') === false) {
            // Trouver la classe et ajouter la collection
            $pattern = '/class\s+(\w+)\s+extends\s+Model\s*\{/';
            if (preg_match($pattern, $content, $matches)) {
                $className = $matches[1];
                $collectionName = strtolower($className) . 's'; // Convention Laravel

                // Insérer après la connexion
                $pattern = '/(protected \$connection = \'mongodb\';\s*\n)/';
                if (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                    $insertPos = $matches[0][1] + strlen($matches[0][0]);
                    $insertText = "    protected \$collection = '{$collectionName}';\n\n";
                    $content = substr_replace($content, $insertText, $insertPos, 0);
                }
            }
        }

        // Convertir les relations hasManyThrough si nécessaire
        $content = $this->convertHasManyThrough($content);

        if ($content !== $originalContent) {
            if (!$dryRun) {
                File::put($filePath, $content);
                $this->info("✅ Modèle converti : " . basename($filePath));
            } else {
                $this->warn("🔍 [DRY-RUN] Modifications détectées pour : " . basename($filePath));
            }
            return true;
        }

        $this->line("⏭️  Aucune modification nécessaire : " . basename($filePath));
        return false;
    }

    /**
     * Convertir les relations hasManyThrough pour MongoDB
     */
    private function convertHasManyThrough(string $content): string
    {
        // hasManyThrough n'est pas directement supporté dans MongoDB
        // Nous le remplaçons par une méthode personnalisée
        $pattern = '/public function (\w+)\(\)\s*\{\s*return \$this->hasManyThrough\(([^}]+)\);\s*\}/';
        $replacement = 'public function $1() {
        // Note: hasManyThrough n\'est pas directement supporté dans MongoDB
        // Utilisez une méthode personnalisée ou restructurez vos données
        return $this->hasManyThrough($2);
    }';

        return preg_replace($pattern, $replacement, $content);
    }
}
