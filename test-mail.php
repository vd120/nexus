<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Mail;

try {
    echo "Attempting to send test email to socialapp.noreply@gmail.com...\n";
    Mail::raw('Test email from Nexus system diagnostic.', function ($message) {
        $message->to('socialapp.noreply@gmail.com')
                ->subject('Nexus Mail Diagnostic');
    });
    echo "Mail::raw call completed without exception.\n";
} catch (\Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " (Line: " . $e->getLine() . ")\n";
}
