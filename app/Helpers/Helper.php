<?php

namespace App\Helpers;

use App\Models\Order;
use App\Models\OrderActivity;
use Ixudra\Curl\Facades\Curl;
use App\Models\ShippingCompany;
use App\Models\VendorAddress;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Helper
{
	/**
	 * Calculate the next cycle from a given date based on payout days
	 * 
	 * @param Carbon $fromDate Starting reference date
	 * @param array $payoutDays Array of one or two weekday names
	 * @return array Cycle data with start, end, and payout dates
	 */
	public static function calculateCycleFromDate($fromDate, $payoutDays)
	{
		// Map day names to Carbon day constants
		$dayMapping = [
			'Monday' => Carbon::MONDAY,
			'Tuesday' => Carbon::TUESDAY,
			'Wednesday' => Carbon::WEDNESDAY,
			'Thursday' => Carbon::THURSDAY,
			'Friday' => Carbon::FRIDAY,
			'Saturday' => Carbon::SATURDAY,
			'Sunday' => Carbon::SUNDAY,
		];

		// Handle single payout day (weekly cycle)
		if (count($payoutDays) === 1) {
			$payoutDay = $dayMapping[$payoutDays[0]] ?? null;

			if ($payoutDay === null) {
				// Fallback to simple 7-day cycle
				return [
					'cycle_start' => $fromDate->copy(),
					'cycle_end' => $fromDate->copy()->addDays(6),
					'payout_date' => $fromDate->copy()->addDays(7),
				];
			}

			// Find next occurrence of the payout day
			$cycleStart = $fromDate->copy()->next($payoutDay);
			if ($fromDate->dayOfWeek === $payoutDay) {
				$cycleStart = $fromDate->copy();
			}

			// Cycle runs for 7 days (one week)
			$cycleEnd = $cycleStart->copy()->addDays(6);
			// Payout is 7 days after cycle starts (next week same day)
			$payoutDate = $cycleStart->copy()->addWeek();

			return [
				'cycle_start' => $cycleStart,
				'cycle_end' => $cycleEnd,
				'payout_date' => $payoutDate,
			];
		}

		// Handle two payout days (alternating bi-weekly cycles)
		$day1 = $dayMapping[$payoutDays[0]] ?? null;
		$day2 = $dayMapping[$payoutDays[1]] ?? null;

		if ($day1 === null || $day2 === null) {
			// Fallback to simple 7-day cycle if invalid days
			return [
				'cycle_start' => $fromDate->copy(),
				'cycle_end' => $fromDate->copy()->addDays(6),
				'payout_date' => $fromDate->copy()->addDays(7),
			];
		}

		// Determine which payout day comes first in the week
		$firstDay = min($day1, $day2);
		$secondDay = max($day1, $day2);

		$currentDayOfWeek = $fromDate->dayOfWeek;

		// Determine which is the next payout day from current position
		if ($currentDayOfWeek <= $firstDay) {
			// We're before or on the first payout day
			// Next cycle starts on first day, ends day before second day
			$cycleStart = $fromDate->copy()->next($firstDay);
			if ($currentDayOfWeek === $firstDay) {
				$cycleStart = $fromDate->copy();
			}
			$cycleEnd = $cycleStart->copy()->next($secondDay)->subDay();
			$payoutDate = $cycleStart->copy()->addWeek();
		} elseif ($currentDayOfWeek <= $secondDay) {
			// We're between first and second payout day (or on second day)
			// Next cycle starts on second day, ends day before next first day
			$cycleStart = $fromDate->copy()->next($secondDay);
			if ($currentDayOfWeek === $secondDay) {
				$cycleStart = $fromDate->copy();
			}
			$cycleEnd = $cycleStart->copy()->next($firstDay)->subDay();
			$payoutDate = $cycleStart->copy()->addWeek();
		} else {
			// We're after the second payout day
			// Next cycle starts on next first day (next week)
			$cycleStart = $fromDate->copy()->next($firstDay);
			$cycleEnd = $cycleStart->copy()->next($secondDay)->subDay();
			$payoutDate = $cycleStart->copy()->addWeek();
		}
	}

	public static function calculateTNCycle($fromDate, $tnDays)
	{
		// In T+N logic, the payout date for orders delivered on 'T' is 'T+N'
		// If we start from 'fromDate' (the day after the last cycle ended), 
		// the cycle end date is that same 'fromDate' (for a daily cycle),
		// and the payout date for it is 'fromDate + tnDays'.

		$cycleStart = $fromDate->copy();
		$cycleEnd = $fromDate->copy();
		$payoutDate = $cycleEnd->copy()->addDays($tnDays);

		return [
			'cycle_start' => $cycleStart,
			'cycle_end' => $cycleEnd,
			'payout_date' => $payoutDate,
		];
	}

	public static function posted($time)
	{
		$timestamp = strtotime($time);

		if (!$timestamp) {
			return '';
		}

		$diff = time() - $timestamp;

		// Future protection (optional safe fallback)
		if ($diff < 0) {
			return 'Just now';
		}

		$sec   = $diff;
		$min   = floor($diff / 60);
		$hrs   = floor($diff / 3600);
		$days  = floor($diff / 86400);
		$weeks = floor($diff / 604800);
		$mnths = floor($diff / 2600640);
		$yrs   = floor($diff / 31207680);

		if ($sec <= 60) {
			return "$sec seconds ago";
		}

		if ($min <= 60) {
			return ($min == 1) ? "one minute ago" : "$min minutes ago";
		}

		if ($hrs <= 24) {
			return ($hrs == 1) ? "an hour ago" : "$hrs hours ago";
		}

		if ($days <= 7) {
			return ($days == 1) ? "Yesterday" : "$days days ago";
		}

		if ($weeks <= 4) {
			return ($weeks == 1) ? "a week ago" : "$weeks weeks ago";
		}

		if ($mnths <= 12) {
			return ($mnths == 1) ? "a month ago" : "$mnths months ago";
		}

		return ($yrs == 1) ? "one year ago" : "$yrs years ago";
	}

	public static function dateDiffInDays($date1, $date2)
	{
		$diff = strtotime($date2) - strtotime($date1);
		return abs(round($diff / 86400)) + 1;
	}

	public static function AmountInWords(float $amount): string
	{
		$number = floor($amount);
		$paise  = round(($amount - $number) * 100);

		$words = [
			0 => '',
			1 => 'One',
			2 => 'Two',
			3 => 'Three',
			4 => 'Four',
			5 => 'Five',
			6 => 'Six',
			7 => 'Seven',
			8 => 'Eight',
			9 => 'Nine',
			10 => 'Ten',
			11 => 'Eleven',
			12 => 'Twelve',
			13 => 'Thirteen',
			14 => 'Fourteen',
			15 => 'Fifteen',
			16 => 'Sixteen',
			17 => 'Seventeen',
			18 => 'Eighteen',
			19 => 'Nineteen',
			20 => 'Twenty',
			30 => 'Thirty',
			40 => 'Forty',
			50 => 'Fifty',
			60 => 'Sixty',
			70 => 'Seventy',
			80 => 'Eighty',
			90 => 'Ninety'
		];

		$units = ['', 'Hundred', 'Thousand', 'Lakh', 'Crore'];

		$str = [];
		$i = 0;

		while ($number > 0) {

			// Divider (Indian format: 3 digits then 2 digits)
			$divider = ($i == 1) ? 10 : 100;
			$chunk   = $number % $divider;

			$number = floor($number / $divider);

			if ($chunk) {
				if ($chunk < 21) {
					$str[] = $words[$chunk] . " " . $units[$i];
				} else {
					$str[] = $words[floor($chunk / 10) * 10] . " " .
						$words[$chunk % 10] . " " . $units[$i];
				}
			}

			$i++;
		}

		$rupeesText = trim(implode(' ', array_reverse($str)));

		// Paise part (keeps same logic)
		$paiseText = '';
		if ($paise > 0) {
			$paiseText = " And " .
				($words[floor($paise / 10) * 10] ?? '') . " " .
				($words[$paise % 10] ?? '') . " Paise";
		}

		return ($rupeesText ? $rupeesText . " Rupees" : "") . $paiseText;
	}

	public static function decimal_number($number)
	{
		return number_format($number, 2);
	}

	public static function decimalsprint($number)
	{
		return sprintf("%0.2f", $number);
	}

	public static function orderActivity($id, $activity_name)
	{
		return  OrderActivity::insert(['order_id' => $id, 'activity_name' => $activity_name, 'date' => date('Y-m-d'), 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
	}

	public static function xpressBeesToken($id)
	{
		$shipping = ShippingCompany::find($id);
		$token = $shipping->api_key;
		if ($shipping->id == 1) {
			if (empty($shipping->expired_at) || $shipping->hasExpired()) {
				$response = Curl::to($shipping->url . 'api/users/login')
					->withData(array('email' => $shipping->email, 'password' => $shipping->password))
					->asJson(true)
					->post();
				ShippingCompany::whereId($id)->update(['api_key' => $response['data'], 'expired_at' => date('Y-m-d H:i:s')]);
				$token = $response['data'];
			} else {
				$token = $shipping->api_key;
			}
		}
		if ($shipping->id == 4) {
			if (empty($shipping->expired_at) || $shipping->hasExpired()) {
				$response = Curl::to($shipping->url . 'auth/login')
					->withData(array('email' => $shipping->email, 'password' => $shipping->password, 'vendorType' => "SELLER"))
					->asJson(true)
					->post();

				ShippingCompany::whereId($id)->update(['api_key' => $response['data']['accessToken'], 'expired_at' => date('Y-m-d H:i:s')]);
				$token = $response['data']['accessToken'];
			} else {
				$token = $shipping->api_key;
			}
		}
		return $token;
	}

	public static function callCurlApi($url, $dataArray, $token)
	{
		$response = Curl::to($url)
			->withData($dataArray)
			->asJson(true)
			->withBearer($token)
			->post();
		return $response;
	}

	public static function callCurlApiPut($url, $dataArray, $token)
	{
		$response = Curl::to($url)
			->withData($dataArray)
			->asJson(true)
			->withBearer($token)
			->put();
		return $response;
	}

	public static function callCurlGetApi($url, $token)
	{
		$response = Curl::to($url)
			->withBearer($token)
			->get();
		return $response;
	}

	public static function callGetUrl($url)
	{
		$response = Curl::to($url)->asJson(true)->get();
		return $response;
	}
	public static function callGetUrlAuthorize($url, $token)
	{
		$response = Curl::to($url)->asJson(true)->withAuthorization('Token ' . $token)->get();
		return $response;
	}
	public static function callPostUrlAuthorize($url, $dataArray, $token)
	{
		$response = Curl::to($url)->withData($dataArray)->asJson(true)->withAuthorization('Token ' . $token)->get();
		return $response;
	}

	public static function postCurl($url, $dataArray, $token)
	{
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS => $dataArray,
			CURLOPT_HTTPHEADER => array(
				'Authorization: Token ' . $token . '',
				'Content-Type: application/json',
			),
		));

		$response = curl_exec($curl);
		curl_close($curl);
		return json_decode($response, true);
	}

	public static function appSetting($key, $default = null)
	{
		return DB::table('settings')->where('key', $key)->value('value') ?? $default;
	}

	public static function userRestrict($user)
	{
		if ($user->role === "user") {
			return abort(403, "Permission Denied");
		}
	}
}
