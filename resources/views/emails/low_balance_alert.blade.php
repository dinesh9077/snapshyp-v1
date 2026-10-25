<!DOCTYPE html>
<html>
	<head>
		<title>Low Wallet Balance Alert</title>
	</head>
	<body>
		<p>Dear {{ $user->name }},</p>
		<p>{{ $messageContent }}</p>
		<p>Please recharge your wallet to continue using our services.</p>
		<br>
		<p>Best Regards,<br>{{ config('setting.company_name') }}</p>
	</body>
</html>
