<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = App\Models\User::latest()->first();

if ($user) {
    echo "✅ User trouvé: " . $user->email . "\n";
    echo "📤 Dispatch du job...\n";
    
    App\Jobs\SendWelcomeOtpJob::dispatch($user->_id, $user->email);
    
    echo "✅ Job créé dans la queue 'notifications'\n";
    echo "\n🔍 Vérification dans MongoDB...\n";
    
    $jobsCount = DB::connection('mongodb')
        ->collection('jobs')
        ->where('queue', 'notifications')
        ->whereNull('reserved_at')
        ->count();
    
    echo "📊 Jobs en attente: $jobsCount\n";
} else {
    echo "❌ Aucun utilisateur trouvé\n";
}
