<?php

namespace App\Console\Commands;

use App\Mail\InactiveUserReminderMail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendInactiveUserReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:remind-inactive 
                            {--days=3 : Number of days of inactivity}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder emails to users who have been inactive for a specified number of days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $inactiveThreshold = now()->subDays($days);

        $this->info("Finding users inactive for {$days}+ days...");

        // Get inactive users who:
        // - Have verified email
        // - Haven't been sent a reminder in the last 3 days (to avoid spam)
        // - Last active before threshold
        $inactiveUsers = User::whereNotNull('email_verified_at')
            ->where('last_active', '<', $inactiveThreshold)
            ->where(function($query) use ($days) {
                // Never sent reminder OR last reminder was more than 3 days ago
                $query->whereNull('inactive_reminder_sent_at')
                      ->orWhere('inactive_reminder_sent_at', '<', now()->subDays(3));
            })
            ->get();

        if ($inactiveUsers->count() === 0) {
            $this->info('No inactive users found to remind.');
            return 0;
        }

        $this->info("Found {$inactiveUsers->count()} inactive users.");

        $bar = $this->output->createProgressBar($inactiveUsers->count());
        $bar->start();

        $sentCount = 0;
        $failedCount = 0;

        foreach ($inactiveUsers as $user) {
            try {
                // Send email
                $originalLocale = app()->getLocale();
                app()->setLocale($user->language ?? 'en');
                Mail::to($user->email, $user->name)->send(new InactiveUserReminderMail($user, $days));
                app()->setLocale($originalLocale);

                // Update reminder sent timestamp
                $user->update(['inactive_reminder_sent_at' => now()]);

                $sentCount++;
            } catch (\Exception $e) {
                $this->error("Failed to send email to {$user->email}: " . $e->getMessage());
                $failedCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("✓ Sent: {$sentCount} emails");
        
        if ($failedCount > 0) {
            $this->warn("✗ Failed: {$failedCount} emails");
        }

        return 0;
    }

    /**
     * Get the default email subject based on days
     */
    private function getDefaultSubject($user, $days)
    {
        $appName = config('app.name');
        
        // Get user's language preference
        $userLang = $user->language ?? 'en';
        
        // Temporarily set app locale to user's language
        $originalLocale = app()->getLocale();
        app()->setLocale($userLang);
        
        $subject = str_replace(':app', $appName, __('emails.inactive_subject'));
        
        // Restore original locale
        app()->setLocale($originalLocale);
        
        return $subject;
    }

    /**
     * Get the email content
     */
    private function getEmailContent($user, $days)
    {
        $appName = config('app.name');

        // Get user's language preference (default to 'en')
        $userLang = $user->language ?? 'en';
        
        // Temporarily set app locale to user's language
        $originalLocale = app()->getLocale();
        app()->setLocale($userLang);

        // Get translated strings
        $greeting = __('emails.inactive_greeting', ['name' => $user->name]);
        $message = __('emails.inactive_message', ['app' => $appName]);

        // Restore original locale
        app()->setLocale($originalLocale);

        // Return HTML for proper RTL/LTR handling
        if ($userLang === 'ar') {
            $userName = $user->name;
            
            // Build greeting with proper LTR span for username (removed يا and comma)
            $greeting = "<span dir='rtl'>أهلاً <span dir='ltr'>{$userName}</span> ايه يا معلم عامل ايه</span>";
            
            // Build message with proper RTL direction (removed periods and exclamation)
            $message = "<span dir='rtl'>"
                     . "بقالنا فترة مشفناكش واحنا بصراحة مفتقدينك<br><br>"
                     . "حصل حاجات كتير وانت بعيد - بوستات جديدة ورسايل وكمان ناس مستنياك<br><br>"
                     . "ارجع شوف ايه اللي فاتك"
                     . "</span>";
            
            return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { 
            font-family: Tahoma, Arial, sans-serif; 
            direction: rtl; 
            text-align: right; 
            line-height: 1.8;
            color: #000;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 500px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            padding: 15px 0;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #5e60ce;
            margin: 0;
            font-size: 24px;
            display: inline-block;
        }
        .greeting { 
            font-size: 18px; 
            margin-bottom: 20px;
            font-weight: bold;
            color: #000;
        }
        .message { 
            font-size: 15px; 
            color: #333;
            line-height: 2;
        }
        .footer {
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            font-size: 13px;
            color: #888;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header"><h1>{$appName}</h1></div>
        <div class="greeting">{$greeting}</div>
        <div class="message">{$message}</div>
        <div class="footer">© {$appName}</div>
    </div>
</body>
</html>
HTML;
        }

        // English (LTR) - Same styling as Arabic for consistency
        return <<<HTML
<!DOCTYPE html>
<html dir="ltr" lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { 
            font-family: Tahoma, Arial, sans-serif; 
            direction: ltr; 
            text-align: left; 
            line-height: 1.8;
            color: #000;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 500px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            padding: 15px 0;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #5e60ce;
            margin: 0;
            font-size: 24px;
            display: inline-block;
        }
        .greeting { 
            font-size: 18px; 
            margin-bottom: 20px;
            font-weight: bold;
            color: #000;
        }
        .message { 
            font-size: 15px; 
            color: #333;
            line-height: 2;
        }
        .footer {
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            font-size: 13px;
            color: #888;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header"><h1>{$appName}</h1></div>
        <div class="greeting">{$greeting}</div>
        <div class="message">{$message}</div>
        <div class="footer">© {$appName}</div>
    </div>
</body>
</html>
HTML;
    }
}