<?php

namespace App\Services\Location;

use Illuminate\Support\Facades\Http;

class GeolocationService
{
   /**
    * Get address from coordinates using Nominatim (OpenStreetMap)
    */
   public static function getAddressFromCoordinates(float $latitude, float $longitude): ?array
   {
      try {
         $response = Http::withoutVerifying()
            ->withOptions(['timeout' => 5])
            ->get('https://nominatim.openstreetmap.org/reverse', [
               'lat' => $latitude,
               'lon' => $longitude,
               'format' => 'json',
               'zoom' => 18,
               'addressdetails' => 1,
            ]);

         if ($response->successful()) {
            $data = $response->json();
            return [
               'address' => $data['address']['road'] ?? $data['address']['village'] ?? $data['display_name'] ?? 'Unknown',
               'city' => $data['address']['city'] ?? $data['address']['county'] ?? null,
               'country' => $data['address']['country'] ?? null,
               'display_name' => $data['display_name'] ?? null,
            ];
         }
      } catch (\Exception $e) {
         \Log::warning('Geolocation reverse lookup failed: ' . $e->getMessage());
      }

      return null;
   }

   /**
    * Check if coordinates are within allowed area
    */
   public static function isWithinAllowedArea(float $latitude, float $longitude, array $allowedAreas): bool
   {
      foreach ($allowedAreas as $area) {
         $distance = self::calculateDistance(
            $latitude,
            $longitude,
            $area['latitude'],
            $area['longitude']
         );

         if ($distance <= $area['radius'] ?? 500) { // Default radius: 500m
            return true;
         }
      }

      return false;
   }

   /**
    * Calculate distance between two coordinates using Haversine formula
    */
   public static function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
   {
      $earthRadius = 6371000; // Radius of the earth in meters

      $dLat = deg2rad($lat2 - $lat1);
      $dLon = deg2rad($lon2 - $lon1);
      $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
      $c = 2 * asin(sqrt($a));
      $distance = $earthRadius * $c;

      return floor($distance); // Return in meters
   }

   /**
    * Log geolocation data for security audit
    */
   public static function logGeolocation($userId, float $latitude, float $longitude, string $action = 'attendance', ?array $metadata = null)
   {
      try {
         \DB::table('geolocation_logs')->insert([
            'user_id' => $userId,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'action' => $action,
            'ip_address' => request()->ip(),
            'user_agent' => request()->header('User-Agent'),
            'metadata' => $metadata ? json_encode($metadata) : null,
            'created_at' => now(),
         ]);
      } catch (\Exception $e) {
         \Log::warning('Failed to log geolocation: ' . $e->getMessage());
      }
   }

   /**
    * Validate location accuracy
    */
   public static function isAccuracyAcceptable(float $accuracy, float $minAccuracy = 100): bool
   {
      // accuracy < 100 meters is generally good for attendance
      return $accuracy <= $minAccuracy;
   }
}
