<?php

namespace App\Console\Commands;

use App\Models\Service;
use App\Models\Setting;
use App\Models\ActivityLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Exception;

class SendRenewalReminders extends Command
{
    protected $signature = 'services:send-renewal-reminders {--force : Send reminders for all active services regardless of date}';
    protected $description = 'Send automated email renewal reminders to clients (30, 15, 7 days before expiry)';

    public function handle()
    {
        $this->info('Starting renewal reminder check...');

        $companyName = Setting::get('company_name', 'AIWA AGENCY');
        $smtpHost = Setting::get('smtp_host');

        // Dynamically configure mail
        if ($smtpHost) {
            config([
                'mail.mailers.smtp.host' => $smtpHost,
                'mail.mailers.smtp.port' => Setting::get('smtp_port', '587'),
                'mail.mailers.smtp.username' => Setting::get('smtp_username'),
                'mail.mailers.smtp.password' => Setting::get('smtp_password'),
                'mail.mailers.smtp.encryption' => Setting::get('smtp_encryption', 'tls'),
                'mail.from.address' => Setting::get('smtp_from_address', 'noreply@aiwa.agency'),
                'mail.from.name' => Setting::get('smtp_from_name', $companyName),
            ]);
        }

        $force = $this->option('force');
        $reminders = [30, 15, 7];
        
        $processedCount = 0;

        foreach ($reminders as $days) {
            $targetDate = now()->addDays($days)->toDateString();
            
            $query = Service::where('service_status', 'active')
                ->with('client');

            if (!$force) {
                $query->whereDate('renewal_date', $targetDate);
            }

            $services = $query->get();

            foreach ($services as $service) {
                if (!$service->client || !$service->client->email) {
                    $this->warn("Skipping service ID {$service->id} because client email is missing.");
                    continue;
                }

                $clientName = $service->client->name;
                $clientEmail = $service->client->email;
                $serviceType = $service->type;
                $renewalDate = $service->renewal_date->format('d M Y');
                $daysRemaining = $force ? now()->diffInDays($service->renewal_date, false) : $days;

                $this->info("Sending {$days}-day reminder for {$serviceType} to {$clientEmail}...");

                try {
                    if ($smtpHost) {
                        Mail::send('emails.renewal_reminder', [
                            'clientName' => $clientName,
                            'companyName' => $companyName,
                            'serviceType' => $serviceType,
                            'renewalDate' => $renewalDate,
                            'daysRemaining' => $daysRemaining,
                            'totalAmount' => $service->total_amount,
                            'loginUrl' => url('/admin'),
                        ], function ($message) use ($clientEmail, $serviceType, $companyName) {
                            $message->to($clientEmail)
                                    ->subject("Renewal Reminder: Your {$serviceType} service with {$companyName}");
                        });

                        $this->info("Email sent successfully to {$clientEmail}.");
                        ActivityLog::log(
                            'Renewal Reminder Sent', 
                            Service::class, 
                            $service->id, 
                            "Sent {$days}-day reminder to {$clientEmail} for {$serviceType}"
                        );
                    } else {
                        $this->warn("SMTP not configured. Logged reminder: client={$clientEmail}, service={$serviceType}, days={$daysRemaining}.");
                        ActivityLog::log(
                            'Renewal Reminder Bypassed (No SMTP)', 
                            Service::class, 
                            $service->id, 
                            "Logged {$days}-day reminder to {$clientEmail} for {$serviceType}"
                        );
                    }
                    $processedCount++;
                } catch (Exception $e) {
                    $this->error("Failed to send reminder to {$clientEmail}: " . $e->getMessage());
                    ActivityLog::log(
                        'Renewal Reminder Failed', 
                        Service::class, 
                        $service->id, 
                        "Failed to send reminder to {$clientEmail}: {$e->getMessage()}"
                    );
                }
            }

            if ($force) {
                break; // If force is true, we already fetched all active services in the first pass
            }
        }

        $this->info("Renewal reminders check complete. Processed {$processedCount} services.");
        return Command::SUCCESS;
    }
}
