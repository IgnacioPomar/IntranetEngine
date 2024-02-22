<?php

namespace PHPSiteEngine;

class UuidGenerator
{
	const GREGORIAN_OFFSET_SECONDS = 12219292800; // Offset in seconds
	private static $clockSeq = 0;
	private static $lastMicrotime = null;


	/**
	 * Generate a version 1 (time-based) UUID
	 *
	 * @return string
	 */
	public static function generateBinary ()
	{
		// ------- Get the current time as a 60-bit timestamp -------
		// Current Unix timestamp in seconds with microseconds as fractional part
		$time = microtime (true);

		// Convert Unix timestamp to UUID timestamp (100-nanosecond intervals since Gregorian offset)
		// Adjust for offset between UUID epoch (1582) and Unix epoch (1970)
		// Note: This conversion loses some precision due to floating point representation
		$uuidTime = ($time + self::GREGORIAN_OFFSET_SECONDS) * 1e7;

		// Split the timestamp into high and low parts for the UUID
		$timeLow = (int) ($uuidTime & 0xFFFFFFFF);
		$timeMid = (int) (($uuidTime >> 32) & 0xFFFF);
		$timeHi = (int) (($uuidTime >> 48) & 0x0FFF); // Only need 12 bits

		// ------- Generate a 14-bit sequence counter -------
		if ($time != self::$lastMicrotime)
		{
			self::$clockSeq = 0;
			self::$lastMicrotime = $time;
		}
		else
		{
			self::$clockSeq = (self::$clockSeq + 1) & 0x3FFF;
		}

		// ------- Generate a random "node name" -------
		$node = random_bytes (6); // 48 bits for node

		// Pack as binary (make it look as a version 1 UUID)
		$uuidBin = pack ('NnnnH*', $timeLow, $timeMid, $timeHi | 0x1000, self::$clockSeq | 0x8000, bin2hex ($node));

		return $uuidBin;
	}


	/**
	 * represent the UUID as a Base64 string
	 *
	 * @return string
	 */
	public static function generateBase64 (): string
	{
		$uuidBin = self::generateBinary ();
		return rtrim (strtr (base64_encode ($uuidBin), '+/', '-_'), '=');
	}


	/**
	 * Decode a base64-like encoded UUID and return the time it was generated
	 *
	 * @param string $uuid
	 * @return string
	 */
	public static function inverseB64 ($uuid)
	{
		// Decode the base64-like encoded UUID back to its binary representation
		$uuidBin = base64_decode (strtr ($uuid, '-_', '+/'));

		// Extraemos los componentes de tiempo del UUID
		$components = unpack ('Ntime_low/ntime_mid/ntime_hi_and_version', $uuidBin);
		$time_low = $components ['time_low'];
		$time_mid = $components ['time_mid'];
		$time_hi_and_version = $components ['time_hi_and_version'] & 0x0FFF; // Quitamos la versión dejando solo time_hi

		// Reconstruimos el tiempo del UUID
		$uuidTime = ($time_hi_and_version << 48) | ($time_mid << 32) | $time_low;

		// Convertimos el tiempo del UUID a tiempo Unix
		$unixTime = ($uuidTime / 1e7) - self::GREGORIAN_OFFSET_SECONDS;

		return date ('Y-m-d H:i:s', (int) $unixTime);
	}
}
