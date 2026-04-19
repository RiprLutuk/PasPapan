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
        $this->line("<fg=green;options=bold>{$hwid}</>");
        $this->info("========================================");
        $this->line("Please copy this ID and give it to the author to receive your Enterprise License.");
    }
    
    /**
     * Generate the HWID. 
     * This is also called by LicenseGuard to verify.
     */
    public static function generate()
    {
        $mac = self::discoverMacAddress();
        
        $fallback = php_uname('n') . '_' . php_uname('m');
        $raw = !empty($mac) ? $mac : $fallback;
        
        // Hash it with a salt to make it opaque and uniform in length
        return md5('riprlutuk_enterprise_' . strtolower(trim($raw)));
    }

    private static function discoverMacAddress(): ?string
    {
        foreach (self::macAddressesFromNativeInterfaces() as $mac) {
            return $mac;
        }

        foreach (self::macAddressesFromSysfs() as $mac) {
            return $mac;
        }

        return null;
    }

    private static function macAddressesFromNativeInterfaces(): array
    {
        if (! function_exists('net_get_interfaces')) {
            return [];
        }

        $macs = [];

        foreach (net_get_interfaces() ?: [] as $interface) {
            $mac = $interface['mac'] ?? null;

            if (is_string($mac) && self::isUsableMacAddress($mac)) {
                $macs[] = strtolower($mac);
            }
        }

        sort($macs);

        return array_values(array_unique($macs));
    }

    private static function macAddressesFromSysfs(): array
    {
        $macs = [];

        foreach (glob('/sys/class/net/*/address') ?: [] as $addressFile) {
            $mac = @file_get_contents($addressFile);

            if (is_string($mac) && self::isUsableMacAddress($mac)) {
                $macs[] = strtolower(trim($mac));
            }
        }

        sort($macs);

        return array_values(array_unique($macs));
    }

    private static function isUsableMacAddress(string $mac): bool
    {
        $mac = strtolower(trim($mac));

        return preg_match('/^[0-9a-f]{2}(:[0-9a-f]{2}){5}$/', $mac) === 1
            && $mac !== '00:00:00:00:00:00';
    }
}
