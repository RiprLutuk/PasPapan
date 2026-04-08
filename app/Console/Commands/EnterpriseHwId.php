<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class EnterpriseHwId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'enterprise:hwid';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate secure Hardware ID for Enterprise Licensing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Scanning hardware fingerprint...");
        $hwid = self::generate();
        
        $this->info("========================================");
        $this->info("Your Server Hardware ID is:");
        $this->line("<options=bold,fg=green>{$hwid}</>");
        $this->info("========================================");
        $this->line("Please copy this ID and give it to the author to receive your Enterprise License.");
    }
    
    /**
     * Generate the HWID. 
     * This is also called by LicenseGuard to verify.
     */
    public static function generate()
    {
        $mac = '';
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            @exec('getmac /fo csv /nh', $output);
            if (!empty($output)) {
                $mac = trim(str_replace('"', '', explode(',', $output[0] ?? '')[0] ?? ''));
            }
        } else {
            // Try 'ip link' first (modern Linux)
            @exec("ip link show 2>/dev/null | awk '/ether/ {print $2}'", $output);
            if (empty($output)) {
                // Fallback to ifconfig (Mac/BSD/Older Linux)
                @exec("ifconfig -a 2>/dev/null | grep -ioE '([a-z0-9]{2}:){5}[a-z0-9]{2}'", $output);
            }
            if (!empty($output)) {
                $mac = trim($output[0] ?? '');
            }
        }
        
        $fallback = php_uname('n') . '_' . php_uname('m');
        $raw = !empty($mac) ? $mac : $fallback;
        
        // Hash it with a salt to make it opaque and uniform length
        return md5('riprlutuk_enterprise_' . strtolower(trim($raw)));
    }
}
