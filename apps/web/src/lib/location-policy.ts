type LocationFix = { latitude: number; longitude: number; accuracy: number; updatedAt: Date };

/** Reuse only a recent, valid fix; a prior visit's location must never authorize a new check-in. */
export function isRecentLocation(location: LocationFix | null, now = Date.now(), maxAge = 15_000): boolean {
 if (!location) return false;
 const age = now - location.updatedAt.getTime();
 return Number.isFinite(location.latitude) && Math.abs(location.latitude) <= 90 &&
  Number.isFinite(location.longitude) && Math.abs(location.longitude) <= 180 &&
  Number.isFinite(location.accuracy) && location.accuracy > 0 &&
  Number.isFinite(age) && age >= -5000 && age <= maxAge;
}
